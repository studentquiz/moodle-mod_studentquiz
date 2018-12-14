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
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');

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
        if ($contextid !== false) {
            if ($studentquiz->category = question_get_default_category($contextid)) {
                $studentquiz->categoryid = $studentquiz->category->id;
                return $studentquiz;
            }
        } else {
            return $studentquiz;
        }
    }
    return false;
}

function mod_studentquiz_get_studenquiz_progress_class($questionid, $userid, $studentquizid, $lastanswercorrect = 0, $attempts = 0, $correctattempts = 0) {
    $studentquizprogress = new stdClass();
    $studentquizprogress->questionid = $questionid;
    $studentquizprogress->userid = $userid;
    $studentquizprogress->studentquizid = $studentquizid;
    $studentquizprogress->lastanswercorrect = $lastanswercorrect;
    $studentquizprogress->attempts = $attempts;
    $studentquizprogress->correctattempts = $correctattempts;

    return $studentquizprogress;
}

/**
 * Flip a question's approval status.
 * TODO: Ensure question is part of a studentquiz context.
 * @param int questionid index number of question
 */
function mod_studentquiz_flip_approved($questionid) {
    global $DB;

    $approved = $DB->get_field('studentquiz_question', 'approved', array('questionid' => $questionid));
    if ($approved === false) {
        // This question has no row in yet, maybe due to category move or import.
        $DB->insert_record('studentquiz_question', (object)array('approved' => true, 'questionid' => $questionid));
    } else {
        $DB->set_field('studentquiz_question', 'approved', !$approved, array('questionid' => $questionid));
    }
}

/**
 * Returns studentquiz_progress entries for a single studentquiz instance.
 * It is calculated using the question_attempts data.
 *
 * @param $studentquizid stdClass
 * @return array
 * @throws dml_exception
 */
function mod_studentquiz_get_studentquiz_progress_from_question_attempts_steps($studentquizid) {
    global $DB;

    $records = $DB->get_recordset_sql(mod_studentquiz_get_studentquiz_progress_from_question_attempts_steps_sql($studentquizid));

    $studentquizprogresses = array();

    foreach ($records as $r) {
        $studentquizprogress = mod_studentquiz_get_studenquiz_progress_class(
            $r->questionid_, $r->userid_, $r->studentquizid,
            $r->lastanswercorrect == 'gradedright' ? 1 : 0, $r->attempts, $r->correctattempts);
        array_push($studentquizprogresses, $studentquizprogress);
    }

    return $studentquizprogresses;
}

/**
 * Return the sql query for migrating question_attempts into studentquiz_progress
 *
 * @param $studentquizid stdClass
 * @return string
 *
 */
function mod_studentquiz_get_studentquiz_progress_from_question_attempts_steps_sql($studentquizid) {
    $sql = <<<EOL
Select
q.id as questionid_,
qas.userid as userid_,
s.id as studentquizid,
COUNT(qas.id) as attempts,
SUM(CASE WHEN qas.state = 'gradedright' THEN 1 ELSE 0 END) as correctattempts,
(Select qas1.state
From {question} q1
join {question_attempts} qa1 ON qa1.questionid = q1.id
join {question_attempt_steps} qas1 ON qas1.questionattemptid = qa1.id
where qas1.fraction is not null and q1.id = questionid_ and qas1.userid = userid_
order by qas1.id DESC limit 1) AS lastanswercorrect
From {question} q
JOIN {question_categories} qc ON qc.id = q.category
JOIN {context} co ON co.id = qc.contextid
join {course_modules} cm ON cm.id = co.instanceid
join {studentquiz} s ON s.coursemodule = cm.id
join {question_attempts} qa ON qa.questionid = q.id
join {question_attempt_steps} qas ON qas.questionattemptid = qa.id
where s.id = $studentquizid and qas.state != 'todo'
group by q.id,qas.userid
EOL;
    return $sql;
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
    $context = context_module::instance($cmid);
    return has_capability('mod/studentquiz:manage', $context);
}

/**
 * Prepare message for notify.
 * @param stdClass $question object
 * @param stdClass $recepient user object receiving the notification
 * @param int $actor user object triggering the notification
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return stdClass Data object with course, module, question, student and teacher info
 */

function mod_studentquiz_prepare_notify_data($question, $recepient, $actor, $course, $module) {

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
    $questionurl = new moodle_url('/mod/studentquiz/preview.php', array('cmid' => $module->id, 'questionid' => $question->id));
    $data->questionurl     = $questionurl->out(false);

    // Notification timestamp.
    // TODO: Note: userdate will format for the actor, not for the recepient.
    $data->timestamp    = userdate($time->getTimestamp(), get_string('strftimedatetime', 'langconfig'));

    // Recepient who receives the notification.
    $data->recepientidnumber = $recepient->idnumber;
    $data->recepientname     = fullname($recepient);
    $data->recepientusername = $recepient->username;

    // User who triggered the noticication.
    $data->actorname     = fullname($actor);
    $data->actorusername = $recepient->username;
    return $data;
}

