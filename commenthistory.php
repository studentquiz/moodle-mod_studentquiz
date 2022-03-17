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
 * The mod_studentquiz comment history.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_studentquiz\commentarea\container;
use mod_studentquiz\utils;

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');

// Get parameters.
$cmid = required_param('cmid', PARAM_INT);
$studentquizquestionid = required_param('studentquizquestionid', PARAM_INT);
$commentid = required_param('commentid', PARAM_INT);

// Load course and course module requested.
if ($cmid) {
    $cm = get_coursemodule_from_id('studentquiz', $cmid);
    if (!$cm) {
        throw new moodle_exception("invalidcoursemodule");
    }
    if (!$comment = $DB->get_record('studentquiz_comment', ['id' => $commentid])) {
        throw new moodle_exception("invalidcommentmodule");
    }
} else {
    throw new moodle_exception("invalidcoursemodule");
}

// Authentication check.
require_login($cm->course, false, $cm);

// Load context.
$context = context_module::instance($cm->id);

// Check to see if any roles setup has been changed since we last synced the capabilities.
\mod_studentquiz\access\context_override::ensure_permissions_are_right($context);
$studentquiz = mod_studentquiz_load_studentquiz($cm->id, $context->id);
$studentquizquestion = new \mod_studentquiz\local\studentquiz_question($studentquizquestionid, null, $studentquiz, $cm, $context);

// Comment access check.
if (!$studentquizquestion) {
    throw new moodle_exception("invalidcommenthistorypermission");
}
$container = new container($studentquizquestion, $USER);
if (!$container->can_view_username() && !$USER->id == $comment->userid) {
    throw new moodle_exception("invalidcommenthistorypermission");
}

$actionurl = new moodle_url('/mod/studentquiz/commenthistory.php',
        ['cmid' => $cmid, 'studentquizquestionid' => $studentquizquestionid, 'commentid' => $commentid]);

$renderer = $PAGE->get_renderer('mod_studentquiz', 'comment_history');
$title = get_string('commenthistory', 'mod_studentquiz');
$PAGE->set_pagelayout('popup');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($actionurl);

utils::require_access_to_a_relevant_group($cm, $context);

echo $OUTPUT->header();
echo $renderer->render_comment_history($container, $commentid);
echo $OUTPUT->footer();
