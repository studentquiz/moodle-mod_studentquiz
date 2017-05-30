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
 * Internal library of functions for module StudentQuiz
 *
 * All the StudentQuiz specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

/** @var string default quiz behaviour */
const STUDENTQUIZ_BEHAVIOUR = 'studentquiz';
/** @var int default course section id for the orphaned activities */
const STUDENTQUIZ_COURSE_SECTION_ID = 999;
/** @var string generated student quiz placeholder */
const STUDENTQUIZ_GENERATE_QUIZ_PLACEHOLDER = 'quiz';
/** @var string generated student quiz intro */
const STUDENTQUIZ_GENERATE_QUIZ_INTRO = 'Studentquiz';
/** @var string generated student quiz overduehandling */
const STUDENTQUIZ_GENERATE_QUIZ_OVERDUEHANDLING = 'autosubmit';
/** @var string default course section name for the orphaned activities */
const STUDENTQUIZ_COURSE_SECTION_NAME = 'studentquiz quizzes';
/** @var string default course section summary for the orphaned activities */
const STUDENTQUIZ_COURSE_SECTION_SUMMARY = 'all student quizzes';
/** @var string default course section summaryformat for the orphaned activities */
const STUDENTQUIZ_COURSE_SECTION_SUMMARYFORMAT = 1;
/** @var string default course section visible for the orphaned activities */
const STUDENTQUIZ_COURSE_SECTION_VISIBLE = false;
/** @var string default StudentQuiz quiz practice behaviour */
const STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR = 'immediatefeedback';

/**
 * Checks whether the StudentQuiz behaviour exists
 *
 * @return bool
 */
function mod_studentquiz_has_behaviour() {
    $archetypalbehaviours = question_engine::get_archetypal_behaviours();

    return array_key_exists(STUDENTQUIZ_BEHAVIOUR, $archetypalbehaviours);
}

/**
 * Returns behaviour option from the course module with fallback
 *
 * @param  stdClass $cm
 * @return string quiz behaviour
 */
function mod_studentquiz_get_current_behaviour($cm=null) {
    global $DB;

    $default = STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR;
    $archetypalbehaviours = question_engine::get_archetypal_behaviours();

    if (array_key_exists(STUDENTQUIZ_BEHAVIOUR, $archetypalbehaviours)) {
        $default = STUDENTQUIZ_BEHAVIOUR;
    }

    if (isset($cm)) {
        $rec = $DB->get_record('studentquiz', array('id' => $cm->instance), 'quizpracticebehaviour');

        if (!$rec) {
            return $default;
        }

        return $rec->quizpracticebehaviour;
    } else {
        return $default;
    }
}

/**
 * Returns quiz module id
 * @return int
 */
function mod_studentquiz_get_quiz_module_id() {
    global $DB;
    return $DB->get_field('modules', 'id', array('name' => 'quiz'));
}

/**
 * Check if user has permission to see creator
 * @return bool
 */
function mod_studentquiz_check_created_permission($cmid) {
    global $USER;

    $admins = get_admins();
    foreach ($admins as $admin) {
        if ($USER->id == $admin->id) {
            return true;
        }
    }

    $context = context_module::instance($cmid);

    return has_capability('moodle/question:editall', $context);
}

/**
 * Checks if activity is anonym or not
 * @param  int  $cmid course module id
 * @return boolean
 */
function mod_studentquiz_is_anonym($cmid) {
    global $DB;

    if (mod_studentquiz_check_created_permission($cmid)) {
        return 0;
    }

    $field = $DB->get_field('studentquiz', 'anonymrank', array('coursemodule' => $cmid));
    if ($field !== false) {
        return intval($field);
    }
    // If no entry was found, better set it anonym.
    return 1;
}

