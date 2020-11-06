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
 * This view renders a single question during the executing of a StudentQuiz
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(__DIR__ . '/locallib.php');

// Get parameters.
$cmid = required_param('cmid', PARAM_INT);
// Comment highlight.
$highlight = optional_param('highlight', 0, PARAM_INT);

// Load course and course module requested.
if ($cmid) {
    if (!$cm = get_coursemodule_from_id('studentquiz', $cmid)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
} else {
    print_error('invalidcoursemodule');
}

// Authentication check.
require_login($cm->course, false, $cm);

$attemptid = required_param('id', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$attempt = $DB->get_record('studentquiz_attempt', array('id' => $attemptid));

$context = context_module::instance($cm->id);
$studentquiz = mod_studentquiz_load_studentquiz($cmid, $context->id);

global $USER;
$userid = $USER->id;

$questionusage = question_engine::load_questions_usage_by_activity($attempt->questionusageid);

$slots = $questionusage->get_slots();

$questionids = explode(",", $attempt->ids);
$originalnumofquestionids = count($questionids);

if (!in_array($slot, $slots)) {
    mod_studentquiz_add_question_to_attempt($questionusage, $studentquiz, $questionids, $slot - 1);
    if (count($questionids) != $originalnumofquestionids) {
        $attempt->ids = implode(",", $questionids);
        $DB->update_record('studentquiz_attempt', $attempt);
    }
}

$actionurl = new moodle_url('/mod/studentquiz/attempt.php', array('cmid' => $cmid, 'id' => $attemptid, 'slot' => $slot));
// Reroute this to attempt summary page if desired.
$stopurl = new moodle_url('/mod/studentquiz/view.php', array('id' => $cmid));

// Get Current Question.
$question = $questionusage->get_question($slot);
// Navigatable?
$questionscount = count($questionids);
$hasnext = $slot < $questionscount;
$hasprevious = $slot > $questionusage->get_first_question_number();
$canfinish = $questionusage->can_question_finish_during_attempt($slot);

if (data_submitted()) {

    // Once data has been submitted, process actions to save the current question answer state. If the question can be
    // finished during the attempt (immediatefeedback), then do so. If it can't (adaptive), finish the question once
    // navigated further in the quiz. After the actions have been processed, proceed the requested navigation.
    $transaction = $DB->start_delegated_transaction();
    $isfinishedbefore = $questionusage->get_question_state($slot)->is_finished();

    $qa = $questionusage->get_question_attempt($slot);
    $sequencecheck = $qa->get_submitted_var($qa->get_control_field_name('sequencecheck'), PARAM_INT);
    if ($sequencecheck == $qa->get_sequence_check_count()) {
        // On every submission save the attempt.
        $questionusage->process_all_actions();
    }

    // So, immediatefeedback finishes question automatically after successful submit. But adaptive doesn't and it
    // should be finished manually but only when navigating further.
    if (!$canfinish && (optional_param('next', null, PARAM_BOOL) || optional_param('finish', null, PARAM_BOOL))) {
        if (!$isfinishedbefore) {
            $questionusage->finish_question($slot);
        }
    }

    // We save the attempts always to db, as there is no finish/submission step expected for the user.
    question_engine::save_questions_usage_by_activity($questionusage);
    $isfinishedafter = $questionusage->get_question_state($slot)->is_finished();

    // If the question is finished after process but was not before, save the attempt to the progress.
    if ($isfinishedafter && !$isfinishedbefore) {
        $q = $questionusage->get_question($slot);

        $studentquizprogress = $DB->get_record('studentquiz_progress', array('questionid' => $q->id,
            'userid' => $userid, 'studentquizid' => $studentquiz->id));
        if ($studentquizprogress == false) {
            $studentquizprogress = mod_studentquiz_get_studenquiz_progress_class($q->id, $userid, $studentquiz->id);
        }

        // Any newly finished attempt is wrong when it wasn't right.
        $studentquizprogress->attempts += 1;
        $studentquizprogress->lastanswercorrect = 0;

        if ($qa->get_state() == question_state::$gradedright) {
            $studentquizprogress->correctattempts += 1;
            $studentquizprogress->lastanswercorrect = 1;
        }

        if (!empty($studentquizprogress->id)) {
            $DB->update_record('studentquiz_progress', $studentquizprogress);
        } else {
            $studentquizprogress->id = $DB->insert_record('studentquiz_progress', $studentquizprogress, true);
        }
    }

    $transaction->allow_commit();

    // Navigate accordingly. If no navigation button has been submitted, then there has been a question answer attempt.
    if (optional_param('next', null, PARAM_BOOL)) {
        if ($hasnext) {
            $actionurl = new moodle_url($actionurl, array('slot' => $slot + 1));
            redirect($actionurl);
        } else {
            redirect($stopurl);
        }
    } else if (optional_param('previous', null, PARAM_BOOL)) {
        if ($hasprevious) {
            $actionurl = new moodle_url($actionurl, array('slot' => $slot - 1));
            redirect($actionurl);
        } else {
            $actionurl = new moodle_url($actionurl, array('slot' => $questionusage->get_first_question_number()));
            redirect($actionurl);
        }
    } else if (optional_param('finish', null, PARAM_BOOL)) {
        redirect($stopurl);
    } else {
        redirect($actionurl);
    }
}

// A question is always answered when it is finished. In immediatefeedback this happens immediatly after anwering
// without an invalid error. With adaptive a question is first answered if there is a graded step which is not null.
$isanswered = $questionusage->get_question_state($slot)->is_finished();
if (!$isanswered) {
    $behaviour = $questionusage->get_question_attempt($slot)->get_behaviour();
    if ($behaviour instanceof qbehaviour_adaptive) {
        $isanswered = (null !== $behaviour->get_graded_step());
    }
}

$options = new question_display_options();
$options->flags = question_display_options::EDITABLE;

/** @var mod_studentquiz_renderer $output */
$output = $PAGE->get_renderer('mod_studentquiz', 'attempt');
// Start output.
$PAGE->set_url($actionurl);
$jsparams = array(
    boolval($studentquiz->forcerating),
    boolval($studentquiz->forcecommenting),
    boolval($isanswered)
);
$PAGE->requires->js_call_amd('mod_studentquiz/studentquiz', 'initialise', $jsparams);
$title = format_string($question->name);
$PAGE->set_title($cm->name);
$PAGE->set_heading($cm->name);
$PAGE->set_context($context);

// Render nav bar.
$navinfo = new stdClass();
$navinfo->current = $slot;
$navinfo->total = $questionscount;
$PAGE->navbar->add(get_string('nav_question_no', 'studentquiz', $navinfo));

echo $OUTPUT->header();

$info = new stdClass();
$info->total = $questionscount;
$info->group = 0;
$info->one = max($slot - (!$isanswered ? 1 : 0), 0);
$texttotal = get_string('num_questions', 'studentquiz', $questionscount);
$html = '';

$html .= html_writer::div($output->render_progress_bar($info, $texttotal, true), '', array('title' => $texttotal));

// Render the question title.
$html .= html_writer::tag('h2', $title);

// Start the question form.

$html .= html_writer::start_tag('form', array('method' => 'post', 'action' => $actionurl,
    'enctype' => 'multipart/form-data', 'id' => 'responseform'));

$html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'cmid', 'value' => $cmid, 'class' => 'cmid_field'));

