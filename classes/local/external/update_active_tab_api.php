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
 * Expand comment services implementation.
 *
 * @package mod_studentquiz
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\local\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use mod_studentquiz\utils;

require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * Update active tab services implementation.
 *
 * @package mod_studentquiz
 * @copyright 2021 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_active_tab_api extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function update_active_tab_parameters(): external_function_parameters {
        return new external_function_parameters([
                'activetab' => new external_value(PARAM_TEXT, 'Active tab'),
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function update_active_tab_returns() {
        return new external_single_structure([
            'value' => new external_value(PARAM_TEXT, 'The value')
        ]);
    }

    /**
     * Update active tab to user preferences.
     *
     * @param string $activetab Active tab Id.
     * @return array
     */
    public static function update_active_tab($activetab) {
        set_user_preference(utils::USER_PREFERENCE_QUESTION_ACTIVE_TAB, $activetab);
        return [
            'value' => $activetab
        ];
    }
}
