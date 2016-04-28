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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/reportlib.php');
require_once(dirname(__FILE__) . '/renderer.php');

$report = new studentquiz_report();
require_login($report->getCourse(), true, $report->getCM());

$params = array(
    'objectid' => $report->getCMid(),
    'context' => $report->getContext()
);
//$event = \mod_studentquiz\event\studentquiz_practice_summary::create($params);
//$event->trigger();

$PAGE->set_title($report->getTitle());
$PAGE->set_heading($report->getHeading());
$PAGE->set_context($report->getContext());
$PAGE->set_url($report->get_viewurl());
$output = $PAGE->get_renderer('mod_studentquiz');

echo $OUTPUT->header();
echo $output->report-quiz_table($psessionid);
echo $OUTPUT->footer();