// Output the question.
$html .= $questionusage->render_question($slot, $options, (string)$slot);

// Output the state change select box.
$statechangehtml = $output->render_state_choice($question->id, $course->id, $cmid);
$navigationhtml = $output->render_navigation_bar($hasprevious, $hasnext, $isanswered);

// Change state will always first thing below navigation.
$orders  = [
    $navigationhtml,
    $statechangehtml
];

if ($isanswered) {
    // Get output the rating.
    $ratinghtml = $output->render_rate($question->id, $studentquiz->forcerating);
    // Get output the comments.
    $commenthtml = $output->render_comment($cmid, $question->id, $userid, $highlight);
    // If force rating and commenting, then it will above navigation.
    if ($studentquiz->forcerating && $studentquiz->forcecommenting) {
         $orders = array_merge([
             $ratinghtml,
             $commenthtml
         ], $orders);
    } else {
        // If force rating, then it will be render first.
        if ($studentquiz->forcerating) {
            array_unshift($orders, $ratinghtml);
        } else {
            $orders[] = $ratinghtml;
        }
        // If force commenting, then it will be render first.
        if ($studentquiz->forcecommenting) {
            array_unshift($orders, $commenthtml);
        } else {
            $orders[] = $commenthtml;
        }
    }
}

foreach ($orders as $v) {
    $html .= $v;
}

$html .= html_writer::end_tag('form');

echo $html;

echo $OUTPUT->footer();
