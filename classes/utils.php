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

/**
 * Class that holds utility functions used by mod_studentquiz.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

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
                'hascomment' => new external_value(PARAM_BOOL, 'Check if in current user has comment')
        ];
    }

    /**
     * Truncate text.
     *
     * @param $text - Full text.
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
     * @param $questionid - Question ID.
     * @param $cmid - Course Module ID.
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
    public static function count_comments_and_replies(array $data) : array {
        $commentcount = 0;
        $deletecommentcount = 0;
        $replycount = 0;
        $deletereplycount = 0;

        if (count($data) > 0) {
            foreach ($data as $v) {
                if ($v->deletedtime === 0) {
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
     * Get blank comment for privacy.
     *
     * @return array
     */
    public static function get_blank_comment() {
        $guestuserid = guest_user()->id;
        return [
                'guestuserid' => $guestuserid,
                'deleted' => time(),
                'deleteuserid' => $guestuserid,
                'comment' => ''
        ];
    }
}
