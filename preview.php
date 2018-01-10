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
 * This page lets a user preview a question including comments.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/viewlib.php');

// Get parameters.
$cmid = required_param('cmid', PARAM_INT);
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

// Load context.
$context = context_module::instance($module->id);
$studentquiz = mod_studentquiz_load_studentquiz($module->id, $context->id);

// Lookup question.
try {
    $question = question_bank::load_question($questionid);
    // There is no capability check on previewothers, because he can gotten the link for review by notification.
    // If this should be limited also here, you need to implement some sort of onetime token for the link in the notification.

    // But we have to check if the question is really from this module, limit questions to categories used in this module.
    $allowedcategories = question_categorylist($studentquiz->categoryid);
    if (!in_array($question->category, $allowedcategories)) {
        $question = null;
    }
} catch (dml_missing_record_exception $e) {
    $question = null;
}

// Get and validate existing preview, or start a new one.
$actionurl = new moodle_url('/mod/studentquiz/preview.php', array('cmid' => $cmid, 'questionid' => $questionid));
$previewid = optional_param('previewid', 0, PARAM_INT);

if ($question) {
    if ($previewid) {
        $actionurl = new moodle_url($actionurl, array('previewid' => $previewid));
        $quba = question_engine::load_questions_usage_by_activity($previewid);
        $slot = $quba->get_first_question_number();

        // Process submitted data.
        if (data_submitted()) {
            $quba->process_all_actions();

            $transaction = $DB->start_delegated_transaction();
            question_engine::save_questions_usage_by_activity($quba);
            $transaction->allow_commit();

            redirect($actionurl);
        }
    } else {
        // Prepare Question for preview.
        // Keep core_question_preview so core question module cares about cleaning them up.
        $quba = question_engine::make_questions_usage_by_activity(
            'core_question_preview', context_user::instance($USER->id));
        $quba->set_preferred_behaviour(STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR);
        $slot = $quba->add_question($question);
        $quba->start_question($slot);
        $transaction = $DB->start_delegated_transaction();
        question_engine::save_questions_usage_by_activity($quba);
        $transaction->allow_commit();

        $previewid = $quba->get_id();
        $actionurl = new moodle_url($actionurl, array('previewid' => $previewid));

        redirect($actionurl);
    }

    $options = new question_display_options();

    // Output.
    $title = get_string('previewquestion', 'question', format_string($question->name));
    $headtags = question_engine::initialise_js() . $quba->render_question_head_html($slot);
} else {
    $title = get_string('deletedquestion', 'qtype_missingtype');
}
$output = $PAGE->get_renderer('mod_studentquiz', 'attempt');
$PAGE->set_pagelayout('popup');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($actionurl);
$PAGE->requires->js_call_amd('mod_studentquiz/studentquiz', 'initialise');

echo $OUTPUT->header();
if ($question) {
    echo html_writer::start_tag('form', array('method' => 'post', 'action' => $actionurl,
        'enctype' => 'multipart/form-data', 'id' => 'responseform'));
    echo '<input type="hidden" class="cmid_field" name="cmid" value="' . $cmid . '" />';

    echo $quba->render_question($slot, $options, 'i');

    $PAGE->requires->js_module('core_question_engine');
    $PAGE->requires->strings_for_js(array(
        'closepreview',
    ), 'question');

    // StudentQuiz renderers.
    $comments = mod_studentquiz_get_comments_with_creators($question->id);

    $anonymize = $studentquiz->anonymrank;
    if (has_capability('mod/studentquiz:unhideanonymous', $context)) {
        $anonymize = false;
    }
    $ismoderator = false;
    if (mod_studentquiz_check_created_permission($cmid)) {
        $ismoderator = true;
    }

    echo $output->feedback($question, $options, $cmid, $comments, $USER->id, $anonymize, $ismoderator);

    echo html_writer::end_tag('form');
} else {
    echo $OUTPUT->notification(get_string('deletedquestiontext', 'qtype_missingtype'));
}
echo $OUTPUT->footer();