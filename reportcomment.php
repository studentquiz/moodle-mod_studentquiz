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
 * Script to send report for inappropriate comments, or show form for it.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_studentquiz\commentarea\container;
use mod_studentquiz\utils;
use mod_studentquiz\commentarea\form\comment_report_form;

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');

$cmid = required_param('cmid', PARAM_INT);
$studentquizquestionid = required_param('studentquizquestionid', PARAM_INT);
$commentid = required_param('commentid', PARAM_INT);
$referer = optional_param('referer', null, PARAM_URL);
$type = optional_param('type', 0, PARAM_INT);
$pageparams = [
        'cmid' => $cmid,
        'studentquizquestionid' => $studentquizquestionid,
        'commentid' => $commentid,
        'type' => $type
];

$studentquizquestion = new \mod_studentquiz\local\studentquiz_question($studentquizquestionid);
$cm = $studentquizquestion->get_cm();

// Authentication check.
require_login($cm->course, false, $cm);

global $OUTPUT, $PAGE, $COURSE, $USER;

$context = $studentquizquestion->get_context();
$studentquiz = $studentquizquestion->get_studentquiz();

$commentarea = new container($studentquizquestion);
$comment = $commentarea->query_comment_by_id($pageparams['commentid']);

// Prepare preview comment report url.
$previewurl = (new moodle_url('/mod/studentquiz/preview.php', [
        'cmid' => $cm->id,
        'questionid' => $question->id,
        'highlight' => $comment->get_id(),
        'type' => $type
]))->out(false);

if (!$referer) {
    $referer = $previewurl;
}

if (!$comment->can_report()) {
    throw new moodle_exception($comment->get_error(), "error");
}

$pagename = get_string('report_comment_pagename', 'studentquiz');
$url = new moodle_url($comment::ABUSE_PAGE, $pageparams);
$PAGE->set_url($url);
$PAGE->set_pagelayout('base');
$PAGE->set_title($pagename);
$PAGE->set_heading($pagename);
$PAGE->set_context($context);
if ($pagename) {
    $PAGE->navbar->add($pagename);
}

utils::require_access_to_a_relevant_group($cm, $context);

// Keep referer url.
$action = (new moodle_url($PAGE->url, ['referer' => $referer]))->out(false);

$customdata = [
        'studentquizquestionid' => $studentquizquestionid,
        'cmid' => $cm->id,
        'commentid' => $comment->get_id(),
        'email' => $USER->email,
        'username' => $USER->username,
        'ip' => getremoteaddr(),
        'fullname' => fullname($USER, true),
        'coursename' => $COURSE->shortname,
        'studentquizname' => $studentquiz->name,
        'previewurl' => $previewurl
];

$form = new comment_report_form($action, (object) $customdata);

if ($form->is_cancelled()) {
    redirect($referer);
}

echo $OUTPUT->header();
// If the form has been submitted successfully, send the email.
$formdata = $form->get_data();
if ($formdata) {
    utils::send_report($formdata, $commentarea->get_reporting_emails(), $customdata, $form->get_options());
    echo $OUTPUT->box(get_string('report_comment_feedback', 'studentquiz'));
    echo $OUTPUT->continue_button($referer);
} else {
    // Show the form.
    echo $form->display();
}
echo $OUTPUT->footer();
