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
 * This page displays the result summary of the current attempt
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/questionlib.php');
require_once(dirname(__FILE__) . '/renderer.php');
require_once(dirname(__FILE__) . '/summarylib.php');
require_once($CFG->libdir . '/formslib.php');


global $PAGE, $USER;

$cmid = required_param('cmid', PARAM_INT);
$attemptid = required_param('id', PARAM_INT);
$attempt = $DB->get_record('studentquiz_attempt', array('id' => $attemptid));
$cm = get_coursemodule_from_instance('studentquiz', $attempt->studentquizid);
$course = $DB->get_record('course', array('id' => $cm->course));
$studentquiz = $DB->get_record('studentquiz', array('id' => $cm->instance));
$userid = $USER->id;

require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$actionurl = new moodle_url('/mod/studentquiz/attempt.php', array('cmid' => $cmid, 'id' => $attemptid, 'slot' => 1));
$stopurl = new moodle_url('/mod/studentquiz/view.php', array('id' => $cm->id));

if (data_submitted()) {
    if (optional_param('back', null, PARAM_BOOL)) {
        redirect($actionurl);
    }
    if (optional_param('finish', null, PARAM_BOOL)) {
        // TODO: Summary and aggregate evaluations of attempt!
        redirect($stopurl);
    }
}
// TODO: Trigger view events!
$view = new mod_studentquiz_summary_view($cm, $studentquiz, $attempt, $userid);

// Set additional values to $view here.

$PAGE->set_title($studentquiz->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);
$PAGE->set_url('/mod/studentquiz/summary.php', array('id' => $attemptid));

$renderer = $PAGE->get_renderer('mod_studentquiz', 'summary');

echo $OUTPUT->header();

echo $renderer->render_summary($view);

echo $OUTPUT->footer();