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
 * This page is the entry page into the StudentQuiz UI.
 *
 * Displays information about the questions to students and teachers,
 * and lets students to generate new quizzes or add questions.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__DIR__)).'/config.php');
require_once(__DIR__ . '/viewlib.php');
require_once(__DIR__.'/classes/event/studentquiz_questionbank_viewed.php');
require_once(__DIR__.'/reportlib.php');

// Get parameters.
if (!$cmid = optional_param('cmid', 0, PARAM_INT)) {
    $cmid = required_param('id', PARAM_INT);
}

// Load course and course module requested.
if ($cmid) {
    if (!$cm = get_coursemodule_from_id('studentquiz', $cmid)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
} else {
    print_error('invalidcoursemodule');
}

// Authentication check.
require_login($cm->course, false, $cm);

// Load context.
$context = context_module::instance($cm->id);

// Load studentquiz.
$studentquiz = mod_studentquiz_load_studentquiz($cm->id, $context->id);

// Redirect if we have received valid POST data.
if (data_submitted()) {
    if (optional_param('startquiz', null, PARAM_BOOL)) {
        if ($ids = mod_studentquiz_helper_get_ids_by_raw_submit(data_submitted())) {
            if ($attempt = mod_studentquiz_generate_attempt($ids, $studentquiz, $USER->id)) {
                $questionusage = question_engine::load_questions_usage_by_activity($attempt->questionusageid);
                redirect(new moodle_url('/mod/studentquiz/attempt.php',
                    array('cmid' => $cmid, 'id' => $attempt->id, 'slot' => $questionusage->get_first_question_number())));
            }
        }
        // Redirect to overview to clear submit.
        redirect(new moodle_url('view.php', array('id' => $cmid)),
                get_string('no_questions_selected_message', 'studentquiz'),
                null, \core\output\notification::NOTIFY_WARNING);
    }
}

// Load view.
$view = new mod_studentquiz_view($course, $context, $cm, $studentquiz, $USER->id);
$report = new mod_studentquiz_report($cmid);

$PAGE->set_url($view->get_pageurl());
$PAGE->set_title($view->get_title());
$PAGE->set_heading($COURSE->fullname);

// Process actions.
$view->process_actions();

// Fire view event for completion API and event API.
mod_studentquiz_overview_viewed($course, $cm, $context);

$renderer = $PAGE->get_renderer('mod_studentquiz', 'overview');

$regions = $PAGE->blocks->get_regions();
$PAGE->blocks->add_fake_block($renderer->render_stat_block($report), reset($regions));
$regions = $PAGE->blocks->get_regions();
$PAGE->blocks->add_fake_block($renderer->render_ranking_block($report), reset($regions));

echo $OUTPUT->header();
// Render view.
echo $renderer->render_overview($view);

echo $OUTPUT->footer();