/**
 * Notify student that someone has edited his question. (Info to question author)
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_changed($questionid, $course, $module) {
    return mod_studentquiz_event_notification_question('changed', $questionid, $course, $module);
}

/**
 * Notify student that someone has deleted his question. (Info to question author)
 * @param int $questionid ID of the author's question.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_deleted($questionid, $course, $module) {
    return mod_studentquiz_event_notification_question('deleted', $questionid, $course, $module);
}

/**
 * Notify student that someone has approved or unapproved his question. (Info to question author)
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_approved($questionid, $course, $module) {
    global $DB;

    $approved = $DB->get_field('studentquiz_question', 'approved', array('questionid' => $questionid));
    return mod_studentquiz_event_notification_question(($approved) ? 'approved' : 'unapproved',
        $questionid, $course, $module, 'approved');
}

/**
 * Notify student that someone has commented to his question. (Info to question author)
 * @param stdClass comment that was just added to the question
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_comment_added($comment, $course, $module) {
    return mod_studentquiz_event_notification_comment('added', $comment, $course, $module);
}

/**
 * Notify student that someone has deleted their comment to his question. (Info to question author)
 * Notify student that someone has deleted his comment to someone's question. (Info to comment author)
 * @param stdClass comment that was just added to the question
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_comment_deleted($comment, $course, $module) {
    $successtoauthor = mod_studentquiz_event_notification_comment('deleted', $comment, $course, $module);
    $successtocommenter = mod_studentquiz_event_notification_minecomment('deleted', $comment, $course, $module);
    return $successtoauthor || $successtocommenter;
}

/**
 * Notify question author that an event occured when the autor has this capabilty
 * @param string $event The name of the event, used to automatically get capability and mail contents
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_event_notification_question($event, $questionid, $course, $module) {
    global $DB, $USER;

    $question = $DB->get_record('question', array('id' => $questionid), 'id, name, timemodified, createdby, modifiedby');

    // Creator and Actor must be different.
    if ($question->createdby != $USER->id) {
        $users = user_get_users_by_id(array($question->createdby, $USER->id));
        $recipient = $users[$question->createdby];
        $actor = $users[$USER->id];
        $data = mod_studentquiz_prepare_notify_data($question, $recipient, $actor, $course, $module);

        return mod_studentquiz_send_notification($event, $recipient, $actor, $data);
    }
    return false;
}

/**
 * Notify question author that an event occured when the autor has this capabilty
 * @param string $event The name of the event, used to automatically get capability and mail contents
 * @param stdClass comment that was just added to the question
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_event_notification_comment($event, $comment, $course, $module) {
    global $DB, $USER;

    $questionid = $comment->questionid;
    $question = $DB->get_record('question', array('id' => $questionid), 'id, name, timemodified, createdby, modifiedby');

    // Creator and Actor must be different.
    // If the comment and question is the same recipient, only send the minecomment notification (see function below).
    if ($question->createdby != $USER->id && $comment->userid != $question->createdby) {
        $users = user_get_users_by_id(array($question->createdby, $USER->id));
        $recipient = $users[$question->createdby];
        $actor = $users[$USER->id];
        $data = mod_studentquiz_prepare_notify_data($question, $recipient, $actor, $course, $module);
        $data->commenttext = $comment->comment;
        $data->commenttime = userdate($comment->created, get_string('strftimedatetime', 'langconfig'));

        return mod_studentquiz_send_notification('comment' . $event, $recipient, $actor, $data);
    }

    return false;
}

/**
 * Notify question author that an event occured when the autor has this capabilty
 * @param string $event The name of the event, used to automatically get capability and mail contents
 * @param stdClass comment that was just added to the question
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_event_notification_minecomment($event, $comment, $course, $module) {
    global $DB, $USER;

    $questionid = $comment->questionid;
    $question = $DB->get_record('question', array('id' => $questionid), 'id, name, timemodified, createdby, modifiedby');

    // Creator and Actor must be different.
    if ($comment->userid != $USER->id) {
        $users = user_get_users_by_id(array($comment->userid, $USER->id));
        $recipient = $users[$comment->userid];
        $actor = $users[$USER->id];
        $data = mod_studentquiz_prepare_notify_data($question, $recipient, $actor, $course, $module);
        $data->commenttext = $comment->comment;
        $data->commenttime = userdate($comment->created, get_string('strftimedatetime', 'langconfig'));

        return mod_studentquiz_send_notification('minecomment' . $event, $recipient, $actor, $data);
    }

    return false;
}

/**
 * Sends notification messages to the interested parties that assign the role capability
 *
 * @param string $event message event string
 * @param stdClass $recipient user object of the intended recipient
 * @param stdClass $submitter user object of the sender
 * @param stdClass $data object of replaceable fields for the templates
 *
 * @return int|false as for {@link message_send()}.
 */
function mod_studentquiz_send_notification($event, $recipient, $submitter, $data) {
    global $CFG;
    // Recipient info for template.
    $data->useridnumber = $recipient->idnumber;
    $data->username     = fullname($recipient);
    $data->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->component         = 'mod_studentquiz';
    $eventdata->name              = $event;
    $eventdata->notification      = 1;

    // Courseid only for moodle >= 3.2.
    if ($CFG->version >= 2016120500) {
        $eventdata->courseid = $data->courseid;
    }

    $eventdata->userfrom          = $submitter;
    $eventdata->userto            = $recipient;
    $eventdata->subject           = get_string('email' . $event . 'subject', 'studentquiz', $data);
    $eventdata->smallmessage      = get_string('email' . $event . 'small', 'studentquiz', $data);
    $eventdata->fullmessage       = get_string('email' . $event . 'body', 'studentquiz', $data);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->contexturl        = $data->questionurl;
    $eventdata->contexturlname    = $data->questionname;

    // ... and send it.
    return message_send($eventdata);
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

    // Add first question to usage.
    mod_studentquiz_add_question_to_attempt($questionusage, $studentquiz, $ids);

    question_engine::save_questions_usage_by_activity($questionusage);

    $attempt->questionusageid = $questionusage->get_id();
    $attempt->ids = implode(",", $ids);

    $attempt->id = $DB->insert_record('studentquiz_attempt', $attempt);

    return $attempt;
}

