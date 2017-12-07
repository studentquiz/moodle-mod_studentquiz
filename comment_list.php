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
 * Ajax request to this script gives all comments of a question back. Requires GET param "question_id"
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

$questionid = required_param('questionid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);

require_login();
require_sesskey();

header('Content-Type: text/html; charset=utf-8');

global $USER;
$userid = $USER->id;
$context = context_module::instance($cmid);
$studentquiz = mod_studentquiz_load_studentquiz($cmid, $context->id);
// TODO: has capability when anonymized?
$anonymize = $studentquiz->anonymrank;
if (has_capability('mod/studentquiz:unhideanonymous', $context)) {
    $anonymize = false;
}
$ismoderator = false;
if (mod_studentquiz_check_created_permission($cmid)) {
    $ismoderator = true;
}

$comments = mod_studentquiz_get_comments_with_creators($questionid);


echo mod_studentquiz_comment_renderer($comments, $userid, $anonymize, $ismoderator);
