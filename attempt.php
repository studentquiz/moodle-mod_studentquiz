<?php

// @TODO Reduce filelib if not necessary.
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

$attemptid = required_param('id', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$attempt = $DB->get_record('studentquiz_attempt', array('id' => $attemptid));

$cm = get_coursemodule_from_instance('studentquiz', $attempt->studentquizid);
$course = $DB->get_record('course', array('id' => $cm->course));

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// TODO: Manage capabilities and events for studentquiz
$questionusage = question_engine::load_questions_usage_by_activity($attempt->questionusageid);

$actionurl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $attemptid, 'slot' => $slot));
$stopurl = new moodle_url('/mod/studentquiz/summary.php', array('id' => $attemptid));

// Get Current Question.
$question = $questionusage->get_question($slot);

if (data_submitted()) {
    if (optional_param('next', null, PARAM_BOOL)) {
        // There is submitted data. Process it.
        $transaction = $DB->start_delegated_transaction();

        $questionusage->finish_question($slot);

        // @TODO: Update tracking data --> studentquiz progress, studentquiz_attempt
        $transaction->allow_commit();

        if (in_array($slot+1 , $questionusage->get_slots())) {
            $actionurl = new moodle_url($actionurl, array('slot' => $slot + 1));
            redirect($actionurl);
        }else{
            redirect($stopurl);
        }
    } else if (optional_param('finish', null, PARAM_BOOL)) {
        question_engine::save_questions_usage_by_activity($questionusage);
        //@TODO Trigger events?
        redirect($stopurl);
    } else {
        $questionusage->process_all_actions();
        question_engine::save_questions_usage_by_activity($questionusage);
        redirect($actionurl);
    }
}

$options = new question_display_options();
$headtags = '';
$headtags .= $questionusage->render_question_head_html($slot);
$headtags .= question_engine::initialise_js();
// Start output.
$PAGE->set_url($actionurl);
$title = format_string($question->name);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_context($context);
echo $OUTPUT->header();

// Start the question form.

$html = html_writer::start_tag('form', array('method' => 'post', 'action' => $actionurl,
    'enctype' => 'multipart/form-data', 'id' => 'responseform'));
//$html .= html_writer::start_tag('div');
//$html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
//$html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'slots', 'value' => $slot));
//$html .= html_writer::end_tag('div');

// Output the question.
$html .= $questionusage->render_question($slot, $options);

// Finish the question form.
$html .= html_writer::start_tag('div');
// @TODO: extract to language file.
$html .= html_writer::empty_tag('input', array('type' => 'submit','name' => 'next', 'value' => 'Next'));
$html .= html_writer::empty_tag('input', array('type' => 'submit','name' => 'finish', 'value' => 'Finish'));
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('form');

echo $html;
// Display the settings form.

echo $OUTPUT->footer();

