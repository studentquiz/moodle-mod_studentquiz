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
 * This page displays the student generated quizzes.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__DIR__)).'/config.php');
require_once(__DIR__.'/reportlib.php');
require_once(__DIR__.'/classes/event/studentquiz_report_quiz_viewed.php');

$cmid = optional_param('id', 0, PARAM_INT);
if (!$cmid) {
    $cmid = required_param('cmid', PARAM_INT);
}

$report = new mod_studentquiz_report($cmid);

require_login($report->get_course(), false, $report->get_coursemodule());

$context = context_module::instance($cmid);

mod_studentquiz_report_viewed($cmid, $context);

$PAGE->set_title($report->get_statistic_title());
$PAGE->set_heading($report->get_heading());
$PAGE->set_context($report->get_context());
$PAGE->set_url($report->get_stat_url());

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_studentquiz', 'report');

echo $renderer->view_stat($report);

echo $OUTPUT->footer();