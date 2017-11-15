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
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot. '/course/lib.php');

/** @var string default quiz behaviour */
const STUDENTQUIZ_BEHAVIOUR = 'studentquiz';
/** @var int legacy course section id for the orphaned activities, only used for import fixes */
const STUDENTQUIZ_OLD_ORPHANED_SECTION_NUMBER = 999;
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

        if (array_key_exists($rec->quizpracticebehaviour, $archetypalbehaviours)) {
            return $rec->quizpracticebehaviour;
        }
    }

    return $default;
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
        return (int)$field;
    }
    // If no entry was found, better set it anonym.
    return 1;
}

/**
 * Prepare message for notify.
 * @param stdClass $question object
 * @param stdClass $recepient user object receiving the notification
 * @param stdClass $actor user object triggering the notification
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return stdClass Data object with course, module, question, student and teacher info
 */

function mod_studentquiz_prepare_notify_data($question, $recepient, $actor, $course, $module) {
    global $CFG;

    // Prepare message.
    $time = new DateTime('now', core_date::get_user_timezone_object());

    $data = new stdClass();

    // Course info.
    $data->coursename      = $course->fullname;
    $data->courseshortname = $course->shortname;

    // Module info.
    $data->modulename      = $module->name;

    // Question info.
    $data->questionname    = $question->name;
    $data->questionurl     = $CFG->wwwroot . '/question/question.php?cmid=' . $course->id . '&id=' . $question->id;

    // Notification timestamp.
    // TODO: Note: userdate will format for the actor, not for the recepient.
    $data->timestamp    = userdate($time->getTimestamp(), get_string('strftimedatetime', 'langconfig'));

    // Recepient who receives the notification
    $data->recepientidnumber = $recepient->idnumber;
    $data->recepientname     = fullname($recepient);
    $data->recepientusername = $recepient->username;

    // User who triggered the noticication
    $data->actorname     = fullname($actor);
    $data->actorusername = $recepient->username;
    return $data;
}

