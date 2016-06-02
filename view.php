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
 * This page is the entry page into the studentquiz UI.
 *
 * Displays information about the questions to students and teachers,
 * and lets students to generate new quizzes or add questions.
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__) . '/viewlib.php');
require_once(dirname(__FILE__).'/classes/event/studentquiz_questionbank_viewed.php');

$cmid = optional_param('id', 0, PARAM_INT);
if (!$cmid) {
    $cmid = required_param('cmid', PARAM_INT);
}

$view = new studentquiz_view($cmid);
require_login($view->get_course(), true, $view->get_coursemodule());

$params = array(
    'objectid' => $view->get_cm_id(),
    'context' => $view->get_context()
);
$event = \mod_studentquiz\event\studentquiz_questionbank_viewed::create($params);
$event->trigger();

if (data_submitted()) {
    if (optional_param('startquiz', null, PARAM_BOOL)) {
        if ($quizmid = $view->generate_quiz_with_selected_ids((array) data_submitted())) {
            redirect(new moodle_url('/mod/quiz/view.php', array('id' => $quizmid)));
        }
    }
    if (optional_param('startfilteredquiz', null, PARAM_RAW)) {
        $ids = required_param('filtered_question_ids', PARAM_RAW);
        if ($quizmid = $view->generate_quiz_with_filtered_ids($ids)) {
            redirect(new moodle_url('/mod/quiz/view.php', array('id' => $quizmid)));
        }
    }
}

$output = $PAGE->get_renderer('mod_studentquiz');

$view->show_questionbank();
$PAGE->set_url($view->get_pageurl());

$PAGE->set_title($view->get_title());
$PAGE->set_heading($COURSE->fullname);

echo $OUTPUT->header();

$output->display_questionbank($view);

echo '<div class="container-fluid" id="page">';
echo $OUTPUT->footer();
