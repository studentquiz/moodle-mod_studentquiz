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
 * Privacy Subsystem implementation for mod_studentquiz.
 *
 * @package    mod_studentquiz
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\privacy;

defined('MOODLE_INTERNAL') || die();

use core_form\util;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\transform;
use mod_studentquiz\commentarea\container;
use mod_studentquiz\local\studentquiz_helper;
use mod_studentquiz\utils;

interface studentquiz_userlist extends \core_privacy\local\request\core_userlist_provider {
}

require_once($CFG->libdir . '/questionlib.php');

/**
 * Implementation of the privacy subsystem plugin provider for the StudentQuiz activity module.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\user_preference_provider,
        studentquiz_userlist {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('studentquiz_rate', [
            'rate' => 'privacy:metadata:studentquiz_rate:rate',
            'questionid' => 'privacy:metadata:studentquiz_rate:studentquizquestionid',
            'userid' => 'privacy:metadata:studentquiz_rate:userid'
        ], 'privacy:metadata:studentquiz_rate');
        $collection->add_database_table('studentquiz_progress', [
            'questionid' => 'privacy:metadata:studentquiz_progress:studentquizquestionid',
            'userid' => 'privacy:metadata:studentquiz_progress:userid',
            'studentquizid' => 'privacy:metadata:studentquiz_progress:studentquizid',
            'lastanswercorrect' => 'privacy:metadata:studentquiz_progress:lastanswercorrect',
            'attempts' => 'privacy:metadata:studentquiz_progress:attempts',
            'correctattempts' => 'privacy:metadata:studentquiz_progress:correctattempts',
            'lastreadprivatecomment' => 'privacy:metadata:studentquiz_progress:lastreadprivatecomment',
            'lastreadpubliccomment' => 'privacy:metadata:studentquiz_progress:lastreadpubliccomment'
        ], 'privacy:metadata:studentquiz_progress');

        $collection->add_database_table('studentquiz_comment', [
            'comment' => 'privacy:metadata:studentquiz_comment:comment',
            'questionid' => 'privacy:metadata:studentquiz_comment:studentquizquestionid',
            'userid' => 'privacy:metadata:studentquiz_comment:userid',
            'created' => 'privacy:metadata:studentquiz_comment:created',
            'parentid' => 'privacy:metadata:studentquiz_comment:parentid',
            'status' => 'privacy:metadata:studentquiz_comment:status',
            'type' => 'privacy:metadata:studentquiz_comment:type',
            'timemodified' => 'privacy:metadata:studentquiz_comment:timemodified',
            'usermodified' => 'privacy:metadata:studentquiz_comment:usermodified'

        ], 'privacy:metadata:studentquiz_comment');

        $collection->add_database_table('studentquiz_comment_history', [
            'commentid' => 'privacy:metadata:studentquiz_comment_history:commentid',
            'content' => 'privacy:metadata:studentquiz_comment_history:content',
            'userid' => 'privacy:metadata:studentquiz_comment_history:userid',
            'action' => 'privacy:metadata:studentquiz_comment_history:action',
            'timemodified' => 'privacy:metadata:studentquiz_comment_history:timemodified',
        ], 'privacy:metadata:studentquiz_comment_history');

        $collection->add_database_table('studentquiz_question', [
            'questionid' => 'privacy:metadata:studentquiz_question:studentquizid',
            'state' => 'privacy:metadata:studentquiz_question:state',
            'hidden' => 'privacy:metadata:studentquiz_question:hidden',
            'pinned' => 'privacy:metadata:studentquiz_question:pinned',
            'groupid' => 'privacy:metadata:studentquiz_question:groupid'
        ], 'privacy:metadata:studentquiz_question');

        $collection->add_database_table('studentquiz_attempt', [
            'studentquizid' => 'privacy:metadata:studentquiz_attempt:studentquizid',
            'userid' => 'privacy:metadata:studentquiz_attempt:userid',
            'questionusageid' => 'privacy:metadata:studentquiz_attempt:questionusageid',
            'categoryid' => 'privacy:metadata:studentquiz_attempt:categoryid'
        ], 'privacy:metadata:studentquiz_attempt');

        $collection->add_database_table('studentquiz_notification', [
            'studentquizid' => 'privacy:metadata:studentquiz_notification:studentquizid',
            'content' => 'privacy:metadata:studentquiz_notification:content',
            'recipientid' => 'privacy:metadata:studentquiz_notification:recipientid',
            'status' => 'privacy:metadata:studentquiz_notification:status',
            'timetosend' => 'privacy:metadata:studentquiz_notification:timetosend'
        ], 'privacy:metadata:studentquiz_attempt');

        $collection->add_database_table('studentquiz_state_history', [
            'questionid' => 'privacy:metadata:studentquiz_state_history:studentquizquestionid',
            'userid' => 'privacy:metadata:studentquiz_state_history:userid',
            'state' => 'privacy:metadata:studentquiz_state_history:state',
            'timecreated' => 'privacy:metadata:studentquiz_state_history:timecreated'
        ], 'privacy:metadata:studentquiz_attempt');

        $collection->add_user_preference(container::USER_PREFERENCE_SORT, 'privacy:metadata:' . container::USER_PREFERENCE_SORT);
        $collection->add_user_preference(utils::USER_PREFERENCE_QUESTION_ACTIVE_TAB,
            'privacy:metadata:' . utils::USER_PREFERENCE_QUESTION_ACTIVE_TAB);

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Get activity context if user created/modified the question or their data exist in these table
        // base on user ID field: rate, comment, progress, attempt.
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {studentquiz} sq ON sq.coursemodule = ctx.instanceid
                       AND contextlevel = :contextmodule
                  JOIN {question_categories} ca ON ca.contextid = ctx.id
             LEFT JOIN {question_bank_entries} qbe ON ca.id = qbe.questioncategoryid
             LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
             LEFT JOIN {question} q ON q.id = qv.questionid
             LEFT JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
             LEFT JOIN {studentquiz_question} question ON question.studentquizid = sq.id AND question.id = qr.itemid
             LEFT JOIN {studentquiz_rate} rate ON rate.studentquizquestionid = question.id
             LEFT JOIN {studentquiz_comment} comment ON comment.studentquizquestionid = question.id
             LEFT JOIN {studentquiz_progress} progress ON progress.studentquizquestionid = question.id
                       AND progress.studentquizid = sq.id
             LEFT JOIN {studentquiz_attempt} attempt ON attempt.categoryid = ca.id
                       AND attempt.studentquizid = sq.id
             LEFT JOIN {studentquiz_comment_history} commenthistory ON commenthistory.commentid = comment.id
             LEFT JOIN {studentquiz_notification} notificationjoin ON notificationjoin.studentquizid = sq.id
             LEFT JOIN {studentquiz_state_history} statehistory ON statehistory.studentquizquestionid = question.id
                 WHERE (
                         question.id IS NOT NULL
                         OR rate.id IS NOT NULL
                         OR comment.id IS NOT NULL
                         OR progress.studentquizquestionid IS NOT NULL
                         OR attempt.id IS NOT NULL
                         OR commenthistory.id IS NOT NULL
                         OR statehistory.id IS NOT NULL
                       )
                       AND (
                             q.createdby = :createduser
                             OR q.modifiedby = :modifieduser
                             OR rate.userid = :rateuser
                             OR comment.userid = :commentuser
                             OR progress.userid = :progressuser
                             OR attempt.userid = :attemptuser
                             OR commenthistory.userid = :commenthistoryuser
                             OR notificationjoin.recipientid = :notificationuser
                             OR statehistory.userid = :statehistoryuser
                           )";

        $params = [
                'contextmodule' => CONTEXT_MODULE,
                'createduser' => $userid,
                'modifieduser' => $userid,
                'rateuser' => $userid,
                'commentuser' => $userid,
                'progressuser' => $userid,
                'attemptuser' => $userid,
                'commenthistoryuser' => $userid,
                'notificationuser' => $userid,
                'statehistoryuser' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparam) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT DISTINCT ctx.id AS contextid,
                       q.id AS questionid, q.name AS questionname,
                       CASE WHEN question.state = 1 THEN question.state ELSE 0 END AS questionapproved,
                       question.groupid questiongroupid, question.pinned AS questionpinned,
                       q.createdby AS questioncreatedby, q.modifiedby AS questionmodifiedby,
                       rate.id AS rateid, rate.rate AS raterate, rate.studentquizquestionid AS ratestudentquizquestionid,
                       rate.userid AS rateuserid,
                       comment.id AS commentid, comment.comment AS commentcomment,
                       comment.studentquizquestionid AS commentstudentquizquestionid,
                       comment.userid AS commentuserid, comment.created AS commentcreate,
                       comment.parentid AS commentparentid, comment.status AS commentstatus, comment.type AS commenttype,
                       comment.timemodified AS commenttimemodified, comment.usermodified AS commentusermodified,
                       progress.studentquizquestionid AS progressstudentquizquestionid, progress.userid AS progressuserid,
                       progress.studentquizid AS progressstudentquizid, progress.lastanswercorrect AS progresslastanswercorrect,
                       progress.attempts AS progressattempts, progress.correctattempts AS progresscorrectattempts,
                       progress.lastreadprivatecomment as progresslastreadprivatecomment,
                       progress.lastreadpubliccomment as progresslastreadpubliccomment,
                       attempt.id AS attemptid, attempt.studentquizid AS attempstudentquizid,attempt.userid AS attemptuserid,
                       attempt.questionusageid AS attemptquestionusageid, attempt.categoryid AS attemptcategoryid,
                       commenthistory.id AS commenthistoryid, commenthistory.commentid AS commenthistorycommentid,
                       commenthistory.content AS commenthistorycontent, commenthistory.userid AS commenthistoryuserid,
                       commenthistory.action AS commenthistoryaction, commenthistory.timemodified AS commenthistorytimemodified,
                       notificationjoin.id AS notificationid, notificationjoin.studentquizid AS notificationstudentquizid,
                       notificationjoin.content AS notificationcontent, notificationjoin.recipientid AS notificationrecipientid,
                       notificationjoin.status AS notificationstatus, notificationjoin.timetosend AS notificationtimetosend,
                       statehistory.id AS statehistoryid, statehistory.studentquizquestionid AS statehistorystudentquizquestionid,
                       statehistory.state AS statehistorystate, statehistory.userid AS statehistoryuserid,
                       statehistory.timecreated AS statehistorytimecreated
                  FROM {context} ctx
                  JOIN {studentquiz} sq ON sq.coursemodule = ctx.instanceid
                       AND contextlevel = :contextmodule
                  JOIN {question_categories} ca ON ca.contextid = ctx.id
             LEFT JOIN {question_bank_entries} qbe ON ca.id = qbe.questioncategoryid
             LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
             LEFT JOIN {question} q ON q.id = qv.questionid
             LEFT JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
             LEFT JOIN {studentquiz_question} question ON question.studentquizid = sq.id AND question.id = qr.itemid
             LEFT JOIN {studentquiz_rate} rate ON rate.studentquizquestionid = question.id
             LEFT JOIN {studentquiz_comment} comment ON comment.studentquizquestionid = question.id
             LEFT JOIN {studentquiz_comment_history} commenthistory ON commenthistory.commentid = comment.id
             LEFT JOIN {studentquiz_progress} progress ON progress.studentquizquestionid = question.id
                       AND progress.studentquizid = sq.id
             LEFT JOIN {studentquiz_attempt} attempt ON attempt.categoryid = ca.id
                       AND attempt.studentquizid = sq.id
             LEFT JOIN {studentquiz_notification} notificationjoin ON notificationjoin.studentquizid = sq.id
             LEFT JOIN {studentquiz_state_history} statehistory ON statehistory.studentquizquestionid = question.id
                 WHERE (
                         question.id IS NOT NULL
                         OR rate.id IS NOT NULL
                         OR comment.id IS NOT NULL
                         OR progress.studentquizquestionid IS NOT NULL
                         OR attempt.id IS NOT NULL
                         OR commenthistory.id IS NOT NULL
                         OR notificationjoin.id IS NOT NULL
                         OR statehistory.id IS NOT NULL
                       )
                       AND (
                             q.createdby = :createduser
                             OR q.modifiedby = :modifieduser
                             OR rate.userid = :rateuser
                             OR comment.userid = :commentuser
                             OR progress.userid = :progressuser
                             OR attempt.userid = :attemptuser
                             OR commenthistory.userid = :commenthistoryuser
                             OR notificationjoin.recipientid = :notificationuser
                             OR statehistory.userid = :statehistoryuser
                           )
                       AND ctx.id {$contextsql}
              ORDER BY ctx.id ASC";

        $params = [
                'contextmodule' => CONTEXT_MODULE,
                'createduser' => $userid,
                'modifieduser' => $userid,
                'rateuser' => $userid,
                'commentuser' => $userid,
                'progressuser' => $userid,
                'attemptuser' => $userid,
                'commenthistoryuser' => $userid,
                'notificationuser' => $userid,
                'statehistoryuser' => $userid,
        ];
        $params += $contextparam;

        $recordset = $DB->get_recordset_sql($sql, $params);

        $subcontext = [get_string('pluginname', 'mod_studentquiz')];
        $context = null;
        $contextdata = null;
        foreach ($recordset as $record) {
            if (empty($context->id) || $context->id != $record->contextid) {
                if (!empty($contextdata)) {
                    writer::with_context($context)->export_data($subcontext, $contextdata);
                    $contextdata = null;
                }

                if (empty($contextdata)) {
                    $context = \context::instance_by_id($record->contextid);
                    $contextdata = helper::get_context_data($context, $user);
                    $contextdata->questions = [];
                    $contextdata->rates = [];
                    $contextdata->comments = [];
                    $contextdata->progresses = [];
                    $contextdata->attempts = [];
                    $contextdata->commenthistory = [];
                    $contextdata->notifications = [];
                    $contextdata->statehistory = [];
                }
            }

            // Export question's approval info.
            if (!empty($record->questionid) && ($userid == $record->questioncreatedby || $userid == $record->questionmodifiedby)) {
                // Purpose of this one is to export the approved status since Moodle have already export
                // whole question info for us, so we won't include full question info here.
                $contextdata->questions[$record->questionid] = (object) [
                        'name' => $record->questionname,
                        'approved' => transform::yesno($record->questionapproved),
                        'groupid' => $record->questiongroupid,
                        'pinned' => transform::yesno($record->questionpinned)
                ];
            }

            // Export rating.
            if (!empty($record->rateid) && $userid == $record->rateuserid) {
                $contextdata->rates[$record->rateid] = (object) [
                        'rate' => $record->raterate,
                        'studentquizquestionid' => $record->ratestudentquizquestionid,
                        'userid' => transform::user($record->rateuserid)
                ];
            }

            // Export comments.
            if (!empty($record->commentid) && $userid == $record->commentuserid) {
                $contextdata->comments[$record->commentid] = (object) [
                        'comment' => $record->commentcomment,
                        'studentquizquestionid' => $record->commentstudentquizquestionid,
                        'userid' => transform::user($record->commentuserid),
                        'created' => transform::datetime($record->commentcreate),
                        'parentid' => $record->commentparentid,
                        'status' => $record->commentstatus,
                        'type' => $record->commenttype,
                        'timemodified' => !is_null($record->commenttimemodified) ?
                                transform::datetime($record->commenttimemodified) : null,
                        'usermodified' => $record->commentusermodified
                ];
            }

            // Export comment history.
            if (!empty($record->commenthistoryid) && $userid == $record->commenthistoryuserid) {
                $contextdata->commenthistory[$record->commenthistoryid] = (object) [
                        'commentid' => $record->commenthistorycommentid,
                        'content' => $record->commenthistorycontent,
                        'userid' => transform::user($record->commenthistoryuserid),
                        'action' => $record->commenthistoryaction,
                        'timemodified' => !is_null($record->commenthistorytimemodified) ?
                                transform::datetime($record->commenthistorytimemodified) : null
                ];
            }

            // Export progresses.
            if (!empty($record->progressstudentquizquestionid) && $userid == $record->progressuserid) {
                $contextdata->progresses[$record->progressstudentquizquestionid] = (object) [
                        'userid' => transform::user($record->progressuserid),
                        'studentquizid' => $record->progressstudentquizid,
                        'lastanswercorrect' => transform::yesno($record->progresslastanswercorrect),
                        'attempts' => $record->progressattempts,
                        'correctattempts' => $record->progresscorrectattempts,
                        'lastreadprivatecomment' => transform::datetime($record->progresslastreadprivatecomment),
                        'lastreadpubliccomment' => transform::datetime($record->progresslastreadpubliccomment)
                ];
            }

            // Export attempts.
            if (!empty($record->attemptid) && $userid == $record->attemptuserid) {
                $contextdata->attempts[$record->attemptid] = (object) [
                        'studentquizid' => $record->attempstudentquizid,
                        'userid' => transform::user($record->attemptuserid),
                        'questionusageid' => $record->attemptquestionusageid,
                        'categoryid' => $record->attemptcategoryid
                ];
            }

            // Export notifications.
            if (!empty($record->notificationid) && $userid == $record->notificationid) {
                $contextdata->notifications[$record->notificationid] = (object) [
                        'studentquizid' => $record->notificationstudentquizid,
                        'content' => $record->notificationcontent,
                        'recipientid' => transform::user($record->notificationrecipientid),
                        'status' => $record->notificationstatus,
                        'timetosend' => !is_null($record->notificationtimetosend) ?
                                transform::datetime($record->notificationtimetosend) : null
                ];
            }

            // Export state history.
            if (!empty($record->statehistoryid) && $userid == $record->statehistoryuserid) {
                $states = studentquiz_helper::get_state_descriptions();
                $contextdata->statehistory[$record->statehistoryid] = (object) [
                        'studentquizquestionid' => $record->statehistorystudentquizquestionid,
                        'userid' => transform::user($record->statehistoryuserid),
                        'state' => $states[$record->statehistorystate],
                        'timecreated' => !is_null($record->statehistorytimecreated) ?
                                transform::datetime($record->statehistorytimecreated) : null
                ];
            }
        }
        $recordset->close();

        // Import last context data.
        if (!empty($contextdata)) {
            writer::with_context($context)->export_data($subcontext, $contextdata);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        // Query to get all question ID belong to this module context.
        $sql = "SELECT q.id, sqq.id as studentquizquestionid
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid
                 WHERE qbe.questioncategoryid IN (
                                       SELECT id
                                         FROM {question_categories} c
                                        WHERE c.contextid = :contextid
                                      )";

        $params = [
            'contextid' => $context->id,
        ];

        $records = $DB->get_records_sql($sql, $params);

        $questionids = array_column($records, 'id');
        $studentquizquestionids = array_column($records, 'studentquizquestionid');

        if (empty($questionids)) {
            return;
        }

        $adminuserid = get_admin()->id;
        list($questionsql, $questionparams) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
        list($studentquizquestionsql, $studentquizquestionparams) = $DB->get_in_or_equal($studentquizquestionids, SQL_PARAMS_NAMED);

        // Delete the question base on question ID.
        foreach ($questionids as $questionid) {
            question_delete_question($questionid);
        }

        // If any question cannot be deleted for some reason (question in use, ...), we'll change the owner
        // to admin and hide it.
        $DB->execute("UPDATE {question}
                         SET createdby = :adminuserid1, modifiedby = :adminuserid2
                       WHERE id {$questionsql}", [
                        'adminuserid1' => $adminuserid,
                        'adminuserid2' => $adminuserid
                ] + $questionparams);

        // If question deleted of hidden, we'll need to remove from studentquiz_question as well.
        $DB->execute("DELETE FROM {studentquiz_question}
                       WHERE id {$studentquizquestionsql}", $studentquizquestionparams);
        // Delete question_references for all studentquiz_question.
        $DB->execute("DELETE FROM {question_references}
                                 WHERE itemid {$studentquizquestionsql}
                                       AND component = 'mod_studentquiz'
                                       AND questionarea = 'studentquiz_question'", $studentquizquestionparams);

        // Delete rates belong to this context.
        $DB->execute("DELETE FROM {studentquiz_rate}
                       WHERE studentquizquestionid {$studentquizquestionsql}", $studentquizquestionparams);

        // Delete comments belong to this context.
        $DB->execute("DELETE FROM {studentquiz_comment}
                       WHERE studentquizquestionid {$studentquizquestionsql}", $studentquizquestionparams);

        // Delete comment history belong to this context.
        $DB->execute("DELETE FROM {studentquiz_comment_history}
                                 WHERE commentid IN (SELECT id FROM {studentquiz_comment}
                                                              WHERE studentquizquestionid {$studentquizquestionsql})",
                $studentquizquestionparams);

        // Delete progress belong to this context.
        $DB->execute("DELETE FROM {studentquiz_progress}
                       WHERE studentquizquestionid {$studentquizquestionsql}", $studentquizquestionparams);

        // Delete attempts belong to this context.
        $DB->execute("DELETE FROM {studentquiz_attempt}
                       WHERE studentquizid IN (
                                                SELECT id
                                                  FROM {studentquiz}
                                                 WHERE coursemodule = :coursemodule
                                              )", [
                'coursemodule' => $context->instanceid
        ]);

        // Delete notifications belong to this context.
        $DB->execute("DELETE FROM {studentquiz_notification}
                       WHERE studentquizid IN (
                                                SELECT id
                                                  FROM {studentquiz}
                                                 WHERE coursemodule = :coursemodule
                                              )", [
                'coursemodule' => $context->instanceid
        ]);

        // Delete state histories belong to this context.
        $DB->execute("DELETE FROM {studentquiz_state_history} WHERE studentquizquestionid {$studentquizquestionsql}",
                $studentquizquestionparams);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;
        $guestuserid = guest_user()->id;
        $adminid = get_admin()->id;

        list($contextsql, $contextparam) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        // Query to get all question ID belong to the course modules.
        $sql = "SELECT q.id, sqq.id as studentquizquestionid
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid
                 WHERE qbe.questioncategoryid IN (
                                       SELECT id
                                         FROM {question_categories} c
                                        WHERE c.contextid {$contextsql}
                                      )";

        $records = $DB->get_records_sql($sql, $contextparam);

        $questionids = array_column($records, 'id');
        $studentquizquestionids = array_column($records, 'studentquizquestionid');

        $instanceids = [];
        foreach ($contextlist as $context) {
            $instanceids[] = $context->instanceid;
        }

        if (empty($questionids)) {
            return;
        }

        list($questionsql, $questionparams) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
        list($studentquizsql, $studentquizparams) = $DB->get_in_or_equal($instanceids, SQL_PARAMS_NAMED);
        list($studentquizquestionsql, $studentquizquestionparams) = $DB->get_in_or_equal($studentquizquestionids, SQL_PARAMS_NAMED);

        // If user created questions, change the owner to guest by set the field User ID to 0.
        $DB->execute("UPDATE {question}
                              SET createdby = :guestid
                            WHERE id {$questionsql}
                                  AND (createdby = :createuser OR createdby = 0)", [
                        'guestid' => $guestuserid,
                        'createuser' => $userid
                ] + $questionparams);

        // If user modified questions, Update this field to guest user.
        $DB->execute("UPDATE {question}
                              SET modifiedby = :guestid
                            WHERE id {$questionsql}
                                  AND (modifiedby = :modifyuser OR modifiedby = 0)", [
                'guestid' => $guestuserid,
                'modifyuser' => $userid
            ] + $questionparams);

        // Delete rates belong to user within approved context.
        $DB->execute("DELETE FROM {studentquiz_rate}
                       WHERE studentquizquestionid {$studentquizquestionsql}
                             AND userid = :userid", ['userid' => $userid] + $studentquizquestionparams);

        // Delete comments belong to user within approved context.
        self::delete_comment_for_user($studentquizquestionsql, $studentquizquestionparams, ['userid' => $userid]);

        // Delete progress belong to user within approved context.
        $DB->execute("DELETE FROM {studentquiz_progress}
                       WHERE studentquizquestionid {$studentquizquestionsql}
                             AND userid = :userid", ['userid' => $userid] + $studentquizquestionparams);

        // Delete attempts belong to user within approved context.
        $DB->execute("DELETE FROM {studentquiz_attempt}
                       WHERE userid = :userid
                             AND studentquizid IN (
                                                    SELECT id
                                                      FROM {studentquiz}
                                                     WHERE coursemodule {$studentquizsql}
                                                  )", [
                        'userid' => $userid
                ] + $studentquizparams);

        // Delete comment history of user.
        $DB->execute("DELETE FROM {studentquiz_comment_history} WHERE userid = :userid", ['userid' => $userid]);

        // Delete notifications of user.
        $DB->execute("DELETE FROM {studentquiz_notification} WHERE recipientid = :userid", ['userid' => $userid]);

        // If user created questions, change the question state owner to guest by set the field userid guest user.
        $DB->execute("UPDATE {studentquiz_state_history}
                         SET userid = :guestuserid
                       WHERE userid = :userid
                             AND state = :questionstate", ['guestuserid' => $guestuserid,
                             'questionstate' => studentquiz_helper::STATE_NEW, 'userid' => $userid]);
        // If user changes state of questions, change the question state owner to admin by set the field userid admin user.
        $DB->execute("UPDATE {studentquiz_state_history}
                         SET userid = :adminid
                       WHERE userid = :userid", ['adminid' => $adminid, 'userid' => $userid]);
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in
     *                           this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $params = [
            'instanceid' => $context->instanceid,
            'modulename' => 'studentquiz',
            'contextid' => $userlist->get_context()->id,
        ];

        // Question's creator.
        $sql = "SELECT q.createdby
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question_bank_entries} qbe ON qc.id = qbe.questioncategoryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('createdby', $sql, $params);

        // Question's modifier.
        $sql = "SELECT q.modifiedby
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question_bank_entries} qbe ON qc.id = qbe.questioncategoryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('modifiedby', $sql, $params);

        // User rating.
        $sql = "SELECT r.userid
                  FROM {course_modules} cm
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question_bank_entries} qbe ON qc.id = qbe.questioncategoryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid
                  JOIN {studentquiz_rate} r ON r.studentquizquestionid = sqq.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // User comment.
        $sql = "SELECT c.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question_bank_entries} qbe ON qc.id = qbe.questioncategoryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid
                  JOIN {studentquiz_comment} c ON c.studentquizquestionid = sqq.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // User comment history.
        $sql = "SELECT c.userid
                  FROM {course_modules} cm
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question_bank_entries} qbe ON qc.id = qbe.questioncategoryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid
                  JOIN {studentquiz_comment} c ON c.studentquizquestionid = sqq.id
                  JOIN {studentquiz_comment_history} h ON h.commentid = c.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // User progress.
        $sql = "SELECT p.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question_bank_entries} qbe ON qc.id = qbe.questioncategoryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid
                  JOIN {studentquiz_progress} p ON p.studentquizquestionid = sqq.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // User attempt.
        $sql = "SELECT attempt.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {studentquiz} sq ON sq.coursemodule = cm.id
                  JOIN {studentquiz_attempt} attempt ON attempt.categoryid = qc.id
                       AND attempt.studentquizid = sq.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // User notification.
        $sql = "SELECT notif.recipientid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {studentquiz} sq ON sq.coursemodule = cm.id
                  JOIN {studentquiz_notification} notif ON notif.studentquizid = sq.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('recipientid', $sql, $params);

        // User change state question.
        $sql = "SELECT sh.userid
                  FROM {course_modules} cm
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question_bank_entries} qbe ON qc.id = qbe.questioncategoryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid
                  JOIN {studentquiz_state_history} sh ON sh.studentquizquestionid = sqq.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);

        // Query to get all question ID belong to the course modules.
        $sql = "SELECT q.id, sqq.id as studentquizquestionid
                  FROM {question} q
                  JOIN {question_versions} qv ON qv.questionid = q.id
                  JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid
                 WHERE qbe.questioncategoryid IN (SELECT id
                                        FROM {question_categories} c
                                       WHERE c.contextid = :contextid)";

        $records = $DB->get_records_sql($sql, ['contextid' => $context->id]);
        $questionids = array_column($records, 'id');
        $studentquizquestionids = array_column($records, 'studentquizquestionid');

        if (empty($questionids)) {
            return;
        }

        $guestuserid = guest_user()->id;
        $adminid = get_admin()->id;

        list($questionsql, $questionparams) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
        list($studentquizquestionsql, $studentquizquestionparams) = $DB->get_in_or_equal($studentquizquestionids, SQL_PARAMS_NAMED);
        // If user created questions, change the owner to guest by set the field User ID guest user.
        $DB->execute("UPDATE {question}
                         SET createdby = :guestid
                       WHERE id {$questionsql}
                             AND (createdby {$userinsql})", ['guestid' => $guestuserid] + $questionparams + $userinparams);

        // If user modified questions, change the owner to guest by set the field User ID to guest user.
        $DB->execute("UPDATE {question}
                         SET modifiedby = :guestid
                       WHERE id {$questionsql}
                             AND (modifiedby {$userinsql})", ['guestid' => $guestuserid] + $questionparams + $userinparams);

        // Delete rates belong to users.
        $DB->execute("DELETE FROM {studentquiz_rate}
                                 WHERE studentquizquestionid {$studentquizquestionsql}
                                   AND userid {$userinsql}", $studentquizquestionparams + $userinparams);

        // Delete comments belong to users.
        self::delete_comment_for_users($studentquizquestionsql, $studentquizquestionparams, $userinsql, $userinparams);

        // Delete comment histories belong to users.
        $DB->execute("DELETE FROM {studentquiz_comment_history}
                                 WHERE userid {$userinsql}", $userinparams);

        // Delete progress belong to users.
        $DB->execute("DELETE FROM {studentquiz_progress}
                                 WHERE studentquizquestionid {$studentquizquestionsql}
                                       AND userid {$userinsql}", $studentquizquestionparams + $userinparams);

        // Delete attempts belong to users.
        $DB->execute("DELETE FROM {studentquiz_attempt}
                                 WHERE userid {$userinsql}
                                       AND studentquizid = :studentquizid", [
                        'studentquizid' => $cm->instance
                ] + $userinparams);

        // Delete notifications belong to users.
        $DB->execute("DELETE FROM {studentquiz_notification}
                                 WHERE recipientid {$userinsql}", $userinparams);

        // If user created questions, change the question state owner to guest by set the field userid guest user.
        $DB->execute("UPDATE {studentquiz_state_history}
                              SET userid = :guestuserid
                            WHERE studentquizquestionid {$studentquizquestionsql}
                                  AND (userid {$userinsql})
                                  AND state = :questionstate", ['guestuserid' => $guestuserid,
                             'questionstate' => studentquiz_helper::STATE_NEW] + $studentquizquestionparams + $userinparams);
        // If user changes state of questions, change the question state owner to admin by set the field userid admin user.
        $DB->execute("UPDATE {studentquiz_state_history}
                              SET userid = :adminid
                            WHERE studentquizquestionid {$studentquizquestionsql}
                                  AND (userid {$userinsql})",
                ['adminid' => $adminid] + $studentquizquestionparams + $userinparams);
    }

    /**
     * Delete comments belong to users.
     *
     * @param string $studentquizquestionsql
     * @param array $studentquizquestionparams
     * @param string $userinsql
     * @param array $userinparams
     */
    private static function delete_comment_for_users($studentquizquestionsql, $studentquizquestionparams,
            $userinsql, $userinparams) {
        global $DB;
        $params = $studentquizquestionparams + $userinparams + ['parentid' => container::PARENTID];
        $blankcomment = utils::get_blank_comment();
        $DB->execute("UPDATE {studentquiz_comment}
                              SET userid = :guestuserid,
                                  status = :status,
                                  comment = :comment,
                                  timemodified = :timemodified,
                                  usermodified = :usermodified
                            WHERE studentquizquestionid {$studentquizquestionsql}
                                  AND userid {$userinsql}
                                  AND parentid = :parentid", $params + $blankcomment);
        $DB->execute("DELETE
                            FROM {studentquiz_comment}
                           WHERE studentquizquestionid {$studentquizquestionsql}
                                 AND userid {$userinsql}
                                 AND parentid != :parentid", $params);
    }

    /**
     * Delete comment for specific user.
     *
     * @param string $studentquizquestionsql
     * @param array $studentquizquestionparams
     * @param array $userparams
     */
    private static function delete_comment_for_user($studentquizquestionsql, $studentquizquestionparams, $userparams) {
        global $DB;
        $params = $studentquizquestionparams + $userparams + ['parentid' => container::PARENTID];
        $blankcomment = utils::get_blank_comment();
        $DB->execute("UPDATE {studentquiz_comment}
                              SET userid = :guestuserid,
                                  status = :status,
                                  comment = :comment,
                                  timemodified = :timemodified,
                                  usermodified = :usermodified
                            WHERE studentquizquestionid {$studentquizquestionsql}
                                  AND userid = :userid
                                  AND parentid = :parentid", $params + $blankcomment);
        $DB->execute("DELETE
                            FROM {studentquiz_comment}
                           WHERE studentquizquestionid {$studentquizquestionsql}
                                 AND userid = :userid
                                 AND parentid != :parentid", $params);
    }

    /**
     * Stores the user preferences related to mod_studentquiz.
     *
     * @param int $userid The user ID that we want the preferences for.
     */
    public static function export_user_preferences(int $userid) {
        $context = \context_system::instance();
        $preferences = [
                container::USER_PREFERENCE_SORT => ['string' => get_string('privacy:metadata:' . container::USER_PREFERENCE_SORT,
                        'mod_studentquiz'),
                        'bool' => false],
                utils::USER_PREFERENCE_QUESTION_ACTIVE_TAB => [
                    'string' => get_string('privacy:metadata:' . utils::USER_PREFERENCE_QUESTION_ACTIVE_TAB, 'mod_studentquiz'),
                    'bool' => false
                ]
        ];
        foreach ($preferences as $key => $preference) {
            $value = get_user_preferences($key, null, $userid);
            if ($preference['bool']) {
                $value = transform::yesno($value);
            }
            if (isset($value)) {
                writer::with_context($context)->export_user_preference('mod_studentquiz', $key, $value, $preference['string']);
            }
        }
    }
}
