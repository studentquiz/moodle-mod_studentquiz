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
 * Class that holds utility functions used by mod_studentquiz.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz;

defined('MOODLE_INTERNAL') || die();

use core\dml\sql_join;
use core_courseformat\output\local\state\cm;
use core_question\bank\search\hidden_condition;
use external_value;
use external_single_structure;
use mod_studentquiz\commentarea\comment;
use moodle_url;
use mod_studentquiz\local\studentquiz_helper;

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

    /**
     * Get Comment Area web service comment reply structure.
     *
     * @return array
     */
    public static function get_comment_area_webservice_comment_reply_structure() {
        return [
                'id' => new external_value(PARAM_INT, 'Comment ID'),
                'questionid' => new external_value(PARAM_INT, 'Question ID'),
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
     * Truncate text.
     *
     * @param string $text - Full text.
     * @param int $length - Max length of text.
     * @return string
     */
    public static function nice_shorten_text($text, $length = 40) {
        $text = trim($text);
        // Replace image tag by placeholder text.
        $text = preg_replace('/<img.*?>/', get_string('image_placeholder', 'mod_studentquiz'), $text);
        $text = mb_convert_encoding($text, "HTML-ENTITIES", "UTF-8");
        // Trim the multiple spaces to single space and multiple lines to one line.
        $text = preg_replace('!\s+!', ' ', $text);
        $summary = shorten_text($text, $length);
        $summary = preg_replace('~\s*\.\.\.(<[^>]*>)*$~', '$1', $summary);
        $dots = $summary != $text ? '...' : '';
        return $summary . $dots;
    }

    /**
     * Get data need for comment area.
     *
     * @param int $questionid - Question ID.
     * @param int $cmid - Course Module ID.
     * @return array
     */
    public static function get_data_for_comment_area($questionid, $cmid) {
        $cm = get_coursemodule_from_id('studentquiz', $cmid);
        $context = \context_module::instance($cm->id);
        $studentquiz = mod_studentquiz_load_studentquiz($cmid, $context->id);
        $question = \question_bank::load_question($questionid);
        return [$question, $cm, $context, $studentquiz];
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
                throw new moodle_exception('error_sendalert', 'studentquiz', $previewurl, $fakeuser->email);
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
     * @param \question_definition $question Current Question stdClass
     * @param int $cmid Current Cmid
     * @param int $type Comment type.
     * @return boolean
     */
    public static function allow_self_comment_and_rating_in_preview_mode(\question_definition $question, $cmid,
             $type = self::COMMENT_TYPE_PUBLIC) {
        global $USER, $PAGE;

        $context = \context_module::instance($cmid);
        if ($PAGE->pagetype == 'mod-studentquiz-preview' && !has_capability('mod/studentquiz:canselfratecomment', $context)) {
            if ($type == self::COMMENT_TYPE_PUBLIC || !get_config('studentquiz', 'showprivatecomment') ||
                    $USER->id != $question->createdby ||
                    self::get_question_state($question) == \mod_studentquiz\local\studentquiz_helper::STATE_APPROVED) {
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
     * @throws coding_exception
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
     * @throws coding_exception if empty or invalid context submitted when $groupid = USERSWITHOUTGROUP
     */
    public static function sq_groups_get_members_join($groupid, $useridcolumn, $context = null) {
        if (!$groupid) {
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
     * @return void.
     */
    public static function mark_question_comment_current_active_tab(&$tabs): void {
        $currentactivetab = '';
        if (get_config('studentquiz', 'showprivatecomment')) {
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
    }

    /**
     * Can the current user view the private comment of this question.
     *
     * @param int $cmid Course module id.
     * @param \question_definition $question Question definition object.
     * @return bool Question's state.
     */
    public static function can_view_private_comment($cmid, $question) {
        global $USER;

        if (!get_config('studentquiz', 'showprivatecomment')) {
            return false;
        }

        $context = \context_module::instance($cmid);
        if (!has_capability('mod/studentquiz:canselfratecomment', $context)) {
            if ($USER->id != $question->createdby) {
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
        if (!has_capability('mod/studentquiz:canselfratecomment', $context) && $USER->id != $question->createdby) {
            return false;
        }

        return true;
    }

    /**
     * Get current state of question.
     *
     * @param \stdClass $question Question.
     * @return int Question's state.
     */
    public static function get_question_state($question) {
        global $DB;

        return $DB->get_field('studentquiz_question', 'state', ['questionid' => $question->id]);
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
     * @param int $questionid Id of question
     * @param int|null $userid
     * @param int $state The state of the question in the StudentQuiz.
     * @param int $timecreated The time do action.
     * @return bool|int True or new id
     */
    public static function question_save_action(int $questionid, int $userid = null, int $state, int $timecreated = null) {
        global $DB, $USER;

        $data = new \stdClass();
        $data->questionid = $questionid;
        $data->userid = isset($userid) ? $userid : $USER->id;
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

        $studentquizes = $DB->get_records('studentquiz', $params);
        $transaction = $DB->start_delegated_transaction();

        try {
            foreach ($studentquizes as $studentquiz) {
                $context = \context_module::instance($studentquiz->coursemodule);
                $studentquiz = mod_studentquiz_load_studentquiz($studentquiz->coursemodule, $context->id);

                $sql = "SELECT sqq.questionid, sqq.state, q.createdby, q.timecreated
                          FROM {studentquiz} sq
                          JOIN {context} con ON con.instanceid = sq.coursemodule
                          JOIN {question_categories} qc ON qc.contextid = con.id
                          JOIN {question} q ON q.category = qc.id
                          JOIN {studentquiz_question} sqq ON sqq.questionid = q.id
                         WHERE sq.coursemodule = :coursemodule
                               AND qc.id = :categoryid";

                $params = [
                    'coursemodule' => $studentquiz->coursemodule,
                    'categoryid' => $studentquiz->categoryid
                ];
                $sqquestions = $DB->get_records_sql($sql, $params);

                if ($sqquestions) {
                    foreach ($sqquestions as $sqquestion) {
                        if (!$DB->count_records('studentquiz_state_history', ['questionid' => $sqquestion->questionid])) {
                            // Create action new question by onwer.
                            self::question_save_action($sqquestion->questionid, $sqquestion->createdby,
                                studentquiz_helper::STATE_NEW, $sqquestion->timecreated);

                            if (!($sqquestion->state == studentquiz_helper::STATE_NEW)) {
                                self::question_save_action($sqquestion->questionid, get_admin()->id, $sqquestion->state, null);
                            }
                        }
                    }
                }
            }

            $DB->commit_delegated_transaction($transaction);
        } catch (Exception $e) {
            $DB->rollback_delegated_transaction($transaction, $e);
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get state history data.
     *
     * @param \question_definition $question Question definition object.
     * @return array State histories and Users array.
     */
    public static function get_state_history_data($question): array {
        global $DB;

        $statehistories = $DB->get_records('studentquiz_state_history', ['questionid' => $question->id]);
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
            $userids[$statehistory->userid] = 1;
        }

        return $DB->get_records_list('user', 'id', array_keys($userids), '', '*');
    }

    /**
     * Get current visibility of question.
     *
     * @param int $questionid Question's id.
     * @return bool Question's visibility hide/show.
     */
    public static function check_is_question_hidden(int $questionid): bool {
        global $DB;
        $ishidden = $DB->get_field('studentquiz_question', 'hidden', ['questionid' => $questionid]);

        return $ishidden == self::HIDDEN;
    }
}