/**
 * @param $questionusage question_usage_by_activity
 * @param $studentquiz stdClass $studentquiz generating this attempt
 * @param $questionids array $ids of question ids to be used in this attempt
 * @throws coding_exception
 */
function mod_studentquiz_add_question_to_attempt(&$questionusage, $studentquiz, &$questionids, $lastslost = 0) {
    $allowedcategories = question_categorylist($studentquiz->categoryid);
    $i = $lastslost;
    $addedquestions = 0;
    while ($addedquestions <= 0 && $i < count($questionids)) {
        $questiondata = question_bank::load_question($questionids[$i]);
        if (in_array($questiondata->category, $allowedcategories)) {
            $questionusage->add_question($questiondata);
            $addedquestions++;
        } else {
            unset($questionids[$i]);
            $questionids = array_values($questionids);
        }
        $i++;
    }

    if ($addedquestions == 0) {
        throw new moodle_exception("Could not load any valid question for attempt", "studentquiz");
    }

    $questionusage->start_question($i);
}


/**
 * Trigger Report viewed Event
 */
function mod_studentquiz_report_viewed($cmid, $context) {
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

        $canedit = $ismoderator || $comment->userid == $userid;
        $seename = !$anonymize || $comment->userid == $userid;

        // Collect distinct anonymous author ids chronologically.
        if (!in_array($comment->userid, $authorids)) {
            $authorids[] = $comment->userid;
        }

        $date = userdate($comment->created, get_string('strftimedatetime', 'langconfig'));

        if ($seename) {
            $username = $comment->firstname . ' ' . $comment->lastname;
        } else {
            $username = get_string('creator_anonym_firstname', 'studentquiz')
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
            ($num >= 2) ? 'hidden' : ''
        );
        $num++;
    }

    if (count($comments) > 2) {
        $output .= html_writer::div(
            html_writer::tag('button', get_string('show_more', $modname),
                array('type' => 'button', 'class' => 'show_more btn btn-secondary'))
            . html_writer::tag('button', get_string('show_less', $modname)
                , array('type' => 'button', 'class' => 'show_less btn btn-secondary hidden')), 'button_controls'
        );
    }

    return $output;
}

/**
 * Get Paginated ranking data ordered (DESC) by points, questions_created, questions_approved, rates_average
 * @param int $cmid Course module id of the StudentQuiz considered.
 * @param stdClass $quantifiers ad-hoc class containing quantifiers for weighted points score.
 * @param int $limitfrom return a subset of records, starting at this point (optional).
 * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
 * @return moodle_recordset of paginated ranking table
 */
function mod_studentquiz_get_user_ranking_table($cmid, $quantifiers, $aggregated, $limitfrom = 0, $limitnum = 0) {
    global $DB;
    $select = mod_studentquiz_helper_attempt_stat_select();
    $joins = mod_studentquiz_helper_attempt_stat_joins($aggregated);
    $statsbycat = ' ) statspercategory GROUP BY userid, firstname, lastname';
    $order = ' ORDER BY points DESC, questions_created DESC, questions_approved DESC, rates_average DESC, '
            .' question_attempts_correct DESC, question_attempts_incorrect ASC ';
    $res = $DB->get_recordset_sql($select.$joins.$statsbycat.$order,
        array('cmid1' => $cmid, 'cmid2' => $cmid, 'cmid3' => $cmid,
              'cmid4' => $cmid, 'cmid5' => $cmid, 'cmid6' => $cmid, 'cmid7' => $cmid
        , 'questionquantifier' => $quantifiers->question
        , 'approvedquantifier' => $quantifiers->approved
        , 'ratequantifier' => $quantifiers->rate
        , 'correctanswerquantifier' => $quantifiers->correctanswer
        , 'incorrectanswerquantifier' => $quantifiers->incorrectanswer
        ), $limitfrom, $limitnum);
    return $res;
}

/**
 * Get aggregated studentquiz data
 * @param int $cmid Course module id of the StudentQuiz considered.
 * @param stdClass $quantifiers ad-hoc class containing quantifiers for weighted points score.
 * @return moodle_recordset of paginated ranking table
 */
function mod_studentquiz_community_stats($cmid, $aggregated) {
    global $DB;
    $select = 'SELECT '
        .' count(*) participants,'
        // Calculate points.
        // TODO: Calc Points if needed - it's messy.
        // questions created.
        .' COALESCE(sum(creators.countq), 0) questions_created,'
        // Questions approved.
        .' COALESCE(sum(approvals.countq), 0) questions_approved,'
        // Questions rating received.
        .' COALESCE(sum(rates.countv), 0) rates_received,'
        .' COALESCE(avg(rates.avgv), 0) rates_average,'
        // Question attempts.
        .' COALESCE(count(1), 0) participated,'
        .' COALESCE(sum(attempts.counta), 0) question_attempts,'
        .' COALESCE(sum(attempts.countright), 0) question_attempts_correct,'
        .' COALESCE(sum(attempts.countwrong), 0) question_attempts_incorrect,'
        // Last attempt.
        .' COALESCE(sum(lastattempt.last_attempt_exists), 0) last_attempt_exists,'
        .' COALESCE(sum(lastattempt.last_attempt_correct), 0) last_attempt_correct,'
        .' COALESCE(sum(lastattempt.last_attempt_incorrect), 0) last_attempt_incorrect';
    $joins = mod_studentquiz_helper_attempt_stat_joins($aggregated);
    $rs = $DB->get_record_sql($select.$joins,
        array('cmid1' => $cmid, 'cmid2' => $cmid, 'cmid3' => $cmid,
            'cmid4' => $cmid, 'cmid5' => $cmid, 'cmid6' => $cmid, 'cmid7' => $cmid
        ));
    return $rs;
}

