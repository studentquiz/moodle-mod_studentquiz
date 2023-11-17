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
 * Ajax requests to this script saves the ratings and comments.
 *
 * Require POST params:
 * "save" can be "rate" or "comment" (save type),
 * "questionid" is necessary for every request,
 * "rate" is necessary if the save type is "rate"
 * "text" is necessary if the save type is "comment"
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

global $COURSE;

// Get parameters.
$cmid = required_param('cmid', PARAM_INT);
$studentquizquestionid = required_param('studentquizquestionid', PARAM_INT);
$save = required_param('save', PARAM_NOTAGS);

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'studentquiz');

// Authentication check.
require_login($course, false, $cm);
require_sesskey();

$data = new \stdClass();
$data->userid = $USER->id;
$data->studentquizquestionid = $studentquizquestionid;

switch($save) {
    case 'rate':
        $data->rate = required_param('rate', PARAM_INT);

        // Rating is only valid if the rate is in or between 1 and 5.
        if ($data->rate < 1 || $data->rate > 5) {
            throw new moodle_exception("invalidrate");
        }

        mod_studentquiz\utils::save_rate($data);
        break;
}

$contextmodule = \context_module::instance($cmid);
$studentquiz = mod_studentquiz_load_studentquiz($cmid, $contextmodule->id);
$studentquizquestion = new mod_studentquiz\local\studentquiz_question(
    $studentquizquestionid, null, $studentquiz);

// Update completion state.
\mod_studentquiz\completion\custom_completion::trigger_completion_state_update(
    $course, $cm, $studentquizquestion->get_question()->createdby);

header('Content-Type: text/html; charset=utf-8');
