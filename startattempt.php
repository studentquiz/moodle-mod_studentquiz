<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/startattempt_form.php');
require_once($CFG->libdir . '/questionlib.php');

$id = required_param('id', PARAM_INT); // Course_module ID.


$PAGE->set_url('/mod/studentquiz/startattempt.php', array('id' => $id));
$DB->set_field('studentquiz_practice_session', 'status', 'finished', null);

if ($id) {
    if (!$cm = get_coursemodule_from_id('studentquiz', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
    $quizPractice = $DB->get_record('studentquiz', array('id' => $cm->instance));
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$behaviours = get_options_behaviour($cm);

$categories = $DB->get_records_menu('question_categories', array('contextid' => $cm->id), 'name', 'id, name');

$data = array();
$data['categories'] = $categories;
$data['behaviours'] = $behaviours;
$data['instanceid'] = $cm->instance;

$mform = new mod_studentquiz_startattempt_form(null, $data);

if ($mform->is_cancelled()) {
    $returnurl = new moodle_url('/mod/studentquiz/view.php', array('id' => $cm->id));
    redirect($returnurl);
} else if ($formData = $mform->get_data()) {
    $sessionid = quiz_practice_session_create($formData, $context);
    $nexturl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $sessionid));
    redirect($nexturl);
}

$mform->set_data(array(
    'id' => $cm->id,
));

// Print the page header.
$PAGE->set_title(format_string($quizPractice->name));
$PAGE->set_heading(format_string($quizPractice->name));
$PAGE->set_context($context);

// Output starts here.
echo $OUTPUT->header();

$mform->display();

// Finish the page.
echo $OUTPUT->footer();