/**
 * Get aggregated studentquiz data
 * @param int $cmid Course module id of the StudentQuiz considered.
 * @param stdClass $quantifiers ad-hoc class containing quantifiers for weighted points score.
 * @param int $userid
 * @return array of user ranking stats
 * TODO: use mod_studentquiz_report_record type
 */
function mod_studentquiz_user_stats($cmid, $quantifiers, $userid, $aggregated) {
    global $DB;
    $select = mod_studentquiz_helper_attempt_stat_select();
    $joins = mod_studentquiz_helper_attempt_stat_joins($aggregated);
    $addwhere = ' AND u.id = :userid ';
    $statsbycat = ' ) statspercategory GROUP BY userid, firstname, lastname';
    $rs = $DB->get_record_sql($select.$joins.$addwhere.$statsbycat,
        array('cmid1' => $cmid, 'cmid2' => $cmid, 'cmid3' => $cmid,
            'cmid4' => $cmid, 'cmid5' => $cmid, 'cmid6' => $cmid, 'cmid7' => $cmid
        , 'questionquantifier' => $quantifiers->question
        , 'approvedquantifier' => $quantifiers->approved
        , 'ratequantifier' => $quantifiers->rate
        , 'correctanswerquantifier' => $quantifiers->correctanswer
        , 'incorrectanswerquantifier' => $quantifiers->incorrectanswer
            , 'userid' => $userid
        ));
    return $rs;
}

/**
 * @return
 * TODO: Refactor: There must be a better way to do this!
 */
function mod_studentquiz_helper_attempt_stat_select() {
    return 'SELECT '
        .' statspercategory.userid userid,'
        .' statspercategory.firstname firstname,'
        .' statspercategory.lastname lastname,'
        // Aggregate values over all categories in cm context.
        // Note: Max() of equals is faster than Sum() of groups.
        // See: https://dev.mysql.com/doc/refman/5.7/en/group-by-optimization.html.
        .' MAX(points) points,'
        .' MAX(questions_created) questions_created,'
        .' MAX(questions_created_and_rated) questions_created_and_rated,'
        .' MAX(questions_approved) questions_approved,'
        .' MAX(rates_received) rates_received,'
        .' MAX(rates_average) rates_average,'
        .' MAX(question_attempts) question_attempts,'
        .' MAX(question_attempts_correct) question_attempts_correct,'
        .' MAX(question_attempts_incorrect) question_attempts_incorrect,'
        .' MAX(last_attempt_exists) last_attempt_exists,'
        .' MAX(last_attempt_correct) last_attempt_correct,'
        .' MAX(last_attempt_incorrect) last_attempt_incorrect'
        // Select for each question category in context.
        .' FROM ( SELECT '
        .' u.id userid,'
        .' u.firstname firstname,'
        .' u.lastname lastname,'
        .' qc.id category, '
        // Calculate points.
        .' COALESCE ( ROUND('
        .' COALESCE(creators.countq, 0) * :questionquantifier  ' // Questions created.
        .'+ COALESCE(approvals.countq, 0) * :approvedquantifier  ' // Questions approved.
        .'+ COALESCE(rates.avgv, 0) * (COALESCE(creators.countq, 0) - COALESCE(rates.not_rated_questions, 0)) * :ratequantifier  ' // Rating.
        .'+ COALESCE(lastattempt.last_attempt_correct, 0) * :correctanswerquantifier  ' // Correct answers.
        .'+ COALESCE(lastattempt.last_attempt_incorrect, 0) * :incorrectanswerquantifier ' // Incorrect answers.
        .' , 1) , 0) points, '
        // Questions created.
        .' COALESCE(creators.countq, 0) questions_created,'
        // Questions created and rated.
        .' COALESCE(COALESCE(creators.countq, 0) - COALESCE(rates.not_rated_questions, 0), 0) questions_created_and_rated,'
        // Questions approved.
        .' COALESCE(approvals.countq, 0) questions_approved,'
        // Questions rating received.
        .' COALESCE(rates.countv, 0) rates_received,'
        .' COALESCE(rates.avgv, 0) rates_average,'
        // Question attempts.
        .' COALESCE(attempts.counta, 0) question_attempts,'
        .' COALESCE(attempts.countright, 0) question_attempts_correct,'
        .' COALESCE(attempts.countwrong, 0) question_attempts_incorrect,'
        // Last attempt.
        .' COALESCE(lastattempt.last_attempt_exists, 0) last_attempt_exists,'
        .' COALESCE(lastattempt.last_attempt_correct, 0) last_attempt_correct,'
        .' COALESCE(lastattempt.last_attempt_incorrect, 0) last_attempt_incorrect';
}

/**
 * @return string
 * TODO: Refactor: There must be a better way to do this!
 */
