<?php

require_once(dirname(dirname(__DIR__)).'/config.php');
require_once(__DIR__ . '/viewlib.php');
require_once(__DIR__ . '/renderer.php');

//$renderer = $PAGE->get_renderer('mod_studentquiz', 'overview');

$cmid = optional_param('id', 0, PARAM_INT);

if (!$cmid) {
    $cmid = required_param('cmid', PARAM_INT);
}

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

// Load context.
$context = context_module::instance($cm->id);

// Load studentquiz.
$studentquiz = mod_studentquiz_load_studentquiz($cm->id, $context->id);

// Load context.
$context = context_module::instance($cm->id);

$justmigrated = false;

if (!has_capability('mod/studentquiz:manage', $context)) {
    redirect(new moodle_url('/mod/studentquiz/view.php', array("id" => $cm->id)));
}

if (data_submitted()) {
    if(optional_param("do", '', PARAM_RAW) === 'yes') {
        if($studentquiz->aggregated == 0) {
            $data = mod_studentquiz_get_studentquiz_progress_from_question_attempts_steps($studentquiz->id);

            $DB->insert_records('studentquiz_progress', new ArrayIterator($data));

            $studentquiz->aggregated = 1;

            $DB->update_record('studentquiz', $studentquiz);

            $justmigrated = true;
        }
    }
}

$PAGE->set_title("Migrate Data");
$PAGE->set_heading("Migrate Data");
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/studentquiz/reportrank.php', array('cmid' => $cmid)));

$output = $PAGE->get_renderer('mod_studentquiz', 'migration');

echo $OUTPUT->header();

if($justmigrated) {
    echo $output->view_body_success($cmid, $studentquiz);
}else{
    echo $output->view_body($cmid, $studentquiz);
}

echo $OUTPUT->footer();