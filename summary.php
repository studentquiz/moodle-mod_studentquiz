<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/renderer.php');
require_once("$CFG->libdir/formslib.php");

$attemptid = required_param('id', PARAM_INT);
$attempt = $DB->get_record('studentquiz_attempt', array('id' => $attemptid));
$cm = get_coursemodule_from_instance('studentquiz', $attempt->studentquizid);
$course = $DB->get_record('course', array('id' => $cm->course));
$studentquiz = $DB->get_record('studentquiz', array('id' => $cm->instance));

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// @TODO: Trigger view events

$actionurl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $attemptid, 'slot' => 1));
$stopurl = new moodle_url('/mod/studentquiz/view.php', array('id' => $cm->id));

if (data_submitted()) {
    if (optional_param('back', null, PARAM_BOOL)) {
        redirect($actionurl);
    }
    if (optional_param('finish', null, PARAM_BOOL)) {
        // @TODO: Summary and aggregate evaluations of attempt?
        redirect($stopurl);
    }
}
$PAGE->set_title($studentquiz->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_url('/mod/studentquiz/summary.php', array('id' => $attemptid));
$output = $PAGE->get_renderer('mod_studentquiz');

echo $OUTPUT->header();

// @TODO: Refactor language strings
$output = '';
$output .= html_writer::start_tag('form', array('method' => 'post', 'action' => '',
    'enctype' => 'multipart/form-data', 'id' => 'responseform'));
$output .= html_writer::start_tag('div', array('align' => 'center'));
$output .= html_writer::empty_tag('input', array('type' => 'submit',
    'name' => 'back', 'value' => 'Retry'));
$output .= html_writer::empty_tag('br');
$output .= html_writer::empty_tag('br');
$output .= html_writer::empty_tag('input', array('type' => 'submit',
    'name' => 'finish', 'value' => 'Finish'));
$output .= html_writer::end_tag('div');
$output .= html_writer::end_tag('form');

echo $output;

// Finish the page.
echo $OUTPUT->footer();