function mod_studentquiz_helper_attempt_stat_joins($aggregated) {
    $sql = ' FROM {studentquiz} sq'
        // Get this Studentquiz Question category.
        . ' JOIN {context} con ON con.instanceid = sq.coursemodule'
        . ' JOIN {question_categories} qc ON qc.contextid = con.id'
        // Only enrolled users.
        . ' JOIN {course} c ON c.id = sq.course'
        . ' JOIN {enrol} e ON e.courseid = c.id'
        . ' JOIN {user_enrolments} ue ON ue.enrolid = e.id'
        . ' JOIN {user} u ON ue.userid = u.id'
        // Question created by user.
        . ' LEFT JOIN'
        . ' ( SELECT count(*) countq, q.createdby creator'
        . ' FROM {studentquiz} sq'
        . ' JOIN {context} con ON con.instanceid = sq.coursemodule'
        . ' JOIN {question_categories} qc ON qc.contextid = con.id'
        . ' JOIN {question} q on q.category = qc.id'
        . ' WHERE q.hidden = 0 AND q.parent = 0 AND sq.coursemodule = :cmid4'
        . ' GROUP BY creator'
        . ' ) creators ON creators.creator = u.id'
        // Approved questions.
        . ' LEFT JOIN'
        . ' ( SELECT count(*) countq, q.createdby creator'
        . ' FROM {studentquiz} sq'
        . ' JOIN {context} con ON con.instanceid = sq.coursemodule'
        . ' JOIN {question_categories} qc ON qc.contextid = con.id'
        . ' JOIN {question} q on q.category = qc.id'
        . ' JOIN {studentquiz_question} sqq ON q.id = sqq.questionid'
        . ' where q.hidden = 0 AND q.parent = 0 AND sqq.approved = 1 AND sq.coursemodule = :cmid5'
        . ' group by creator'
        . ' ) approvals ON approvals.creator = u.id'
        // Average of Average Rating of own questions.
        . ' LEFT JOIN'
        . ' (SELECT'
        . '    createdby,'
        . '    AVG(avg_rate_perq) avgv,'
        . '    SUM(num_rate_perq) countv,'
        . '    SUM(question_not_rated) not_rated_questions'
        . '  FROM ('
        . '      SELECT'
        . '          q.id,'
        . '          q.createdby createdby,'
        . '          AVG(sqv.rate) avg_rate_perq,'
        . '          COUNT(sqv.rate) num_rate_perq,'
        . '          MAX(CASE WHEN sqv.id is null then 1 else 0 end) question_not_rated'
        . '      FROM {studentquiz} sq'
        . '      JOIN {context} con on con.instanceid = sq.coursemodule'
        . '      JOIN {question_categories} qc on qc.contextid = con.id'
        . '      JOIN {question} q on q.category = qc.id'
        . '      LEFT JOIN {studentquiz_rate} sqv on q.id = sqv.questionid'
        . '      WHERE'
        . '          q.hidden = 0 AND q.parent = 0'
        . '          and sq.coursemodule = :cmid6'
        . '      GROUP BY q.id, q.createdby'
        . '      ) avgratingperquestion'
        . '  GROUP BY createdby'
        . ' ) rates ON rates.createdby = u.id';
    if ($aggregated) {
        $sql .= ' LEFT JOIN (SELECT'
            . ' sp.userid,'
            . ' COUNT(*) last_attempt_exists,'
            . ' SUM(lastanswercorrect) last_attempt_correct,'
            . ' SUM(1 - lastanswercorrect) last_attempt_incorrect'
            . ' FROM'
            . ' {studentquiz_progress} AS sp'
            . ' JOIN {studentquiz} sq ON sq.id = sp.studentquizid'
            . ' WHERE'
            . ' sq.coursemodule = :cmid2'
            . ' GROUP BY sp.userid) lastattempt ON lastattempt.userid = u.id'
            . ' LEFT JOIN (SELECT'
            . ' SUM(attempts) counta,'
            . ' SUM(correctattempts) countright,'
            . ' SUM(attempts - correctattempts) countwrong,'
            . ' sp.userid userid'
            . ' FROM'
            . ' {studentquiz_progress} AS sp'
            . ' JOIN {studentquiz} sq ON sq.id = sp.studentquizid'
            . ' WHERE'
            . ' sq.coursemodule = :cmid1'
            . ' GROUP BY sp.userid) attempts ON attempts.userid = u.id';
    } else {
        $sql .= ' LEFT JOIN'
            . ' ('
            . ' SELECT count(*) counta,'
            . ' SUM(CASE WHEN state = \'gradedright\' THEN 1 ELSE 0 END) countright,'
            . ' SUM(CASE WHEN qas.state = \'gradedwrong\' THEN 1 WHEN qas.state = \'gradedpartial\' THEN 1 ELSE 0 END) countwrong,'
            . ' sqa.userid userid'
            . ' FROM {studentquiz} sq'
            . ' JOIN {studentquiz_attempt} sqa ON sq.id = sqa.studentquizid'
            . ' JOIN {question_usages} qu ON qu.id = sqa.questionusageid'
            . ' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
            . ' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
            . ' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
            . ' WHERE sq.coursemodule = :cmid7'
            . ' AND qas.state in (\'gradedright\', \'gradedwrong\', \'gradedpartial\')'
            // Only count grading triggered by submits.
            . ' AND qasd.name = \'-submit\''
            . ' group by sqa.userid'
            . ' ) attempts ON attempts.userid = u.id'
            // Latest attempts.
            . ' LEFT JOIN'
            . ' ('
            . ' SELECT'
            . ' sqa.userid,'
            . ' count(*) last_attempt_exists,'
            . ' SUM(CASE WHEN qas.state = \'gradedright\' THEN 1 ELSE 0 END) last_attempt_correct,'
            . ' SUM(CASE '
            . '        WHEN qas.state = \'gradedwrong\' THEN 1'
            . '        WHEN qas.state = \'gradedpartial\' THEN 1 ELSE 0 END) last_attempt_incorrect'
            . ' FROM {studentquiz} sq'
            . ' JOIN {studentquiz_attempt} sqa ON sq.id = sqa.studentquizid'
            . ' JOIN {question_usages} qu ON qu.id = sqa.questionusageid'
            . ' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
            . ' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
            . ' LEFT JOIN {question_attempt_step_data} qasd ON'
            . ' qasd.attemptstepid = qas.id and'
            . ' qasd.id in ('
            // SELECT only latest states (its a constant result).
            . ' SELECT max(qasd.id) latest_grading_event'
            . ' FROM {studentquiz} sq'
            . ' JOIN {studentquiz_attempt} sqa ON sq.id = sqa.studentquizid'
            . ' JOIN {question_usages} qu ON qu.id = sqa.questionusageid'
            . ' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
            . ' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
            . ' JOIN {question} qq ON qq.id = qa.questionid'
            . ' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
            . ' WHERE sq.coursemodule = :cmid1'
            . '   AND qas.state in (\'gradedright\', \'gradedwrong\', \'gradedpartial\')'
            . '   AND qasd.name = \'-submit\''
            . ' group by sqa.userid, questionid'
            . ' )'
            . ' WHERE sq.coursemodule = :cmid2'
            . ' AND qas.state in (\'gradedright\', \'gradedpartial\', \'gradedwrong\')'
            // Only count grading triggered by submits.
            . ' AND qasd.name = \'-submit\''
            . ' group by sqa.userid'
            . ' ) lastattempt ON lastattempt.userid = u.id';
    }
    // Question attempts: sum of number of graded attempts per question.
    $sql .= ' WHERE sq.coursemodule = :cmid3';

    return $sql;
}

