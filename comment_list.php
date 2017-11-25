<?php
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
if(has_capability('mod/studentquiz:unhideanonymous', $context)) {
    $anonymize = false;
}
$ismoderator = false;
if(mod_studentquiz_check_created_permission($cmid)) {
    $ismoderator = true;
}

$comments = mod_studentquiz_get_comments_with_creators($questionid);


echo mod_studentquiz_comment_renderer($comments, $userid, $anonymize, $ismoderator);
