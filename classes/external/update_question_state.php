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
 * Create update question state services implementation.
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use mod_studentquiz\local\studentquiz_helper;
use mod_studentquiz\local\studentquiz_question;

require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * Create update question state services implementation.
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_question_state extends external_api {

    /**
     * Get the required question state parameters.
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_REQUIRED),
            'cmid' => new external_value(PARAM_INT, 'coursemodule id', VALUE_REQUIRED),
            'studentquizquestionid' => new external_value(PARAM_INT, 'id of studentquiz_question table', VALUE_REQUIRED),
            'state' => new external_value(PARAM_INT, 'Question state', VALUE_REQUIRED)
        ]);
    }

    /**
     * Update the question state as provided.
     *
     * @param int $courseid Course id
     * @param int $cmid Course module id
     * @param int $studentquizquestionid StudentQuiz-Question id,
     * @param int $state State value
     * @return array Response
     */
    public static function execute($courseid, $cmid, $studentquizquestionid, $state) {
        global $PAGE, $USER;

        // Student can not delete the question when the question is in approved state.
        $context = \context_course::instance($courseid);
        $canmanage = has_capability('mod/studentquiz:manage', $context);
        $contextmodule = \context_module::instance($cmid);
        $cm = get_coursemodule_from_id('studentquiz', $cmid);
        $studentquiz = mod_studentquiz_load_studentquiz($cmid, $contextmodule->id);
        $studentquizquestion = new studentquiz_question($studentquizquestionid, null, $studentquiz);

        if (!$canmanage && $state == studentquiz_helper::STATE_DELETE) {
            if ($studentquizquestion->get_state() == studentquiz_helper::STATE_APPROVED) {
                $result = [];
                $result['status'] = get_string('api_state_change_error_title', 'studentquiz');
                $result['message'] = get_string('api_state_change_error_content', 'studentquiz');
                return $result;
            }
        }
        if ($state === studentquiz_helper::STATE_HIDE) {
            $studentquizquestion->change_hidden_status(1);
        } else if ($state === studentquiz_helper::STATE_DELETE) {
            $studentquizquestion->change_delete_state();
        } else {
            $studentquizquestion->change_state_visibility($state);
        }
        $studentquizquestion->save_action($state, $USER->id);

        $course = get_course($courseid);
        $PAGE->set_context($contextmodule);
        if (!$canmanage) {
            if ($state == studentquiz_helper::STATE_REVIEWABLE) {
                mod_studentquiz_notify_reviewable_question($studentquizquestion, $course, $cm);
            }
        } else {
            $statename = studentquiz_helper::$statename[$state];
            mod_studentquiz_event_notification_question($statename, $studentquizquestion, $course, $cm);
        }
        $result = [];
        $result['status'] = get_string('api_state_change_success_title', 'studentquiz');
        $result['message'] = get_string('api_state_change_success_content', 'studentquiz');
        return $result;
    }

    /**
     * Get available state return fields.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'status'),
            'message' => new external_value(PARAM_TEXT, 'message')
        ]);
    }
}
