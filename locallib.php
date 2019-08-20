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

use mod_studentquiz\local\studentquiz_helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

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
 * Change a question state of visibility
 *
 * @param int $questionid Id of question
 * @param string $type Type of change
 * @param int $value Value of change
 */
function mod_studentquiz_change_state_visibility($questionid, $type, $value) {
    global $DB;
    $question = $DB->record_exists('studentquiz_question', ['questionid' => $questionid]);
    if (!$question) {
        // This question has no row in yet, maybe due to category move or import.
        $DB->insert_record('studentquiz_question', (object) ['state' => true, 'questionid' => $questionid]);
    } else {
        if ($type == 'deleted') {
            $DB->set_field('question', 'hidden', 1, ['id' => $questionid]);
        } else {
            $DB->set_field('studentquiz_question', $type, $value, ['questionid' => $questionid]);
        }
    }
}

/**
 * Migrates all studentquizes that are not yet aggregated to the aggreated state.
 *
 * If it fails, try the following:
 *  - Set all entries in the table studentquiz to aggregated = 0
 *  - Truncate the table studentquiz_progress
 *  - Retry
 *
 * @throws Throwable
 * @throws coding_exception
 * @throws dml_exception
 * @throws dml_transaction_exception
 * @param int|null $courseorigid
 */
function mod_studentquiz_migrate_all_studentquiz_instances_to_aggregated_state($courseorigid=null) {
    global $DB;

    $params = array('aggregated' => '0');
    if (!empty($courseorigid)) {
        $params['course'] = $courseorigid;
    }
    $studentquizes = $DB->get_records('studentquiz', $params);

    $transaction = $DB->start_delegated_transaction();

    try {
        foreach ($studentquizes as $studentquiz) {
            mod_studentquiz_migrate_single_studentquiz_instances_to_aggregated_state($studentquiz);
        }
        $DB->commit_delegated_transaction($transaction);
    } catch (Exception $e) {
        $DB->rollback_delegated_transaction($transaction, $e);
        throw new Exception($e->getMessage());
    }
}

/**
 * Migrate a single studentquiz instance to aggregated state
 *
 * @param $studentquiz
 * @throws coding_exception
 * @throws dml_exception
 */
function mod_studentquiz_migrate_single_studentquiz_instances_to_aggregated_state($studentquiz) {
    global $DB;

    $context = context_module::instance($studentquiz->coursemodule);
    $data = mod_studentquiz_get_studentquiz_progress_from_question_attempts_steps($studentquiz->id, $context);

    $DB->insert_records('studentquiz_progress', new ArrayIterator($data));

    $studentquiz->aggregated = 1;

    $DB->update_record('studentquiz', $studentquiz);
}

/**
 * Returns studentquiz_progress entries for a single studentquiz instance.
 * It is calculated using the question_attempts data.
 *
 * @param int $studentquizid id of this studentquiz instance from the studentquiz table.
 * @param context $context the module context for this studentquiz.
 * @return array data that can be inserted into the studentquiz_progress table.
 * @throws dml_exception
 */
