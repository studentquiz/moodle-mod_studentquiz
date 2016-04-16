<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/renderer.php');
require_once("$CFG->libdir/formslib.php");

$sessionid = required_param('id', PARAM_INT);
$session = $DB->get_record('studentquiz_practice_session', array('id' => $sessionid));
$cm = get_coursemodule_from_instance('studentquiz', $session->studentquiz_id);
$course = $DB->get_record('course', array('id' => $cm->course));
$studentquiz = $DB->get_record('studentquiz', array('id' => $cm->instance));

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$params = array(
    'objectid' => $cm->id,
    'context' => $context
);
//$event = \mod_studentquiz\event\studentquiz_practice_summary::create($params);
//$event->trigger();

$actionurl = new moodle_url('/mod/studentquiz/attempt.php', array('id' => $sessionid));
$stopurl = new moodle_url('/mod/studentquiz/view.php', array('id' => $cm->id));

if (data_submitted()) {
    if (optional_param('back', null, PARAM_BOOL)) {
        redirect($actionurl);
    } if (optional_param('finish', null, PARAM_BOOL)) {
        $quba = question_engine::load_questions_usage_by_activity($session->question_usage_id);
        $DB->set_field('studentquiz_practice_session', 'status', 'finished', array('id' => $sessionid));
        $slots = $quba->get_slots();
        $slot = end($slots);
        if (!$slot) {
            redirect($stopurl);
        } else {
            $fraction = $quba->get_question_fraction($slot);
            $maxmarks = $quba->get_question_max_mark($slot);
            $obtainedmarks = $fraction * $maxmarks;
            $updatesql = "UPDATE {studentquiz_practice_session}
                          SET marks_obtained = marks_obtained + ?, total_marks = total_marks + ?
                        WHERE id=?";
            $DB->execute($updatesql, array($obtainedmarks, $maxmarks, $sessionid));
            if ($fraction > 0) {
                $updatesql1 = "UPDATE {studentquiz_practice_session}
                          SET total_no_of_questions_right = total_no_of_questions_right + '1'
                        WHERE id=?";
                $DB->execute($updatesql1, array($sessionid));
            }
            $DB->set_field('studentquiz_practice_session', 'status', 'finished', array('id' => $sessionid));
            redirect($stopurl);
        }
    }
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
