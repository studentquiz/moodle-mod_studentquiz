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
use mod_studentquiz\utils;
use \core_question\local\bank\question_version_status;
use \mod_studentquiz\local\studentquiz_progress;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

/** @var string default StudentQuiz quiz behaviour */
const STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR = 'immediatefeedback';

/**
 * Load studentquiz from coursemodule id
 *
 * @param int $cmid course module id
 * @param int $contextid id of the context of this course module
 * @return stdClass|bool studentquiz or false
 * TODO: Should we refactor dependency on questionlib by inserting category as parameter?
 */
function mod_studentquiz_load_studentquiz($cmid, $contextid) {
    global $DB;
    if ($studentquiz = $DB->get_record('studentquiz', array('coursemodule' => $cmid))) {
        if ($contextid !== false) {
            // It seems there are studentquiz instances missing the default category, we've not expected that case
            // so far. The question bank page calls the function question_make_default_categories() through
            // question_build_edit_resources() on every page view so we'd honor that behavior here as well now.
            // The function question_make_default_categories() will return the existing category if it exists.
            $context = \context::instance_by_id($contextid);
            $studentquiz->category = question_make_default_categories(array($context));
            $studentquiz->categoryid = $studentquiz->category->id;

            return $studentquiz;
        } else {
            return $studentquiz;
        }
    }
    return false;
}

/**
 * Make studentquiz progress object
 *
 * @param int $questionid
 * @param int $userid
 * @param int $studentquizid
 * @param int $sqqid studentquiz question Id.
 * @param int $lastanswercorrect
 * @param int $attempts
 * @param int $correctattempts
 * @param int $lastreadprivatecomment
 * @param int $lastreadpubliccomment
 * @return stdClass
 */
function mod_studentquiz_get_studenquiz_progress_class($questionid, $userid, $studentquizid, $sqqid, $lastanswercorrect = 0,
    $attempts = 0, $correctattempts = 0, $lastreadprivatecomment = 0, $lastreadpubliccomment = 0) {
    $studentquizprogress = new stdClass();
    $studentquizprogress->questionid = $questionid;
    $studentquizprogress->userid = $userid;
    $studentquizprogress->studentquizid = $studentquizid;
    $studentquizprogress->studentquizquestionid = $sqqid;
    $studentquizprogress->lastanswercorrect = $lastanswercorrect;
    $studentquizprogress->attempts = $attempts;
    $studentquizprogress->correctattempts = $correctattempts;
    $studentquizprogress->lastreadprivatecomment = $lastreadprivatecomment;
    $studentquizprogress->lastreadpubliccomment = $lastreadpubliccomment;
    return $studentquizprogress;
}

/**
 * Migrates all studentquizes that are not yet aggregated to the aggreated state.
 *
 * If it fails, try the following:
 *  - Set all entries in the table studentquiz to aggregated = 0
 *  - Truncate the table studentquiz_progress
 *  - Retry
 *
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
 * @param stdClass $studentquiz
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
 */
