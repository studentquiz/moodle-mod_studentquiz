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
 * Load studentquiz from coursemodule id
 *
 * @param int cmid course module id
 * @param int context id id of the context of this course module
 * @return stdClass|bool studentquiz or false
 * TODO: Should we refactor dependency on questionlib by inserting category as parameter?
 */
function mod_studentquiz_load_studentquiz($cmid, $contextid) {
    global $DB;
    if ($studentquiz = $DB->get_record('studentquiz', array('coursemodule' => $cmid))) {
        if ($studentquiz->category = question_get_default_category($contextid)) {
            $studentquiz->categoryid = $studentquiz->category->id;
            return $studentquiz;
        }
    }
    return false;
}

/**
 * Flip a question's approval status.
 * TODO: Ensure question is part of a studentquiz context.
 * @param int questionid index number of question
 */
function mod_studentquiz_flip_approved($questionid) {
    global $DB;

    $approved = $DB->get_field('studentquiz_question', 'approved', array('questionid' => $questionid));

    // TODO: Handle record not found!
    $DB->set_field('studentquiz_question', 'approved', !$approved, array('questionid' => $questionid));
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
 * TODO: Define studentquiz capabilities and check against those only!
 */
function mod_studentquiz_check_created_permission($cmid) {
    global $USER;

    $context = context_module::instance($cmid);
    return has_capability('moodle/question:editall', $context);
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
    $data->courseid        = $course->id;
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
function mod_studentquiz_notify_changed($questionid, $course, $module) {
    global $DB, $USER;

    // Requires the right permission.
    if (question_has_capability_on($questionid, 'editall')) {
        $question = $DB->get_record('question', array('id' => $questionid), 'id, name, timemodified, createdby, modifiedby');
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

            return mod_studentquiz_send_notification('changed', $recepient, $actor, $subject, $fulltext, $smalltext, $data);
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
        $question = $DB->get_record('question', array('id' => $questionid), 'id, name, timemodified, createdby, modifiedby');
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
        $question = $DB->get_record('question', array('id' => $questionid), 'id, name, timemodified, createdby, modifiedby');

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
        $question = $DB->get_record('question', array('id' => $questionid), 'id, name, timemodified, createdby, modifiedby');
        $approved = $DB->get_field('studentquiz_question', 'approved', array('questionid' => $questionid));

        $recepient = $DB->get_record('user', array('id' => $question->createdby), '*', MUST_EXIST);
        $actor = $DB->get_record('user', array('id' => $USER->id), '*', MUST_EXIST);
        $data = mod_studentquiz_prepare_notify_data($question, $recepient, $actor, $course, $module);

        if ($approved) {
            $subject = get_string('emailapprovedsubject', 'studentquiz', $data);
            $fulltext = get_string('emailapprovedbody', 'studentquiz', $data);
            $smalltext = get_string('emailapprovedsmall', 'studentquiz', $data);
            return mod_studentquiz_send_notification('changed', $recepient, $actor, $subject, $fulltext, $smalltext, $data);
        }

        $subject = get_string('emailunapprovedsubject', 'studentquiz', $data);
        $fulltext = get_string('emailunapprovedbody', 'studentquiz', $data);
        $smalltext = get_string('emailunapprovedsmall', 'studentquiz', $data);
        return mod_studentquiz_send_notification('changed', $recepient, $actor, $subject, $fulltext, $smalltext, $data);
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
    $eventdata->courseid          = $data->courseid;

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

/**
 * Generate an attempt with question usage
 * @param array $ids of question ids to be used in this attempt
 * @param stdClass $studentquiz generating this attempt
 * @param userid attempting this StudentQuiz
 * @return stdClass attempt from generate quiz or false on error
 * TODO: Remove dependency on persistence from factory!
 */
function mod_studentquiz_generate_attempt($ids, $studentquiz, $userid) {

    global $DB;

    // Load context of studentquiz activity.
    // TODO: use: this->get_context()?
    $context = context_module::instance($studentquiz->coursemodule);

    $questionusage = question_engine::make_questions_usage_by_activity('mod_studentquiz', $context);

    $attempt = new stdClass();

    // Add further attempt default values here.
    // TODO: Check if get category id always points to lowest context level category of our studentquiz activity.
    $attempt->categoryid = $studentquiz->categoryid;
    $attempt->userid = $userid;

    // TODO: Configurable on Activity Level.
    $questionusage->set_preferred_behaviour(STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR);
    // TODO: Check if this is instance id from studentquiz table.
    $attempt->studentquizid = $studentquiz->id;

    // Add questions to usage
    $usageorder = array();
    foreach ($ids as $i => $questionid) {
        $questiondata = question_bank::load_question($questionid);
        $usageorder[$i] = $questionusage->add_question($questiondata);
    }

    // Persistence.
    // TODO: Is it necessary to start all questions here, or just the current one?
    $questionusage->start_all_questions();

    question_engine::save_questions_usage_by_activity($questionusage);

    $attempt->questionusageid = $questionusage->get_id();

    $attempt->id = $DB->insert_record('studentquiz_attempt', $attempt);

    return $attempt;
}


/**
 * Trigger Report viewed Event
 */
function mod_studentquiz_report_viewed($cmid, $context)
{
    // TODO: How about $cmid from $context?
    $params = array(
        'objectid' => $cmid,
        'context' => $context
    );

    $event = \mod_studentquiz\event\studentquiz_report_quiz_viewed::create($params);
    $event->trigger();
}

/**
 * Trigger Completion api and view Event
 *
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 */
function mod_studentquiz_overview_viewed($course, $cm, $context) {

    $params = array(
        'objectid' => $cm->id,
        'context' => $context
    );

    $event = \mod_studentquiz\event\course_module_viewed::create($params);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Helper to get ids from prefexed ids in raw submit data
 */
function mod_studentquiz_helper_get_ids_by_raw_submit($rawdata) {
    if (!isset($rawdata)&& empty($rawdata)) {
        return false;
    }
    $ids = array();
    foreach ($rawdata as $key => $value) {
        if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
            $ids[] = $matches[1];
        }
    }
    if (!count($ids)) {
        return false;
    }
    return $ids;
}

/**
 * @param module_context context
 * TODO: Refactor! This check not only checks but also updates!
 */
function mod_studentquiz_check_question_category($context) {
    global $DB;
    $questioncategory = $DB->get_record('question_categories', array('contextid' => $context->id));

    if ($questioncategory->parent != -1) {
        return;
    }

    $parentqcategory = $DB->get_records('question_categories',
        array('contextid' => $context->get_parent_context()->id, 'parent' => 0));
    // If there are multiple parents category with parent == 0, use the one with the lowest id.
    if (!empty($parentqcategory)) {
        $questioncategory->parent = reset($parentqcategory)->id;

        foreach ($parentqcategory as $category) {
            if ($questioncategory->parent > $category->id) {
                $questioncategory->parent = $category->id;
            }
        }
        // TODO: Why is this update necessary?
        $DB->update_record('question_categories', $questioncategory);
    }
}

/**
 * Returns comment records joined with their user first & lastname
 * @param int $questionid
 */
function mod_studentquiz_get_comments_with_creators($questionid) {
    global $DB;

    $sql = 'SELECT co.*, u.firstname, u.lastname FROM {studentquiz_comment} co'
            .' JOIN {user} u on u.id = co.userid'
            .' WHERE co.questionid = :questionid'
            .' ORDER BY co.created ASC';

    return $DB->get_records_sql($sql, array( 'questionid' => $questionid));
}


/**
 * Generate some HTML to render comments
 *
 * @param array $comments from studentquiz_coments joind with user.firstname, user.lastname on comment.userid
 *        ordered by comment->created ASC
 * @param int $userid, viewing user id
 * @param bool $anonymize Display or hide other author names
 * @param bool $ismoderator True renders edit buttons to all comments false, only those for createdby userid
 * @return string HTML fragment
 * TODO: Render function should move to renderers!
 */
function mod_studentquiz_comment_renderer($comments, $userid, $anonymize, $ismoderator) {

    $output = '';

    $modname = 'studentquiz';

    if (empty($comments)) {
        return html_writer::div(get_string('no_comments', $modname));
    }

    $authorids = array();

    $num = 0;
    foreach ($comments as $comment) {

        // Collect distinct comment author ids chronologically.
        if (!in_array($comment->userid, $authorids)) {
            $authorids[] = $comment->userid;
        }

        $date = userdate($comment->created, get_string('strftimedatetime', 'langconfig'));

        $canedit = $ismoderator || $comment->userid == $userid;
        $seename = !$anonymize || $comment->userid == $userid;

        if ($seename) {
            $username = $comment->firstname . ' ' . $comment->lastname;
        } else {
            $username = get_string('student', 'studentquiz')
                . ' #' . (1 + array_search($comment->userid, $authorids));
        }

        if ($canedit) {
            $editspan = html_writer::span('remove', 'remove_action',
                array(
                    'data-id' => $comment->id,
                    'data-question_id' => $comment->questionid
                ));
        } else {
            $editspan = '';
        }

        $output .= html_writer::div( $editspan
            . html_writer::tag('p', $date . ' | ' . $username)
            . html_writer::tag('p', $comment->comment),
            ($num>=2)? 'hidden': ''
        );
        $num++;
    }

    if (count($comments) > 2) {
        $output .= html_writer::div(
            html_writer::tag('button', get_string('show_more', $modname), array('type' => 'button', 'class' => 'show_more'))
            . html_writer::tag('button', get_string('show_less', $modname)
                , array('type' => 'button', 'class' => 'show_less hidden')), 'button_controls'
        );
    }

    return $output;
}

/**
 * gets the Stats of the user for the actual StudentQuiz
 * @param int $userid
 * @param int $cmid course module id of studentquiz activity
 * @return array
 * @deprecated
 * TODO: Refactor!
 */
function mod_studentquiz_get_user_quiz_stats($userid, $cmid) {
    global $DB;
    $sql = 'select ( '
        . '  SELECT count(1) '
        . '  FROM {question} q '
        . '    LEFT JOIN {question_categories} qc ON q.category = qc.id '
        . '    LEFT JOIN {context} c ON qc.contextid = c.id '
        . '  WHERE c.instanceid = :cmid AND q.parent = 0 AND c.contextlevel = 70 '
        . ') AS TotalNrOfQuestions, '
        . '  (SELECT count(1) '
        . '   FROM {question} q '
        . '     LEFT JOIN {question_categories} qc ON q.category = qc.id '
        . '     LEFT JOIN {context} c ON qc.contextid = c.id '
        . '   WHERE c.instanceid = :cmid2 AND q.parent = 0 AND c.contextlevel = 70 AND q.createdby = :userid '
        . '  ) AS TotalUsersQuestions, '
        . '  (select count(DISTINCT att.questionid) '
        . '   from {question_attempt_steps} ats '
        . '     left JOIN {question_attempts} att on att.id = ats.questionattemptid '
        . '   WHERE ats.userid = :userid2 AND ats.state = \'gradedright\' '
        . '         AND att.questionid in (SELECT q.id '
        . '                            FROM {question} q '
        . '                              LEFT JOIN {question_categories} qc ON q.category = qc.id '
        . '                              LEFT JOIN {context} c ON qc.contextid = c.id '
        . '                            WHERE c.instanceid = :cmid3 AND c.contextlevel = 70)'
        . '          AND ats.id IN (SELECT max(suatsmax.id)'
        . '           FROM {question_attempt_steps} suatsmax LEFT JOIN {question_attempts} suattmax'
        . '                 ON suatsmax.questionattemptid = suattmax.id'
        . '             WHERE suatsmax.state IN (\'gradedright\', \'gradedpartial\', \'gradedwrong\') AND'
        . '                   suatsmax.userid = ats.userid'
        . '             GROUP BY suattmax.questionid)) AS TotalRightAnswers ,'
        . '     (select  COALESCE(round(avg(v.vote), 1), 0.0)'
        . ' from {studentquiz_vote} v'
        . ' where v.questionid in (SELECT q.id'
        . '            FROM {question} q LEFT JOIN'
        . '              {question_categories} qc'
        . '                ON q.category = qc.id'
        . '              LEFT JOIN {context} c'
        . '                ON qc.contextid = c.id'
        . '            WHERE c.instanceid = :cmid4 AND'
        . '                  c.contextlevel = 70'
        . '                  and q.createdby = :userid3)) as avgvotes,'
        . ' (select COALESCE(sum(v.approved), 0)'
        . ' from {studentquiz_question} v'
        . ' WHERE v.questionid in (SELECT q.id'
        . '            FROM {question} q LEFT JOIN'
        . '              {question_categories} qc'
        . '                ON q.category = qc.id'
        . '              LEFT JOIN {context} c'
        . '                ON qc.contextid = c.id'
        . '            WHERE c.instanceid = :cmid5 AND'
        . '                  c.contextlevel = 70'
        . '                  and q.createdby = :userid4)) as numapproved ';
    $record = $DB->get_record_sql($sql, array(
        'cmid' => $cmid, 'cmid2' => $cmid, 'cmid3' => $cmid,
        'cmid4' => $cmid, 'cmid5' => $cmid,
        'userid' => $userid, 'userid2' => $userid, 'userid3' => $userid, 'userid4' => $userid));
    return $record;
}

/**
 * Get all users in a course
 * @param int $courseid
 * @return array stdClass userid, courseid, firstname, lastname$
 * TODO: Refactor! We don't want to load all users of a course into memory ever.
 * @deprecated
 */
function mod_studentquiz_get_all_users_in_course($courseid) {
    global $DB;
    $sql = 'SELECT u.id as userid, c.id as courseid, u.firstname, u.lastname'
        . '     FROM {user} u'
        . '     INNER JOIN {user_enrolments} ue ON ue.userid = u.id'
        . '     INNER JOIN {enrol} e ON e.id = ue.enrolid'
        . '     INNER JOIN {course} c ON e.courseid = c.id'
        . '     WHERE c.id = :courseid';

    return $DB->get_records_sql($sql, array(
        'courseid' => $courseid
    ));
}

/**
 * @param $userid
 * @return array usermaxmark usermark stuquizmaxmark
 * @deprecated TODO: We don't want to call this vor every user in the table!
 */
function mod_studentquiz_get_user_quiz_grade($userid, $cmid) {
    global $DB;
    $sql = 'SELECT COALESCE(round(sum(sub.maxmark), 1), 0.0) as usermaxmark, '
        .'         COALESCE(round(sum(sub.mark), 1), 0.0) usermark, '
        .'         COALESCE((SELECT round(sum(q.defaultmark), 1) '
        .'                   FROM {question} q '
        .'                   LEFT JOIN {question_categories} qc ON q.category = qc.id '
        .'                   LEFT JOIN {context} c ON qc.contextid = c.id '
        .'                   WHERE q.parent = 0 AND c.instanceid = :cmid'
        .'                            AND c.contextlevel = 70), 0.0) as stuquizmaxmark '
        .'   FROM ( '
        .'         SELECT suatt.id, suatt.questionid, questionattemptid, max(fraction) as fraction, suatt.maxmark,  '
        .'         max(fraction) * suatt.maxmark as mark '
        .'         from {question_attempt_steps} suats '
        .'         LEFT JOIN {question_attempts} suatt on suats.questionattemptid = suatt.id '
        .'         WHERE state in (\'gradedright\', \'gradedpartial\', \'gradedwrong\') '
        .'            AND userid = :userid AND suatt.questionid'
        .'              IN ('
        .'               SELECT q.id '
        .'               FROM {question} q '
        .'               LEFT JOIN {question_categories} qc ON q.category = qc.id '
        .'               LEFT JOIN {context} c ON qc.contextid = c.id '
        .'               WHERE q.parent = 0 AND c.instanceid = :cmid2 AND c.contextlevel = 70'
        .'              ) '
        .'            AND suats.id '
        .'              IN ('
        .'                SELECT max(suatsmax.id)'
        .'                FROM {question_attempt_steps} suatsmax'
        .'                LEFT JOIN {question_attempts} suattmax ON suatsmax.questionattemptid = suattmax.id'
        .'                where suatsmax.state '
        .'                IN (\'gradedright\', \'gradedpartial\', \'gradedwrong\')'
        .'                AND suatsmax.userid = suats.userid'
        .'                GROUP BY suattmax.questionid'
        .'               )'
        .'  GROUP BY suatt.questionid, suatt.id, suatt.questionid, suatt.maxmark, suats.questionattemptid) as sub ';
    $record = $DB->get_record_sql($sql, array(
        'cmid' => $cmid, 'cmid2' => $cmid,
        'userid' => $userid));
    return $record;
}

/**
 * Get the calculcated user ranking from the database
 * @param $cmid
 * @param $questionquantifier
 * @param $approvedquantifier
 * @param $votequantifier
 * @param $correctanswerquantifier
 * @param $incorrectanswerquantifier
 * @return array user ranking data
 * @deprecated
 * TODO: Introduce some sort of pagination!
 * TODO: Refactor quantifiers to quantifier object
 */
function mod_studentquiz_get_user_ranking($cmid, $questionquantifier, $approvedquantifier, $votequantifier, $correctanswerquantifier, $incorrectanswerquantifier ) {
    global $DB;
    $sql = 'SELECT'
        . '    u.id AS userid, u.firstname, u.lastname,'
        . '    MAX(c.id) AS courseid, MAX(c.fullname), MAX(c.shortname),'
        . '    MAX(r.archetype) AS rolename,'
        . '    MAX(countq.countquestions),'
        . '    MAX(votes.meanvotes),'
        . '    ROUND(COALESCE(MAX(countquestions),0),1) AS countquestions,'
        . '    COALESCE(approvedq.countapproved, 0) as numapproved,'
        . '    ROUND(COALESCE(SUM(votes.meanvotes),0),1) AS summeanvotes,'
        . '    ROUND(COALESCE(MAX(correctanswers.countanswer),0),1) AS correctanswers,'
        . '    ROUND(COALESCE(MAX(incorrectanswers.countanswer),0),1) AS incorrectanswers,'
        . '    ROUND(COALESCE('
        . '        COALESCE(MAX(countquestions) * :questionquantifier, 0) +'
        . '        COALESCE(approvedq.countapproved, 0) * :approvedquantifier + '
        . '        COALESCE(SUM(votes.meanvotes) * :votequantifier, 0) +'
        . '        COALESCE(MAX(correctanswers.countanswer) * :correctanswerquantifier, 0) +'
        . '        COALESCE(MAX(incorrectanswers.countanswer) * :incorrectanswerquantifier, 0)'
        . '    , 0), 1) AS points'
        . '     FROM {studentquiz} sq'
        . '     JOIN {context} con ON( con.instanceid = sq.coursemodule )'
        . '     JOIN {question_categories} qc ON( qc.contextid = con.id )'
        . '     JOIN {course} c ON( sq.course = c.id )'
        . '     JOIN {enrol} e ON( c.id = e.courseid )'
        . '     JOIN {role} r ON( r.id = e.roleid )'
        . '     JOIN {user_enrolments} ue ON( ue.enrolid = e.id )'
        . '     JOIN {user} u ON( u.id = ue.userid )'
        . '     LEFT JOIN {question} q ON( q.createdby = u.id AND q.category = qc.id )'
        // Answered questions.
        // Correct answers.
        . '   LEFT JOIN (SELECT  count(DISTINCT suatt.questionid) AS countanswer, userid'
        . ' FROM {question_attempt_steps} suats'
        . ' LEFT JOIN {question_attempts} suatt ON suats.questionattemptid = suatt.id'
        . ' WHERE'
        . '  state IN (\'gradedright\', \'gradedpartial\', \'gradedwrong\')'
        . '  AND suatt.rightanswer = suatt.responsesummary'
        . '  AND suatt.questionid IN ('
        . '    SELECT q.id  FROM {question} q'
        . '    LEFT JOIN {question_categories} qc ON q.category = qc.id'
        . '    LEFT JOIN {context} c ON qc.contextid = c.id'
        . '  WHERE q.parent = 0'
        . '        AND c.instanceid = :cmid2 AND'
        . '        c.contextlevel = 70) AND'
        . '        suats.id IN (SELECT max(suatsmax.id)'
        . '             FROM {question_attempt_steps} suatsmax LEFT JOIN {question_attempts} suattmax'
        . '                 ON suatsmax.questionattemptid = suattmax.id'
        . '             WHERE suatsmax.state IN (\'gradedright\', \'gradedpartial\', \'gradedwrong\') AND'
        . '                   suatsmax.userid = suats.userid'
        . '             GROUP BY suattmax.questionid)'
        . ' GROUP BY userid) correctanswers'
        . '  ON (correctanswers.userid = u.id) '
        // Incorrect answers.
        . '    LEFT JOIN'
        . '    ('
        . '         SELECT'
        . '            count(distinct q.id) AS countanswer,'
        . '            qza.userid, q.category'
        . '         FROM {quiz_attempts} qza'
        . '         LEFT JOIN {quiz_slots} qs ON ( qs.quizid = qza.quiz )'
        . '         LEFT JOIN {question_attempts} qna ON ('
        . '              qza.uniqueid = qna.questionusageid'
        . '              AND qna.questionid = qs.questionid'
        . '              AND qna.rightanswer <> qna.responsesummary'
        . '              AND qna.responsesummary IS NOT NULL'
        . '         )'
        . '         LEFT JOIN {question} q ON( q.id = qna.questionid )'
        . '         GROUP BY q.category, qza.userid'
        . '    ) incorrectanswers ON ( incorrectanswers.userid = u.id AND incorrectanswers.category = qc.id )'
        // Questions created.
        . '    LEFT JOIN'
        . '    ('
        . '         SELECT COUNT(*) AS countquestions, createdby, category FROM {question}'
        . '         WHERE parent = 0 GROUP BY category, createdby'
        . '    ) countq ON( countq.createdby = u.id AND countq.category = qc.id )'
        // Questions approved.
        . '    LEFT JOIN'
        . '    ('
        . '         SELECT SUM(sqq.approved) AS countapproved, createdby, category '
        . '         FROM {question} q JOIN {studentquiz_question} sqq ON q.id = sqq.questionid'
        . '         GROUP BY q.createdby, q.category'
        . '    ) approvedq ON( approvedq.createdby = u.id AND approvedq.category = qc.id )'
        // Question votes.
        . '    LEFT JOIN'
        . '    ('
        . '         SELECT'
        . '            ROUND(SUM(sqvote.vote) / COUNT(sqvote.vote),2) AS meanvotes,'
        . '            questionid'
        . '         FROM {studentquiz_vote} sqvote'
        . '         GROUP BY sqvote.questionid'
        . '     ) votes ON( votes.questionid = q.id )'
        . '     WHERE sq.coursemodule = :cmid'
        . '     GROUP BY u.id, u.firstname, u.lastname'
        . '     ORDER BY points DESC';

    return $DB->get_records_sql($sql, array(
        'cmid' => $cmid, 'cmid2' => $cmid
    , 'questionquantifier' => $questionquantifier
    , 'approvedquantifier' => $approvedquantifier
    , 'votequantifier' => $votequantifier
    , 'correctanswerquantifier' => $correctanswerquantifier
    , 'incorrectanswerquantifier' => $incorrectanswerquantifier
    ));
}

/**
 * @param $studentquizid
 * @param $userid
 * @return array
 * @deprecated
 * TODO: Add pagination!
 */
function mod_studentquiz_get_user_attempts($studentquizid, $userid) {
    global $DB;
    return $DB->get_records('studentquiz_attempt',
        array('studentquizid' => $studentquizid, 'userid' => $userid));
}

/**
 * @param $usageid
 * @param $total
 * @deprecated
 * TODO: We dont want to sum this in memory for each attempt for each user!
 */
function mod_studentquiz_get_attempt_stats($usageid, &$total) {
    $quba = question_engine::load_questions_usage_by_activity($usageid);
    foreach ($quba->get_slots() as $slot) {
        $fraction = $quba->get_question_fraction($slot);
        $maxmarks = $quba->get_question_max_mark($slot);
        $total->obtainedmarks += $fraction * $maxmarks;
        if ($fraction > 0) {
            ++$total->questionsright;
        }
        ++$total->questionsanswered;
    }
}

/**
 * Lookup available question types.
 * @return array question types with identifier as key and name as value
 */
function mod_studentquiz_get_question_types() {
    $types = question_bank::get_creatable_qtypes();
    $returntypes = array();

    foreach ($types as $name => $qtype) {
        if ($name != 'randomsamatch') {
            $returntypes[$name] = $qtype->local_name();
        }
    }
    return $returntypes;
}

/**
 * Add capabilities to teacher (Non editing teacher) and
 * Student roles in the context of this context
 * @param stdClass $context of the studentquiz activity
 * @return true or exception
 */
function mod_studentquiz_add_question_capabilities($context) {
    $archtyperoles = array('student', 'teacher');
    $roles = array();
    foreach($archtyperoles as $archtyperole) {
        foreach(get_archetype_roles($archtyperole) as $role) {
            $roles[] = $role;
        }
    }
    $capabilities = array(
        'moodle/question:add',
        'moodle/question:usemine',
        'moodle/question:viewmine',
        'moodle/question:editmine');
    foreach($capabilities as $capability) {
        foreach($roles as $role) {
            assign_capability($capability, CAP_ALLOW, $role->id, $context->id, false);
        }
    }
    return true;
}