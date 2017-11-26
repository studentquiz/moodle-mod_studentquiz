<?php
/**
 * Ajax requests to this script saves the ratings and comments.
 *
 * Require POST params:
 * "save" can be "vote" or "comment" (save type),
 * "questionid" is necessary for every request,
 * "rate" is necessary if the save type is "vote"
 * "text" is necessary if the save type is "comment"
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

// Get parameters.
$cmid = optional_param('cmid', 0, PARAM_INT);
$questionid = required_param('questionid', PARAM_INT);

// Load course and course module requested.
if ($cmid) {
    if (!$module = get_coursemodule_from_id('studentquiz', $cmid)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $module->course))) {
        print_error('coursemisconf');
    }
} else {
    print_error('invalidcoursemodule');
}

// Authentication check.
// TODO: Do we want to allow guests to use StudentQuiz at all?
require_login($module->course, true, $module);

$data = new \stdClass();
if (!isset($USER->id) || empty($USER->id)) {
    return;
}
$data->userid = $USER->id;

$data->questionid = $questionid;

// TODO: Missing verification!
// Question is part of this StudentQuiz Activity.
// StudentQuiz activity is part of this Course.
// User is enrolled in tghis course.

$save = required_param('save', PARAM_NOTAGS);
require_sesskey();

switch($save) {
    case 'vote': mod_studentquiz_save_vote($data);
        break;
    case 'comment': mod_studentquiz_save_comment($data, $course, $module);
        break;
}

header('Content-Type: text/html; charset=utf-8');

/**
 * Saves question rating
 *
 * // TODO:
 * @param  stdClass $data requires userid, questionid
 * @internal param $course
 * @internal param $module
 */
function mod_studentquiz_save_vote($data) {
    global $DB, $USER;

    $data->vote = required_param('rate', PARAM_INT);

    $row = $DB->get_record('studentquiz_vote', array('userid' => $USER->id, 'questionid' => $data->questionid));
    if ($row === false) {
        $DB->insert_record('studentquiz_vote', $data);
    } else {
        $row->vote = $data->vote;
        $DB->update_record('studentquiz_vote', $row);
    }
}

/**
 * Saves question comment
 *
 * // TODO:
 * @param  stdClass $data requires userid, questionid
 * @param $course
 * @param $module
 */
function mod_studentquiz_save_comment($data, $course, $module) {
    global $DB;

    $text = required_param('text', PARAM_TEXT);

    $data->comment = $text;
    //TODO Why manually date instead of moodle's Datetime API?
    $data->created = usertime(time(), usertimezone());

    $DB->insert_record('studentquiz_comment', $data);
    mod_studentquiz_notify_comment_added($data, $course, $module);
}