/**
 * Notify student if a teacher makes changes to a student's question.
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_change($questionid, $course, $module) {
    global $DB, $USER;

    // Requires the right permission.
    if (question_has_capability_on($questionid, 'editall')) {
        $question = $DB->get_record('question', array('id' => $questionid), 'name, timemodified, createdby, modifiedby');
        $lesteditthreshold = 5;

        // Creator and modifier must be different and don't send when refreshing the page.
        if ($question->createdby != $question->modifiedby
            && $question->createdby != $USER->id
            && $question->modifiedby == $USER->id
            && $question->timemodified + $lesteditthreshold >= time()) {

            $recepient = $DB->get_record('user', array('id' => $question->createdby), '*', MUST_EXIST);
            $actor = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
            $data = mod_studentquiz_prepare_notify_data($question, $recepient, $actor, $course, $module);

            $subject = get_string('emailchangesubject', 'studentquiz', $data);
            $fulltext = get_string('emailchangebody', 'studentquiz', $data);
            $smalltext = get_string('emailchangesmall', 'studentquiz', $data);

            return mod_studentquiz_send_notification('change', $recepient, $actor, $subject, $fulltext, $smalltext, $data);
        }
    }

    return false;
}

/**
 * Notify author of a question if anyone commented on it.
 * @param stdClass comment that was just added to the question
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_comment($comment, $questionid, $course, $module) {
    global $DB, $USER;

    // Requires the right permission.
    if (question_has_capability_on($questionid, 'editall')) {
        $question = $DB->get_record('question', array('id' => $questionid), 'name, timemodified, createdby, modifiedby');
        $lesteditthreshold = 5;

        // Creator and modifier must be different and don't send when refreshing the page.
        if ($comment->userid != $question->createdby
            && $comment->userid == $USER->id
            && $question->createdby != $USER->id
            && $question->timemodified + $lesteditthreshold >= time()) {

            $recepient = $DB->get_record('user', array('id' => $question->createdby), '*', MUST_EXIST);
            $actor = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
            $data = mod_studentquiz_prepare_notify_data($question, $recepient, $actor, $course, $module);
            $data->comment = $comment;

            $subject = get_string('emailcommentedsubject', 'studentquiz', $data);
            $fulltext = get_string('emailcommentedbody', 'studentquiz', $data);
            $smalltext = get_string('emailcommentedsmall', 'studentquiz', $data);

            return mod_studentquiz_send_notification('commented', $recepient, $actor, $subject, $fulltext, $smalltext, $data);
        }
    }

    return false;
}

/**
 * Notify author of a question about its deletion.
 * @param int $questionid ID of the author's question.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_question_deleted($questionid, $course, $module) {
    global $DB, $USER;

    // Requires the right permission.
    if (question_has_capability_on($questionid, 'editall')) {
        $question = $DB->get_record('question', array('id' => $questionid), 'name, timemodified, createdby, modifiedby');

        // Creator and deletor must be different.
        if ($question->createdby != $USER->id) {

            $recepient = $DB->get_record('user', array('id' => $question->createdby), '*', MUST_EXIST);
            $actor = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
            $data = mod_studentquiz_prepare_notify_data($question, $recepient, $actor, $course, $module);

            $subject = get_string('emailquestiondeletedsubject', 'studentquiz', $data);
            $fulltext = get_string('emailquestiondeletedbody', 'studentquiz', $data);
            $smalltext = get_string('emailquestiondeletedsmall', 'studentquiz', $data);

            return mod_studentquiz_send_notification('commented', $recepient, $actor, $subject, $fulltext, $smalltext, $data);
        }
    }

    return false;
}

/**
 * Notify student if a teacher approves or disapproves a student's question.
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_approving($questionid, $course, $module) {
    global $DB, $USER;

    // Requires the right permission.
    if (question_has_capability_on($questionid, 'editall')) {
        $question = $DB->get_record('question', array('id' => $questionid), 'name, timemodified, createdby, modifiedby');
        $approved = $DB->get_field('studentquiz_question', 'approved', array('questionid' => $questionid));

        $recepient = $DB->get_record('user', array('id' => $question->createdby), '*', MUST_EXIST);
        $actor = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
        $data = mod_studentquiz_prepare_notify_data($question, $recepient, $actor, $course, $module);

        if ($approved) {
            $subject = get_string('emailapprovedsubject', 'studentquiz', $data);
            $fulltext = get_string('emailapprovedbody', 'studentquiz', $data);
            $smalltext = get_string('emailapprovedsmall', 'studentquiz', $data);
            return mod_studentquiz_send_notification('updated', $recepient, $actor, $subject, $fulltext, $smalltext, $data);
        }

        $subject = get_string('emailunapprovedsubject', 'studentquiz', $data);
        $fulltext = get_string('emailunapprovedbody', 'studentquiz', $data);
        $smalltext = get_string('emailunapprovedsmall', 'studentquiz', $data);
        return mod_studentquiz_send_notification('updated', $recepient, $actor, $subject, $fulltext, $smalltext, $data);
    }

    return false;
}

/**
 * Sends notification messages to the interested parties that assign the role capability
 *
 * @param string $event message event string
 * @param stdClass $recipient user object of the intended recipient
 * @param stdClass $submitter user object of the sender
 * @param string $subject subject of the message
 * @param string $fullmessage Full message text
 * @param string $smallmessage Small message text
 * @param stdClass $data object of replaceable fields for the templates
 *
 * @return int|false as for {@link message_send()}.
 */
function mod_studentquiz_send_notification($event, $recipient, $submitter, $subject, $fullmessage, $smallmessage, $data) {
    // Recipient info for template.
    $data->useridnumber = $recipient->idnumber;
    $data->username     = fullname($recipient);
    $data->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->component         = 'mod_studentquiz';
    $eventdata->name              = $event;
    $eventdata->notification      = 1;

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

/**
 * Returns an array of course module ids of quiz instances generated by the
 * StudentQuiz Activity with id $studentquizid
 * @param $studentquizid
 * @return array
 * @deprecated
 */
function mod_studentquiz_get_quiz_cmids($studentquizid) {
    global $DB;
    $result = $DB->get_records(
        'studentquiz_practice',
        array('studentquizcoursemodule' => $studentquizid),
        null, 'id,quizcoursemodule');
    $cmids = array();
    foreach ($result as $k => $v) {
        $cmids[$k] = intval($v->quizcoursemodule);
    }
    return $cmids;
}

/**
 * Creates a new default category for StudentQuiz
 * @param stdClass $contexts The context objects for this context and all parent contexts.
 * @param string $name Append the name of the module if the context hasn't it yet.
 * @return stdClass The default category - the category in the course context
 */
function mod_studentquiz_add_default_question_category($context, $name='') {
    global $DB;

    $questioncategory = question_make_default_categories(array($context));
    if ($name !== '') {
        $questioncategory->name .= $name;
    }
    $questioncategory->parent = -1;
    $DB->update_record('question_categories', $questioncategory);
    return $questioncategory;
}
