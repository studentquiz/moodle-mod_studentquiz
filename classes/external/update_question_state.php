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
     * States the question author may set without mod/studentquiz:changestate.
     *
     * Mirrors the choices offered by renderer::render_state_choice() to authors.
     */
    private const AUTHOR_STATES = [
        studentquiz_helper::STATE_CHANGED,
        studentquiz_helper::STATE_REVIEWABLE,
        studentquiz_helper::STATE_DELETE,
    ];

    /**
     * States a user holding mod/studentquiz:changestate may set.
     *
     * Mirrors the choices offered by renderer::render_state_choice() and the bulk change state dialog.
     */
    private const MANAGER_STATES = [
        studentquiz_helper::STATE_DISAPPROVED,
        studentquiz_helper::STATE_APPROVED,
        studentquiz_helper::STATE_CHANGED,
        studentquiz_helper::STATE_REVIEWABLE,
        studentquiz_helper::STATE_HIDE,
        studentquiz_helper::STATE_DELETE,
    ];

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
     * @throws \invalid_parameter_exception When the question does not belong to the given activity or the state is not allowed.
     * @throws \required_capability_exception When a user without changestate acts on a question they did not author.
     */
    public static function execute($courseid, $cmid, $studentquizquestionid, $state) {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'studentquizquestionid' => $studentquizquestionid,
            'state' => $state,
        ]);
        $state = $params['state'];

        // Derive the activity from the question itself: caller-supplied course and module ids are never trusted.
        $studentquizquestion = new studentquiz_question($params['studentquizquestionid']);
        $cm = $studentquizquestion->get_cm();
        if (!$cm || (int) $cm->id !== $params['cmid'] || (int) $cm->course !== $params['courseid']) {
            throw new \invalid_parameter_exception('The question does not belong to the given course module.');
        }
        $contextmodule = $studentquizquestion->get_context();
        self::validate_context($contextmodule);

        $canmanage = has_capability('mod/studentquiz:changestate', $contextmodule);
        if (!$canmanage && (int) $studentquizquestion->get_question()->createdby !== (int) $USER->id) {
            throw new \required_capability_exception($contextmodule, 'mod/studentquiz:changestate', 'nopermissions', '');
        }
        if (!in_array($state, $canmanage ? self::MANAGER_STATES : self::AUTHOR_STATES, true)) {
            throw new \invalid_parameter_exception('Invalid or forbidden question state.');
        }

        // Student can not delete the question when the question is in approved state.
        if (!$canmanage && $state === studentquiz_helper::STATE_DELETE
                && (int) $studentquizquestion->get_state() === studentquiz_helper::STATE_APPROVED) {
            return [
                'status' => get_string('api_state_change_error_title', 'studentquiz'),
                'message' => get_string('api_state_change_error_content', 'studentquiz'),
            ];
        }
        if ($state === studentquiz_helper::STATE_HIDE) {
            $studentquizquestion->change_hidden_status(1);
        } else if ($state === studentquiz_helper::STATE_DELETE) {
            $studentquizquestion->change_delete_state();
        } else {
            $studentquizquestion->change_state_visibility($state);
        }
        $studentquizquestion->save_action($state, $USER->id);

        $course = get_course($cm->course);
        if (!$canmanage) {
            if ($state == studentquiz_helper::STATE_REVIEWABLE) {
                mod_studentquiz_notify_reviewable_question($studentquizquestion, $course, $cm);
            }
        } else {
            $statename = studentquiz_helper::$statename[$state];
            mod_studentquiz_event_notification_question($statename, $studentquizquestion, $course, $cm);
        }

        // Update completion state.
        \mod_studentquiz\completion\custom_completion::trigger_completion_state_update($course, $cm,
            $studentquizquestion->get_question()->createdby);

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
