<?php
/**
 * moodle core export object extension
 *
 * @package    mod_studentquiz
 * @subpackage questionbank
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__DIR__)).'/config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once(__DIR__ . '/export_form.php');
require_once($CFG->dirroot . '/question/format.php');

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars)
    = question_edit_setup('export', '/mod/studentquiz/export.php');
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, false, $cm);

// Get display strings.
$strexportquestions = get_string('exportquestions', 'question');

list($catid, $catcontext) = explode(',', $pagevars['cat']);
$category = $DB->get_record('question_categories', array("id" => $catid, 'contextid' => $catcontext), '*', MUST_EXIST);

// Header.
$PAGE->set_url($thispageurl);
$PAGE->set_title($strexportquestions);
$PAGE->set_heading($COURSE->fullname);
echo $OUTPUT->header();

$exportform = new mod_studentquiz_question_export_form($thispageurl,
    array('contexts' => $contexts->having_one_edit_tab_cap('export'), 'defaultcategory' => $pagevars['cat']));


if ($formform = $exportform->get_data()) {
    $thiscontext = $contexts->lowest();
    if (!is_readable($CFG->dirroot . "/question/format/{$formform->format}/format.php")) { // Extension file check.
        print_error('unknowformat', '', '', $formform->format);
    }
    $withcategories = 'nocategories';
    if (!empty($formform->cattofile)) {
        $withcategories = 'withcategories';
    }
    $withcontexts = 'nocontexts';
    if (!empty($formform->contexttofile)) {
        $withcontexts = 'withcontexts';
    }

    $classname = 'qformat_' . $formform->format;
    $qformat = new $classname();
    $filename = question_default_export_filename($COURSE, $category) .
        $qformat->export_file_extension();
    $exporturl = question_make_export_url($thiscontext->id, $category->id,
        $formform->format, $withcategories, $withcontexts, $filename);

    echo $OUTPUT->box_start();
    echo get_string('yourfileshoulddownload', 'question', $exporturl->out());
    echo $OUTPUT->box_end();

    // Don't allow force download for behat site, as pop-up can't be handled by selenium.
    if (!defined('BEHAT_SITE_RUNNING')) {
        $PAGE->requires->js_function_call('document.location.replace', array($exporturl->out(false)), false, 1);
    }

    echo $OUTPUT->continue_button(new moodle_url('view.php', $thispageurl->params()));
    echo $OUTPUT->footer();
    exit;
}

// Display export form.
echo $OUTPUT->heading_with_help($strexportquestions, 'exportquestions', 'question');

$exportform->display();

echo $OUTPUT->footer();
