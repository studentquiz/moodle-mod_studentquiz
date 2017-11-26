<?php
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