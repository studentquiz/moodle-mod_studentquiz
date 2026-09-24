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
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\transform;
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
            'userid' => 'privacy:metadata:studentquiz_rate:userid',
        ], 'privacy:metadata:studentquiz_rate');
        $collection->add_database_table('studentquiz_progress', [
            'questionid' => 'privacy:metadata:studentquiz_progress:studentquizquestionid',
            'userid' => 'privacy:metadata:studentquiz_progress:userid',
            'studentquizid' => 'privacy:metadata:studentquiz_progress:studentquizid',
            'lastanswercorrect' => 'privacy:metadata:studentquiz_progress:lastanswercorrect',
            'attempts' => 'privacy:metadata:studentquiz_progress:attempts',
            'correctattempts' => 'privacy:metadata:studentquiz_progress:correctattempts',
            'lastreadprivatecomment' => 'privacy:metadata:studentquiz_progress:lastreadprivatecomment',
            'lastreadpubliccomment' => 'privacy:metadata:studentquiz_progress:lastreadpubliccomment',
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
            'usermodified' => 'privacy:metadata:studentquiz_comment:usermodified',

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
            'groupid' => 'privacy:metadata:studentquiz_question:groupid',
        ], 'privacy:metadata:studentquiz_question');

        $collection->add_database_table('studentquiz_attempt', [
            'studentquizid' => 'privacy:metadata:studentquiz_attempt:studentquizid',
            'userid' => 'privacy:metadata:studentquiz_attempt:userid',
            'questionusageid' => 'privacy:metadata:studentquiz_attempt:questionusageid',
            'categoryid' => 'privacy:metadata:studentquiz_attempt:categoryid',
        ], 'privacy:metadata:studentquiz_attempt');

        $collection->add_database_table('studentquiz_notification', [
            'studentquizid' => 'privacy:metadata:studentquiz_notification:studentquizid',
            'content' => 'privacy:metadata:studentquiz_notification:content',
            'recipientid' => 'privacy:metadata:studentquiz_notification:recipientid',
            'status' => 'privacy:metadata:studentquiz_notification:status',
            'timetosend' => 'privacy:metadata:studentquiz_notification:timetosend',
        ], 'privacy:metadata:studentquiz_attempt');

        $collection->add_database_table('studentquiz_state_history', [
            'questionid' => 'privacy:metadata:studentquiz_state_history:studentquizquestionid',
            'userid' => 'privacy:metadata:studentquiz_state_history:userid',
            'state' => 'privacy:metadata:studentquiz_state_history:state',
            'timecreated' => 'privacy:metadata:studentquiz_state_history:timecreated',
        ], 'privacy:metadata:studentquiz_attempt');

        $collection->add_user_preference(container::USER_PREFERENCE_SORT, 'privacy:metadata:' . container::USER_PREFERENCE_SORT);
        $collection->add_user_preference(
            utils::USER_PREFERENCE_QUESTION_ACTIVE_TAB,
            'privacy:metadata:' . utils::USER_PREFERENCE_QUESTION_ACTIVE_TAB
        );

        return $collection;
    }

    /**
     * Get the list of contexts where the specified user has personal data recorded.
     *
     * @param int $userid The user ID to search for.
     * @return contextlist The populated contextlist instance.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT ctx.id AS contextid
                  FROM {context} ctx
                  JOIN {studentquiz} sq ON sq.coursemodule = ctx.instanceid
                 WHERE ctx.contextlevel = :contextmodule
                       AND ctx.id IN (
                   -- 1. Questions created or modified by user
                   SELECT ca.contextid
                     FROM {question_categories} ca
                     JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = ca.id
                     JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                     JOIN {question} q ON q.id = qv.questionid
                    WHERE q.createdby = :userid1 OR q.modifiedby = :userid2

                   UNION

                   -- 2. Questions rated by user
                   SELECT ca.contextid
                     FROM {question_categories} ca
                     JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = ca.id
                     JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                          AND qr.component = 'mod_studentquiz' AND qr.questionarea = 'studentquiz_question'
                     JOIN {studentquiz_rate} rate ON rate.studentquizquestionid = qr.itemid
                    WHERE rate.userid = :userid3

                   UNION

                   -- 3. Comments created or edited by user
                   SELECT ca.contextid
                     FROM {question_categories} ca
                     JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = ca.id
                     JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                          AND qr.component = 'mod_studentquiz' AND qr.questionarea = 'studentquiz_question'
                     JOIN {studentquiz_comment} comment ON comment.studentquizquestionid = qr.itemid
                    WHERE comment.userid = :userid4 OR comment.usermodified = :userid5

                   UNION

                   -- 4. Comment history entries by user
                   SELECT ca.contextid
                     FROM {question_categories} ca
                     JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = ca.id
                     JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                          AND qr.component = 'mod_studentquiz' AND qr.questionarea = 'studentquiz_question'
                     JOIN {studentquiz_comment} comment ON comment.studentquizquestionid = qr.itemid
                     JOIN {studentquiz_comment_history} ch ON ch.commentid = comment.id
                    WHERE ch.userid = :userid6

                   UNION

                   -- 5. Student progress records
                   SELECT ca.contextid
                     FROM {question_categories} ca
                     JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = ca.id
                     JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                          AND qr.component = 'mod_studentquiz' AND qr.questionarea = 'studentquiz_question'
                     JOIN {studentquiz_progress} progress ON progress.studentquizquestionid = qr.itemid
                    WHERE progress.userid = :userid7

                   UNION

                   -- 6. Quiz attempts by user
                   SELECT ca.contextid
                     FROM {question_categories} ca
                     JOIN {studentquiz_attempt} attempt ON attempt.categoryid = ca.id
                    WHERE attempt.userid = :userid8

                   UNION

                   -- 7. Notifications received by user
                   SELECT ctx2.id AS contextid
                     FROM {studentquiz_notification} n
                     JOIN {studentquiz} sq2 ON sq2.id = n.studentquizid
                     JOIN {context} ctx2 ON ctx2.instanceid = sq2.coursemodule AND ctx2.contextlevel = :contextmodule_notif
                    WHERE n.recipientid = :userid9

                   UNION

                   -- 8. State history entries by user
                   SELECT ca.contextid
                     FROM {question_categories} ca
                     JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = ca.id
                     JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                          AND qr.component = 'mod_studentquiz' AND qr.questionarea = 'studentquiz_question'
                     JOIN {studentquiz_state_history} sh ON sh.studentquizquestionid = qr.itemid
                    WHERE sh.userid = :userid10
               )
              ORDER BY ctx.id ASC";

        $params = [
            'contextmodule'       => CONTEXT_MODULE,
            'contextmodule_notif' => CONTEXT_MODULE,
            'userid1'             => $userid,
            'userid2'             => $userid,
            'userid3'             => $userid,
            'userid4'             => $userid,
            'userid5'             => $userid,
            'userid6'             => $userid,
            'userid7'             => $userid,
            'userid8'             => $userid,
            'userid9'             => $userid,
            'userid10'            => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export all user data for the specified contexts.
     *
     * @param approved_contextlist $contextlist The list of approved contexts to export for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;
        $contextids = $contextlist->get_contextids();

        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);

        // Structure context export containers.
        $exportdata = [];
        foreach ($contextids as $cid) {
            $exportdata[$cid] = (object) [
                'questions'      => [],
                'rates'          => [],
                'comments'       => [],
                'commenthistory' => [],
                'progresses'     => [],
                'attempts'       => [],
                'notifications'  => [],
                'statehistory'   => [],
            ];
        }

        // 1. Export Questions (Created or Modified by user).
        $sql = "SELECT q.id AS questionid, ctx.id AS contextid, q.name AS questionname,
                       CASE WHEN sqq.state = 1 THEN 1 ELSE 0 END AS questionapproved,
                       sqq.groupid AS questiongroupid, sqq.pinned AS questionpinned
                  FROM {context} ctx
                  JOIN {question_categories} ca ON ca.contextid = ctx.id
                  JOIN {question_bank_entries} qbe ON ca.id = qbe.questioncategoryid
                  JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                  JOIN {question} q ON q.id = qv.questionid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid
                 WHERE ctx.id {$contextsql}
                       AND (q.createdby = :qcreatedby OR q.modifiedby = :qmodifiedby)";

        $params = array_merge($contextparams, ['qcreatedby' => $userid, 'qmodifiedby' => $userid]);
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $r) {
            $exportdata[$r->contextid]->questions[$r->questionid] = (object) [
                'name'     => $r->questionname,
                'approved' => transform::yesno($r->questionapproved),
                'groupid'  => $r->questiongroupid,
                'pinned'   => transform::yesno($r->questionpinned),
            ];
        }
        $records->close();

        // 2. Export Ratings.
        $sql = "SELECT r.id AS rateid, ctx.id AS contextid, r.rate, r.studentquizquestionid, r.userid
                  FROM {context} ctx
                  JOIN {question_categories} ca ON ca.contextid = ctx.id
                  JOIN {question_bank_entries} qbe ON ca.id = qbe.questioncategoryid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_rate} r ON r.studentquizquestionid = qr.itemid
                 WHERE ctx.id {$contextsql} AND r.userid = :ruserid";

        $params = array_merge($contextparams, ['ruserid' => $userid]);
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $r) {
            $exportdata[$r->contextid]->rates[$r->rateid] = (object) [
                'rate'                  => $r->rate,
                'studentquizquestionid' => $r->studentquizquestionid,
                'userid'                => transform::user($r->userid),
            ];
        }
        $records->close();

        // 3. Export Comments (Created or edited by user).
        $sql = "SELECT c.id AS commentid, ctx.id AS contextid, c.comment, c.studentquizquestionid,
                       c.userid, c.created, c.parentid, c.status, c.type, c.timemodified, c.usermodified
                  FROM {context} ctx
                  JOIN {question_categories} ca ON ca.contextid = ctx.id
                  JOIN {question_bank_entries} qbe ON ca.id = qbe.questioncategoryid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_comment} c ON c.studentquizquestionid = qr.itemid
                 WHERE ctx.id {$contextsql}
                       AND (c.userid = :cuserid OR c.usermodified = :cusermodified)";

        $params = array_merge($contextparams, ['cuserid' => $userid, 'cusermodified' => $userid]);
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $r) {
            $exportdata[$r->contextid]->comments[$r->commentid] = (object) [
                'comment'               => $r->comment,
                'studentquizquestionid' => $r->studentquizquestionid,
                'userid'                => transform::user($r->userid),
                'created'               => transform::datetime($r->created),
                'parentid'              => $r->parentid,
                'status'                => $r->status,
                'type'                  => $r->type,
                'timemodified'          => !is_null($r->timemodified) ? transform::datetime($r->timemodified) : null,
                'usermodified'          => $r->usermodified,
            ];
        }
        $records->close();

        // 4. Export Comment History.
        $sql = "SELECT h.id AS historyid, ctx.id AS contextid, h.commentid, h.content,
                       h.userid, h.action, h.timemodified
                  FROM {context} ctx
                  JOIN {question_categories} ca ON ca.contextid = ctx.id
                  JOIN {question_bank_entries} qbe ON ca.id = qbe.questioncategoryid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_comment} c ON c.studentquizquestionid = qr.itemid
                  JOIN {studentquiz_comment_history} h ON h.commentid = c.id
                 WHERE ctx.id {$contextsql} AND h.userid = :huserid";

        $params = array_merge($contextparams, ['huserid' => $userid]);
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $r) {
            $exportdata[$r->contextid]->commenthistory[$r->historyid] = (object) [
                'commentid'    => $r->commentid,
                'content'      => $r->content,
                'userid'       => transform::user($r->userid),
                'action'       => $r->action,
                'timemodified' => !is_null($r->timemodified) ? transform::datetime($r->timemodified) : null,
            ];
        }
        $records->close();

        // 5. Export Progress.
        $sql = "SELECT p.studentquizquestionid, ctx.id AS contextid, p.userid, p.studentquizid,
                       p.lastanswercorrect, p.attempts, p.correctattempts,
                       p.lastreadprivatecomment, p.lastreadpubliccomment
                  FROM {context} ctx
                  JOIN {question_categories} ca ON ca.contextid = ctx.id
                  JOIN {question_bank_entries} qbe ON ca.id = qbe.questioncategoryid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_progress} p ON p.studentquizquestionid = qr.itemid
                 WHERE ctx.id {$contextsql} AND p.userid = :puserid";

        $params = array_merge($contextparams, ['puserid' => $userid]);
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $r) {
            $exportdata[$r->contextid]->progresses[$r->studentquizquestionid] = (object) [
                'userid'                 => transform::user($r->userid),
                'studentquizid'          => $r->studentquizid,
                'lastanswercorrect'      => transform::yesno($r->lastanswercorrect),
                'attempts'               => $r->attempts,
                'correctattempts'        => $r->correctattempts,
                'lastreadprivatecomment' => transform::datetime($r->lastreadprivatecomment),
                'lastreadpubliccomment'  => transform::datetime($r->lastreadpubliccomment),
            ];
        }
        $records->close();

        // 6. Export Attempts.
        $sql = "SELECT a.id AS attemptid, ctx.id AS contextid, a.studentquizid, a.userid,
                       a.questionusageid, a.categoryid
                  FROM {context} ctx
                  JOIN {question_categories} ca ON ca.contextid = ctx.id
                  JOIN {studentquiz_attempt} a ON a.categoryid = ca.id
                 WHERE ctx.id {$contextsql} AND a.userid = :auserid";

        $params = array_merge($contextparams, ['auserid' => $userid]);
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $r) {
            $exportdata[$r->contextid]->attempts[$r->attemptid] = (object) [
                'studentquizid'   => $r->studentquizid,
                'userid'          => transform::user($r->userid),
                'questionusageid' => $r->questionusageid,
                'categoryid'      => $r->categoryid,
            ];
        }
        $records->close();

        // 7. Export Notifications (Fixed recipientid bug).
        $sql = "SELECT n.id AS notificationid, ctx.id AS contextid, n.studentquizid, n.content,
                       n.recipientid, n.status, n.timetosend
                  FROM {context} ctx
                  JOIN {studentquiz} sq ON sq.coursemodule = ctx.instanceid AND ctx.contextlevel = :contextmodule
                  JOIN {studentquiz_notification} n ON n.studentquizid = sq.id
                 WHERE ctx.id {$contextsql} AND n.recipientid = :nrecipientid";

        $params = array_merge($contextparams, ['contextmodule' => CONTEXT_MODULE, 'nrecipientid' => $userid]);
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $r) {
            $exportdata[$r->contextid]->notifications[$r->notificationid] = (object) [
                'studentquizid' => $r->studentquizid,
                'content'       => $r->content,
                'recipientid'   => transform::user($r->recipientid),
                'status'        => $r->status,
                'timetosend'    => !is_null($r->timetosend) ? transform::datetime($r->timetosend) : null,
            ];
        }
        $records->close();

        // 8. Export State History.
        $sql = "SELECT sh.id AS statehistoryid, ctx.id AS contextid, sh.studentquizquestionid,
                       sh.state, sh.userid, sh.timecreated
                  FROM {context} ctx
                  JOIN {question_categories} ca ON ca.contextid = ctx.id
                  JOIN {question_bank_entries} qbe ON ca.id = qbe.questioncategoryid
                  JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
                  JOIN {studentquiz_state_history} sh ON sh.studentquizquestionid = qr.itemid
                 WHERE ctx.id {$contextsql} AND sh.userid = :shuserid";

        $params = array_merge($contextparams, ['shuserid' => $userid]);
        $records = $DB->get_recordset_sql($sql, $params);
        $statedescriptions = studentquiz_helper::get_state_descriptions();
        foreach ($records as $r) {
            $exportdata[$r->contextid]->statehistory[$r->statehistoryid] = (object) [
                'studentquizquestionid' => $r->studentquizquestionid,
                'userid'                => transform::user($r->userid),
                'state'                 => $statedescriptions[$r->state] ?? $r->state,
                'timecreated'           => !is_null($r->timecreated) ? transform::datetime($r->timecreated) : null,
            ];
        }
        $records->close();

        // Finalize export to writer for each context.
        $subcontext = [get_string('pluginname', 'mod_studentquiz')];
        foreach ($contextids as $cid) {
            $data = $exportdata[$cid];

            // Only export if at least one data category contains user records.
            $hasdata = !empty($data->questions) || !empty($data->rates) || !empty($data->comments)
                || !empty($data->commenthistory) || !empty($data->progresses) || !empty($data->attempts)
                || !empty($data->notifications) || !empty($data->statehistory);

            if ($hasdata) {
                $context = \context::instance_by_id($cid);
                $contextdata = helper::get_context_data($context, $user);
                foreach ((array)$data as $key => $val) {
                    $contextdata->$key = $val;
                }
                writer::with_context($context)->export_data($subcontext, $contextdata);
            }
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
        [$questionsql, $questionparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
        [$studentquizquestionsql, $studentquizquestionparams] = $DB->get_in_or_equal($studentquizquestionids, SQL_PARAMS_NAMED);

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
                        'adminuserid2' => $adminuserid,
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
        $DB->execute(
            "DELETE FROM {studentquiz_comment_history}
                   WHERE commentid IN (SELECT id FROM {studentquiz_comment}
                                        WHERE studentquizquestionid {$studentquizquestionsql})",
            $studentquizquestionparams
        );

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
                'coursemodule' => $context->instanceid,
        ]);

        // Delete notifications belong to this context.
        $DB->execute("DELETE FROM {studentquiz_notification}
                       WHERE studentquizid IN (
                                                SELECT id
                                                  FROM {studentquiz}
                                                 WHERE coursemodule = :coursemodule
                                              )", [
                'coursemodule' => $context->instanceid,
        ]);

        // Delete state histories belong to this context.
        $DB->execute(
            "DELETE FROM {studentquiz_state_history} WHERE studentquizquestionid {$studentquizquestionsql}",
            $studentquizquestionparams
        );
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

        [$contextsql, $contextparam] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
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

        [$questionsql, $questionparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
        [$studentquizsql, $studentquizparams] = $DB->get_in_or_equal($instanceids, SQL_PARAMS_NAMED);
        [$studentquizquestionsql, $studentquizquestionparams] = $DB->get_in_or_equal($studentquizquestionids, SQL_PARAMS_NAMED);

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
                'modifyuser' => $userid,
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
                        'userid' => $userid,
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
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Fast check: verify this context module actually belongs to a StudentQuiz instance.
        if (!$DB->record_exists('studentquiz', ['coursemodule' => $context->instanceid])) {
            return;
        }

        // Every named parameter in the query string must be unique for Moodle's $DB layer.
        $params = [
            'cmid'        => $context->instanceid,
            'contextid1'  => $context->id,
            'contextid2'  => $context->id,
            'contextid3'  => $context->id,
            'contextid4'  => $context->id,
            'contextid5'  => $context->id,
            'contextid6'  => $context->id,
            'contextid7'  => $context->id,
            'contextid8'  => $context->id,
            'contextid9'  => $context->id,
        ];

        $sql = "
        -- 1. Question creators
        SELECT q.createdby AS userid
          FROM {question_categories} qc
          JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
          JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
          JOIN {question} q ON q.id = qv.questionid
         WHERE qc.contextid = :contextid1 AND q.createdby > 0

        UNION

        -- 2. Question modifiers
        SELECT q.modifiedby AS userid
          FROM {question_categories} qc
          JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
          JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
          JOIN {question} q ON q.id = qv.questionid
         WHERE qc.contextid = :contextid2 AND q.modifiedby IS NOT NULL AND q.modifiedby > 0

        UNION

        -- 3. User rating
        SELECT r.userid AS userid
          FROM {question_categories} qc
          JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
          JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
               AND qr.component = 'mod_studentquiz'
               AND qr.questionarea = 'studentquiz_question'
          JOIN {studentquiz_rate} r ON r.studentquizquestionid = qr.itemid
         WHERE qc.contextid = :contextid3

        UNION

        -- 4. User comment creator
        SELECT c.userid AS userid
          FROM {question_categories} qc
          JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
          JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
               AND qr.component = 'mod_studentquiz'
               AND qr.questionarea = 'studentquiz_question'
          JOIN {studentquiz_comment} c ON c.studentquizquestionid = qr.itemid
         WHERE qc.contextid = :contextid4

        UNION

        -- 5. User comment editor
        SELECT c.usermodified AS userid
          FROM {question_categories} qc
          JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
          JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
               AND qr.component = 'mod_studentquiz'
               AND qr.questionarea = 'studentquiz_question'
          JOIN {studentquiz_comment} c ON c.studentquizquestionid = qr.itemid
         WHERE qc.contextid = :contextid5 AND c.usermodified IS NOT NULL AND c.usermodified > 0

        UNION

        -- 6. User comment history (Fixed: h.userid instead of c.userid)
        SELECT h.userid AS userid
          FROM {question_categories} qc
          JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
          JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
               AND qr.component = 'mod_studentquiz'
               AND qr.questionarea = 'studentquiz_question'
          JOIN {studentquiz_comment} c ON c.studentquizquestionid = qr.itemid
          JOIN {studentquiz_comment_history} h ON h.commentid = c.id
         WHERE qc.contextid = :contextid6

        UNION

        -- 7. User progress
        SELECT p.userid AS userid
          FROM {question_categories} qc
          JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
          JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
               AND qr.component = 'mod_studentquiz'
               AND qr.questionarea = 'studentquiz_question'
          JOIN {studentquiz_progress} p ON p.studentquizquestionid = qr.itemid
         WHERE qc.contextid = :contextid7

        UNION

        -- 8. User attempt
        SELECT attempt.userid AS userid
          FROM {question_categories} qc
          JOIN {studentquiz_attempt} attempt ON attempt.categoryid = qc.id
         WHERE qc.contextid = :contextid8

        UNION

        -- 9. User notification
        SELECT notif.recipientid AS userid
          FROM {studentquiz} sq
          JOIN {studentquiz_notification} notif ON notif.studentquizid = sq.id
         WHERE sq.coursemodule = :cmid

        UNION

        -- 10. User change state question
        SELECT sh.userid AS userid
          FROM {question_categories} qc
          JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
          JOIN {question_references} qr ON qr.questionbankentryid = qbe.id
               AND qr.component = 'mod_studentquiz'
               AND qr.questionarea = 'studentquiz_question'
          JOIN {studentquiz_state_history} sh ON sh.studentquizquestionid = qr.itemid
         WHERE qc.contextid = :contextid9
    ";

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

        [$userinsql, $userinparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);

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

        [$questionsql, $questionparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
        [$studentquizquestionsql, $studentquizquestionparams] = $DB->get_in_or_equal($studentquizquestionids, SQL_PARAMS_NAMED);
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
                        'studentquizid' => $cm->instance,
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
        $DB->execute(
            "UPDATE {studentquiz_state_history}
                SET userid = :adminid
              WHERE studentquizquestionid {$studentquizquestionsql}
                    AND (userid {$userinsql})",
            ['adminid' => $adminid] + $studentquizquestionparams + $userinparams
        );
    }

    /**
     * Delete comments belong to users.
     *
     * @param string $studentquizquestionsql
     * @param array $studentquizquestionparams
     * @param string $userinsql
     * @param array $userinparams
     */
    private static function delete_comment_for_users(
        string $studentquizquestionsql,
        array $studentquizquestionparams,
        string $userinsql,
        array $userinparams
    ): void {
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
    private static function delete_comment_for_user(
        string $studentquizquestionsql,
        array $studentquizquestionparams,
        array $userparams
    ): void {
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
                container::USER_PREFERENCE_SORT => [
                    'string' => get_string('privacy:metadata:' . container::USER_PREFERENCE_SORT, 'mod_studentquiz'),
                    'bool' => false,
                ],
                utils::USER_PREFERENCE_QUESTION_ACTIVE_TAB => [
                    'string' => get_string('privacy:metadata:' . utils::USER_PREFERENCE_QUESTION_ACTIVE_TAB, 'mod_studentquiz'),
                    'bool' => false,
                ],
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
