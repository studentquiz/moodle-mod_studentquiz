<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/attemptlib.php');
require_once($CFG->libdir . '/filelib.php');

$sessionId = required_param('id', PARAM_INT);
$attempt = studentquiz_practice_attempt::create($sessionId);
$PAGE->set_url($attempt->get_attempturl());

require_login($attempt->get_course(), true, $attempt->get_coursemodule());

if ($attempt->get_user_id() != $USER->id) {
    throw new moodle_studentquiz_practice_exception($attempt, 'notyourattempt');
}
if ($attempt->is_finished()) {
    redirect($attempt->get_summaryurl());
}
$output = $PAGE->get_renderer('mod_studentquiz');

$params = array(
    'objectid' => $attempt->get_cm_id(),
    'context' => $attempt->get_context()
);
$event = \mod_studentquiz\event\studentquiz_practice_attempted::create($params);
$event->trigger();


if (data_submitted()) {
    if (optional_param('next', null, PARAM_BOOL)) {
        $attempt->process_question(required_param('slots', PARAM_INT), $_POST);

        if($attempt->is_last_question()){
            $params = array(
                'objectid' => $attempt->get_cm_id(),
                'context' => $attempt->get_context()
            );
            $event = \mod_studentquiz\event\studentquiz_practice_finished::create($params);
            $event->trigger();
            redirect($attempt->get_summaryurl());
        }
    } else if (optional_param('finish', null, PARAM_BOOL)){
        $attempt->process_finish();

        $params = array(
            'objectid' => $attempt->get_cm_id(),
            'context' => $attempt->get_context()
        );
        $event = \mod_studentquiz\event\studentquiz_practice_finished::create($params);
        $event->trigger();

        redirect($attempt->get_abandonurl());
    } else {
        $attempt->review_question(required_param('slots', PARAM_INT), $_POST);
    }
} else {
    $attempt->process_first_question();

}

$headtags = $attempt->get_html_head_tags();

$PAGE->set_title($attempt->get_title());
$PAGE->set_heading($attempt->get_heading());
$PAGE->set_context($attempt->get_context());

echo $OUTPUT->header();
echo $output->attempt_page($attempt);
echo $OUTPUT->footer();