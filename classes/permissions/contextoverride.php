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
 * Privacy Subsystem implementation for mod_required.
 *
 * @package    mod_required
 * @copyright  2020 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\permissions;

use context;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/accesslib.php');

/**
 * Context permissions helper to manage context specific overrides
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contextoverride {

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
            'moodle/question:useall'
        ],
        // Allows to create questions.
        'mod/studentquiz:submit' => [
            // Allows to create edit and tag own questions.
            'moodle/question:add',
            'moodle/question:editmine',
            'moodle/question:tagmine'
        ],
        // Allows to preview other questions.
        'mod/studentquiz:previewothers' => [
            // Allows to view edit questions in read-only of others.
            'moodle/question:viewall'
        ],
        // Allows to move questions into categories.
        'mod/studentquiz:organize' => [
            // Allows to move questions into categories.
            'moodle/question:moveall'
        ],
        // Allows to edit and delete questions.
        'mod/studentquiz:manage' => [
            // Allows to edit and delete questions.
            'moodle/question:editall'
        ]
    ];

    /**
     * Add context specific capabilities as overrides to all roles assigned to this context tree to the given context.
     * All other capability overrides not given in relation are removed!
     * Warning: This functions assigns and unassigns capabilities. If this function is called from a
     * capability_[un]assigned event, it will trigger that event again if it finds out that changes have to be made. The
     * outcome of this chain of events is uncalculatable and may be uncontrollable and thus should be avoided!
     *
     * @param context $context to apply the override
     * @param array $relation where keys are needed capabilities and its values an array of capabilities to override
     */
    public static function ensurerelation(context $context, array $relation) {
        global $CFG;

        // Get a list of roles assigned to this context tree (since it is possible that there are no roles assigned
        // directly to the context, includeparents is set to true).
        $roles = get_roles_used_in_context($context, true);
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