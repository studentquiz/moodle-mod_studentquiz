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

use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\writer;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\transform;
use mod_studentquiz\commentarea\container;
use mod_studentquiz\utils;

interface studentquiz_userlist extends \core_privacy\local\request\core_userlist_provider
{
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
                'questionid' => 'privacy:metadata:studentquiz_rate:questionid',
                'userid' => 'privacy:metadata:studentquiz_rate:userid'
        ], 'privacy:metadata:studentquiz_rate');
        $collection->add_database_table('studentquiz_progress', [
                'questionid' => 'privacy:metadata:studentquiz_progress:questionid',
                'userid' => 'privacy:metadata:studentquiz_progress:userid',
                'studentquizid' => 'privacy:metadata:studentquiz_progress:studentquizid',
                'lastanswercorrect' => 'privacy:metadata:studentquiz_progress:lastanswercorrect',
                'attempts' => 'privacy:metadata:studentquiz_progress:attempts',
                'correctattempts' => 'privacy:metadata:studentquiz_progress:correctattempts'
        ], 'privacy:metadata:studentquiz_progress');

        $collection->add_database_table('studentquiz_comment', [
                'comment' => 'privacy:metadata:studentquiz_comment:comment',
                'questionid' => 'privacy:metadata:studentquiz_comment:questionid',
                'userid' => 'privacy:metadata:studentquiz_comment:userid',
                'created' => 'privacy:metadata:studentquiz_comment:created',
                'parentid' => 'privacy:metadata:studentquiz_comment:parentid',
                'status' => 'privacy:metadata:studentquiz_comment:status',
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

        $collection->add_user_preference(container::USER_PREFERENCE_SORT, 'privacy:metadata:' . container::USER_PREFERENCE_SORT);

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
             LEFT JOIN {question} q ON q.category = ca.id
             LEFT JOIN {studentquiz_question} question ON question.questionid = q.id
             LEFT JOIN {studentquiz_rate} rate ON rate.questionid = q.id
             LEFT JOIN {studentquiz_comment} comment ON comment.questionid = q.id
             LEFT JOIN {studentquiz_progress} progress ON progress.questionid = q.id
                       AND progress.studentquizid = sq.id
             LEFT JOIN {studentquiz_attempt} attempt ON attempt.categoryid = ca.id
                       AND attempt.studentquizid = sq.id
             LEFT JOIN {studentquiz_comment_history} commenthistory ON commenthistory.commentid = comment.id
             LEFT JOIN {studentquiz_notification} notificationjoin ON notificationjoin.studentquizid = sq.id
                 WHERE (
                         question.id IS NOT NULL
                         OR rate.id IS NOT NULL
                         OR comment.id IS NOT NULL
                         OR progress.questionid IS NOT NULL
                         OR attempt.id IS NOT NULL
                         OR commenthistory.id IS NOT NULL
                         OR notificationjoin.studentquizid IS NOT NULL
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
                'notificationuser' => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws \coding_exception
     * @throws \dml_exception
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
                       q.createdby AS questioncreatedby, q.modifiedby AS questionmodifiedby,
                       rate.id AS rateid, rate.rate AS raterate, rate.questionid AS ratequestionid, rate.userid AS rateuserid,
                       comment.id AS commentid, comment.comment AS commentcomment, comment.questionid AS commentquestionid,
                       comment.userid AS commentuserid, comment.created AS commentcreate,
                       comment.parentid AS commentparentid, comment.status AS commentstatus,
                       comment.timemodified AS commenttimemodified, comment.usermodified AS commentusermodified,
                       progress.questionid AS progressquestionid, progress.userid AS progressuserid,
                       progress.studentquizid AS progressstudentquizid, progress.lastanswercorrect AS progresslastanswercorrect,
                       progress.attempts AS progressattempts, progress.correctattempts AS progresscorrectattempts,
                       attempt.id AS attemptid, attempt.studentquizid AS attempstudentquizid,attempt.userid AS attemptuserid,
                       attempt.questionusageid AS attemptquestionusageid, attempt.categoryid AS attemptcategoryid,
                       commenthistory.id AS commenthistoryid, commenthistory.commentid AS commenthistorycommentid,
                       commenthistory.content AS commenthistorycontent, commenthistory.userid AS commenthistoryuserid,
                       commenthistory.action AS commenthistoryaction, commenthistory.timemodified AS commenthistorytimemodified,
                       notificationjoin.id AS notificationid, notificationjoin.studentquizid AS notificationstudentquizid,
                       notificationjoin.content AS notificationcontent, notificationjoin.recipientid AS notificationrecipientid,
                       notificationjoin.status AS notificationstatus, notificationjoin.timetosend AS notificationtimetosend
                  FROM {context} ctx
                  JOIN {studentquiz} sq ON sq.coursemodule = ctx.instanceid
                       AND contextlevel = :contextmodule
                  JOIN {question_categories} ca ON ca.contextid = ctx.id
             LEFT JOIN {question} q ON q.category = ca.id
             LEFT JOIN {studentquiz_question} question ON question.questionid = q.id
             LEFT JOIN {studentquiz_rate} rate ON rate.questionid = q.id
             LEFT JOIN {studentquiz_comment} comment ON comment.questionid = q.id
             LEFT JOIN {studentquiz_comment_history} commenthistory ON commenthistory.commentid = comment.id
             LEFT JOIN {studentquiz_progress} progress ON progress.questionid = q.id
                       AND progress.studentquizid = sq.id
             LEFT JOIN {studentquiz_attempt} attempt ON attempt.categoryid = ca.id
                       AND attempt.studentquizid = sq.id
             LEFT JOIN {studentquiz_notification} notificationjoin ON notificationjoin.studentquizid = sq.id
                 WHERE (
                         question.id IS NOT NULL
                         OR rate.id IS NOT NULL
                         OR comment.id IS NOT NULL
                         OR progress.questionid IS NOT NULL
                         OR attempt.id IS NOT NULL
                         OR commenthistory.id IS NOT NULL
                         OR notificationjoin.id IS NOT NULL
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
                'notificationuser' => $userid
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
                }
            }

            // Export question's approval info.
            if (!empty($record->questionid) && ($userid == $record->questioncreatedby || $userid == $record->questionmodifiedby)) {
                // Purpose of this one is to export the approved status since Moodle have already export
                // whole question info for us, so we won't include full question info here.
                $contextdata->questions[$record->questionid] = (object) [
                        'name' => $record->questionname,
                        'approved' => transform::yesno($record->questionapproved)
                ];
            }

            // Export rating.
            if (!empty($record->rateid) && $userid == $record->rateuserid) {
                $contextdata->rates[$record->rateid] = (object) [
                        'rate' => $record->raterate,
                        'questionid' => $record->ratequestionid,
                        'userid' => transform::user($record->rateuserid)
                ];
            }

            // Export comments.
            if (!empty($record->commentid) && $userid == $record->commentuserid) {
                $contextdata->comments[$record->commentid] = (object) [
                        'comment' => $record->commentcomment,
                        'questionid' => $record->commentquestionid,
                        'userid' => transform::user($record->commentuserid),
                        'created' => transform::datetime($record->commentcreate),
                        'parentid' => $record->commentparentid,
                        'status' => $record->commentstatus,
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
            if (!empty($record->progressquestionid) && $userid == $record->progressuserid) {
                $contextdata->progresses[$record->progressquestionid] = (object) [
                        'userid' => transform::user($record->progressuserid),
                        'studentquizid' => $record->progressstudentquizid,
                        'lastanswercorrect' => transform::yesno($record->progresslastanswercorrect),
                        'attempts' => $record->progressattempts,
                        'correctattempts' => $record->progresscorrectattempts
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
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        // Query to get all question ID belong to this module context.
        $sql = "SELECT q.id
                  FROM {question} q
                 WHERE q.category IN (
                                       SELECT id
                                         FROM {question_categories} c
                                        WHERE c.contextid = :contextid
                                      )";

        $params = [
                'contextid' => $context->id
        ];

        $records = $DB->get_records_sql($sql, $params);

        $questionids = array_column($records, 'id');

        if (empty($questionids)) {
            return;
        }

        $adminuserid = get_admin()->id;
        list($questionsql, $questionparams) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);

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
                       WHERE questionid {$questionsql}", $questionparams);

        // Delete rates belong to this context.
        $DB->execute("DELETE FROM {studentquiz_rate}
                       WHERE questionid {$questionsql}", $questionparams);

        // Delete comments belong to this context.
        $DB->execute("DELETE FROM {studentquiz_comment}
                       WHERE questionid {$questionsql}", $questionparams);

        // Delete comment history belong to this context.
        $DB->execute("DELETE FROM {studentquiz_comment_history}
                                 WHERE commentid IN (SELECT id FROM {studentquiz_comment}
                                                              WHERE questionid {$questionsql})", $questionparams);

        // Delete progress belong to this context.
        $DB->execute("DELETE FROM {studentquiz_progress}
                       WHERE questionid {$questionsql}", $questionparams);

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
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;
        $guestuserid = guest_user()->id;

        list($contextsql, $contextparam) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // Query to get all question ID belong to the course modules.
        $sql = "SELECT q.id
                  FROM {question} q
                 WHERE q.category IN (
                                       SELECT id
                                         FROM {question_categories} c
                                        WHERE c.contextid {$contextsql}
                                      )";

        $records = $DB->get_records_sql($sql, $contextparam);

        $questionids = array_column($records, 'id');
        $instanceids = [];
        foreach ($contextlist as $context) {
            $instanceids[] = $context->instanceid;
        }

        if (empty($questionids)) {
            return;
        }

        list($questionsql, $questionparams) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
        list($studentquizsql, $sudentquizparams) = $DB->get_in_or_equal($instanceids, SQL_PARAMS_NAMED);

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
                       WHERE questionid {$questionsql}
                             AND userid = :userid", ['userid' => $userid] + $questionparams);

        // Delete comments belong to user within approved context.
        self::delete_comment_for_user($questionsql, $questionparams, ['userid' => $userid]);

        // Delete progress belong to user within approved context.
        $DB->execute("DELETE FROM {studentquiz_progress}
                       WHERE questionid {$questionsql}
                             AND userid = :userid", ['userid' => $userid] + $questionparams);

        // Delete attempts belong to user within approved context.
        $DB->execute("DELETE FROM {studentquiz_attempt}
                       WHERE userid = :userid
                             AND studentquizid IN (
                                                    SELECT id
                                                      FROM {studentquiz}
                                                     WHERE coursemodule {$studentquizsql}
                                                  )", [
                        'userid' => $userid
                ] + $sudentquizparams);

        // Delete comment history of user.
        $DB->execute("DELETE FROM {studentquiz_comment_history} WHERE userid = :userid", ['userid' => $userid]);

        // Delete notifications of user.
        $DB->execute("DELETE FROM {studentquiz_notification} WHERE recipientid = :userid", ['userid' => $userid]);
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
            'contextid' => $userlist->get_context()->id
        ];

        // Question's creator.
        $sql = "SELECT q.createdby
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question} q ON q.category = qc.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('createdby', $sql, $params);

        // Question's modifier.
        $sql = "SELECT q.modifiedby
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question} q ON q.category = qc.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('modifiedby', $sql, $params);

        // User rating.
        $sql = "SELECT r.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question} q ON q.category = qc.id
                  JOIN {studentquiz_rate} r ON r.questionid = q.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // User comment.
        $sql = "SELECT c.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question} q ON q.category = qc.id
                  JOIN {studentquiz_comment} c ON c.questionid = q.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // User comment history.
        $sql = "SELECT c.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question} q ON q.category = qc.id
                  JOIN {studentquiz_comment} c ON c.questionid = q.id
                  JOIN {studentquiz_comment_history} h ON h.commentid = c.id
                 WHERE cm.id = :instanceid";
        $userlist->add_from_sql('userid', $sql, $params);

        // User progress.
        $sql = "SELECT p.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {question_categories} qc ON qc.contextid = :contextid
                  JOIN {question} q ON q.category = qc.id
                  JOIN {studentquiz_progress} p ON p.questionid = q.id
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
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);

        // Query to get all question ID belong to the course modules.
        $sql = "SELECT q.id
                  FROM {question} q
                 WHERE q.category IN (SELECT id
                                        FROM {question_categories} c
                                       WHERE c.contextid = :contextid)";

        $records = $DB->get_records_sql($sql, ['contextid' => $context->id]);
        $questionids = array_column($records, 'id');

        if (empty($questionids)) {
            return;
        }

        $guestuserid = guest_user()->id;

        list($questionsql, $questionparams) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
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
                       WHERE questionid {$questionsql}
                             AND userid {$userinsql}", $questionparams + $userinparams);

        // Delete comments belong to users.
        self::delete_comment_for_users($questionsql, $questionparams, $userinsql, $userinparams);

        // Delete comment histories belong to users.
        $DB->execute("DELETE FROM {studentquiz_comment_history}
                                 WHERE userid {$userinsql}", $userinparams);

        // Delete progress belong to users.
        $DB->execute("DELETE FROM {studentquiz_progress}
                       WHERE questionid {$questionsql}
                             AND userid {$userinsql}", $questionparams + $userinparams);

        // Delete attempts belong to users.
        $DB->execute("DELETE FROM {studentquiz_attempt}
                       WHERE userid {$userinsql}
                             AND studentquizid = :studentquizid", [
                        'studentquizid' => $cm->instance
                ] + $userinparams);

        // Delete notifications belong to users.
        $DB->execute("DELETE FROM {studentquiz_notification}
                            WHERE recipientid {$userinsql}", $userinparams);
    }

    /**
     * Delete comments belong to users.
     *
     * @param string $questionsql
     * @param array $questionparams
     * @param string $userinsql
     * @param array $userinparams
     */
    private static function delete_comment_for_users($questionsql, $questionparams, $userinsql, $userinparams) {
        global $DB;
        $params = $questionparams + $userinparams + ['parentid' => container::PARENTID];
        $blankcomment = utils::get_blank_comment();
        $DB->execute("UPDATE {studentquiz_comment}
                              SET userid = :guestuserid,
                                  status = :status,
                                  comment = :comment,
                                  timemodified = :timemodified,
                                  usermodified = :usermodified
                            WHERE questionid {$questionsql}
                                  AND userid {$userinsql}
                                  AND parentid = :parentid", $params + $blankcomment);
        $DB->execute("DELETE
                            FROM {studentquiz_comment}
                           WHERE questionid {$questionsql}
                                 AND userid {$userinsql}
                                 AND parentid != :parentid", $params);
    }

    /**
     * Delete comment for specific user.
     *
     * @param string $questionsql
     * @param array $questionparams
     * @param array $userparams
     */
    private static function delete_comment_for_user($questionsql, $questionparams, $userparams) {
        global $DB;
        $params = $questionparams + $userparams + ['parentid' => container::PARENTID];
        $blankcomment = utils::get_blank_comment();
        $DB->execute("UPDATE {studentquiz_comment}
                              SET userid = :guestuserid,
                                  status = :status,
                                  comment = :comment,
                                  timemodified = :timemodified,
                                  usermodified = :usermodified
                            WHERE questionid {$questionsql}
                                  AND userid = :userid
                                  AND parentid = :parentid", $params + $blankcomment);
        $DB->execute("DELETE
                            FROM {studentquiz_comment}
                           WHERE questionid {$questionsql}
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
                        'bool' => false]
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
