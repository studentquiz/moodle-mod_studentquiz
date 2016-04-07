<?php
define('CACHE_DISABLE_ALL', true);
define('CACHE_DISABLE_STORES', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

function mod_studentquiz_get_question_bank_search_conditions() {
    echo "get extendsion";
    return array();
}

$cmid = optional_param('id', 0, PARAM_INT);
if(!$cmid){
    $cmid = required_param('cmid', PARAM_INT);
}
$search  = optional_param('search', '', PARAM_RAW);

$context = context_module::instance($cmid);
$category = question_get_default_category($context->id);
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