function mod_studentquiz_get_studentquiz_progress_from_question_attempts_steps($studentquizid, $context) {
    global $DB;

    $sql = "SELECT innerq.questionid, innerq.userid, innerq.attempts, innerq.correctattempts, sqq.id as studentquizquestionid,
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
                    WHERE qu_last.contextid = :contextid2
                      AND qa_last.questionid = innerq.questionid
                      AND qas_last.userid = innerq.userid
                      AND qas_last.state IN (:rightstate1, :partialstate1, :wrongstate1)
                   )
              JOIN {question_versions} qv ON innerq.questionid = qv.questionid
              JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
              JOIN {question_references} qr ON qbe.id = qr.questionbankentryid
                   AND qr.component = 'mod_studentquiz'
                   AND qr.questionarea = 'studentquiz_question'
              JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid
              ";
    $records = $DB->get_recordset_sql($sql, array(
            'rightstate2' => (string) question_state::$gradedright, 'rightstate3' => (string) question_state::$gradedright,
            'contextid1' => $context->id, 'contextid2' => $context->id,
            'rightstate' => (string) question_state::$gradedright, 'partialstate' => (string) question_state::$gradedpartial,
            'wrongstate' => (string) question_state::$gradedwrong, 'rightstate1' => (string) question_state::$gradedright,
            'partialstate1' => (string) question_state::$gradedpartial, 'wrongstate1' => (string) question_state::$gradedwrong,
            ));
    $studentquizprogresses = [];

    foreach ($records as $r) {
        $time = time();
        $studentquizprogress = new studentquiz_progress($r->questionid, $r->userid, $studentquizid, $r->studentquizquestionid,
            $r->lastanswercorrect, $r->attempts, $r->correctattempts, $time, $time);
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
 *
 * @param int $cmid
 * @return bool
 */
function mod_studentquiz_check_created_permission($cmid) {
    $context = context_module::instance($cmid);
    return has_capability('mod/studentquiz:manage', $context);
}

/**
 * Prepare message for notify.
 *
 * @param \mod_studentquiz\local\studentquiz_question $studentquizquestion object
 * @param stdClass $recepient user object receiving the notification
 * @param stdClass $actor user object triggering the notification
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return stdClass Data object with course, module, question, student and teacher info
 */
function mod_studentquiz_prepare_notify_data($studentquizquestion, $recepient, $actor, $course, $module) {
    // Get StudentQuiz.
    $studentquiz = $studentquizquestion->get_studentquiz();
    $question = $studentquizquestion->get_question();
    // Prepare message.
    $time = new DateTime('now', core_date::get_user_timezone_object());

    $data = new stdClass();

    // Course info.
    $data->courseid        = $course->id;
    $data->coursename      = $course->fullname;
    $data->courseshortname = $course->shortname;

    // Module info.
    $data->modulename      = $module->name;
    $data->moduleid = $module->id;

    // Question info.
    $data->questionname    = $question->name;
    $questionurl = new moodle_url('/mod/studentquiz/preview.php', ['cmid' => $module->id,
            'studentquizquestionid' => $studentquizquestion->get_id()]);
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
    $data->actorusername = $actor->username;

    // Set to anonymous student and manager if needed.
    $context = \context_course::instance($course->id);
    $isstudent = !is_enrolled($context, $recepient->id, 'mod/studentquiz:manage');
    $data->isstudent = $isstudent;
    if ($studentquiz->anonymrank) {
        $anonymousstudent = get_string('creator_anonym_fullname', 'studentquiz');
        $anonymousmanager = get_string('manager_anonym_fullname', 'studentquiz');
        $data->recepientname = $isstudent ? $anonymousstudent : $anonymousmanager;
        $data->actorname = $isstudent ? $anonymousmanager : $anonymousstudent;
    }

    // Notification settings.
    $data->digesttype = $studentquiz->digesttype;
    $data->digestfirstday = $studentquiz->digestfirstday;

    return $data;
}

/**
 * Notify student that someone has change the state / visibility of his question. (Info to question author)
 *
 * @param \mod_studentquiz\local\studentquiz_question $studentquizquestion Id of the student quiz
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @param string $type Type of change
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_state_notify($studentquizquestion, $course, $module, $type) {
    if ($type == 'state') {
        $state = $studentquizquestion->get_state();
        $states = [
                studentquiz_helper::STATE_DISAPPROVED => 'disapproved',
                studentquiz_helper::STATE_APPROVED => 'approved',
                studentquiz_helper::STATE_NEW => 'new',
                studentquiz_helper::STATE_CHANGED => 'changed',
                studentquiz_helper::STATE_REVIEWABLE => 'reviewable',
        ];
        $event = $states[$state];
    } else {
        $event = $type;
    }
    return mod_studentquiz_event_notification_question($event, $studentquizquestion, $course, $module);
}

/**
 * Notify student that someone has commented to his question. (Info to question author)
 * @param stdClass $comment that was just added to the question
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
 * @param stdClass $comment that was just added to the question
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
 * Notify question to teacher/tutor that an event occurred when the author change question's state to reviewable.
 * @param \mod_studentquiz\local\studentquiz_question $studentquizquestion SQQ instance.
 * @param stdClass $course Course object.
 * @param stdClass $module Course module object.
 */
function mod_studentquiz_notify_reviewable_question($studentquizquestion, stdClass $course, stdClass $module) {
    global $USER;
    $context = \context_course::instance($course->id);
    $actor = \core_user::get_user($USER->id);
    $recipients = get_enrolled_users($context, 'mod/studentquiz:emailnotifyreviewablequestion', 0, 'u.*', null, 0, 0, true);
    foreach ($recipients as $recipient) {
        $data = mod_studentquiz_prepare_notify_data($studentquizquestion, $recipient, $actor, $course, $module);
        mod_studentquiz_send_notification(studentquiz_helper::$statename[studentquiz_helper::STATE_REVIEWABLE],
            $recipient, $actor, $data);
    }
}

/**
 * Notify question author that an event occured when the autor has this capabilty
 * @param string $event The name of the event, used to automatically get capability and mail contents
 * @param \mod_studentquiz\local\studentquiz_question $studentquizquestion ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_event_notification_question($event, $studentquizquestion, $course, $module) {
    global $USER;

    $question = $studentquizquestion->get_question();

    // Creator and Actor must be different.
    if ($question->createdby != $USER->id) {
        $users = user_get_users_by_id(array($question->createdby, $USER->id));
        $recipient = $users[$question->createdby];
        $actor = $users[$USER->id];
        $data = mod_studentquiz_prepare_notify_data($studentquizquestion, $recipient, $actor, $course, $module);

        return mod_studentquiz_send_notification($event, $recipient, $actor, $data);
    }
    return false;
}

/**
 * Notify question author that an event occured when the autor has this capabilty
 * @param string $event The name of the event, used to automatically get capability and mail contents
 * @param stdClass $comment that was just added to the question
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_event_notification_comment($event, $comment, $course, $module) {
    global $USER;
    $studentquizquestionid = $comment->studentquizquestionid;
    $studentquizquestion = new \mod_studentquiz\local\studentquiz_question($studentquizquestionid);
    $question = $studentquizquestion->get_question();
    // Creator and Actor must be different.
    // If the comment and question is the same recipient, only send the minecomment notification (see function below).
    if ($question->createdby != $USER->id && $comment->userid != $question->createdby) {
        $users = user_get_users_by_id(array($question->createdby, $USER->id));
        $recipient = $users[$question->createdby];
        $actor = $users[$USER->id];
        $data = mod_studentquiz_prepare_notify_data($studentquizquestion, $recipient, $actor, $course, $module);
        $data->commenttext = $comment->comment;
        $data->commenttime = userdate($comment->created, get_string('strftimedatetime', 'langconfig'));

        return mod_studentquiz_send_comment_notification('comment' . $event, $recipient, $actor, $data);
    }

    return false;
}

/**
 * Notify question author that an event occured when the autor has this capabilty
 * @param string $event The name of the event, used to automatically get capability and mail contents
 * @param stdClass $comment that was just added to the question
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_event_notification_minecomment($event, $comment, $course, $module) {
    global $USER;

    $studentquizquestionid = $comment->studentquizquestionid;
    $studentquizquestion = new \mod_studentquiz\local\studentquiz_question($studentquizquestionid);
    $question = $studentquizquestion->get_question();
    // Creator and Actor must be different.
    if ($comment->userid != $USER->id) {
        $users = user_get_users_by_id(array($comment->userid, $USER->id));
        $recipient = $users[$comment->userid];
        $actor = $users[$USER->id];
        $data = mod_studentquiz_prepare_notify_data($studentquizquestion, $recipient, $actor, $course, $module);
        $data->commenttext = $comment->comment;
        $data->commenttime = userdate($comment->created, get_string('strftimedatetime', 'langconfig'));

        return mod_studentquiz_send_comment_notification('minecomment' . $event, $recipient, $actor, $data);
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
 * @return int|false as for {@see message_send()}.
 */
function mod_studentquiz_send_notification($event, $recipient, $submitter, $data) {
    global $DB;
    $customdata = [
            'eventname' => $event,
            'courseid' => $data->courseid,
            'submitter' => $submitter,
            'recipient' => $recipient,
            'messagedata' => $data,
            'questionurl' => $data->questionurl,
            'questionname' => $data->questionname,
    ];
    if ($data->digesttype == 0) {
        $task = new \mod_studentquiz\task\send_no_digest_notification_task();
        $task->set_custom_data($customdata);
        $task->set_component('mod_studentquiz');
        \core\task\manager::queue_adhoc_task($task);
    } else {
        date_default_timezone_set('UTC');
        $notificationqueue = new stdClass();
        $notificationqueue->studentquizid = $data->moduleid;
        $notificationqueue->content = serialize($customdata);
        $notificationqueue->recipientid = $recipient->id;
        if ($data->digesttype == 1) {
            $notificationqueue->timetosend = strtotime(date('Y-m-d'));
        } else {
            $digestfirstday = $data->digestfirstday;
            $notificationqueue->timetosend = \mod_studentquiz\utils::calculcate_notification_time_to_send($digestfirstday);
        }
        $DB->insert_record('studentquiz_notification', $notificationqueue);
    }
}

/**
 * Send notification for comment
 *
 * @todo Support this feature in {@see mod_studentquiz_send_notification} for the next release.
 *
 * @param string $event message event string
 * @param stdClass $recipient user object of the intended recipient
 * @param stdClass $submitter user object of the sender
 * @param stdClass $data object of replaceable fields for the templates
 *
 * @return int|false as for {@see message_send()}.
 */
function mod_studentquiz_send_comment_notification($event, $recipient, $submitter, $data) {
    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->component = 'mod_studentquiz';
    $eventdata->name = $event;
    $eventdata->notification = 1;
    $eventdata->courseid = $data->courseid;
    $eventdata->userfrom = $submitter;
    $eventdata->userto = $recipient;
    $eventdata->subject = get_string('email' . $event . 'subject', 'studentquiz', $data);
    $eventdata->smallmessage = get_string('email' . $event . 'small', 'studentquiz', $data);
    $eventdata->fullmessage = get_string('email' . $event . 'body', 'studentquiz', $data);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml = '';

    $eventdata->contexturl = $data->questionurl;
    $eventdata->contexturlname = $data->questionname;

    // ... and send it.
    return message_send($eventdata);
}

/**
 * Generate an attempt with question usage
 * @param array $ids of question ids to be used in this attempt
 * @param stdClass $studentquiz generating this attempt
 * @param int $userid attempting this StudentQuiz
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
 * Add question to attempt.
 *
 * @param stdClass $questionusage question_usage_by_activity
 * @param stdClass $studentquiz generating this attempt
 * @param array $questionids of question ids to be used in this attempt
 * @param int $lastslot
 */
function mod_studentquiz_add_question_to_attempt(&$questionusage, $studentquiz, &$questionids, $lastslot = 0) {
    $allowedcategories = question_categorylist($studentquiz->categoryid);
    $i = $lastslot;
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
 *
 * @param array $rawdata from REQUEST
 * @return array
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
 * Lookup available question types.
 * @return array question types with identifier as key and name as value
 */
function mod_studentquiz_get_question_types() {
    $returntypes = array();
    $types = question_bank::get_creatable_qtypes();

    // Filter out question types which can't be graded automatically.
    foreach ($types as $name => $qtype) {
        if (!$qtype->is_real_question_type() || $qtype->is_manual_graded()) {
            unset($types[$name]);
        }
    }

    // Get the translated name for displaying purposes.
    foreach ($types as $name => $qtype) {
        if ($name != 'randomsamatch') {
            $returntypes[$name] = $qtype->local_name();
        }
    }

    return $returntypes;
}

/**
 * Get key name of question types
 *
 * @return array
 */
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
 * This is a helper to ensure we have a studentquiz_question record for a specific question
 *
 * @param int $id question id
 * @param int $cmid The course_module id
 * @param bool $honorpublish Honor the setting publishnewquestions
 * @param bool $hidden If the question should be hidden, only used if $honorpublish is false
 */
function mod_studentquiz_ensure_studentquiz_question_record($id, $cmid, $honorpublish = true, $hidden = true) {
    global $DB, $USER;
    $params = [
        'questionid' => $id,
    ];
    // Check if record exist.
    $sql = "SELECT COUNT(*)
              FROM {studentquiz} sq
              -- Get this StudentQuiz question.
              JOIN {studentquiz_question} sqq ON sqq.studentquizid = sq.id
              JOIN {question_references} qr ON qr.itemid = sqq.id
                   AND qr.component = 'mod_studentquiz'
                   AND qr.questionarea = 'studentquiz_question'
              JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
              JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                      SELECT MAX(version)
                                        FROM {question_versions}
                                       WHERE questionbankentryid = qbe.id
                                  )
              -- Only enrolled users.
              JOIN {question} q ON q.id = qv.questionid
             WHERE q.id = :questionid
    ";
    if (!$DB->count_records_sql($sql, $params)) {
        $studentquiz = $DB->get_record('studentquiz', ['coursemodule' => $cmid]);
        $cm = get_coursemodule_from_id('studentquiz', $cmid);
        $groupid = groups_get_activity_group($cm, true);
        $params = [
                'studentquizid' => $studentquiz->id,
                'state' => studentquiz_helper::STATE_NEW,
                'groupid' => $groupid
        ];
        if ($honorpublish) {
            if (isset($studentquiz->publishnewquestion) && !$studentquiz->publishnewquestion) {
                $params['hidden'] = 1;
            }
        } else {
            if ($hidden) {
                $params['hidden'] = 1;
            }
        }
        $record = $DB->insert_record('studentquiz_question', (object) $params);
        utils::question_save_action($record, $USER->id, studentquiz_helper::STATE_NEW);
        if ($honorpublish && isset($studentquiz->publishnewquestion) && $studentquiz->publishnewquestion) {
            utils::question_save_action($record, null, studentquiz_helper::STATE_SHOW);
        }
        // Load question to create a question references.
        $question = question_bank::load_question($id);
        $contextid = context_module::instance($cmid)->id;
        $referenceparams = [
                'usingcontextid' => $contextid,
                'itemid' => $record,
                'component' => 'mod_studentquiz',
                'questionarea' => 'studentquiz_question',
                'questionbankentryid' => $question->questionbankentryid,
                'version' => null
        ];
        $DB->insert_record('question_references', (object) $referenceparams);
    }
}

/**
 * Count questions in a coursemodule
 *
 * @param int $cmid
 * @return int
 */
function mod_studentquiz_count_questions($cmid) {
    global $DB;

    $sql = "SELECT COUNT(*)
              FROM {studentquiz} sq
              -- Get this StudentQuiz question.
              JOIN {studentquiz_question} sqq ON sqq.studentquizid = sq.id
              JOIN {question_references} qr ON qr.itemid = sqq.id
                   AND qr.component = 'mod_studentquiz'
                   AND qr.questionarea = 'studentquiz_question'
              JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
              JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                      SELECT MAX(version)
                                        FROM {question_versions}
                                       WHERE questionbankentryid = qbe.id AND status = :ready
                                  )
              -- Only enrolled users.
              JOIN {question} q ON q.id = qv.questionid
             WHERE q.parent = 0
                   AND sq.coursemodule = :cmid";
    $rs = $DB->count_records_sql($sql, ['cmid' => $cmid, 'ready' => question_version_status::QUESTION_STATUS_READY]);

    return $rs;
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
 * @param stdClass $data requires userid, questionid, rate
 */
function mod_studentquiz_save_rate($data) {
    global $DB;

    $row = $DB->get_record('studentquiz_rate', ['userid' => $data->userid,
        'studentquizquestionid' => $data->studentquizquestionid]);
    if ($row === false) {
        $DB->insert_record('studentquiz_rate', $data);
    } else {
        $data->id = $row->id;
        $DB->update_record('studentquiz_rate', $data);
    }
}

/**
 * Compare and create new record for studentquiz_questions table if needed.
 *
 * @param object $studentquiz StudentQuiz object
 * @param bool $honorpublish Honor the setting publishnewquestions
 * @param bool $hidden If the question should be hidden, only used if $honorpublish is false
 */
function mod_studentquiz_compare_questions_data($studentquiz, $honorpublish = true, $hidden = true) {
    global $DB;

    $sql = "SELECT q.id
              FROM {studentquiz} sq
              JOIN {context} con ON con.instanceid = sq.coursemodule
              JOIN {question_categories} qc ON qc.contextid = con.id
              JOIN {question_bank_entries} qbe ON qc.id = qbe.questioncategoryid
              JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
              -- Only enrolled users.
              JOIN {question} q ON q.id = qv.questionid
             WHERE q.parent = 0
                   AND sq.coursemodule = :coursemodule
                   AND qc.id = :categoryid
                   AND qbe.id NOT IN (SELECT qr.questionbankentryid
                                            FROM {studentquiz_question} sqq2
                                            JOIN {question_references} qr ON qr.itemid = sqq2.id
                                                 AND qr.component = 'mod_studentquiz'
                                                 AND qr.questionarea = 'studentquiz_question'
                                           WHERE state != 0)";

    $params = [
        'coursemodule' => $studentquiz->coursemodule,
        'categoryid' => $studentquiz->categoryid
    ];

    $missingquestions = $DB->get_records_sql($sql, $params);
    if ($missingquestions) {
        foreach ($missingquestions as $missingquestion) {
            mod_studentquiz_ensure_studentquiz_question_record(
                $missingquestion->id, $studentquiz->coursemodule, $honorpublish, $hidden
            );
        }
    }
}

/**
 * Adds the default state to questions for restores since there's a bug in the moodle code.
 * ref: https://tracker.moodle.org/browse/MDL-67406
 *
 * Finds all the questions missing the state information and writes the default state for imports
 * into the database.
 *
 * @param int|null $courseorigid
 */
function mod_studentquiz_fix_all_missing_question_state_after_restore($courseorigid=null) {
    global $DB;

    $params = array();
    if (!empty($courseorigid)) {
        $params['course'] = $courseorigid;
    }

    $studentquizes = $DB->get_records('studentquiz', $params);
    $transaction = $DB->start_delegated_transaction();

    try {
        foreach ($studentquizes as $studentquiz) {
            $context = \context_module::instance($studentquiz->coursemodule);
            $studentquiz = mod_studentquiz_load_studentquiz($studentquiz->coursemodule, $context->id);
            mod_studentquiz_compare_questions_data($studentquiz, false, false);
        }
        $DB->commit_delegated_transaction($transaction);
    } catch (Exception $e) {
        $DB->rollback_delegated_transaction($transaction, $e);
        throw new Exception($e->getMessage());
    }
}

/**
 * Init permission and related data for single action page in SQ.
 *
 * @param stdClass $module
 * @param int $studentquizquestionid
 * @return \mod_studentquiz\local\studentquiz_question
 */
function mod_studentquiz_init_single_action_page($module, $studentquizquestionid): \mod_studentquiz\local\studentquiz_question {
    $context = context_module::instance($module->id);
    try {
        $studentquiz = mod_studentquiz_load_studentquiz($module->id, $context->id);
        $studentquizquestion = new \mod_studentquiz\local\studentquiz_question($studentquizquestionid,
                null, $studentquiz, $module, $context);
    } catch (moodle_exception $e) {
        throw new moodle_exception("invalidconfirmdata', 'error");
    }
    $questionid = $studentquizquestion->get_question()->id;
    question_require_capability_on($questionid, 'edit');
    return $studentquizquestion;
}
