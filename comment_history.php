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
 * View comment histories list.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/viewlib.php');

// Get parameters.
$cmid = required_param('cmid', PARAM_INT);
$questionid = required_param('questionid', PARAM_INT);
$commentid = required_param('commentid', PARAM_INT);

// Load course and course module requested.
if ($cmid) {
    if (!$module = get_coursemodule_from_id('studentquiz', $cmid)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $module->course))) {
        print_error('coursemisconf');
    }
    if (!$comment = $DB->get_record('studentquiz_comment', ['id' => $commentid], 'id')) {
        print_error('invalidcommentmodule');
    }
} else {
    print_error('invalidcoursemodule');
}

// Authentication check.
require_login($module->course, false, $module);

// Load context.
$context = context_module::instance($module->id);
$studentquiz = mod_studentquiz_load_studentquiz($module->id, $context->id);

$actionurl = new moodle_url('/mod/studentquiz/comment_history.php', array('cmid' => $cmid, 'questionid' => $commentid));

$renderer = $PAGE->get_renderer('mod_studentquiz', 'comment_history');
$title = get_string('commenthistory', 'mod_studentquiz');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($actionurl);
$PAGE->navbar->add($title);

echo $OUTPUT->header();
echo $renderer->render_comment_history($questionid, $commentid, $cmid);
echo $OUTPUT->footer();
