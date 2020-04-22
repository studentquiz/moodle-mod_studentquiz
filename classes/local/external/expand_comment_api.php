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
 * Expand comment services implementation.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\local\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use mod_studentquiz\commentarea\container;
use mod_studentquiz\utils;

require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * Expand comment services implementation.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class expand_comment_api extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function expand_comment_parameters() {
        return new external_function_parameters([
                'questionid' => new external_value(PARAM_INT, 'Question ID'),
                'cmid' => new external_value(PARAM_INT, 'Cm ID'),
                'commentid' => new external_value(PARAM_INT, 'Comment ID'),
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function expand_comment_returns() {
        $replystructure = utils::get_comment_area_webservice_comment_reply_structure();
        $repliesstructure = $replystructure;
        $repliesstructure['replies'] = new external_multiple_structure(
                new external_single_structure($replystructure), 'List of replies belong to first level comment'
        );
        return new external_single_structure($repliesstructure);
    }

    /**
     * Get posts belong to diccussion.
     *
     * @param int $questionid - Question ID
     * @param int $cmid - CM ID
     * @param int $commentid - Comment ID
     * @return mixed
     */
    public static function expand_comment($questionid, $cmid, $commentid) {

        $params = self::validate_parameters(self::expand_comment_parameters(), [
                'questionid' => $questionid,
                'cmid' => $cmid,
                'commentid' => $commentid
        ]);

        list($question, $cm, $context, $studentquiz) = utils::get_data_for_comment_area($params['questionid'], $params['cmid']);
        self::validate_context($context);
        $commentarea = new container($studentquiz, $question, $cm, $context);

        $comment = $commentarea->query_comment_by_id($params['commentid']);

        if (!$comment) {
            throw new \moodle_exception(\get_string('invalidcomment', 'studentquiz'), 'studentquiz');
        }

        $data = $comment->convert_to_object();

        $data->replies = [];

        foreach ($comment->get_replies() as $reply) {
            $data->replies[] = $reply->convert_to_object();
        }

        return $data;
    }
}
