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
 * Defines services for the studentquiz module.
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
        'mod_studentquiz_set_state' => [
                'classname' => 'mod_studentquiz_external',
                'classpath' => 'mod/studentquiz/externallib.php',
                'methodname' => 'change_question_state',
                'description' => 'Copy a students previous attempt to a new attempt.',
                'type' => 'write',
                'ajax' => true
        ],
        // Get comments from a question.
        'mod_studentquiz_get_comments' => [
                'classname'   => 'mod_studentquiz\local\external\get_comments_api',
                'methodname'  => 'get_comments',
                'description' => 'Get comments belong to question',
                'type'        => 'read',
                'ajax'        => true
        ],
        // Expand comment and show all replies.
        'mod_studentquiz_expand_comment' => [
                'classname'   => 'mod_studentquiz\local\external\expand_comment_api',
                'methodname'  => 'expand_comment',
                'description' => 'Expand comment and show all replies',
                'type'        => 'read',
                'ajax'        => true
        ],
        // Create comment.
        'mod_studentquiz_create_comment' => [
                'classname'   => 'mod_studentquiz\local\external\create_comment_api',
                'methodname'  => 'create_comment',
                'description' => 'Create comment',
                'type'        => 'write',
                'ajax'        => true
        ],
        // Delete comment.
        'mod_studentquiz_delete_comment' => [
                'classname'   => 'mod_studentquiz\local\external\delete_comment_api',
                'methodname'  => 'delete_comment',
                'description' => 'Delete comment',
                'type' => 'write',
                'ajax' => true
        ],
        // Edit comment.
        'mod_studentquiz_edit_comment' => [
                'classname'   => 'mod_studentquiz\local\external\edit_comment_api',
                'methodname'  => 'edit_comment',
                'description' => 'Edit comment',
                'type'        => 'write',
                'ajax'        => true
        ]
];
$services = [
        'StudentQuiz services' => [
                'shortname' => 'studentquizservices',
                'functions' => [
                        'mod_studentquiz_set_state',
                        'mod_studentquiz_get_comments',
                        'mod_studentquiz_expand_comment',
                        'mod_studentquiz_delete_comment',
                        'mod_studentquiz_edit_comment'
                ],
                'requiredcapability' => '',
                'enabled' => 1,
        ]
];
