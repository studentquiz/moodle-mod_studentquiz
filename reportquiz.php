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
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/reportlib.php');
require_once(dirname(__FILE__).'/classes/event/studentquiz_report_quiz_viewed.php');

$cmid = optional_param('id', 0, PARAM_INT);
if (!$cmid) {
    $cmid = required_param('cmid', PARAM_INT);
}

$report = new mod_studentquiz_report($cmid);
require_login($report->get_course(), true, $report->get_coursemodule());

$params = array(
    'objectid' => $report->get_cm_id(),
    'context' => $report->get_context()
);

$event = \mod_studentquiz\event\studentquiz_report_quiz_viewed::create($params);
$event->trigger();

$PAGE->set_title($report->get_title());
$PAGE->set_heading($report->get_heading());
$PAGE->set_context($report->get_context());
$PAGE->set_url($report->get_quizreporturl());


echo $OUTPUT->header();
if ($report->is_admin()) {
    echo $report->get_quiz_admin_statistic_view();
} else {
    echo $report->get_quiz_tables();
}
echo $OUTPUT->footer();
