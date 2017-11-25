<?php
/**
 * This page displays the result summary of the current attempt
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/renderer.php');
require_once(dirname(__FILE__) . '/summarylib.php');
require_once($CFG->libdir . '/formslib.php');


global $PAGE, $USER;

$attemptid = required_param('id', PARAM_INT);
$attempt = $DB->get_record('studentquiz_attempt', array('id' => $attemptid));
$cm = get_coursemodule_from_instance('studentquiz', $attempt->studentquizid);
$course = $DB->get_record('course', array('id' => $cm->course));
$studentquiz = $DB->get_record('studentquiz', array('id' => $cm->instance));
$userid = $USER->id;

require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$actionurl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $attemptid, 'slot' => 1));
$stopurl = new moodle_url('/mod/studentquiz/view.php', array('id' => $cm->id));

if (data_submitted()) {
    if (optional_param('back', null, PARAM_BOOL)) {
        redirect($actionurl);
    }
    if (optional_param('finish', null, PARAM_BOOL)) {
        // TODO: Summary and aggregate evaluations of attempt!
        redirect($stopurl);
    }
}
// TODO: Trigger view events!
$view = new mod_studentquiz_summary_view($cm, $studentquiz, $attempt, $userid);

// Set additional values to $view here.

$PAGE->set_title($studentquiz->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_url('/mod/studentquiz/summary.php', array('id' => $attemptid));

$renderer = $PAGE->get_renderer('mod_studentquiz', 'summary');

echo $OUTPUT->header();

echo $renderer->render_summary($view);

echo $OUTPUT->footer();