function mod_studentquiz_get_studentquiz_progress_from_question_attempts_steps($studentquizid, $context) {
    global $DB;

    $sql = "SELECT innerq.questionid, innerq.userid, innerq.attempts, innerq.correctattempts,
                   CASE WHEN qas1.state = :rightstate2 THEN 1 ELSE 0 END AS lastanswercorrect

              FROM (
                    SELECT qa.questionid, qas.userid,
                           COUNT(qas.id) AS attempts,
                           SUM(CASE WHEN qas.state = :rightstate3 THEN 1 ELSE 0 END) AS correctattempts

                      FROM {question_usages} qu
                      JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                      JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id

                     WHERE qu.contextid = :contextid1
                           AND qas.state IN (:rightstate, :partialstate, :wrongstate)

                  GROUP BY qa.questionid, qas.userid
                    ) innerq

              JOIN {question_attempt_steps} qas1 ON qas1.id = (
                   SELECT MAX(qas_last.id)
                     FROM {question_usages} qu_last
                     JOIN {question_attempts} qa_last ON qa_last.questionusageid = qu_last.id
                     JOIN {question_attempt_steps} qas_last ON qas_last.questionattemptid = qa_last.id
                    WHERE qu_last.contextid = :contextid2 AND qa_last.questionid = innerq.questionid AND qas_last.userid = innerq.userid
                          AND qas_last.state IN (:rightstate1, :partialstate1, :wrongstate1)
                   )";
    $records = $DB->get_recordset_sql($sql, array(
            'rightstate2' => (string) question_state::$gradedright, 'rightstate3' => (string) question_state::$gradedright,
            'contextid1' => $context->id, 'contextid2' => $context->id,
            'rightstate' => (string) question_state::$gradedright, 'partialstate' => (string) question_state::$gradedpartial,
            'wrongstate' => (string) question_state::$gradedwrong, 'rightstate1' => (string) question_state::$gradedright,
            'partialstate1' => (string) question_state::$gradedpartial, 'wrongstate1' => (string) question_state::$gradedwrong));

    $studentquizprogresses = array();

    foreach ($records as $r) {
        $studentquizprogress = mod_studentquiz_get_studenquiz_progress_class(
            $r->questionid, $r->userid, $studentquizid,
            $r->lastanswercorrect, $r->attempts, $r->correctattempts);
        array_push($studentquizprogresses, $studentquizprogress);
    }

    return $studentquizprogresses;
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
    // Get StudentQuiz.
    $context = context_module::instance($module->id);
    $studentquiz = mod_studentquiz_load_studentquiz($module->id, $context->id);

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

    // Set to anonymous student and manager if needed.
    if ($studentquiz->anonymrank) {
        $data->recepientname = get_string('creator_anonym_fullname', 'studentquiz');
        $data->actorname = get_string('manager_anonym_fullname', 'studentquiz');
    }

    return $data;
}