/**
 * Lookup available question types.
 * @return array question types with identifier as key and name as value
 */
function mod_studentquiz_get_question_types() {
    $types = question_bank::get_creatable_qtypes();
    $returntypes = array();
    // Don't allow Question type essay anymore.
    unset($types["essay"]);
    foreach ($types as $name => $qtype) {
        if ($name != 'randomsamatch') {
            $returntypes[$name] = $qtype->local_name();
        }
    }
    return $returntypes;
}

function mod_studentquiz_get_question_types_keys() {
    $types = mod_studentquiz_get_question_types();
    return array_keys($types);
}



/**
 * Add capabilities to teacher (Non editing teacher) and
 * Student roles in the context of this context
 *
 * @param context $context of the studentquiz activity
 */
function mod_studentquiz_ensure_question_capabilities($context) {
    global $CFG;

    $neededcapabilities = array(
        'moodle/question:add',
        'moodle/question:usemine',
        'moodle/question:viewmine',
        'moodle/question:editmine'
    );
    if ($CFG->version >= 2018051700) { // Moodle 3.5+.
        $neededcapabilities[] = 'moodle/question:tagmine';
    }

    // Get the ids of all the roles that can submit questions in this activity.
    list($roleids) = get_roles_with_cap_in_context($context, 'mod/studentquiz:submit');
    foreach ($roleids as $roleid) {
        // If needed, add an override for each question capability.
        foreach ($neededcapabilities as $capability) {
            // This function only creates an override if needed.
            role_change_permission($roleid, $context, $capability, CAP_ALLOW);
        }
    }
}

/**
 * Migrate old StudentQuiz quiz usages to new data-logic.
 * Old Studentquiz created quiz instances for each "Run Quiz", while the new StudentQuiz uses the question-engine directly.
 * StudentQuiz <= 2.0.3 stored the quizzes in section 999 (and a import creates empty sections in between).
 * StudentQuiz <= 2.1.0 was not dependent on a section 999, but instead the teacher could choose in which section they are.
 *
 * This function must be usable for the restore and the plugin update process. In the restore we can get a courseid,
 * and a studentquizid. In the plugin update we have nothing, so all affected courses must be considered and checked.
 *
 * This task is basically the following:
 * MIGRATION.
 * - Find out if there is an orphaned section.
 * - For each studentquiz activity.
 * - Find all question-usages for each user in the quizzes matching the studentquiz name.
 * - Each question-usage must now be moved into a new studentquiz attempt table row.
 * CLEANUP.
 * - Find the last nonempty section not beeing the above orphaned section.
 * - Remove all sections with number bigger than the found one.
 *
 * Hint: To save time during these processes, the old quizzes are not yet removed, the cronjob has gotten this step
 *
 * @param int|null $courseorigid
 */
