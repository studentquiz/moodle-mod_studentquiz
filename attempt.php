<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/filelib.php');

$sessionid = required_param('id', PARAM_INT);
$session = $DB->get_record('studentquiz_practice_session', array('id' => $sessionid));

$cm = get_coursemodule_from_instance('studentquiz', $session->studentquiz_id);

$course = $DB->get_record('course', array('id' => $cm->course));

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

//require_capability('mod/studentquiz:attempt', $context);
$params = array(
    'objectid' => $cm->id,
    'context' => $context
);
$event = \mod_studentquiz\event\studentquiz_practice_attempted::create($params);
$event->trigger();

$quba = question_engine::load_questions_usage_by_activity($session->question_usage_id);

$actionurl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $sessionid));
$stopurl = new moodle_url('/mod/studentquiz/summary.php', array('id' => $sessionid));

if (data_submitted()) {
    if (optional_param('next', null, PARAM_BOOL)) {
        $slot = optional_param('slots', 0, PARAM_INT);
        $quba->process_all_actions($slot, $_POST);
        $quba->finish_question($slot);
        $slot += 1;

        $slots = $quba->get_slots();
        if($slot > end($slots)){
            question_engine::save_questions_usage_by_activity($quba);
            quiz_practice_update_points($quba, $sessionid);
            $params = array(
                'objectid' => $cm->id,
                'context' => $context
            );
            $event = \mod_studentquiz\event\studentquiz_practice_finished::create($params);
            $event->trigger();
            redirect($stopurl);
        }
        question_engine::save_questions_usage_by_activity($quba);
        $question = $quba->get_question($slot);

    } else if (optional_param('finish', null, PARAM_BOOL)){
        question_engine::save_questions_usage_by_activity($quba);
        quiz_practice_update_points($quba, $sessionid);
        $params = array(
            'objectid' => $cm->id,
            'context' => $context
        );
        $event = \mod_studentquiz\event\studentquiz_practice_finished::create($params);
        $event->trigger();
        redirect($stopurl);
    } else {
        echo "todo - redirect back to question view";
        die();
    }
} else {
    $slots = $quba->get_slots();
    $slot = reset($slots);
    $question = $quba->get_question($slot);
}

$options = new question_display_options();
$headtags = '';
$headtags .= $quba->render_question_head_html($slot);
$headtags .= question_engine::initialise_js();
// Start output.
$PAGE->set_url('/mod/studentquiz/attempt.php', array('id' => $sessionid));
$title = get_string('practice_session', 'studentquiz', format_string($question->name));
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_context($context);
echo $OUTPUT->header();

// Start the question form.

$html = html_writer::start_tag('form', array('method' => 'post', 'action' => $actionurl,
    'enctype' => 'multipart/form-data', 'id' => 'responseform'));
$html .= html_writer::start_tag('div');
$html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
$html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'slots', 'value' => $slot));
$html .= html_writer::end_tag('div');


// Output the question.
$html .= $quba->render_question($slot, $options, $slot);

// Finish the question form.
$html .= html_writer::start_tag('div');
$html .= html_writer::empty_tag('input', array('type' => 'submit',
    'name' => 'next', 'value' => get_string('practice_nextquestion', 'studentquiz')));
$html .= html_writer::empty_tag('input', array('type' => 'submit',
    'name' => 'finish', 'value' => get_string('practice_stoppractice', 'studentquiz')));
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('form');

echo $html;
// Display the settings form.

echo $OUTPUT->footer();

