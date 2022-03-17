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
use mod_studentquiz\utils;

use mod_studentquiz\local\studentquiz_question;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/viewlib.php');

// Get parameters.
$cmid = required_param('cmid', PARAM_INT);
$studentquizquestionid = required_param('studentquizquestionid', PARAM_INT);

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

// Load context.
$context = context_module::instance($module->id);

// Check to see if any roles setup has been changed since we last synced the capabilities.
\mod_studentquiz\access\context_override::ensure_permissions_are_right($context);

$studentquiz = mod_studentquiz_load_studentquiz($module->id, $context->id);
$output = $PAGE->get_renderer('mod_studentquiz', 'attempt');
$PAGE->set_pagelayout('popup');
$actionurl = new moodle_url('/mod/studentquiz/preview.php', array('cmid' => $cmid, 'questionid' => $questionid));
$PAGE->set_url($actionurl);

utils::require_access_to_a_relevant_group($module, $context, get_string('studentquiz:preview', 'studentquiz'));
try {
    $studentquiz = mod_studentquiz_load_studentquiz($module->id, $context->id);
    $studentquizquestion = new \mod_studentquiz\local\studentquiz_question($studentquizquestionid,
            null, $studentquiz, $module, $context);
} catch (moodle_exception $e) {
    throw new moodle_exception("invalidconfirmdata', 'error");
}

// Lookup question.
try {
    $question = $studentquizquestion->get_question();
    // A user can view this page if it is his question or he is allowed to view others questions.
    if ($question->createdby != $USER->id) {
        require_capability('mod/studentquiz:previewothers', $context);
    }

    // We have to check if the question is really from this module, limit questions to categories used in this module.
    $allowedcategories = question_categorylist($studentquiz->categoryid);
    if (!in_array($question->category, $allowedcategories)) {
        $question = null;
    }
} catch (dml_missing_record_exception $e) {
    $question = null;
}

// Get and validate existing preview, or start a new one.
$actionurl = new moodle_url('/mod/studentquiz/preview.php', ['cmid' => $cmid, 'studentquizquestionid' => $studentquizquestionid]);
$previewid = optional_param('previewid', 0, PARAM_INT);
$highlight = optional_param('highlight', 0, PARAM_INT);

if ($question) {
    if ($previewid) {
        $params = ['previewid' => $previewid];
        if ($highlight != 0) {
            $params['highlight'] = $highlight;
        }
        $actionurl = new moodle_url($actionurl, $params);
        $quba = question_engine::load_questions_usage_by_activity($previewid);
        $slot = $quba->get_first_question_number();

        // Process submitted data.
        if (data_submitted()) {
            $qa = $quba->get_question_attempt($slot);
            $sequencecheck = $qa->get_submitted_var($qa->get_control_field_name('sequencecheck'), PARAM_INT);
            if ($sequencecheck == $qa->get_sequence_check_count()) {
                $quba->process_all_actions();
            }

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
        $params = ['previewid' => $previewid];
        if ($highlight != 0) {
            $params['highlight'] = $highlight;
        }
        $actionurl = new moodle_url($actionurl, $params);

        redirect($actionurl);
    }

    $options = new question_display_options();
    $options->flags = question_display_options::EDITABLE;

    // Output.
    $title = get_string('previewquestion', 'question', format_string($question->name));
    $headtags = question_engine::initialise_js() . $quba->render_question_head_html($slot);
} else {
    $title = get_string('deletedquestion', 'qtype_missingtype');
}
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->requires->js_call_amd('mod_studentquiz/studentquiz', 'initialise');

echo $OUTPUT->header();
if ($question) {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $actionurl,
        'enctype' => 'multipart/form-data', 'id' => 'responseform']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'cmid', 'value' => $cmid, 'class' => 'cmid_field']);

    echo $quba->render_question($slot, $options, 'i');

    $PAGE->requires->js_module('core_question_engine');
    $PAGE->requires->strings_for_js(array(
        'closepreview',
    ), 'question');
    echo $output->render_state_choice($studentquizquestion);

    echo html_writer::end_tag('form');

    echo $output->render_comment_nav_tabs($studentquizquestion, $USER->id, $highlight, $studentquiz->privatecommenting);
} else {
    echo $OUTPUT->notification(get_string('deletedquestiontext', 'qtype_missingtype'));
}
echo $OUTPUT->footer();
