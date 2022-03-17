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
 * Get comments services implementation.
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
 * Get comments services implementation.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_comments_api extends external_api {

    /**
     * Gets function parameter metadata.
     *
     * @return external_function_parameters Parameter info
     */
    public static function get_comments_parameters() {
        return new external_function_parameters([
                'studentquizquestionid' => new external_value(PARAM_INT, 'SQQ ID'),
                'cmid' => new external_value(PARAM_INT, 'Cm ID'),
                'numbertoshow' => new external_value(PARAM_INT, 'Number of comments to show, 0 will return all comments/replies',
                        VALUE_DEFAULT, container::NUMBER_COMMENT_TO_SHOW_BY_DEFAULT),
                'sort' => new external_value(PARAM_TEXT, 'Sort type', false),
                'type' => new external_value(PARAM_INT, 'Comment type', VALUE_DEFAULT, utils::COMMENT_TYPE_PUBLIC)
        ]);
    }

    /**
     * Returns description of method result values.
     *
     * @return external_single_structure
     */
    public static function get_comments_returns() {

        $replystructure = utils::get_comment_area_webservice_comment_reply_structure();

        $repliesstructure = $replystructure;
        $repliesstructure['replies'] = new external_multiple_structure(
                new external_single_structure($replystructure), 'List of replies belong to first level comment'
        );

        return new external_single_structure([
                'total' => new external_value(PARAM_INT, 'Total comments belong to this question'),
                'data' => new external_multiple_structure(new external_single_structure($repliesstructure), 'comments array')
        ]);
    }

    /**
     * Get comments belong to question.
     *
     * @param int $studentquizquestionid - Student quiz question Id.
     * @param int $cmid - CM ID.
     * @param int $numbertoshow - Number comments to show.
     * @param string $sort - Type of sort.
     * @param int $type - Comment type.
     * @return array
     */
    public static function get_comments($studentquizquestionid, $cmid, $numbertoshow, $sort, $type) {

        $params = self::validate_parameters(self::get_comments_parameters(), [
                'studentquizquestionid' => $studentquizquestionid,
                'cmid' => $cmid,
                'numbertoshow' => $numbertoshow,
                'sort' => $sort,
                'type' => $type
        ]);

        $studentquizquestion = utils::get_data_for_comment_area($params['studentquizquestionid'], $params['cmid']);
        $context = $studentquizquestion->get_context();
        self::validate_context($context);
        $commentarea = new container($studentquizquestion, null, $sort, $type);
        $comments = $commentarea->fetch_all($numbertoshow);

        $data = [];

        foreach ($comments as $comment) {
            $item = $comment->convert_to_object();
            $item->replies = [];
            if ($numbertoshow == 0) {
                foreach ($comment->get_replies() as $reply) {
                    $item->replies[] = $reply->convert_to_object();
                }
            }
            $data[] = $item;
        }

        return [
                'total' => $commentarea->get_num_comments(),
                'data' => $data
        ];
    }
}
