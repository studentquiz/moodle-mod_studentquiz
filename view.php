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
use mod_studentquiz\utils;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/viewlib.php');
require_once(__DIR__ . '/reportlib.php');

// Get parameters.
if (!$cmid = optional_param('cmid', 0, PARAM_INT)) {
    $cmid = required_param('id', PARAM_INT);
    // Some internal moodle functions (e.g. question_edit_setup()) require the cmid to be found in $_xxx['cmid'],
    // but moodle allows to view a mod page with parameter id in place of cmid.
    $_GET['cmid'] = $cmid;
}

// TODO: make course-, context- and login-check in a better starting class (not magically hidden in "report").
// And when doing that, offer course, context and studentquiz object over it, which all following actions can use.
$report = new mod_studentquiz_report($cmid);
require_login($report->get_course(), false, $report->get_coursemodule());

$course = $report->get_course();
$context = $report->get_context();
$cm = $report->get_coursemodule();

$studentquiz = mod_studentquiz_load_studentquiz($cmid, $context->id);

// If for some weired reason a studentquiz is not aggregated yet, now would be a moment to do so.
if (!$studentquiz->aggregated) {
    mod_studentquiz_migrate_single_studentquiz_instances_to_aggregated_state($studentquiz);
}
// Load view.
$view = new mod_studentquiz_view($course, $context, $cm, $studentquiz, $USER->id, $report);
$baseurl = $view->get_questionbank()->base_url();

// Redirect if we have received valid data.
// Usually we should use submitted_data(), but since we have two forms merged and exchanging their values
// using GET params, we can't use that.
if (!empty($_GET)) {
    if (optional_param('startquiz', null, PARAM_BOOL)) {
        require_sesskey();
        if ($ids = mod_studentquiz_helper_get_ids_by_raw_submit(fix_utf8($_GET))) {
            if ($attempt = mod_studentquiz_generate_attempt($ids, $studentquiz, $USER->id)) {
                $questionusage = question_engine::load_questions_usage_by_activity($attempt->questionusageid);
                $baseurl->remove_params('startquiz');
                foreach ($ids as $id) {
                    $baseurl->remove_params('q' . $id);
                }
                redirect(new moodle_url('/mod/studentquiz/attempt.php',
                    ['cmid' => $cmid, 'id' => $attempt->id, 'slot' => $questionusage->get_first_question_number(),
                        'returnurl' => $baseurl->out_as_local_url(false)]));
            }
        }
        // Redirect to overview to clear submit.
        redirect(new moodle_url('view.php', array('id' => $cmid)),
                get_string('no_questions_selected_message', 'studentquiz'),
                null, \core\output\notification::NOTIFY_WARNING);
    }
    if (!optional_param('qperpage', utils::DEFAULT_QUESTIONS_PER_PAGE, PARAM_INT)) {
        // Invalid page size param in the URL.
        redirect(new \moodle_url('view.php', ['id' => $cmid]),
            get_string('pagesize_invalid_input', 'studentquiz'),
            null, \core\output\notification::NOTIFY_ERROR);
    }
}

$renderer = $PAGE->get_renderer('mod_studentquiz', 'overview');

// Redirect to overview if there are no selected questions.
if ((optional_param('approveselected', false, PARAM_BOOL) || optional_param('deleteselected', false, PARAM_BOOL)) &&
        !optional_param('confirm', '', PARAM_ALPHANUM) ||
        optional_param('move', false, PARAM_BOOL)) {
    if (!mod_studentquiz_helper_get_ids_by_raw_submit($_REQUEST)) {
        $baseurl->remove_params('deleteselected', 'approveselected', 'move');
        redirect($baseurl, get_string('noquestionsselectedtodoaction', 'studentquiz'),
            null, \core\output\notification::NOTIFY_WARNING);
    }
    if (!has_capability('mod/studentquiz:changestate', $PAGE->context) && optional_param('approveselected', false, PARAM_BOOL)) {
        redirect(new moodle_url('view.php', array('id' => $cmid)), get_string('nopermissions', 'error',
            get_string('studentquiz:changestate', 'studentquiz')), null, \core\output\notification::NOTIFY_WARNING);
    }
}

// Since this page has 2 forms interacting with each other, all params must be passed in GET, thus
// $PAGE->url will be as it has recieved the request.
$PAGE->set_url($view->get_pageurl());
$PAGE->set_title($view->get_title());
$PAGE->set_heading($COURSE->fullname);
$PAGE->set_cm($cm, $course);

utils::require_access_to_a_relevant_group($cm, $context);

// Trigger completion.
mod_studentquiz_completion($course, $cm);

$renderer->add_fake_block($report);

echo $OUTPUT->header();
// Render view.
echo $renderer->render_overview($view);

$PAGE->requires->js_init_code($renderer->render_bar_javascript_snippet(), true);
$PAGE->requires->js_call_amd('mod_studentquiz/studentquiz', 'setFocus');
$PAGE->requires->js_call_amd('mod_studentquiz/studentquiz', 'selectAllQuestions');
$PAGE->requires->js_call_amd('mod_studentquiz/toggle_filter_checkbox', 'init');

echo $OUTPUT->footer();

// Trigger overview viewed event.
mod_studentquiz_overview_viewed($cm->id, $context);
