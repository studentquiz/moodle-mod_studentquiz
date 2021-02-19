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

/**
 * Access helper to manage context specific overrides.
 *
 * @package    mod_studentquiz
 * @copyright  2020 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\access;

use context;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');

/**
 * Access helper to manage context specific overrides.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_override {

    /**
     * Needed context specific permissions for roles in StudentQuiz. The key of this array is the StudentQuiz
     * capability and its contents is an array for all question capabilities needed to fulfill the purpose.
     *
     * @var array studentquiz capability relation
     */
    public static $studentquizrelation = [
        // Allows to view and use the activity.
        'mod/studentquiz:view' => [
            // Allows to attempt all questions.
            'moodle/question:useall',
            // Required to even be able to see question bank and thus the overview.
            'moodle/question:viewmine',
        ],
        // Allows to create questions.
        'mod/studentquiz:submit' => [
            // Allows to create edit and tag own questions.
            'moodle/question:add',
            'moodle/question:editmine',
            'moodle/question:tagmine',
        ],
        // Allows to preview other questions.
        'mod/studentquiz:previewothers' => [
            // Allows to view edit questions in read-only of others.
            'moodle/question:viewall',
        ],
        // Allows to move questions into categories.
        'mod/studentquiz:organize' => [
            // Allows to move questions into categories.
            'moodle/question:moveall',
            // Allows editing of categories.
            'moodle/question:managecategory',
        ],
        // Allows to edit and delete questions.
        'mod/studentquiz:manage' => [
            // Allows to edit and delete questions.
            'moodle/question:editall',
        ],
    ];

    const ROLES_CHANGED_TIME_CACHE_KEY = 'roleschanged';

    private static function cache_key_for_cm(int $cmid) {
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
     * This ensures that
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
            self::ensure_relation($context, self::$studentquizrelation);
            $cache->set($ourcachekey, $timenow);
        }
    }

    /**
     * Add context specific question capability overrides to match the StudentQuiz capabilities each role has.
     *
     * As well as assigning the capabilities that are needed according to the relation array,
     * any capability that is mentioned in the array will be removed from roles that don't need it.
     *
     * Warning: This functions assigns and unassigns capabilities. If this function is called from a
     * capability_[un]assigned event, it will trigger that event again if it finds out that changes have to be made. The
     * outcome of this chain of events may be uncontrollable and thus should be avoided or filtered very carefully!
     *
     * @param context $context where to apply the overrides.
     * @param array $relation where keys are needed capabilities and its values an array of capabilities to override
     */
    private static function ensure_relation(context $context, array $relation) {
        global $DB;

        // We fix all roles here. That way, we don't have to worry about roles being assigned or unassigned in future.
        $roles = $DB->get_records('role');
        foreach ($roles as $role) {
            // Get the list of resolved capabilities of this role in this exact context (includes overrides). This list
            // represents which capabilities are given to the role.
            $resolvedcapnames = array();
            foreach (role_context_capabilities($role->id, $context) as $cap => $permission) {
                if (in_array($cap, array_keys($relation)) && $permission == CAP_ALLOW) {
                    $resolvedcapnames[] = $cap;
                }
            }

            // Get the list of unresolved capabilities of this role in this exact context (so only overrides).
            $overridecapnames = array();
            foreach (get_capabilities_from_role_on_context($role, $context) as $capoverride) {
                if ($capoverride->permission == CAP_ALLOW) {
                    $overridecapnames[] = $capoverride->capability;
                }
            }

            // For each required cap there are override caps only for this context. So if the override cap is not found,
            // it has to be assigned. While doing that the override caps will be removed from the working list.
            foreach ($relation as $requiredcap => $overridecaps) {
                // It's fine for us that the required capability is set via override, we just don't want to remove
                // it later, so also remove that from the working list.
                if (($key = array_search($requiredcap, $overridecapnames)) !== false) {
                    unset($overridecapnames[$key]);
                }

                // If the required capability is given resolved, apply the override capability if needed.
                if (in_array($requiredcap, $resolvedcapnames)) {
                    foreach ($overridecaps as $overridecap) {
                        if (in_array($overridecap, $overridecapnames)) {
                            // Capability already set, no changes needed, so remove it from the working list to prevent
                            // removing it.
                            if (($key = array_search($overridecap, $overridecapnames)) !== false) {
                                unset($overridecapnames[$key]);
                            }
                        } else {
                            // Capability missing, add it.
                            assign_capability($overridecap, CAP_ALLOW, $role->id, $context, true);
                        }
                    }
                }
            }

            // After going through, all remaining caps are excessive have to be usassigned. If there are capabilities in
            // the list not related to required or override, they have no meaning anyway, since this list only contains
            // unresolved capabilities.
            foreach ($overridecapnames as $capoverridename) {
                unassign_capability($capoverridename, $role->id, $context);
            }
        }
    }
}
