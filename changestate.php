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
 * Change state question page.
 *
 * This code is based on question/classes/bank/view.php
 *
 * @package mod_studentquiz
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_studentquiz\local\studentquiz_helper;
use mod_studentquiz\local\studentquiz_question;
use mod_studentquiz\utils;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$approveselected = optional_param('approveselected', false, PARAM_BOOL);
$returnurl = optional_param('returnurl', 0, PARAM_LOCALURL);
$cmid = optional_param('cmid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($cmid, 'studentquiz');
require_login($course, false, $cm);
$context = context_module::instance($cmid);
require_capability('mod/studentquiz:changestate', $context);

if ($returnurl) {
    $returnurl = new moodle_url($returnurl);
}

$url = new moodle_url('/mod/studentquiz/changestate.php');

$PAGE->set_url($url);
$PAGE->set_course($course);
$PAGE->set_context($context);
$PAGE->set_title(get_string('state_toggle', 'mod_studentquiz'));
$PAGE->set_heading($course->fullname);
$PAGE->activityheader->disable();
$PAGE->set_secondary_active_tab("studentquiz");

// If user has already confirmed the action.
if (
    $approveselected &&
    ($confirm = optional_param('confirm', '', PARAM_ALPHANUM)) &&
    confirm_sesskey()
) {
    // If teacher has already confirmed the action.
    $approveselected = required_param('approveselected', PARAM_RAW);
    $state = required_param('state', PARAM_INT);
    if ($confirm == md5($approveselected)) {
        // We should not change the state if state is not being changed.
        if ($state !== -1) {
            $selectedquestions = studentquiz_question::get_studentquiz_questions_from_question_ids(
                explode(',', $approveselected),
                $cmid,
                $cm,
                $context
            );
            foreach ($selectedquestions as $studentquizquestion) {
                $studentquizquestion->change_state($state);
            }
        }
        redirect($returnurl);
    } else {
        throw new moodle_exception("invalidconfirm', 'question");
    }
}

$questionids = mod_studentquiz_helper_get_ids_by_raw_submit($_REQUEST);
if ($approveselected) {
    $selectedquestions = studentquiz_question::get_studentquiz_questions_from_question_ids($questionids, $cmid, $cm, $context);
    if (!$selectedquestions) {
        redirect($returnurl);
    }
}

echo $OUTPUT->header();

if ($approveselected) {
    // Make a list of all the questions that are selected.
    $questionlist = '';  // Comma separated list of ids of questions to be deleted.
    $questionnames = ''; // String with names of questions separated by <br/> with an asterix in front of those that are in use.
    $inuse = false;      // Set to true if at least one of the questions is in use.
    $statedesc = studentquiz_helper::get_state_descriptions();
    $questionnames = utils::get_question_names($questionids);
    $questions = [];

    foreach ($selectedquestions as $key => $sqq) {
        $questionlist .= $key . ',';
        $question = new stdClass();
        $question->name = '';
        if (questions_in_use([$key])) {
            $question->name .= '* ';
            $inuse = true;
        }
        $question->name .= $questionnames[$key]->name;
        $question->state = $statedesc[$sqq->get_state()];
        $questions[] = $question;
    }
    if (!$questionlist) {
        // No questions were selected.
        redirect($returnurl);
    }
    $questionlist = rtrim($questionlist, ',');

    // Add an explanation about questions in use.
    $approveurl = new \moodle_url($url, ['approveselected' => $questionlist, 'state' => -1,
        'confirm' => md5($questionlist), 'sesskey' => sesskey(), 'returnurl' => $returnurl,
        'cmid' => $cmid, 'courseid' => $courseid]);

    $continue = new \single_button($approveurl, get_string('state_toggle', 'studentquiz'), 'get');
    $renderer = $PAGE->get_renderer('mod_studentquiz', 'overview');
    $currentstatequestions = $renderer->render_current_state_questions($questions, $inuse);
    echo $renderer->render_change_state_dialog($currentstatequestions, $continue, $returnurl);
}

echo $OUTPUT->footer();
