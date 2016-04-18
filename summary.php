<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/renderer.php');
require_once("$CFG->libdir/formslib.php");

$sessionid = required_param('id', PARAM_INT);
$session = $DB->get_record('studentquiz_p_session', array('studentquiz_p_session_id' => $sessionid));
$overview = $DB->get_record('studentquiz_p_overview', array('studentquiz_p_overview_id' => $session->studentquiz_p_overview_id));

if (!$cm = get_coursemodule_from_instance('studentquiz', $overview->studentquiz_id)) {
    print_error('invalidquizid', 'studentquiz');
}
$course = $DB->get_record('course', array('id' => $cm->course));
$studentquiz = $DB->get_record('studentquiz', array('id' => $cm->instance));

$quba = question_engine::load_questions_usage_by_activity($session->question_usage_id);
$DB->set_field('studentquiz_p_session', 'state', 'finished', array('studentquiz_p_session_id' => $sessionid));

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$params = array(
    'objectid' => $cm->id,
    'context' => $context
);
//$event = \mod_studentquiz\event\studentquiz_practice_summary::create($params);
//$event->trigger();

$retryurl = new moodle_url('/mod/studentquiz/view.php', array('sessionid' => $sessionid, 'id' => $cm->id, 'retryquiz' => '1'));
$finishurl = new moodle_url('/mod/studentquiz/view.php', array('id' => $cm->id));

if(optional_param('retry', null, PARAM_BOOL)){
    redirect($retryurl);
}

if(optional_param('finish', null, PARAM_BOOL)){
    redirect($finishurl);
}

$PAGE->set_title($studentquiz->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_url('/mod/studentquiz/summary.php', array('id' => $sessionid));
$output = $PAGE->get_renderer('mod_studentquiz');

echo $OUTPUT->header();

echo $output->summary_table($sessionid);

echo $output->summary_form($sessionid);

// Finish the page.
echo $OUTPUT->footer();
