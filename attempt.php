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

require_capability('mod/studentquiz:attempt', $context);
$params = array(
    'objectid' => $cm->id,
    'context' => $context
);
$event = \mod_studentquiz\event\studentquiz_attempted::create($params);
$event->trigger();

$quba = question_engine::load_questions_usage_by_activity($session->question_usage_id);

$actionurl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $sessionid));
$stopurl = new moodle_url('/mod/studentquiz/summary.php', array('id' => $sessionid));

if (data_submitted()) {
    if(optional_param('startquiz', null, PARAM_BOOL)){
        quiz_add_selected_questions((array) data_submitted(), $quba);
    }
    else if (optional_param('next', null, PARAM_BOOL)) {
        // There is submitted data. Process it.
        $transaction = $DB->start_delegated_transaction();
        $slots = $quba->get_slots();
        $slot = end($slots);
        $quba->finish_question($slot);
        $fraction = $quba->get_question_fraction($slot);
        $maxmarks = $quba->get_question_max_mark($slot);
        $obtainedmarks = $fraction * $maxmarks;
        $updatesql = "UPDATE {qpractice_session}
                          SET marksobtained = marksobtained + ?, totalmarks = totalmarks + ?
                        WHERE id=?";
        $DB->execute($updatesql, array($obtainedmarks, $maxmarks, $sessionid));

        if ($fraction > 0) {
            $updatesql1 = "UPDATE {qpractice_session}
                          SET totalnoofquestionsright = totalnoofquestionsright + '1'
                        WHERE id=?";
            $DB->execute($updatesql1, array($sessionid));
        }
        $slot = get_next_question($sessionid, $quba);
        $question = $quba->get_question($slot);
        $transaction->allow_commit();
        redirect($actionurl);
    } else if (optional_param('finish', null, PARAM_BOOL)) {
        question_engine::save_questions_usage_by_activity($quba);
        $params = array(
            'objectid' => $cm->id,
            'context' => $context
        );
        $event = \mod_qpractice\event\qpractice_finished::create($params);
        $event->trigger();
        redirect($stopurl);
    } else {
        $quba->process_all_actions();
        $slots = $quba->get_slots();
        $slot = end($slots);
        question_engine::save_questions_usage_by_activity($quba);
        redirect($actionurl);
    }
} else {
    // We are just viewing the page again. Is there a currently active question?
    $slots = $quba->get_slots();
    $slot = end($slots);
    if (!$slot) {

        $slot = get_next_question($sessionid, $quba);
        $question = $quba->get_question($slot);
    } else {
        // The current question is still in progress. Continue with it.
        $question = $quba->get_question($slot);
    }
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
    'name' => 'next', 'value' => get_string('nextquestion', 'qpractice')));
$html .= html_writer::empty_tag('input', array('type' => 'submit',
    'name' => 'finish', 'value' => get_string('stoppractice', 'qpractice')));
$html .= html_writer::end_tag('div');
$html .= html_writer::end_tag('form');

echo $html;
// Display the settings form.

echo $OUTPUT->footer();