function mod_studentquiz_migrate_old_quiz_usage($courseorigid=null) {
    global $DB;

    // If we haven't gotten a courseid, migration is meant to whole moodle instance.
    $courseids = array();
    if (!empty($courseorigid)) {
        $courseids[] = $courseorigid;
    } else {
        $courseids = $DB->get_fieldset_sql('
            select distinct cm.course
            from {course_modules} cm
            inner join {context} c on cm.id = c.instanceid
            inner join {question_categories} qc on qc.contextid = c.id
            inner join {modules} m on cm.module = m.id
            where m.name = :modulename
        ', array(
            'modulename' => 'studentquiz'
        ));
    }

    // Step into each course so they operate independent from each other.
    foreach ($courseids as $courseid) {
        // Import old Core Quiz Data (question attempts) to studentquiz.
        // This is the case, when orphaned section(s) can be found.
        $orphanedsectionids = $DB->get_fieldset_sql('
            select id
            from {course_sections}
            where course = :course
            and name = :name
        ', array(
            'course' => $courseid,
            'name' => STUDENTQUIZ_COURSE_SECTION_NAME
        ));

        if (!empty($orphanedsectionids)) {
            $oldquizzes = array();

            // For each course we need to find the studentquizzes.
            // "up" section: Only get the topmost category of that studentquiz, which isn't "top" if that one exists.
            $studentquizzes = $DB->get_records_sql('
                select s.id, s.name, cm.id as cmid, c.id as contextid, qc.id as categoryid, qc.name as categoryname, qc.parent
                from {studentquiz} s
                inner join {course_modules} cm on s.id = cm.instance
                inner join {context} c on cm.id = c.instanceid
                inner join {question_categories} qc on qc.contextid = c.id
                inner join {modules} m on cm.module = m.id
                left join {question_categories} up on qc.contextid = up.contextid and qc.parent = up.id
                where m.name = :modulename
                and cm.course = :course
                and (
                    up.name = :topname1
	                or (
	                    up.id is null
	                    and qc.name <> :topname2
	                )
	            )
            ', array(
                'modulename' => 'studentquiz',
                'course' => $courseid,
                'topname1' => 'top',
                'topname2' => 'top'
            ));

            foreach ($studentquizzes as $studentquiz) {

                // Each studentquiz wants the question attempt id, which can be found inside the matching quizzes.
                $oldusages = $DB->get_records_sql('
                    select qu.id as qusageid, q.id as quizid, cm.id as cmid, cm.section as sectionid, c.id as contextid
                    from {quiz} q
                    inner join {course_modules} cm on q.id = cm.instance
                    inner join {context} c on cm.id = c.instanceid
                    inner join {modules} m on cm.module = m.id
                    inner join {question_usages} qu on c.id = qu.contextid
                    where m.name = :modulename
                    and cm.course = :course
                    and ' . $DB->sql_like('q.name', ':name', false) . '
                ', array(
                    'modulename' => 'quiz',
                    'course' => $courseid,
                    'name' => $studentquiz->name . '%'
                ));

                // For each old question usage we need to move it to studentquiz.
                foreach ($oldusages as $oldusage) {
                    $oldquizzes[$oldusage->quizid] = true;
                    $DB->set_field('question_usages', 'component', 'mod_studentquiz',
                        array('id' => $oldusage->qusageid));
                    $DB->set_field('question_usages', 'contextid', $studentquiz->contextid,
                        array('id' => $oldusage->qusageid));
                    $DB->set_field('question_usages', 'preferredbehaviour', STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR,
                        array('id' => $oldusage->qusageid));
                    $DB->set_field('question_attempts', 'behaviour', STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR,
                        array('questionusageid' => $oldusage->qusageid));

                    // Now we need each user as own attempt.
                    $userids = $DB->get_fieldset_sql('
                        select distinct qas.userid
                        from {question_attempt_steps} qas
                        inner join {question_attempts} qa on qas.questionattemptid = qa.id
                        where qa.questionusageid = :qusageid
                    ', array(
                        'qusageid' => $oldusage->qusageid
                    ));
                    foreach ($userids as $userid) {
                        $DB->insert_record('studentquiz_attempt', (object)array(
                            'studentquizid' => $studentquiz->id,
                            'userid' => $userid,
                            'questionusageid' => $oldusage->qusageid,
                            'categoryid' => $studentquiz->categoryid,
                        ));
                    }
                }
            }

            // Cleanup quizzes as we have migrated the question usages now.
            foreach (array_keys($oldquizzes) as $quizid) {
                // So that quiz doesn't remove the question usages.
                $DB->delete_records('quiz_attempts', array('quiz' => $quizid));
                // Quiz deletion over classes/task/delete_quiz_after_migration.php.
            }

            // So lookup the last non-empty section first.
            $orphanedsectionids[] = 0; // Force multiple entries, so next command makes a IN statement in every case.
            list($insql, $inparams) = $DB->get_in_or_equal($orphanedsectionids, SQL_PARAMS_NAMED, 'section');

            $lastnonemptysection = $DB->get_record_sql('
                SELECT MAX(s.section) as max_section
                   FROM {course_sections} s
                   left join {course_modules} m on s.id = m.section
                   where s.course = :course
                   and s.id NOT ' . $insql . '
                   and (
                       m.id is not NULL
                       or s.name <> :sectionname
                       or s.summary <> :sectionsummary
                   )
            ', array_merge($inparams, array(
                'course' => $courseid,
                'sectionname' => '',
                'sectionsummary' => ''
            )));

            if ($lastnonemptysection !== false) {
                // And remove all these useless sections.
                $DB->delete_records_select('course_sections',
                    'course = :course AND section > :nonemptysection',
                    array(
                        'course' => $courseid,
                        'nonemptysection' => $lastnonemptysection->max_section
                    )
                );
            }
        }
    }
}

/**
 * This is a helper to ensure we have a studentquiz_question record for a specific question
 * @param int $id question id
 */
function mod_studentquiz_ensure_studentquiz_question_record($id) {
    global $DB;
    // Check if record exist.
    if (!$DB->count_records('studentquiz_question', array('questionid' => $id)) ) {
        $DB->insert_record('studentquiz_question', array('questionid' => $id, 'approved' => 0));
    }
}

