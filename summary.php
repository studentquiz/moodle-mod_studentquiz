<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/renderer.php');
require_once("$CFG->libdir/formslib.php");
require_once(dirname(__FILE__) . '/attemptlib.php');

$psessionid = required_param('id', PARAM_INT);
$session = $DB->get_record('studentquiz_psession', array('id' => $psessionid));
$overview = $DB->get_record('studentquiz_poverview', array('id' => $session->studentquizpoverviewid));

if (!$cm = get_coursemodule_from_instance('studentquiz', $overview->studentquizid)) {
    print_error('invalidquizid', 'studentquiz');
}
$course = $DB->get_record('course', array('id' => $cm->course));
$studentquiz = $DB->get_record('studentquiz', array('id' => $cm->instance));

$quba = question_engine::load_questions_usage_by_activity($session->questionusageid);

$hasabandoned = optional_param('hasabandoned', false, PARAM_BOOL);
$attempt = new studentquiz_practice_attempt($session, $overview,$cm ,$course);

$state = $hasabandoned ? $attempt::ABANDONED : $attempt::FINISHED;
$DB->set_field('studentquiz_psession', 'state', $state, array('id' => $psessionid));

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$params = array(
    'objectid' => $cm->id,
    'context' => $context
);
//$event = \mod_studentquiz\event\studentquiz_practice_summary::create($params);
//$event->trigger();

$retryurl = new moodle_url('/mod/studentquiz/view.php', array('sessionid' => $psessionid, 'id' => $cm->id, 'retryquiz' => '1'));
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
$PAGE->set_url('/mod/studentquiz/summary.php', array('id' => $psessionid));
$output = $PAGE->get_renderer('mod_studentquiz');

echo $OUTPUT->header();

echo $output->summary_table($psessionid);

echo $output->summary_form($psessionid);

// Finish the page.
echo $OUTPUT->footer();
