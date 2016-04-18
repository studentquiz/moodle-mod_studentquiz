<?php
define('CACHE_DISABLE_ALL', true);
define('CACHE_DISABLE_STORES', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once(dirname(__FILE__) . '/locallib.php');


$cmid = optional_param('id', 0, PARAM_INT);
if(!$cmid){
    $cmid = required_param('cmid', PARAM_INT);
}

if($cmid){
    if (!$cm = get_coursemodule_from_id('studentquiz', $cmid)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
}


require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$search  = optional_param('search', '', PARAM_RAW);

$context = context_module::instance($cmid);
$category = question_get_default_category($context->id);

if (data_submitted()) {
    if(optional_param('startquiz', null, PARAM_BOOL)){
        $data = new stdClass();
        $data->behaviour = "studentquiz";
        $data->instanceid = $cm->instance;
        $data->categoryid = $category->id;
        $sessionid = quiz_practice_create_quiz_helper($data, $context, (array) data_submitted());
        $nexturl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $sessionid, 'startquiz' => 1));
        redirect($nexturl);
    }
    if(optional_param('startrandomquiz', null, PARAM_RAW)){
        $ids = required_param('filtered_question_ids', PARAM_RAW);
        $ids = explode(',', $ids);
        $data = new stdClass();
        $data->behaviour = "studentquiz";
        $data->instanceid = $cm->instance;
        $data->categoryid = $category->id;
        $sessionid = quiz_practice_create_quiz_helper($data, $context, $ids, false);
        $nexturl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $sessionid, 'startquiz' => 1));
        redirect($nexturl);
    }
}
if(optional_param('retryquiz', null, PARAM_BOOL)) {
    $sessionid = required_param('sessionid' , PARAM_INT);
    if (!$session = $DB->get_record('studentquiz_p_session', array('studentquiz_p_session_id' => $sessionid), 'question_usage_id, studentquiz_p_overview_id')) {
        print_error('sessionmissconf');
    }
    $data = new stdClass();
    $data->behaviour = "studentquiz";
    $data->instanceid = $cm->instance;
    $data->categoryid = $category->id;
    $sessionid = quiz_practice_retry_quiz($data, $context, $session);
    $nexturl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $sessionid, 'startquiz' => 1));
    redirect($nexturl);

}


$_GET['cmid'] = $cmid;
$_POST['cat'] = $category->id . ',' . $context->id;
list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
    question_edit_setup('questions', '/mod/studentquiz/view.php', true, false);



$url = new moodle_url($thispageurl);
if (($lastchanged = optional_param('lastchanged', 0, PARAM_INT)) !== 0) {
    $url->param('lastchanged', $lastchanged);
}
$thispageurl->param('search', $search);
$PAGE->set_url($url);

$questionbank = new \mod_studentquiz\question\bank\custom_view($contexts, $thispageurl, $COURSE, $cm, $search);
$questionbank->process_actions();

// TODO log this page view.

$context = $contexts->lowest();
$streditingquestions = get_string('editquestions', 'question');
$PAGE->set_title($streditingquestions);
$PAGE->set_heading($COURSE->fullname);

echo $OUTPUT->header();

echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';

$questionbank->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
    $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'],
    $pagevars['qbshowtext']);
echo "</div>\n";

echo $OUTPUT->footer();
