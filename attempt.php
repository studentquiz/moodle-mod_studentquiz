<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/attemptlib.php');
require_once($CFG->libdir . '/filelib.php');

$sessionId = required_param('id', PARAM_INT);
$attempt = studentquiz_practice_attempt::create($sessionId);
$PAGE->set_url($attempt->getAttemptUrl());

require_login($attempt->getCourse(), true, $attempt->getCourseModule());

if ($attempt->getUserId() != $USER->id) {
    throw new moodle_studentquiz_practice_exception($attempt, 'notyourattempt');
}
if ($attempt->isFinished()) {
    redirect($attempt->getSummaryUrl());
}
$output = $PAGE->get_renderer('mod_studentquiz');

$params = array(
    'objectid' => $attempt->getCMId(),
    'context' => $attempt->getContext()
);
$event = \mod_studentquiz\event\studentquiz_practice_attempted::create($params);
$event->trigger();


if (data_submitted()) {
    if (optional_param('next', null, PARAM_BOOL)) {
        $attempt->processQuestion(required_param('slots', PARAM_INT), $_POST);

        if($attempt->isLastQuestion()){
            $params = array(
                'objectid' => $attempt->getCMId(),
                'context' => $attempt->getContext()
            );
            $event = \mod_studentquiz\event\studentquiz_practice_finished::create($params);
            $event->trigger();
            redirect($attempt->getSummaryUrl());
        }
    } else if (optional_param('finish', null, PARAM_BOOL)){
        $attempt->processFinish();

        $params = array(
            'objectid' => $attempt->getCMId(),
            'context' => $attempt->getContext()
        );
        $event = \mod_studentquiz\event\studentquiz_practice_finished::create($params);
        $event->trigger();

        redirect($attempt->getAbandonUrl());
    } else {
        $attempt->reviewQuestion(required_param('slots', PARAM_INT), $_POST);
        redirect($attempt->getViewUrl());
    }
} else {
    $attempt->processFirstQuestion();

}

$headtags = $attempt->getHTMLHeadTags();

$PAGE->set_title($attempt->getTitle());
$PAGE->set_heading($attempt->getHeading());
$PAGE->set_context($attempt->getContext());

echo $OUTPUT->header();
echo $output->attemptPage($attempt);
echo $OUTPUT->footer();