/**
 * @param $ids
 * @return array [questionid] -> array ( array($tagname, $tagrawname) )
 */
function mod_studentquiz_get_tags_by_question_ids($ids) {
    global $DB;

    // Return an empty array for empty selection.
    if (empty($ids)) {
        return array();
    }

    list($insql, $params) = $DB->get_in_or_equal($ids);
    $result = array();
    $tags = $DB->get_records_sql(
        'SELECT ti.id id, t.id tagid, t.name, t.rawname, ti.itemid '
        . ' FROM {tag} t JOIN {tag_instance} ti ON ti.tagid = t.id '
        . ' WHERE ti.itemtype = \'question\' AND ti.itemid '
        . $insql, $params);
    foreach ($tags as $tag) {
        if (empty($result[$tag->itemid])) {
            $result[$tag->itemid] = array();
        }
        $result[$tag->itemid][] = $tag;
    }
    return $result;
}

function mod_studentquiz_count_questions($cmid) {
    global $DB;
    $rs = $DB->count_records_sql('SELECT count(*) FROM {studentquiz} sq'
        // Get this Studentquiz Question category.
        .' JOIN {context} con ON con.instanceid = sq.coursemodule'
        .' JOIN {question_categories} qc ON qc.contextid = con.id'
        // Only enrolled users.
        .' JOIN {question} q ON q.category = qc.id'
        .'  WHERE q.hidden = 0 AND q.parent = 0 AND sq.coursemodule = :cmid', array('cmid' => $cmid));
    return $rs;
}

/**
 * This query collects aggregated information about the questions in this StudentQuiz.
 *
 * @param $cmid
 * @throws dml_exception
 */
function mod_studentquiz_question_stats($cmid) {
    global $DB;
    $sql = 'SELECT count(*) questions_available,'
       .'          avg(rating.avg_rating) as average_rating,'
       .'          sum(sqq.approved) as questions_approved'
       .'   FROM {studentquiz} sq'
        // Get this Studentquiz Question category.
        .' JOIN {context} con ON con.instanceid = sq.coursemodule'
        .' JOIN {question_categories} qc ON qc.contextid = con.id'
        // Only enrolled users.
        .' JOIN {question} q ON q.category = qc.id'
        .' LEFT JOIN {studentquiz_question} sqq on sqq.questionid = q.id'
        .' LEFT JOIN ('
        .'  SELECT'
        .'      q.id questionid,'
        .'      coalesce(avg(sqr.rate),0) avg_rating'
        .'  FROM {studentquiz} sq'
        .'   JOIN {context} con ON con.instanceid = sq.coursemodule'
        .'   JOIN {question_categories} qc ON qc.contextid = con.id'
        .'   JOIN {question} q ON q.category = qc.id'
        .'   LEFT JOIN {studentquiz_rate} sqr ON sqr.questionid = q.id'
        .'  WHERE sq.coursemodule = :cmid2'
        .'  GROUP BY q.id'
        .' ) rating on rating.questionid = q.id'
        .' WHERE q.hidden = 0 and q.parent = 0 and sq.coursemodule = :cmid1';
    $rs = $DB->get_record_sql($sql, array('cmid1' => $cmid, 'cmid2' => $cmid));
    return $rs;
}

/**
 * Fix parent of question categories of StudentQuiz.
 * Old Studentquiz have the parent of question categories not equalling to 0 for various reasons, but they should.
 * In Moodle < 3.5 there is no "top" parent category, so the question category itself has to be corrected if it's not 0.
 * In Moodle >= 3.5 there is a new "top" parent category, so the question category of StudentQuiz has to have that as parent.
 * See https://tracker.moodle.org/browse/MDL-61132 and its diff.
 *
 * This function must be usable for the restore and the plugin update process.
 */
function mod_studentquiz_fix_wrong_parent_in_question_categories() {
    global $DB;

    if (function_exists('question_get_top_category')) { // We have a moodle with "top" category feature
        $categorieswithouttop = $DB->get_records_sql('
            select qc.id, qc.contextid, qc.name, qc.parent
            from {question_categories} qc
            inner join {context} c on qc.contextid = c.id
            inner join {course_modules} cm on c.instanceid = cm.id
            inner join {modules} m on cm.module = m.id
            left join {question_categories} up on qc.contextid = up.contextid and qc.parent = up.id
            where m.name = :modulename
            and up.name is null
            and qc.name <> :topname
        ', array(
            'modulename' => 'studentquiz',
            'topname' => 'top'
        ));
        foreach ($categorieswithouttop as $currentcat) {
            $topcat = question_get_top_category($currentcat->contextid, true);
            // now set the parent to the newly created top id
            $DB->set_field('question_categories', 'parent', $topcat->id, array('id' => $currentcat->id));
        }
    } else {
        $categorieswithoutparent = $DB->get_records_sql('
            select qc.id, qc.contextid, qc.name, qc.parent
            from {question_categories} qc
            inner join {context} c on qc.contextid = c.id
            inner join {course_modules} cm on c.instanceid = cm.id
            inner join {modules} m on cm.module = m.id
            left join {question_categories} up on qc.contextid = up.contextid and qc.parent = up.id
            where m.name = :modulename
            and up.id is null
            and qc.parent <> 0
        ', array(
                'modulename' => 'studentquiz'
        ));
        foreach ($categorieswithoutparent as $currentcat) {
            // now set the parent to 0
            $DB->set_field('question_categories', 'parent', 0, array('id' => $currentcat->id));
        }
    }
}
