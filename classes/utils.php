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

use external_value;
use external_single_structure;
use mod_studentquiz\commentarea\comment;

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
                'posttime' => new external_value(PARAM_RAW, 'Comment create time'),
                'deleted' => new external_value(PARAM_BOOL, 'Comment is deleted or not'),
                'deletedtime' => new external_value(PARAM_RAW, 'Comment edited time, if not deleted return 0'),
                'deleteuser' => new external_single_structure([
                        'firstname' => new external_value(PARAM_TEXT, 'Delete user first name'),
                        'lastname' => new external_value(PARAM_TEXT, 'Delete user last name'),
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
                print_error('error_sendalert', 'studentquiz', $previewurl, $fakeuser->email);
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
}
