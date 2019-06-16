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
 * Defines external functions for the studentquiz module.
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/questionlib.php');

class mod_studentquiz_external extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function change_question_state_parameters() {
        return new external_function_parameters([
                'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_REQUIRED),
                'cmid' => new external_value(PARAM_INT, 'CM id', VALUE_REQUIRED),
                'questionid' => new external_value(PARAM_INT, 'Question id', VALUE_REQUIRED),
                'state' => new external_value(PARAM_INT, 'Question state', VALUE_REQUIRED)
        ]);
    }

    /**
     * @param int $courseid Course id
     * @param int $cmid Course module id
     * @param int $questionid Question id
     * @param int $state State value
     * @return array Response
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function change_question_state($courseid, $cmid, $questionid, $state) {
        global $PAGE;
        if ($state == 4) {
            $type = 'hidden';
            $value = 1;
        } else if ($state == 5) {
            $type = 'deleted';
            $value = 1;
        } else {
            $type = 'state';
            $value = $state;
        }
        mod_studentquiz_change_state_visibility($questionid, $type, $value);
        $course = get_course($courseid);
        $cm = get_coursemodule_from_id('studentquiz', $cmid);
        $context = context_module::instance($cmid);
        $PAGE->set_context($context);
        mod_studentquiz_state_notify($questionid, $course, $cm, $type);
        $result = [];
        $result['status'] = get_string('api_state_change_success_title', 'studentquiz');
        $result['message'] = get_string('api_state_change_success_content', 'studentquiz');
        return $result;
    }

    /**
     * @return external_single_structure
     */
    public static function change_question_state_returns() {
        return new external_single_structure([
                'status' => new external_value(PARAM_TEXT, 'status'),
                'message' => new external_value(PARAM_TEXT, 'message')
        ]);
    }
}
