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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_studentquiz\local\studentquiz_question;

require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');

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
     * @throws \invalid_parameter_exception When the question does not belong to the given activity or the state is not allowed.
     * @throws \required_capability_exception When a user without changestate acts on a question they did not author.
     */
    public static function execute($courseid, $cmid, $studentquizquestionid, $state) {
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'cmid' => $cmid,
            'studentquizquestionid' => $studentquizquestionid,
            'state' => $state,
        ]);
        $contextmodule = \context_module::instance($params['cmid']);
        self::validate_context($contextmodule);
        $cm = get_coursemodule_from_id('studentquiz', $params['cmid'], 0, false, MUST_EXIST);
        if ((int) $cm->course !== $params['courseid']) {
            throw new \invalid_parameter_exception('The course module does not belong to the given course.');
        }

        $studentquizquestion = new studentquiz_question($params['studentquizquestionid']);
        $questioncm = $studentquizquestion->get_cm();
        if (!$questioncm || (int) $questioncm->id !== $params['cmid']) {
            throw new \invalid_parameter_exception('The question does not belong to the given course module.');
        }

        return $studentquizquestion->change_state($params['state']);
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
