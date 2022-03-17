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
 * Single action: Pin/Unpin question in SQ
 *
 * Require POST params:
 * "studentquizquestionid" is necessary for every request,
 * "courseid" is necessary for every request,
 * "sesskey" is necessary for every request
 * "cmid" is necessary for every request,
 * "pin" is necessary
 *
 * @package mod_studentquiz
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

// Get parameters.
$studentquizquestionid = required_param('studentquizquestionid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_LOCALURL);
$pin = required_param('pin', PARAM_INT);

// Load course and course module requested.
if ($cmid) {
    if (!$module = get_coursemodule_from_id('studentquiz', $cmid)) {
        throw new moodle_exception("invalidcoursemodule");
    }
    if (!$course = $DB->get_record('course', array('id' => $module->course))) {
        throw new moodle_exception("coursemisconf");
    }
} else {
    throw new moodle_exception("invalidcoursemodule");
}

// Authentication check.
require_login($module->course, false, $module);
require_sesskey();

$studentquizquestion = mod_studentquiz_init_single_action_page($module, $studentquizquestionid);
$pinnotification = $pin ? 'pin' : 'unpin';
$DB->set_field('studentquiz_question', 'pinned', $pin, ['id' => $studentquizquestionid]);
mod_studentquiz_state_notify($studentquizquestion, $course, $module, $pinnotification);
redirect($returnurl);