/**
 * Notify student that someone has change the state / visibility of his question. (Info to question author)
 *
 * @param int $questionid Id of the question
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @param string $type Type of change
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_state_notify($questionid, $course, $module, $type) {
    global $DB;
    if ($type == 'state') {
        $state = $DB->get_field('studentquiz_question', $type, ['questionid' => $questionid]);
        $states = [
                studentquiz_helper::STATE_DISAPPROVED => 'disapproved',
                studentquiz_helper::STATE_APPROVED => 'approved',
                studentquiz_helper::STATE_NEW => 'new',
                studentquiz_helper::STATE_CHANGED => 'changed',
        ];
        $event = $states[$state];
    } else {
        $event = $type;
    }
    return mod_studentquiz_event_notification_question($event, $questionid, $course, $module);
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

    question_engine::save_questions_usage_by_activity($questionusage);
}

/**
 * Trigger completion.
 *
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 */
function mod_studentquiz_completion($course, $cm) {
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Trigger overview viewed event.
 *
 * @param int      $cmid       course module id
 * @param stdClass $context    context object
 */
function mod_studentquiz_overview_viewed($cmid, $context) {
    $params = array(
        'objectid' => $cmid,
        'context' => $context
    );
    $event = \mod_studentquiz\event\course_module_viewed::create($params);
    $event->trigger();
}

/**
 * Trigger instance list viewed event.
 *
 * @param stdClass $context    context object
 */
function mod_studentquiz_instancelist_viewed($context) {
    $params = array(
        'context' => $context
    );
    $event = \mod_studentquiz\event\course_module_instance_list_viewed::create($params);
    $event->trigger();
}

/**
 * Trigger report viewed event.
 *
 * @param int      $cmid       course module id
 * @param stdClass $context    context object
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
 * Trigger report rank viewed event.
 *
 * @param stdClass $cmid       course module id
 * @param stdClass $context    context object
 */
function mod_studentquiz_reportrank_viewed($cmid, $context) {
    $params = array(
        'objectid' => $cmid,
        'context' => $context
    );
    $event = \mod_studentquiz\event\studentquiz_report_rank_viewed::create($params);
    $event->trigger();
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
    return $ids;
}

/**
 * Returns comment records
 * @param int $questionid
 */
function mod_studentquiz_get_comments_with_creators($questionid) {
    global $DB;

    $sql = "SELECT co.*
              FROM {studentquiz_comment} co
              WHERE co.questionid = :questionid
            ORDER BY co.created ASC";

    return $DB->get_records_sql($sql, array( 'questionid' => $questionid));
}


/**
 * Generate some HTML to render comments
 *
 * @param array $comments from studentquiz_coments ordered by comment->created ASC
 * @param int $userid, viewing user id
 * @param int $cmid, course module id
 * @param bool $anonymize Display or hide other author names
 * @param bool $ismoderator True renders edit buttons to all comments false, only those for createdby userid
 * @return string HTML fragment
 * TODO: Render function should move to renderers!
 */
function mod_studentquiz_comment_renderer($comments, $userid, $cmid, $anonymize, $ismoderator) {

    $output = '';

    $modname = 'studentquiz';

    if (empty($comments)) {
        return html_writer::div(get_string('no_comments', $modname));
    }

    $authorids = array();
    $authors = array();

    // Collect distinct anonymous author ids chronologically.
    foreach ($comments as $comment) {
        if (!in_array($comment->userid, $authorids)) {
            $authorids[] = $comment->userid;
            $authors[] = user_get_users_by_id(array($comment->userid))[$comment->userid];
        }
    }

    $num = 0;
    $showmoreafter = 10;
    // Output comments in chronically reverse order.
    foreach (array_reverse($comments) as $comment) {
        $isfromcreator = $comment->userid == $userid;
        $canedit = $ismoderator || $isfromcreator;
        $seename = !$anonymize || $isfromcreator;

        $date = userdate($comment->created, get_string('strftimedatetime', 'langconfig'));

        if ($seename) {
            $username = fullname($authors[array_search($comment->userid, $authorids)]);
        } else {
            $username = get_string('creator_anonym_fullname', 'studentquiz')
                . ' #' . (1 + array_search($comment->userid, $authorids));
        }

        $editspan = '';
        if ($canedit) {
            $editspan = html_writer::span(get_string('remove_comment', 'studentquiz'), 'remove_action',
                    [
                            'data-id' => $comment->id,
                            'data-question_id' => $comment->questionid,
                            'tabindex' => '0',
                            'aria-label' => get_string('remove_comment_label', 'studentquiz')
                    ]
            );
        }

        $output .= html_writer::div( $editspan
            . html_writer::tag('p', $date . ' | ' . $username)
            . format_text(
                $comment->comment,
                FORMAT_MOODLE,
                array('context' => context_module::instance($cmid))
            ),
            (($num >= $showmoreafter) ? 'hidden' : '')
            . (($isfromcreator) ? 'fromcreator' : '')
        );
        $num++;
    }

    if (count($comments) > $showmoreafter) {
        $output .= html_writer::div(
            html_writer::tag('button', get_string('show_more', $modname),
                array('type' => 'button', 'class' => 'show_more btn btn-secondary')
            )
            . html_writer::tag('button', get_string('show_less', $modname)
                , array('type' => 'button', 'class' => 'show_less btn btn-secondary hidden')
            )
            , 'button_controls'
        );
    }

    return $output;
}

/**
 * Get Paginated ranking data ordered (DESC) by points, questions_created, questions_approved, rates_average
 * @param int $cmid Course module id of the StudentQuiz considered.
 * @param stdClass $quantifiers ad-hoc class containing quantifiers for weighted points score.
 * @param []int $excluderoles array of role ids to exclude
 * @param int $limitfrom return a subset of records, starting at this point (optional).
 * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
 * @return moodle_recordset of paginated ranking table
 */
function mod_studentquiz_get_user_ranking_table($cmid, $quantifiers, $excluderoles=array(), $limitfrom = 0, $limitnum = 0) {
    global $DB;
    $select = mod_studentquiz_helper_attempt_stat_select();
    $joins = mod_studentquiz_helper_attempt_stat_joins($excluderoles);
    $statsbycat = ' ) statspercategory GROUP BY userid';
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
function mod_studentquiz_community_stats($cmid) {
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
    $joins = mod_studentquiz_helper_attempt_stat_joins();
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
function mod_studentquiz_user_stats($cmid, $quantifiers, $userid) {
    global $DB;
    $select = mod_studentquiz_helper_attempt_stat_select();
    $joins = mod_studentquiz_helper_attempt_stat_joins();
    $addwhere = ' AND u.id = :userid ';
    $statsbycat = ' ) statspercategory GROUP BY userid';
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
    return "SELECT statspercategory.userid AS userid,
                   -- Aggregate values over all categories in cm context.
                   -- Note: Max() of equals is faster than Sum() of groups.
                   -- See: https://dev.mysql.com/doc/refman/5.7/en/group-by-optimization.html.
                   MAX(points) AS points, MAX(questions_created) AS questions_created,
                   MAX(questions_created_and_rated) AS questions_created_and_rated, MAX(questions_approved) AS questions_approved,
                   MAX(questions_disapproved) AS questions_disapproved,
                   MAX(rates_received) AS rates_received, MAX(rates_average) AS rates_average,
                   MAX(question_attempts) AS question_attempts, MAX(question_attempts_correct) AS question_attempts_correct,
                   MAX(question_attempts_incorrect) AS question_attempts_incorrect,
                   MAX(last_attempt_exists) AS last_attempt_exists, MAX(last_attempt_correct) AS last_attempt_correct,
                   MAX(last_attempt_incorrect) AS last_attempt_incorrect
              -- Select for each question category in context.
              FROM (
                     SELECT u.id AS userid, qc.id AS category,
                            -- Calculate points.
                            COALESCE (
                                       ROUND (
                                               -- Questions created.
                                               COALESCE(creators.countq, 0) * :questionquantifier +
                                               -- Questions approved.
                                               COALESCE(approvals.approved, 0) * :approvedquantifier +
                                               -- Rating.
                                               COALESCE(rates.avgv, 0) * (COALESCE(creators.countq, 0) -
                                                   COALESCE(rates.not_rated_questions, 0)) * :ratequantifier +
                                               -- Correct answers.
                                               COALESCE(lastattempt.last_attempt_correct, 0) * :correctanswerquantifier +
                                               -- Incorrect answers.
                                               COALESCE(lastattempt.last_attempt_incorrect, 0) * :incorrectanswerquantifier,
                                               1
                                             ),
                                       0
                                     ) AS points,
                            -- Questions created.
                            COALESCE(creators.countq, 0) AS questions_created,
                            -- Questions created and rated.
                            COALESCE(COALESCE(creators.countq, 0) - COALESCE(rates.not_rated_questions, 0),
                                0) AS questions_created_and_rated,
                            -- Questions approved.
                            COALESCE(approvals.approved, 0) AS questions_approved,
                            -- Questions disapproved.
                            COALESCE(approvals.disapproved, 0) AS questions_disapproved,
                            -- Questions rating received.
                            COALESCE(rates.countv, 0) AS rates_received,
                            COALESCE(rates.avgv, 0) AS rates_average,
                            -- Question attempts.
                            COALESCE(attempts.counta, 0) AS question_attempts,
                            COALESCE(attempts.countright, 0) AS question_attempts_correct,
                            COALESCE(attempts.countwrong, 0) AS question_attempts_incorrect,
                            -- Last attempt.
                            COALESCE(lastattempt.last_attempt_exists, 0) AS last_attempt_exists,
                            COALESCE(lastattempt.last_attempt_correct, 0) AS last_attempt_correct,
                            COALESCE(lastattempt.last_attempt_incorrect, 0) AS last_attempt_incorrect
               -- WARNING: the trailing ) is intentionally missing, found in mod_studentquiz_user_stats var statsbycat
               -- Following newline is intentional because this string is concatenated
           ";
}

/**
 * @return string
 * TODO: Refactor: There must be a better way to do this!
 */
function mod_studentquiz_helper_attempt_stat_joins($excluderoles=array()) {
    $sql = " FROM {studentquiz} sq
             -- Get this Studentquiz Question category.
             JOIN {context} con ON con.instanceid = sq.coursemodule
                  AND con.contextlevel = ".CONTEXT_MODULE."
             JOIN {question_categories} qc ON qc.contextid = con.id
             -- Only enrolled users.
             JOIN {course} c ON c.id = sq.course
             JOIN {context} cctx ON cctx.instanceid = c.id
                  AND cctx.contextlevel = ".CONTEXT_COURSE."
             JOIN {role_assignments} ra ON cctx.id = ra.contextid
             JOIN {user} u ON u.id = ra.userid";
    if (!empty($excluderoles)) {
        $sql .= "
            -- Only not excluded roles
            JOIN {role} r ON r.id = ra.roleid
                AND r.id NOT IN (".implode(',', $excluderoles).")";
    }
    $sql .= "
        -- Question created by user.
        LEFT JOIN (
                    SELECT count(*) AS countq, q.createdby AS creator
                      FROM {studentquiz} sq
                      JOIN {context} con ON con.instanceid = sq.coursemodule
                      JOIN {question_categories} qc ON qc.contextid = con.id
                      JOIN {question} q ON q.category = qc.id
                      JOIN {studentquiz_question} sqq ON q.id = sqq.questionid
                     WHERE q.hidden = 0
                           AND sqq.hidden = 0
                           AND q.parent = 0
                           AND sq.coursemodule = :cmid4
                  GROUP BY creator
                  ) creators ON creators.creator = u.id
        -- Approved questions.
        LEFT JOIN (
                    SELECT count(*) AS countq, q.createdby AS creator,
                    COUNT(CASE WHEN sqq.state = 0 THEN q.id END) as disapproved,
	                COUNT(CASE WHEN sqq.state = 1 THEN q.id END) as approved
                      FROM {studentquiz} sq
                      JOIN {context} con ON con.instanceid = sq.coursemodule
                      JOIN {question_categories} qc ON qc.contextid = con.id
                      JOIN {question} q ON q.category = qc.id
                      JOIN {studentquiz_question} sqq ON q.id = sqq.questionid
                      WHERE q.hidden = 0
                            AND q.parent = 0
                            AND sqq.hidden = 0
                            AND sq.coursemodule = :cmid5
                   GROUP BY creator
                   ) approvals ON approvals.creator = u.id
        -- Average of Average Rating of own questions.
        LEFT JOIN (
                    SELECT createdby, AVG(avg_rate_perq) AS avgv, SUM(num_rate_perq) AS countv,
                           SUM(question_not_rated) AS not_rated_questions
                      FROM (
                             SELECT q.id, q.createdby AS createdby, AVG(sqv.rate) AS avg_rate_perq,
                                    COUNT(sqv.rate) AS num_rate_perq,
                                    MAX(CASE WHEN sqv.id IS NULL THEN 1 ELSE 0 END) AS question_not_rated
                               FROM {studentquiz} sq
                               JOIN {context} con ON con.instanceid = sq.coursemodule
                               JOIN {question_categories} qc ON qc.contextid = con.id
                               JOIN {question} q ON q.category = qc.id
                          LEFT JOIN {studentquiz_rate} sqv ON q.id = sqv.questionid
                              WHERE q.hidden = 0
                                    AND q.parent = 0
                                    AND sq.coursemodule = :cmid6
                           GROUP BY q.id, q.createdby
                           ) avgratingperquestion
                  GROUP BY createdby
                  ) rates ON rates.createdby = u.id";
        $sql .= "
        LEFT JOIN (
                    SELECT sp.userid, COUNT(*) AS last_attempt_exists, SUM(lastanswercorrect) AS last_attempt_correct,
                           SUM(1 - lastanswercorrect) AS last_attempt_incorrect
                      FROM {studentquiz_progress} sp
                      JOIN {studentquiz} sq ON sq.id = sp.studentquizid
                      JOIN {question} q ON q.id = sp.questionid
                      JOIN {studentquiz_question} sqq ON sp.questionid = sqq.questionid
                     WHERE sq.coursemodule = :cmid2
                           AND q.hidden = 0
                           AND sqq.hidden = 0
                  GROUP BY sp.userid
                  ) lastattempt ON lastattempt.userid = u.id
        LEFT JOIN (
                    SELECT SUM(attempts) AS counta, SUM(correctattempts) AS countright,
                           SUM(attempts - correctattempts) AS countwrong, sp.userid AS userid
                      FROM {studentquiz_progress} sp
                      JOIN {studentquiz} sq ON sq.id = sp.studentquizid
                      JOIN {question} q ON q.id = sp.questionid
                      JOIN {studentquiz_question} sqq ON sp.questionid = sqq.questionid
                     WHERE sq.coursemodule = :cmid1
                           AND q.hidden = 0
                           AND sqq.hidden = 0
                  GROUP BY sp.userid
                  ) attempts ON attempts.userid = u.id";
    // Question attempts: sum of number of graded attempts per question.
    $sql .= "
            WHERE sq.coursemodule = :cmid3";

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
 * Lookup available system roles
 * @return array roles with identifier as key and name as value
 */
function mod_studentquiz_get_roles() {
    $roles = role_get_names();
    $return = array();
    foreach ($roles as $role) {
        $return[$role->id] = $role->localname;
    }
    return $return;
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
        $sql = "SELECT DISTINCT cm.course
                  FROM {course_modules} cm
            INNER JOIN {context} c ON cm.id = c.instanceid
            INNER JOIN {question_categories} qc ON qc.contextid = c.id
            INNER JOIN {modules} m ON cm.module = m.id
                 WHERE m.name = :modulename";
        $courseids = $DB->get_fieldset_sql($sql, array(
            'modulename' => 'studentquiz'
        ));
    }

    // Step into each course so they operate independent from each other.
    foreach ($courseids as $courseid) {
        // Import old core quiz data (question attempts) to studentquiz.
        // This is the case, when orphaned section(s) can be found.
        $sql = "SELECT id
                  FROM {course_sections}
                  WHERE course = :course
                        AND name = :name";
        $orphanedsectionids = $DB->get_fieldset_sql($sql, array(
            'course' => $courseid,
            'name' => STUDENTQUIZ_COURSE_SECTION_NAME
        ));

        if (!empty($orphanedsectionids)) {
            $oldquizzes = array();

            // For each course we need to find the studentquizzes.
            // "up" section: Only get the topmost category of that studentquiz, which isn't "top" if that one exists.
            $sql = "SELECT s.id, s.name, cm.id AS cmid, c.id AS contextid, qc.id AS categoryid, qc.name AS categoryname, qc.parent
                      FROM {studentquiz} s
                INNER JOIN {course_modules} cm ON s.id = cm.instance
                INNER JOIN {context} c ON cm.id = c.instanceid
                INNER JOIN {question_categories} qc ON qc.contextid = c.id
                INNER JOIN {modules} m ON cm.module = m.id
                 LEFT JOIN {question_categories} up ON qc.contextid = up.contextid
                           AND qc.parent = up.id
                     WHERE m.name = :modulename
                           AND cm.course = :course
                           AND (
                                 up.name = :topname1
                                 OR (
                                      up.id IS NULL
                                      AND qc.name <> :topname2
                                    )
                               )";
            $studentquizzes = $DB->get_records_sql($sql, array(
                'modulename' => 'studentquiz',
                'course' => $courseid,
                'topname1' => 'top',
                'topname2' => 'top'
            ));

            foreach ($studentquizzes as $studentquiz) {

                // Each studentquiz wants the question attempt id, which can be found inside the matching quizzes.
                $sql = "SELECT qu.id AS qusageid, q.id AS quizid, cm.id AS cmid, cm.section AS sectionid, c.id AS contextid
                         FROM {quiz} q
                   INNER JOIN {course_modules} cm ON q.id = cm.instance
                   INNER JOIN {context} c ON cm.id = c.instanceid
                   INNER JOIN {modules} m ON cm.module = m.id
                   INNER JOIN {question_usages} qu ON c.id = qu.contextid
                        WHERE m.name = :modulename
                              AND cm.course = :course
                              AND " . $DB->sql_like('q.name', ':name', false);
                $oldusages = $DB->get_records_sql($sql, array(
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
                    $sql = "SELECT DISTINCT qas.userid
                              FROM {question_attempt_steps} qas
                        INNER JOIN {question_attempts} qa ON qas.questionattemptid = qa.id
                             WHERE qa.questionusageid = :qusageid";
                    $userids = $DB->get_fieldset_sql($sql, array(
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
            }

            // So lookup the last non-empty section first.
            $orphanedsectionids[] = 0; // Force multiple entries, so next command makes a IN statement in every case.
            list($insql, $inparams) = $DB->get_in_or_equal($orphanedsectionids, SQL_PARAMS_NAMED, 'section');

            $sql = "SELECT MAX(s.section) AS max_section
                      FROM {course_sections} s
                 LEFT JOIN {course_modules} m ON s.id = m.section
                     WHERE s.course = :course
                           AND s.id NOT " . $insql . "
                           AND (
                                 m.id IS NOT NULL
                                 OR s.name <> :sectionname
                                 OR s.summary <> :sectionsummary
                               )";
            $lastnonemptysection = $DB->get_record_sql($sql, array_merge($inparams, array(
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
 *
 * @param int $id question id
 * @param int $cmid The course_module id
 */
function mod_studentquiz_ensure_studentquiz_question_record($id, $cmid) {
    global $DB;
    // Check if record exist.
    if (!$DB->count_records('studentquiz_question', array('questionid' => $id)) ) {
        $studentquiz = $DB->get_record('studentquiz', ['coursemodule' => $cmid]);
        $params = [
                'questionid' => $id,
                'state' => studentquiz_helper::STATE_NEW
        ];
        if (isset($studentquiz->publishnewquestion) && !$studentquiz->publishnewquestion) {
            $params['hidden'] = 1;
        }
        $DB->insert_record('studentquiz_question', (object) $params);
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
    $sql = "SELECT ti.id AS id, t.id AS tagid, t.name, t.rawname, ti.itemid
              FROM {tag} t
              JOIN {tag_instance} ti ON ti.tagid = t.id
             WHERE ti.itemtype = 'question' AND ti.itemid " . $insql;
    $tags = $DB->get_records_sql($sql, $params);
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
    $sql = "SELECT COUNT(*)
              FROM {studentquiz} sq
              -- Get this StudentQuiz question category.
              JOIN {context} con ON con.instanceid = sq.coursemodule
              JOIN {question_categories} qc ON qc.contextid = con.id
              -- Only enrolled users.
              JOIN {question} q ON q.category = qc.id
             WHERE q.hidden = 0
                   AND q.parent = 0
                   AND sq.coursemodule = :cmid";
    $rs = $DB->count_records_sql($sql, array('cmid' => $cmid));
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
    $sql = "SELECT COUNT(*) AS questions_available,
                   AVG(rating.avg_rating) AS average_rating,
                   SUM(CASE WHEN sqq.state = 1 THEN 1 ELSE 0 END) AS questions_approved
              FROM {studentquiz} sq
              -- Get this StudentQuiz question category.
              JOIN {context} con ON con.instanceid = sq.coursemodule
              JOIN {question_categories} qc ON qc.contextid = con.id
              -- Only enrolled users.
              JOIN {question} q ON q.category = qc.id
         LEFT JOIN {studentquiz_question} sqq ON sqq.questionid = q.id
         LEFT JOIN (
                     SELECT q.id AS questionid, COALESCE(AVG(sqr.rate),0) AS avg_rating
                       FROM {studentquiz} sq
                       JOIN {context} con ON con.instanceid = sq.coursemodule
                       JOIN {question_categories} qc ON qc.contextid = con.id
                       JOIN {question} q ON q.category = qc.id
                  LEFT JOIN {studentquiz_rate} sqr ON sqr.questionid = q.id
                      WHERE sq.coursemodule = :cmid2
                   GROUP BY q.id
                   ) rating ON rating.questionid = q.id
             WHERE q.hidden = 0
                   AND sqq.hidden = 0
                   AND q.parent = 0
                   AND sq.coursemodule = :cmid1";
    $rs = $DB->get_record_sql($sql, array('cmid1' => $cmid, 'cmid2' => $cmid));
    return $rs;
}

/**
 * Fix parent of question categories of StudentQuiz.
 * Old StudentQuiz had the parent of question categories not equalling to 0 for various reasons, but they should.
 * In Moodle < 3.5 there is no "top" parent category, so the question category itself has to be corrected if it's not 0.
 * In Moodle >= 3.5 there is a new "top" parent category, so the question category of StudentQuiz has to have that as parent.
 * See https://tracker.moodle.org/browse/MDL-61132 and its diff.
 *
 * This function must be usable for the restore and the plugin update process.
 */
function mod_studentquiz_fix_wrong_parent_in_question_categories() {
    global $DB;

    if (function_exists('question_get_top_category')) { // We have a moodle with "top" category feature.
        $sql = "SELECT qc.id, qc.contextid, qc.name, qc.parent
                  FROM {question_categories} qc
            INNER JOIN {context} c ON qc.contextid = c.id
            INNER JOIN {course_modules} cm ON c.instanceid = cm.id
            INNER JOIN {modules} m ON cm.module = m.id
             LEFT JOIN {question_categories} up ON qc.contextid = up.contextid
                       AND qc.parent = up.id
                 WHERE m.name = :modulename
                       AND up.name IS NULL
                       AND qc.name <> :topname";
        $categorieswithouttop = $DB->get_records_sql($sql, array(
            'modulename' => 'studentquiz',
            'topname' => 'top'
        ));
        foreach ($categorieswithouttop as $currentcat) {
            $topcat = question_get_top_category($currentcat->contextid, true);
            // Now set the parent to the newly created top id.
            $DB->set_field('question_categories', 'parent', $topcat->id, array('id' => $currentcat->id));
        }
    } else {
        $sql = "SELECT qc.id, qc.contextid, qc.name, qc.parent
                  FROM {question_categories} qc
            INNER JOIN {context} c ON qc.contextid = c.id
            INNER JOIN {course_modules} cm ON c.instanceid = cm.id
            INNER JOIN {modules} m ON cm.module = m.id
             LEFT JOIN {question_categories} up ON qc.contextid = up.contextid
                       AND qc.parent = up.id
                 WHERE m.name = :modulename
                       AND up.id IS NULL
                       AND qc.parent <> 0";
        $categorieswithoutparent = $DB->get_records_sql($sql, array(
                'modulename' => 'studentquiz'
        ));
        foreach ($categorieswithoutparent as $currentcat) {
            // Now set the parent to 0.
            $DB->set_field('question_categories', 'parent', 0, array('id' => $currentcat->id));
        }
    }
}

/**
 * Check that StudentQuiz is allowing answering or not.
 *
 * @param int $openform Open date
 * @param int $closefrom Close date
 * @param string $type submission or answering
 * @return array Message and Allow or not
 */
function mod_studentquiz_check_availability($openform, $closefrom, $type) {
    $message = '';
    $availabilityallow = true;

    if (time() < $openform) {
        $availabilityallow = false;
        $message = get_string('before_' . $type . '_start_date', 'studentquiz',
                userdate($openform, get_string('strftimedatetimeshort', 'langconfig')));
    } else if (time() < $closefrom) {
        $message = get_string('before_' . $type . '_end_date', 'studentquiz',
                userdate($closefrom, get_string('strftimedatetimeshort', 'langconfig')));
    }
    if ($closefrom && time() >= $closefrom) {
        $availabilityallow = false;
        $message = get_string('after_' . $type . '_end_date', 'studentquiz',
                userdate($closefrom, get_string('strftimedatetimeshort', 'langconfig')));
    }

    return [$message, $availabilityallow];
}

/**
 * Saves question rating.
 *
 * // TODO:
 * @param  stdClass $data requires userid, questionid, rate
 * @internal param $course
 * @internal param $module
 */
function mod_studentquiz_save_rate($data) {
    global $DB, $USER;

    $row = $DB->get_record('studentquiz_rate', array('userid' => $USER->id, 'questionid' => $data->questionid));
    if ($row === false) {
        $DB->insert_record('studentquiz_rate', $data);
    } else {
        $DB->update_record('studentquiz_rate', $row);
    }
}

/**
 * Saves question comment.
 *
 * // TODO:
 * @param  stdClass $data requires userid, questionid, comment
 * @param $course
 * @param $module
 */
function mod_studentquiz_save_comment($data, $course, $module) {
    global $DB;

    $data->created = usertime(time(), usertimezone());
    $DB->insert_record('studentquiz_comment', $data);
    mod_studentquiz_notify_comment_added($data, $course, $module);
}

/**
 * Deletes question comment.
 *
 * // TODO:
 * @param  stdClass $data requires commentid
 * @param $course
 * @param $module
 * @return bool success
 */
function mod_studentquiz_delete_comment($commentid, $course, $module) {
    global $DB, $USER;

    $success = false;
    $comment = $DB->get_record('studentquiz_comment', array('id' => $commentid));
    // The manager is allowed to delete any comment and additionally sends a notification.
    if (mod_studentquiz_check_created_permission($module->id)) {
        $success = $DB->delete_records('studentquiz_comment', array('id' => $commentid));
        mod_studentquiz_notify_comment_deleted($comment, $course, $module);
    } else {
        // Only the student can delete his own comment.
        $success = $DB->delete_records('studentquiz_comment', array('id' => $commentid, 'userid' => $USER->id));
    }
    return $success;
}

/**
 * Compare and create new record for studentquiz_questions table if need. Only for Moodle version smaller than 3.7
 *
 * @param object $studentquiz StudentQuiz object
 * @throws dml_exception
 */
function mod_studentquiz_compare_questions_data($studentquiz) {
    global $DB, $CFG;
    if ($CFG->branch >= 37) {
        return;
    }
    $sql = "SELECT q.id
              FROM {studentquiz} sq
              JOIN {context} con ON con.instanceid = sq.coursemodule
              JOIN {question_categories} qc ON qc.contextid = con.id
              JOIN {question} q ON q.category = qc.id
             WHERE q.hidden = 0
                   AND q.parent = 0
                   AND sq.coursemodule = :coursemodule
                   AND qc.id = :categoryid
                   AND q.id NOT IN (SELECT questionid FROM {studentquiz_question} WHERE state != 0)";

    $params = [
            'coursemodule' => $studentquiz->coursemodule,
            'categoryid' => $studentquiz->categoryid
    ];

    $missingquestions = $DB->get_records_sql($sql, $params);
    if ($missingquestions) {
        foreach ($missingquestions as $missingquestion) {
            mod_studentquiz_ensure_studentquiz_question_record($missingquestion->id, $studentquiz->coursemodule);
        }
    }
}
