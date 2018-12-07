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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\writer;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\transform;

require_once($CFG->libdir . '/questionlib.php');

global $CFG;
/**
 * Implementation of the privacy subsystem plugin provider for the StudentQuiz activity module.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
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
                'created' => 'privacy:metadata:studentquiz_comment:created'
        ], 'privacy:metadata:studentquiz_comment');

        $collection->add_database_table('studentquiz_practice', [
                'quizcoursemodule' => 'privacy:metadata:studentquiz_practice:quizcoursemodule',
                'studentquizcoursemodule' => 'privacy:metadata:studentquiz_practice:studentquizcoursemodule',
                'userid' => 'privacy:metadata:studentquiz_practice:userid'
        ], 'privacy:metadata:studentquiz_practice');

        $collection->add_database_table('studentquiz_attempt', [
                'studentquizid' => 'privacy:metadata:studentquiz_attempt:studentquizid',
                'userid' => 'privacy:metadata:studentquiz_attempt:userid',
                'questionusageid' => 'privacy:metadata:studentquiz_attempt:questionusageid',
                'categoryid' => 'privacy:metadata:studentquiz_attempt:categoryid'
        ], 'privacy:metadata:studentquiz_attempt');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        // Get activity context if user created/modified the question or their data exist in these table
        // base on user ID field: rate, comment, progress, practice, attempt.
        $sql = 'SELECT DISTINCT ctx.id
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
             LEFT JOIN {studentquiz_practice} practice ON practice.studentquizcoursemodule = sq.coursemodule
             LEFT JOIN {studentquiz_attempt} attempt ON attempt.categoryid = ca.id
                       AND attempt.studentquizid = sq.id
                 WHERE (question.id IS NOT NULL
                       OR rate.id IS NOT NULL
                       OR comment.id IS NOT NULL
                       OR progress.questionid IS NOT NULL
                       OR practice.id IS NOT NULL
                       OR attempt.id IS NOT NULL)
                       AND (q.createdby = :createduser
                       OR q.modifiedby = :modifieduser
                       OR rate.userid = :rateuser
                       OR comment.userid = :commentuser
                       OR progress.userid = :progressuser
                       OR practice.userid = :practiceuser
                       OR attempt.userid = :attemptuser)';

        $params = [
                'contextmodule' => CONTEXT_MODULE,
                'createduser' => $userid,
                'modifieduser' => $userid,
                'rateuser' => $userid,
                'commentuser' => $userid,
                'progressuser' => $userid,
                'practiceuser' => $userid,
                'attemptuser' => $userid
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

        $sql = "SELECT DISTINCT ctx.id as contextid,
                       q.id as questionid, q.name as questionname, question.approved as questionapproved,
                       q.createdby as questioncreatedby, q.modifiedby as questionmodifiedby,
                       rate.id as rateid, rate.rate as raterate, rate.questionid as ratequestionid, rate.userid as rateuserid,
                       comment.id as commentid, comment.comment as commentcomment, comment.questionid as commentquestionid,
                       comment.userid as commentuserid, comment.created as commentcreate,
                       progress.questionid as progressquestionid, progress.userid as progressuserid,
                       progress.studentquizid as progressstudentquizid, progress.lastanswercorrect as progresslastanswercorrect,
                       progress.attempts as progressattempts, progress.correctattempts as progresscorrectattempts,
                       practice.id as practiceid, practice.quizcoursemodule as practicequizcoursemodule,
                       practice.studentquizcoursemodule as practicestudentquizcoursemodule, practice.userid as practiceuserid,
                       attempt.id as attemptid, attempt.studentquizid as attempstudentquizid,attempt.userid as attemptuserid,
                       attempt.questionusageid as attemptquestionusageid, attempt.categoryid as attemptcategoryid
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
             LEFT JOIN {studentquiz_practice} practice ON practice.studentquizcoursemodule = sq.coursemodule
             LEFT JOIN {studentquiz_attempt} attempt ON attempt.categoryid = ca.id
                       AND attempt.studentquizid = sq.id
                 WHERE (question.id IS NOT NULL
                       OR rate.id IS NOT NULL
                       OR comment.id IS NOT NULL
                       OR progress.questionid IS NOT NULL
                       OR practice.id IS NOT NULL
                       OR attempt.id IS NOT NULL)
                       AND (q.createdby = :createduser
                       OR q.modifiedby = :modifieduser
                       OR rate.userid = :rateuser
                       OR comment.userid = :commentuser
                       OR progress.userid = :progressuser
                       OR practice.userid = :practiceuser
                       OR attempt.userid = :attemptuser)
                       AND ctx.id {$contextsql}
              ORDER BY ctx.id ASC";

        $params = [
                'contextmodule' => CONTEXT_MODULE,
                'createduser' => $userid,
                'modifieduser' => $userid,
                'rateuser' => $userid,
                'commentuser' => $userid,
                'progressuser' => $userid,
                'practiceuser' => $userid,
                'attemptuser' => $userid
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
                    $contextdata->practices = [];
                    $contextdata->attempts = [];
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
                        'created' => transform::datetime($record->commentcreate)
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

            // Export practices.
            if (!empty($record->practiceid) && $userid == $record->practiceuserid) {
                $contextdata->practices[$record->practiceid] = (object) [
                        'quizcoursemodule' => $record->practicequizcoursemodule,
                        'studentquizcoursemodule' => $record->practicestudentquizcoursemodule,
                        'userid' => transform::user($record->practiceuserid),
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
        $sql = 'SELECT q.id
                  FROM {question} q
                 WHERE q.category IN (SELECT id
                                        FROM {question_categories} c
                                       WHERE c.contextid = :contextid)';

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

        // Delete progress belong to this context.
        $DB->execute("DELETE FROM {studentquiz_progress}
                       WHERE questionid {$questionsql}", $questionparams);

        // Delete practices belong to this context.
        $DB->execute("DELETE FROM {studentquiz_practice}
                       WHERE studentquizcoursemodule = :studentquizcoursemodule",
                ['studentquizcoursemodule' => $context->instanceid]);

        // Delete attempts belong to this context.
        $DB->execute("DELETE FROM {studentquiz_attempt}
                       WHERE studentquizid IN (SELECT id
                                                 FROM {studentquiz}
                                                WHERE coursemodule = :coursemodule)", [
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
                 WHERE q.category IN (SELECT id
                                        FROM {question_categories} c
                                       WHERE c.contextid {$contextsql})";

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
        $DB->execute("DELETE FROM {studentquiz_comment}
                       WHERE questionid {$questionsql}
                             AND userid = :userid", ['userid' => $userid] + $questionparams);

        // Delete progress belong to user within approved context.
        $DB->execute("DELETE FROM {studentquiz_progress}
                       WHERE questionid {$questionsql}
                             AND userid = :userid", ['userid' => $userid] + $questionparams);

        // Delete practices belong to user within approved context.
        $DB->execute("DELETE FROM {studentquiz_practice}
                       WHERE studentquizcoursemodule {$studentquizsql}
                             AND userid = :userid", ['userid' => $userid] + $sudentquizparams);

        // Delete attempts belong to user within approved context.
        $DB->execute("DELETE FROM {studentquiz_attempt}
                       WHERE userid = :userid
                             AND studentquizid IN (SELECT id
                                                     FROM {studentquiz}
                                                    WHERE coursemodule {$studentquizsql})", [
                        'userid' => $userid
                ] + $sudentquizparams);
    }
}
