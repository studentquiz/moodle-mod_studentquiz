<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_studentquiz\access;

use context;

/**
 * Access helper to manage context specific overrides.
 *
 * @package   mod_studentquiz
 * @copyright 2020 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_override {

    /**
     * @var array This stores, for each StudentQuiz capability, the core question capabilities required to make it work.
     */
    public static $studentquizrelation = [

        // Any access to the activity.
        'mod/studentquiz:view' => [
            'moodle/question:useall', // Attempt all questions.
            'moodle/question:viewmine', // Required to even see the question bank and thus the overview.
        ],

        // Allows users to create questions.
        'mod/studentquiz:submit' => [
            // Allows to create edit and tag own questions.
            'moodle/question:add',
            'moodle/question:editmine',
            'moodle/question:tagmine',
        ],

        // Preview any questions.
        'mod/studentquiz:previewothers' => [
            'moodle/question:viewall', // Allows read-only view of the edit question form.
        ],

        // Allows moving questions into categories.
        'mod/studentquiz:organize' => [
            'moodle/question:moveall',
            'moodle/question:managecategory', // Allows editing of categories.
        ],

        // Allows editing and deleting any question.
        'mod/studentquiz:manage' => [
            'moodle/question:editall',
        ],
    ];

    /** @var array these are the capabilities which exist in 'all' and 'mine' pairs. */
    protected static $capswithallandmine = [
        'moodle/question:edit' => 1,
        'moodle/question:view' => 1,
        'moodle/question:use' => 1,
        'moodle/question:move' => 1,
        'moodle/question:tag' => 1,
    ];

    /**
     * Cache key where the roles changed time is stored.
     */
    const ROLES_CHANGED_TIME_CACHE_KEY = 'roleschanged';

    /**
     * Return defined cache key for this course module
     *
     * @param int $cmid the course module id
     * @return string the cache key.
     */
    private static function cache_key_for_cm(int $cmid): string {
        return 'cm' . $cmid . 'synced';
    }

    /**
     * This method gets called by the observer class whenever roles change somewhere.
     */
    public static function roles_setup_has_changed() {
        $cache = \cache::make('mod_studentquiz', 'permissionssync');
        $cache->set(self::ROLES_CHANGED_TIME_CACHE_KEY, time());
    }

    /**
     * This method should be called from every page where a user interacts with a StudentQuiz.
     *
     * @param \context_module $context the context for the studentquiz to check. This must be a studentquiz context.
     */
    public static function ensure_permissions_are_right(\context_module $context) {
        $cache = \cache::make('mod_studentquiz', 'permissionssync');
        $ourcachekey = self::cache_key_for_cm($context->instanceid);

        $lastsync = $cache->get($ourcachekey);
        if (!$lastsync) {
            $syncrequired = true;
        } else {
            $lastroleschange = $cache->get(self::ROLES_CHANGED_TIME_CACHE_KEY);
            // 2 second fudge factor in case there are multi servers with slightly misaligned clocks,
            // and even on one server, there may be two changes in the same second.
            $syncrequired = $lastsync < $lastroleschange + 2;
        }

        if ($syncrequired) {
            $timenow = time(); // Sync can take more than 1 second. Get the time when we start.
            self::ensure_relation($context);
            $cache->set($ourcachekey, $timenow);
        }
    }

    /**
     * Add context specific question capability overrides to match the StudentQuiz capabilities each role has.
     *
     * There are various standard Moodle capabilities which need to be assigned if a role has certain
     * StudentQuiz capabilities. The link between the two is set in the $studentquizrelation array above.
     *
     * In this code, the capabilities that we might assign or un-assign are referred to as 'managed capabilities'.
     *
     * As well as adding ALLOW overrides for the capabilities that are needed, we also remove any unnecessary
     * overrides of managed capabilities which are not required.
     *
     * Warning: This functions assigns and un-assigns capabilities. If this function is called from a
     * capability_[un]assigned event handler, that could lead to an infinite loop. However, this is not
     * currently an issue, because the handlers in mod_studentquiz_observer only respond to changes
     * in StudentQuiz capabilities, and they are never managed capabilities.
     *
     * @param context $context where to apply the overrides.
     */
    private static function ensure_relation(context $context) {
        global $DB;

        // Get a list of all the capabilities we manage.
        $allmanagedcapabilities = [];
        foreach (self::$studentquizrelation as $managedcapabilities) {
            foreach ($managedcapabilities as $capability) {
                $allmanagedcapabilities[$capability] = 1;
            }
        }

        // We fix all roles here. That way, we don't have to worry about roles being assigned or unassigned in the future.
        $roles = $DB->get_records('role');
        foreach ($roles as $role) {
            // Examine the permissions this role currently has here.
            // - For the managed capabilities, we track which permission the role currently has for them.
            // - Based in the combination of StudentQuiz permissions the role has, we work out which of the managed
            // capabilities are required.
            $currentpermissions = role_context_capabilities($role->id, $context);
            $permissionsrequired = [];
            foreach ($currentpermissions as $capability => $permission) {
                if (isset(self::$studentquizrelation[$capability]) && $permission == CAP_ALLOW) {
                    foreach (self::$studentquizrelation[$capability] as $requiredcapability) {
                        $permissionsrequired[$requiredcapability] = CAP_ALLOW;
                    }
                }
            }

            // Now we look through the capabilities that are required, and if we are going require any 'all' capability,
            // then we don't need to require the equivalent 'mine' capability.
            foreach (self::$capswithallandmine as $capability => $notused) {
                if (isset($permissionsrequired[$capability . 'all'])) {
                    unset($permissionsrequired[$capability . 'mine']);
                }
            }

            // Now, remove any existing overrides of the managed capabilities that are not required.
            $existingoverrides = get_capabilities_from_role_on_context($role, $context);
            foreach ($existingoverrides as $override) {
                if (!isset($allmanagedcapabilities[$override->capability])) {
                    continue; // Not a managed capability. Skip.
                }

                if (isset($permissionsrequired[$override->capability])) {
                    continue; // This override should exist. Skip.
                }

                unassign_capability($override->capability, $role->id, $context);
            }

            // After doing that it is important to re-fetch the current permissions.
            $currentpermissions = role_context_capabilities($role->id, $context);

            // Finally, we assign any capabilities which are required, and which the role does not already have.
            foreach ($permissionsrequired as $capability => $notused) {
                if (isset($currentpermissions[$capability]) && $currentpermissions[$capability] == CAP_ALLOW) {
                    // Role already has the capability.
                    continue;
                }
                if (substr($capability, -4) === 'mine') {
                    // Role should have 'mine' capability. Do they already have the equivalent 'all' one?
                    $basecapability = substr($capability, 0, -4);
                    if (isset(self::$capswithallandmine[$basecapability])) {
                        $allcapability = $basecapability . 'all';
                        if (isset($currentpermissions[$allcapability]) && $currentpermissions[$allcapability] == CAP_ALLOW) {
                            // Role already has the all capability.
                            continue;
                        }
                    }
                }

                // We need to add the override.
                assign_capability($capability, CAP_ALLOW, $role->id, $context, true);
            }
        }
    }
}
