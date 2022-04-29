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

namespace mod_studentquiz;

use core\dml\sql_join;
use external_value;
use external_single_structure;
use mod_studentquiz\commentarea\comment;
use moodle_url;
use mod_studentquiz\local\studentquiz_helper;
use mod_studentquiz\local\studentquiz_question;
use \core_question\local\bank\question_version_status;

/**
 * Class that holds utility functions used by mod_studentquiz.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    /** @var int - Integer value of create history. */
    const COMMENT_HISTORY_CREATE = 0;
    /** @var int - Integer value of edit history. */
    const COMMENT_HISTORY_EDIT = 1;
    /** @var int - Integer value of delete history. */
    const COMMENT_HISTORY_DELETE = 2;
    /** @var int No digest type */
    const NO_DIGEST_TYPE = 0;
    /** @var int Daily digest type */
    const DAILY_DIGEST_TYPE = 1;
    /** @var int Weekly digest type */
    const WEEKLY_DIGEST_TYPE = 2;

    /** @var string - Atto Toolbar define. */
    const ATTO_TOOLBAR = 'style1 = bold, italic
style2 = link, unlink
style3 = superscript, subscript
style4 = unorderedlist, orderedlist
style5 = html';

    /** @var string - Comment type public. */
    const COMMENT_TYPE_PUBLIC = 0;

    /** @var string - Comment type private. */
    const COMMENT_TYPE_PRIVATE = 1;

    /** @var string - User preference question active tab. */
    const USER_PREFERENCE_QUESTION_ACTIVE_TAB = 'mod_studentquiz_question_active_tab';

    /** @var int Hidden question. */
    const HIDDEN  = 1;

    /** @var int default StudentQuiz page size. */
    const DEFAULT_QUESTIONS_PER_PAGE = 20;

    /**
     * Get Comment Area web service comment reply structure.
     *
     * @return array
     */
    public static function get_comment_area_webservice_comment_reply_structure() {
        return [
                'id' => new external_value(PARAM_INT, 'Comment ID'),
                'studentquizquestionid' => new external_value(PARAM_INT, 'studentquizquestionid ID'),
                'parentid' => new external_value(PARAM_INT, 'Parent comment ID'),
                'content' => new external_value(PARAM_RAW, 'Comment content'),
                'shortcontent' => new external_value(PARAM_RAW, 'Comment short content'),
                'numberofreply' => new external_value(PARAM_INT, 'Number of reply for this comment'),
                'authorname' => new external_value(PARAM_TEXT, 'Author of this comment'),
                'authorprofileurl' => new external_value(PARAM_TEXT, 'Profile url author of this comment'),
                'posttime' => new external_value(PARAM_RAW, 'Comment create time'),
                'deleted' => new external_value(PARAM_BOOL, 'Comment is deleted or not'),
                'deletedtime' => new external_value(PARAM_RAW, 'Comment edited time, if not deleted return 0'),
                'deleteuser' => new external_single_structure([
                        'fullname' => new external_value(PARAM_TEXT, 'Delete user first name'),
                        'profileurl' => new external_value(PARAM_TEXT, 'Delete user last name'),
                ]),
                'candelete' => new external_value(PARAM_BOOL, 'Can delete this comment or not.'),
                'canreply' => new external_value(PARAM_BOOL, 'Can reply this comment or not.'),
                'rownumber' => new external_value(PARAM_INT, 'Row number of comment.'),
                'iscreator' => new external_value(PARAM_BOOL, 'Check if this comment belongs to current logged in user.'),
                'root' => new external_value(PARAM_BOOL, 'Check if is comment or reply.'),
                'plural' => new external_value(PARAM_TEXT, 'text reply or replies.'),
                'hascomment' => new external_value(PARAM_BOOL, 'Check if in current user has comment'),
                'canreport' => new external_value(PARAM_BOOL, 'Can report this comment or not.'),
                'reportlink' => new external_value(PARAM_TEXT, 'Report link for this comment.'),
                'canedit' => new external_value(PARAM_BOOL, 'Can delete this comment or not.'),
                'commenthistorymetadata' => new external_value(PARAM_RAW, 'Show comment history meta data'),
                'commenthistorylink' => new external_value(PARAM_RAW, 'Link to connect comment history page'),
                'isedithistory' => new external_value(PARAM_BOOL, 'Check history is edit show link'),
                'status' => new external_value(PARAM_INT, 'Status of comment.'),
                'allowselfcommentrating' => new external_value(PARAM_BOOL, 'User can comment in owned question in preview mode.'),
        ];
    }

    /**
     * Get data need for comment area.
     *
     * @param int $studentquizquestionid - SQ Question ID.
     * @param int $cmid - Course Module ID.
     * @return studentquiz_question $studentquizquestion
     */
    public static function get_data_for_comment_area($studentquizquestionid, $cmid) {
        $studentquizquestion = new studentquiz_question($studentquizquestionid);
        return $studentquizquestion;
    }

    /**
     * Count comments and replies.
     *
     * @param array $data
     * @return array
     */
    public static function count_comments_and_replies(array $data): array {
        $commentcount = 0;
        $deletecommentcount = 0;
        $replycount = 0;
        $deletereplycount = 0;

        if (count($data) > 0) {
            foreach ($data as $v) {
                if ($v->status !== self::COMMENT_HISTORY_DELETE) {
                    $commentcount++;
                } else {
                    $deletecommentcount++;
                }
                if (count($v->replies) > 0) {
                    foreach ($v->replies as $reply) {
                        if ($reply->deletedtime === 0) {
                            $replycount++;
                        } else {
                            $deletereplycount++;
                        }
                    }
                }
            }
        }

        return array_merge(compact('commentcount', 'deletecommentcount', 'replycount', 'deletereplycount'), [
                'total' => $commentcount + $replycount,
                'totaldelete' => $deletecommentcount + $deletereplycount
        ]);
    }

    /**
     * Extract emails from string of reporting email column of SQ table.
     *
     * @param string $string
     * @return array
     */
    public static function extract_reporting_emails_from_string($string): array {
        return $string ? explode(';', $string) : [];
    }

    /**
     * Send to admin emails.
     *
     * @param object $formdata - Form data.
     * @param array $recipients - Emails list.
     * @param array $customdata - Custom data.
     * @param array $options - Custom options.
     * @param \stdClass $user - User data.
     * @return void
     */
    public static function send_report($formdata, $recipients, $customdata, $options, $user = null) {
        global $USER;

        $numconditions = $options['numconditions'];
        $conditions = $options['conditions'];
        $previewurl = $customdata['previewurl'];

        $content = \html_writer::div(get_string('report_comment_emailpreface', 'studentquiz', $customdata));

        $link = \html_writer::link($previewurl, get_string('report_comment_link_text', 'studentquiz'));

        $content .= \html_writer::div($link);

        $content .= \html_writer::empty_tag('br');

        // Print the reasons for reporting.
        $content .= \html_writer::div(get_string('report_comment_reasons', 'studentquiz'));

        for ($i = 1; $i <= $numconditions; $i++) {
            if (!empty($formdata->{'condition' . $i})) {
                $content .= \html_writer::div('- ' . $conditions[$i]);
            }
        }

        if (!empty($formdata->conditionmore)) {
            $content .= \html_writer::div(preg_replace("/\r\n|\r|\n/", '<br/>', $formdata->conditionmore));
        }

        $content .= \html_writer::empty_tag('br');

        // Email append.
        $content .= \html_writer::div(get_string('report_comment_emailappendix', 'studentquiz', $customdata));

        // Build email content.
        $mailcontent = \html_writer::div($content);

        $subject = get_string('report_comment_emailsubject', 'studentquiz', $customdata);

        if ($user === null) {
            $from = $USER;
        } else {
            $from = $user;
        }

        foreach ($recipients as $email) {
            // Send out email.
            $fakeuser = (object) [
                    'email' => $email,
                    'mailformat' => 1,
                    'id' => -1
            ];
            // Send email.
            if (!email_to_user($fakeuser, $from, $subject, null, $mailcontent)) {
                throw new \moodle_exception('error_sendalert', 'studentquiz', $previewurl, $fakeuser->email);
            }
        }
    }

    /**
     * Get blank comment for privacy.
     *
     * @return array
     */
    public static function get_blank_comment() {
        $guestuserid = guest_user()->id;
        return [
                'guestuserid' => $guestuserid,
                'comment' => '',
                'status' => self::COMMENT_HISTORY_CREATE,
                'timemodified' => time(),
                'usermodified' => $guestuserid
        ];
    }

    /**
     * Create comment history.
     *
     * @param comment $comment Comment object
     * @param int $historytype Type of history
     */
    public static function create_comment_history(comment $comment, int $historytype) {
        // Create history.
        $historyid = $comment->create_history(
                $comment->get_id(),
                $comment->get_user_id(),
                $historytype,
                $comment->get_comment_content()
        );

        if (!$historyid) {
            throw new \moodle_exception(\get_string('cannotcapturecommenthistory', 'studentquiz'), 'studentquiz');
        }
    }

    /**
     * Calculate and return the timestamp of timetosend
     *
     * @param int $digestfirstday First day of the week
     *
     * @return int the timestamp to send
     */
    public static function calculcate_notification_time_to_send(int $digestfirstday): int {
        date_default_timezone_set('UTC');
        $timetosend = 0;
        switch ($digestfirstday) {
            case 0:
                $timetosend = strtotime('next sunday', mktime(0, 0, 0));
                break;
            case 1:
                $timetosend = strtotime('next monday', mktime(0, 0, 0));
                break;
            case 2:
                $timetosend = strtotime('next tuesday', mktime(0, 0, 0));
                break;
            case 3:
                $timetosend = strtotime('next wednesday', mktime(0, 0, 0));
                break;
            case 4:
                $timetosend = strtotime('next thursday', mktime(0, 0, 0));
                break;
            case 5:
                $timetosend = strtotime('next friday', mktime(0, 0, 0));
                break;
            case 6:
                $timetosend = strtotime('next saturday', mktime(0, 0, 0));
                break;
        }

        return $timetosend;
    }

    /**
     * Check permision can self comment.
     *
     * @param studentquiz_question $studentquizquestion Current studentquiz question stdClass
     * @param int $cmid Current Cmid
     * @param int $type Comment type.
     * @param bool $privatecommenting Does this studentquiz enable private commenting?
     * @return boolean
     */
    public static function allow_self_comment_and_rating_in_preview_mode(studentquiz_question $studentquizquestion, $cmid,
             $type = self::COMMENT_TYPE_PUBLIC, $privatecommenting = false) {
        global $USER, $PAGE;
        $question = $studentquizquestion->get_question();
        $context = \context_module::instance($cmid);
        if ($PAGE->pagetype == 'mod-studentquiz-preview' && !has_capability('mod/studentquiz:canselfratecomment', $context)) {
            if ($type == self::COMMENT_TYPE_PUBLIC || !$privatecommenting ||
                    $USER->id != $question->createdby ||
                    self::get_question_state($studentquizquestion) == \mod_studentquiz\local\studentquiz_helper::STATE_APPROVED) {
                return false;
            }
        }

        return true;
    }

    /** @var string - Less than operator */
    const OP_LT = "<";
    /** @var string - equal operator */
    const OP_E = "=";
    /** @var string - greater than operator */
    const OP_GT = ">";

    /**
     * Conveniently compare the current moodle version to a provided version in branch format. This function will
     * inflate version numbers to a three digit number before comparing them. This way moodle minor versions greater
     * than 9 can be correctly and easily compared.
     *
     * Examples:
     *   utils::moodle_version_is("<", "39");
     *   utils::moodle_version_is("<=", "310");
     *   utils::moodle_version_is(">", "39");
     *   utils::moodle_version_is(">=", "38");
     *   utils::moodle_version_is("=", "41");
     *
     * CFG reference:
     * $CFG->branch = "311", "310", "39", "38", ...
     * $CFG->release = "3.11+ (Build: 20210604)", ...
     * $CFG->version = "2021051700.04", ...
     *
     * @param string $operator for the comparison
     * @param string $version to compare to
     * @return boolean
     */
    public static function moodle_version_is(string $operator, string $version): bool {
        global $CFG;

        if (strlen($version) == 2) {
            $version = $version[0]."0".$version[1];
        }

        $current = $CFG->branch;
        if (strlen($current) == 2) {
            $current = $current[0]."0".$current[1];
        }

        $from = intval($current);
        $to = intval($version);
        $ops = str_split($operator);

        foreach ($ops as $op) {
            switch ($op) {
                case self::OP_LT:
                    if ($from < $to) {
                        return true;
                    }
                    break;
                case self::OP_E:
                    if ($from == $to) {
                        return true;
                    }
                    break;
                case self::OP_GT:
                    if ($from > $to) {
                        return true;
                    }
                    break;
                default:
                    throw new \coding_exception('invalid operator '.$op);
            }
        }

        return false;
    }

    /**
     * We hide 'All participants' option in group mode. It doesn't make sense to display question of all groups together,
     * and it makes confusing in reports. If the group = 0, NULL or an invalid group,
     * we force to chose first available group by default.
     *
     * @param stdClass $cm Course module class.
     */
    public static function set_default_group($cm) {
        global $USER;

        $allowedgroups = groups_get_activity_allowed_groups($cm, $USER->id);
        if ($allowedgroups && !groups_get_activity_group($cm, true, $allowedgroups)) {
            // Although the UI show that the first group is selected, the param 'group' is not set,
            // so the groups_get_activity_group() will return wrong value. We have to set it in $_GET to prevent the
            // problem when user go to the student quiz in the first time.
            $_GET['group'] = reset($allowedgroups)->id;
        }
    }

    /**
     * Get group joins for creating sql, using field groupid in studentquiz_question table.
     * If $groupid = 0, return empty sql_join to reduce the complication of the sql.
     *
     * @param int $groupid Group id.
     * @param string $groupidcolumn Group id column for the where clause.
     * @return sql_join The joins clause will be empty in this case, we just return the wheres and params.
     */
    public static function groups_get_questions_joins($groupid = 0, $groupidcolumn = 'sqq.groupid') {
        static $i = 0;
        $i++;
        $alias = 'gid' . $i;

        $joins = '';
        $wheres = '';
        $params = [];
        if ($groupid) {
            $wheres = "{$groupidcolumn} = :{$alias}";
            $params[$alias] = $groupid;
        }

        return new sql_join($joins, $wheres, $params);
    }

    /**
     * Get sql join to return users in a group.
     * To fix the issue in MOODLE_38_STABLE: the groups_get_members_join still return the join clause when we
     * turn off the group mode.
     *
     * @param int $groupid The group id.
     * @param string $useridcolumn The column of the user id from the calling SQL, e.g. u.id
     * @param context $context Course context or a context within a course. Mandatory when $groupids includes USERSWITHOUTGROUP
     * @return sql_join Contains joins, wheres, params
     */
    public static function sq_groups_get_members_join($groupid, $useridcolumn, $context = null) {
        // Don't need to join with group members if the user has the capability 'moodle/site:accessallgroups'.
        if (!$groupid || has_capability('moodle/site:accessallgroups', $context)) {
            $joins = '';
            $wheres = '';
            $params = [];

            return new sql_join($joins, $wheres, $params);
        }

        return groups_get_members_join($groupid, $useridcolumn, $context);
    }

    /**
     * Mark the active tab in question comment tabs.
     *
     * @param array $tabs All tabs.
     * @param bool $privatecommenting Does the studentquiz enable private commenting?
     * @return void.
     */
    public static function mark_question_comment_current_active_tab(&$tabs, $privatecommenting = false): void {
        $currentactivetab = '';
        if ($privatecommenting) {
            // First view default is private comment tab.
            $currentactivetab = get_user_preferences(self::USER_PREFERENCE_QUESTION_ACTIVE_TAB, self::COMMENT_TYPE_PRIVATE);
        }

        $found = false;
        if ($currentactivetab) {
            foreach ($tabs as $key => $tab) {
                if ($tab['id'] == $currentactivetab) {
                    $tabs[$key]['active'] = true;
                    $found = true;
                }
            }
        }

        // If we can not found any tab, just active the first tab.
        if (!$found) {
            $tabs[0]['active'] = true;
        }

        // Allow user to update user preference via ajax.
        user_preference_allow_ajax_update(self::USER_PREFERENCE_QUESTION_ACTIVE_TAB, PARAM_TEXT);
    }

    /**
     * Can the current user view the private comment of this question.
     *
     * @param int $cmid Course module id.
     * @param \question_definition $question Question definition object.
     * @param bool $privatecommenting Does the studentquiz enable private commenting?

     * @return bool Question's state.
     */
    public static function can_view_private_comment($cmid, $question, $privatecommenting = false) {
        global $USER;

        if (!$privatecommenting) {
            return false;
        }

        $context = \context_module::instance($cmid);
        if (!has_capability('mod/studentquiz:cancommentprivately', $context)) {
            if (!has_capability('mod/studentquiz:canselfcommentprivately', $context) ||
                    $USER->id != $question->createdby) {
                return false;
            }
        }

        return true;
    }

    /**
     * Can the current user view the state_history table of this question.
     *
     * @param int $cmid Course module id.
     * @param \question_definition $question Question definition object.
     * @return bool Question's state.
     */
    public static function can_view_state_history($cmid, $question) {
        global $USER;

        $context = \context_module::instance($cmid);
        if (!has_capability('mod/studentquiz:changestate', $context) && $USER->id != $question->createdby) {
            return false;
        }

        return true;
    }

    /**
     * Get current state of question.
     *
     * @param studentquiz_question $studentquizquestion studentquizquestion instance.
     * @return int Question's state.
     */
    public static function get_question_state($studentquizquestion) {
        global $DB;

        return $DB->get_field('studentquiz_question', 'state', ['id' => $studentquizquestion->id]);
    }

    /**
     * Get the url to view an user's profile.
     *
     * @param int $userid The userid
     * @param int $courseid The courseid
     * @return moodle_url
     */
    public static function get_user_profile_url(int $userid, int $courseid): moodle_url {
        return new moodle_url('/user/view.php', [
            'id' => $userid,
            'course' => $courseid
        ]);
    }

    /**
     * Saving the action change state.
     *
     * @param int $studentquizquestionid Id of studentquizquestion.
     * @param int|null $userid
     * @param int $state The state of the question in the StudentQuiz.
     * @param int $timecreated The time do action.
     * @return bool|int True or new id
     */
    public static function question_save_action(int $studentquizquestionid, ?int $userid, int $state,
            int $timecreated = null) {
        global $DB;

        $data = new \stdClass();
        $data->studentquizquestionid = $studentquizquestionid;
        $data->userid = $userid;
        $data->state = $state;
        $data->timecreated = isset($timecreated) ? $timecreated : time();

        return $DB->insert_record('studentquiz_state_history', $data);
    }

    /**
     * Finds all the questions missing the state history information and create the default state history for imports
     * into the database.
     *
     * @param int|null $courseorigid
     * @return void
     */
    public static function fix_all_missing_question_state_history_after_restore($courseorigid=null): void {
        global $DB;

        $params = [];
        if (!empty($courseorigid)) {
            $params['course'] = $courseorigid;
        }

        $transaction = $DB->start_delegated_transaction();
        $studentquizes = $DB->get_recordset_select('studentquiz', 'course = :course', $params);

        foreach ($studentquizes as $studentquiz) {
            $context = \context_module::instance($studentquiz->coursemodule);
            $studentquiz = mod_studentquiz_load_studentquiz($studentquiz->coursemodule, $context->id);

            $sql = "SELECT sqq.id as studentquizquestionid, sqq.state, q.createdby, q.timecreated
                      FROM {studentquiz} sq
                      JOIN {studentquiz_question} sqq ON sqq.studentquizid = sq.id
                      JOIN {question_references} qr ON qr.itemid = sqq.id
                           AND qr.component = 'mod_studentquiz'
                           AND qr.questionarea = 'studentquiz_question'
                      JOIN {question_categories} qc ON qc.contextid = qr.usingcontextid
                      JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
                      JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid
                      JOIN {question} q ON qv.questionid = q.id
                     WHERE sq.coursemodule = :coursemodule
                           AND qc.id = :categoryid
                           AND NOT EXISTS (SELECT 1 FROM {studentquiz_state_history} WHERE studentquizquestionid = sqq.id)";

            $params = [
                'coursemodule' => $studentquiz->coursemodule,
                'categoryid' => $studentquiz->categoryid,
            ];
            $sqquestions = $DB->get_recordset_sql($sql, $params);

            if ($sqquestions) {
                foreach ($sqquestions as $sqquestion) {
                    // Create action new question by onwer.
                    self::question_save_action($sqquestion->studentquizquestionid, $sqquestion->createdby,
                        studentquiz_helper::STATE_NEW, $sqquestion->timecreated);

                    if (!($sqquestion->state == studentquiz_helper::STATE_NEW)) {
                        self::question_save_action($sqquestion->studentquizquestionid, null, $sqquestion->state, null);
                    }
                }
            }
            $sqquestions->close();
        }

        $studentquizes->close();
        $transaction->allow_commit();
    }

    /**
     * Get state history data.
     *
     * @param int $studentquizquestionid Student quiz question Id.
     * @return array State histories and Users array.
     */
    public static function get_state_history_data($studentquizquestionid): array {
        global $DB;

        $statehistories = $DB->get_records('studentquiz_state_history', ['studentquizquestionid' => $studentquizquestionid],
                'timecreated, id');
        $users = self::get_users_change_state($statehistories);

        return [$statehistories, $users];
    }

    /**
     * List of users do action change state.
     *
     * @param array $statehistories Lists of state histories.
     * @return array List of users do action change state.
     */
    public static function get_users_change_state(array $statehistories): array {
        global $DB;

        $userids = [];
        foreach ($statehistories as $statehistory) {
            if (!empty($statehistory->userid)) {
                $userids[$statehistory->userid] = 1;
            }
        }
        return $DB->get_records_list('user', 'id', array_keys($userids), '', '*');
    }

    /**
     * Return 'comment' or 'comments' base on the $numberofcomments.
     *
     * @param int $numberofcomments The studentquiz progress object.
     * @return string
     */
    public static function get_comment_plural_text($numberofcomments) {
        if (isset($numberofcomments) && $numberofcomments == 1) {
            return get_string('comment', 'studentquiz');
        } else {
            return get_string('commentplural', 'studentquiz');
        }
    }

    /**
     * List of states of questions.
     *
     * @param array $questionids Array of question's id.
     * @return array List of states.
     */
    public static function get_states(array $questionids): array {
        global $DB;
        list ($conditionquestionids, $params) = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
        $sql = "SELECT q.id, sqq.state
              FROM {studentquiz_question} sqq
              JOIN {question_references} qr ON qr.itemid = sqq.id
                   AND qr.component = 'mod_studentquiz'
                   AND qr.questionarea = 'studentquiz_question'
              JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
              JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                      SELECT MAX(version)
                                        FROM {question_versions}
                                       WHERE questionbankentryid = qbe.id AND status = :ready
                                  )
              JOIN {question} q ON q.id = qv.questionid
             WHERE q.id $conditionquestionids";

        $params['ready'] = question_version_status::QUESTION_STATUS_READY;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * List of questionnames of questions.
     *
     * @param array $questionids Array of question's id.
     * @return array List of questionnames.
     */
    public static function get_question_names(array $questionids): array {
        global $DB;

        return $DB->get_records_list('question', 'id', $questionids, '', 'id, name');
    }

    /**
     * Makes security checks for viewing. Will return an error message if the user cannot access the student quiz.
     *
     * @param object $cm - The course module object.
     * @param \context $context The context module.
     * @param string $title Page's title.
     * @return void
     */
    public static function require_access_to_a_relevant_group(object $cm, \context $context, string $title = ''): void {
        global $COURSE, $PAGE;
        $groupmode = (int)groups_get_activity_groupmode($cm, $COURSE);
        $currentgroup = groups_get_activity_group($cm, true);

        if ($groupmode === SEPARATEGROUPS && !$currentgroup && !has_capability('moodle/site:accessallgroups', $context)) {
            $renderer = $PAGE->get_renderer('mod_studentquiz');
            $renderer->render_error_message(get_string('error_permission', 'studentquiz'), $title);
            exit();
        }
    }
}
