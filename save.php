<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Ajax requests to this script saves the ratings and comments.
 *
 * Require POST params:
 * "save" can be "rate" or "comment" (save type),
 * "questionid" is necessary for every request,
 * "rate" is necessary if the save type is "rate"
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
require_login($module->course, false, $module);

$data = new \stdClass();
if (!isset($USER->id) || empty($USER->id)) {
    return;
}
$data->userid = $USER->id;

$data->questionid = $questionid;

$save = required_param('save', PARAM_NOTAGS);
require_sesskey();

switch($save) {
    case 'rate': mod_studentquiz_save_rate($data);
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
function mod_studentquiz_save_rate($data) {
    global $DB, $USER;

    $rate = required_param('rate', PARAM_INT);

    $row = $DB->get_record('studentquiz_rate', array('userid' => $USER->id, 'questionid' => $data->questionid));
    if ($row === false) {
        $data->rate = $rate;
        $DB->insert_record('studentquiz_rate', $data);
    } else {
        $row->rate = $rate;
        $DB->update_record('studentquiz_rate', $row);
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
    // TODO Why manually date instead of moodle's Datetime API?
    $data->created = usertime(time(), usertimezone());

    $DB->insert_record('studentquiz_comment', $data);
    mod_studentquiz_notify_comment_added($data, $course, $module);
}
