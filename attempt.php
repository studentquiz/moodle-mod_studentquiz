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

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Get parameters.
$cmid = required_param('cmid', PARAM_INT);

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
    // There should be no question data if he has already answered them, as the fields are disabled.
    if (optional_param('next', null, PARAM_BOOL)) {
        // There is submitted data. Process it.
        $transaction = $DB->start_delegated_transaction();
        $questionusage->finish_question($slot);
        // TODO: Update tracking data --> studentquiz progress, studentquiz_attempt.
        $transaction->allow_commit();

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
        $transaction = $DB->start_delegated_transaction();
        $questionusage->finish_question($slot);
        // TODO: Update tracking data --> studentquiz progress, studentquiz_attempt.
        $transaction->allow_commit();

        // TODO Trigger events?
        redirect($stopurl);
    } else {
        // On every submission save the attempt.
        $questionusage->process_all_actions();
        // We save the attempts always to db, as there is no finish/submission step expected for the user.
        question_engine::save_questions_usage_by_activity($questionusage);

        // Only add studentquiz_progress information, if it is a studentquiz aggregated type
        if ($studentquiz->aggregated) {
            $qa = $questionusage->get_question_attempt($slot);
            $q = $questionusage->get_question($slot);

            $studentquizprogress = $DB->get_record('studentquiz_progress', array('questionid' => $q->id,
                'userid' => $userid, 'studentquizid' => $studentquiz->id));
            $updatestudentquizprogress = true;
            if ($studentquizprogress == false) {
                $updatestudentquizprogress = false;
                $studentquizprogress = mod_studentquiz_get_studenquiz_progress_class($q->id, $userid, $studentquiz->id);
            }

            $studentquizprogress->attempts += 1;

            switch($qa->get_state()) {
                case question_state::$gradedright:
                    $studentquizprogress->correctattempts += 1;
                    $studentquizprogress->lastanswercorrect = 1;
                    break;
                case question_state::$gradedwrong:
                case question_state::$gradedpartial:
                    $studentquizprogress->lastanswercorrect = 0;
                    break;
                case question_state::$todo:
                default:
                    break;
            }

            if ($updatestudentquizprogress) {
                $DB->update_record('studentquiz_progress', $studentquizprogress);
            } else {
                $studentquizprogress->id = $DB->insert_record('studentquiz_progress', $studentquizprogress, true);
            }
        }

        redirect($actionurl);
    }
}

// Has answered?
$hasanswered = false;
switch($questionusage->get_question_attempt($slot)->get_state()) {
    case question_state::$gradedpartial:
    case question_state::$gradedright:
    case question_state::$gradedwrong:
    case question_state::$complete:
        $hasanswered = true;
        break;
    case question_state::$todo:
    default:
        $hasanswered = false;
}

// Is rated?
$hasrated = false;

$options = new question_display_options();


if ($question->qtype instanceof qtype_description
    || $question->qtype instanceof qtype_essay) {
    $hasanswered = true;
    $options->readonly = true;
}

// TODO do they do anything? $headtags not used anywhere and question_engin..._js returns void.
$headtags = '';
$headtags .= $questionusage->render_question_head_html($slot);
$headtags .= question_engine::initialise_js();

/** @var mod_studentquiz_renderer $output */
$output = $PAGE->get_renderer('mod_studentquiz', 'attempt');
// Start output.
$PAGE->set_url($actionurl);
$PAGE->requires->js_call_amd('mod_studentquiz/studentquiz', 'initialise');
$title = format_string($question->name);
$PAGE->set_title($cm->name);
$PAGE->set_heading($cm->name);
$PAGE->set_context($context);
echo $OUTPUT->header();

$info = new stdClass();
$info->total = $questionscount;
$info->group = 0;
$info->one = max($slot - (!$hasanswered ? 1 : 0), 0);
$texttotal = $questionscount . ' ' . get_string('questions', 'studentquiz');
$html = '';

$html .= html_writer::div($output->render_progress_bar($info, $texttotal, true), '', array('title' => $texttotal));

// Render the question title.
$html .= html_writer::tag('h2', $title);

// Start the question form.

$html .= html_writer::start_tag('form', array('method' => 'post', 'action' => $actionurl,
    'enctype' => 'multipart/form-data', 'id' => 'responseform'));

$html .= '<input type="hidden" class="cmid_field" name="cmid" value="' . $cmid . '" />';

// Output the question.
$html .= $questionusage->render_question($slot, $options, (string)$slot);

// Output the rating.
if ($hasanswered) {
    $comments = mod_studentquiz_get_comments_with_creators($question->id);

    $anonymize = $studentquiz->anonymrank;
    if (has_capability('mod/studentquiz:unhideanonymous', $context)) {
        $anonymize = false;
    }
    $ismoderator = false;
    if (mod_studentquiz_check_created_permission($cmid)) {
        $ismoderator = true;
    }

    $html .= $output->feedback($question, $options, $cmid, $comments, $userid, $anonymize, $ismoderator);
}

// Finish the question form.
$html .= html_writer::start_tag('div', array('class' => 'mod-studentquiz-attempt-nav row'));
$html .= html_writer::start_tag('div', array('class' => 'col-md-4'));
$html .= html_writer::start_tag('div', array('class' => 'pull-left'));
if ($hasprevious) {
    $html .= html_writer::empty_tag('input',
        array('type' => 'submit', 'name' => 'previous',
            'value' => get_string('previous_button', 'studentquiz'), 'class' => 'btn btn-primary'));
} else {
    $html .= '&nbsp;';
}
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');

$html .= html_writer::start_tag('div', array('class' => 'col-md-4'));
$html .= html_writer::start_tag('div', array('class' => 'mdl-align'));

// Not has rated, is done using javascript.
if ($canfinish && ($hasnext || !$hasanswered)) {
    $html .= html_writer::empty_tag('input',
        array('type' => 'submit', 'name' => 'finish',
            'value' => get_string('finish_button', 'studentquiz'), 'class' => 'btn btn-link'));
}

$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');
$html .= html_writer::start_tag('div', array('class' => 'col-md-4'));
$html .= html_writer::start_tag('div', array('class' => 'pull-right'));

// And not hasrated, but done using javascript as not showing the next button seems not intuitive.
if ($hasanswered) {
    if ($hasnext) {
        $html .= html_writer::empty_tag('input',
            array('type' => 'submit', 'name' => 'next',
                'value' => get_string('next_button', 'studentquiz'), 'class' => 'btn btn-primary'));
    } else { // Finish instead of next on the last question.
        $html .= html_writer::empty_tag('input',
            array('type' => 'submit', 'name' => 'finish',
                'value' => get_string('finish_button', 'studentquiz'), 'class' => 'btn btn-primary'));
    }
}
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('form');


echo $html;

// Display the settings form.

echo $OUTPUT->footer();