/**
 * Notify student if a teacher makes changes to a student's question.
 * @param int $questionid ID of the student's questions.
 * @param \context $context Category context for this view.
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_change($questionid, $course, $module) {
    global $DB, $USER, $CFG;

    // Requires the right permission.
    if (question_has_capability_on($questionid, 'editall')) {
        $question = $DB->get_record('question', array('id' => $questionid), 'name, timemodified, createdby, modifiedby');
        $lesteditthreshold = 5;

        // Creator and modifier must be different and don't send when refreshing the page.
        if ($question->createdby != $question->modifiedby
            && $question->createdby != $USER->id
            && $question->modifiedby == $USER->id
            && $question->timemodified + $lesteditthreshold >= time()) {

            // Prepare message.
            $student = $DB->get_record('user', array('id' => $question->createdby), '*', MUST_EXIST);
            $teacher = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
            $time = new DateTime("now", core_date::get_user_timezone_object());

            $data = new stdClass();
            // Course info.
            $data->coursename      = $course->fullname;
            $data->courseshortname = $course->shortname;
            // Module info.
            $data->modulename      = $module->name;
            // Question info.
            $data->questionname    = $question->name;
            $data->questionurl     = $CFG->wwwroot . '/question/question.php?cmid=' . $course->id . '&id=' . $questionid;
            $data->questiontime    = userdate($time->getTimestamp(), get_string('strftimedatetime', 'langconfig'));
            // Student who created the question.
            $data->studentidnumber = $student->idnumber;
            $data->studentname     = fullname($student);
            $data->studentusername = $student->username;
            // Teacher who edited the question.
            $data->teachername     = fullname($teacher);
            $data->teacherusername = $teacher->username;

            $subject = get_string('emailchangesubject', 'studentquiz', $data);
            $fulltext = get_string('emailchangebody', 'studentquiz', $data);
            $smalltext = get_string('emailchangesmall', 'studentquiz', $data);

            return mod_studentquiz_send_notification('change', $student, $teacher, $subject, $fulltext, $smalltext, $data);
        }
    }

    return false;
}

/**
 * Notify student if a teacher approves or disapproves a student's question.
 * @param int $questionid ID of the student's questions.
 * @param \context $context Category context for this view.
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_approving($questionid, $course, $module) {
    global $DB, $USER, $CFG;

    // Requires the right permission.
    if (question_has_capability_on($questionid, 'editall')) {
        $question = $DB->get_record('question', array('id' => $questionid), 'name, timemodified, createdby, modifiedby');
        $approved = $DB->get_field('studentquiz_question', 'approved', array('questionid' => $questionid));

        // Prepare message.
        $student = $DB->get_record('user', array('id' => $question->createdby), '*', MUST_EXIST);
        $teacher = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
        $time = new DateTime("now", core_date::get_user_timezone_object());

        $data = new stdClass();
        // Course info.
        $data->coursename      = $course->fullname;
        $data->courseshortname = $course->shortname;
        // Module info.
        $data->modulename      = $module->name;
        // Question info.
        $data->questionname    = $question->name;
        $data->questionurl     = $CFG->wwwroot . '/question/question.php?cmid=' . $course->id . '&id=' . $questionid;
        $data->questiontime    = userdate($time->getTimestamp(), get_string('strftimedatetime', 'langconfig'));
        // Student who created the question.
        $data->studentidnumber = $student->idnumber;
        $data->studentname     = fullname($student);
        $data->studentusername = $student->username;
        // Teacher who edited the question.
        $data->teachername     = fullname($teacher);
        $data->teacherusername = $teacher->username;

        if ($approved) {
            $subject = get_string('emailapprovedsubject', 'studentquiz', $data);
            $fulltext = get_string('emailapprovedbody', 'studentquiz', $data);
            $smalltext = get_string('emailapprovedsmall', 'studentquiz', $data);
            return mod_studentquiz_send_notification('approved', $student, $teacher, $subject, $fulltext, $smalltext, $data);
        }

        $subject = get_string('emailunapprovedsubject', 'studentquiz', $data);
        $fulltext = get_string('emailunapprovedbody', 'studentquiz', $data);
        $smalltext = get_string('emailunapprovedsmall', 'studentquiz', $data);
        return mod_studentquiz_send_notification('unapproved', $student, $teacher, $subject, $fulltext, $smalltext, $data);
    }

    return false;
}

/**
 * Sends notification messages to the interested parties that assign the role capability
 *
 * @param string $event message event string
 * @param object $recipient user object of the intended recipient
 * @param object $submitter user object of the sender
 * @param string $subject subject of the message
 * @param string $fullmessage Full message text
 * @param string $smallemessage Small message text
 * @param object $data associative array of replaceable fields for the templates
 *
 * @return int|false as for {@link message_send()}.
 */
function mod_studentquiz_send_notification($event, $recipient, $submitter, $subject, $fullmessage, $smallmessage, $data) {
    // Recipient info for template.
    $data->useridnumber = $recipient->idnumber;
    $data->username     = fullname($recipient);
    $data->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new stdClass();
    $eventdata->component         = 'mod_studentquiz';
    $eventdata->name              = $event;
    $eventdata->notification      = true;

    $eventdata->userfrom          = $submitter;
    $eventdata->userto            = $recipient;
    $eventdata->subject           = $subject;
    $eventdata->fullmessage       = $fullmessage;
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->smallmessage      = $smallmessage;
    $eventdata->contexturl        = $data->questionurl;
    $eventdata->contexturlname    = $data->questionname;

    // ... and send it.
    return message_send($eventdata);
}
