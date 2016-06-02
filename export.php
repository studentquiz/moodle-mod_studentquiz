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
 * moodle core export object extension
 *
 * @package    mod_studentquiz
 * @subpackage questionbank
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once(dirname(__FILE__) . '/export_form.php');
require_once($CFG->dirroot . '/question/format.php');

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars)
    = question_edit_setup('export', '/mod/studentquiz/export.php');

// Get display strings.
$strexportquestions = get_string('exportquestions', 'question');

list($catid, $catcontext) = explode(',', $pagevars['cat']);
$category = $DB->get_record('question_categories', array("id" => $catid, 'contextid' => $catcontext), '*', MUST_EXIST);

// Header.
$PAGE->set_url($thispageurl);
$PAGE->set_title($strexportquestions);
$PAGE->set_heading($COURSE->fullname);
echo $OUTPUT->header();

$exportform = new question_export_form($thispageurl,
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
