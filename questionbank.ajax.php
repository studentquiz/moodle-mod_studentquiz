<?php
/**
 * Ajax script to update the contents of the question bank dialogue. extend mod_quiz questionbankajax
 *
 * @package    mod_studentquiz
 * @copyright  2014 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(dirname(__DIR__)).'/config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/editlib.php');

list($thispageurl, $contexts, $cmid, $cm, $quiz, $pagevars)
    = question_edit_setup('editq', '/mod/studentquiz/view.php', true);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, false, $cm);
require_capability('mod/quiz:manage', $contexts->lowest());

// Create quiz question bank view.
$questionbank = new mod_quiz\question\bank\custom_view($contexts, $thispageurl, $course, $cm, $quiz);
$questionbank->set_quiz_has_attempts(quiz_has_attempts($quiz->id));

// Output.
$output = $PAGE->get_renderer('mod_quiz', 'edit');
$contents = $output->question_bank_contents($questionbank, $pagevars);
echo json_encode(array(
    'status'   => 'OK',
    'contents' => $contents,
));
