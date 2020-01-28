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
require_once(__DIR__ . '/locallib.php');

// Get parameters.
$cmid = optional_param('cmid', 0, PARAM_INT);
$questionid = required_param('questionid', PARAM_INT);
$save = required_param('save', PARAM_NOTAGS);

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
require_sesskey();

$data = new \stdClass();
$data->userid = $USER->id;
$data->questionid = $questionid;

switch($save) {
    case 'rate':
        $data->rate = required_param('rate', PARAM_INT);
        mod_studentquiz_save_rate($data);
        break;
}

header('Content-Type: text/html; charset=utf-8');
