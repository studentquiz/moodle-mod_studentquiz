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
 * This page lists all the instances of StudentQuiz in a given course.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__DIR__)).'/config.php');
require_once(__DIR__ .'/lib.php');

$id = required_param('id', PARAM_INT);
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}
$coursecontext = context_course::instance($id);
require_login($course);

$params = array(
    'context' => $coursecontext
);
$event = \mod_studentquiz\event\course_module_instance_list_viewed::create($params);
$event->trigger();

$strname = get_string('modulenameplural', 'mod_studentquiz');
$PAGE->set_url('/mod/studentquiz/index.php', array('id' => $id));
$PAGE->navbar->add($strname);
$PAGE->set_title("$course->shortname: $strname");
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading($strname, 2);

// Get all the appropriate data.
if (!$studentquizzes = get_all_instances_in_course("studentquiz", $course)) {
    notice(get_string('thereareno', 'moodle', $strname), "../../course/view.php?id=$course->id");
    die;
}

// Configure table for displaying the list of instances.
$headings = array(get_string('name'));
$align = array('left');

if (course_format_uses_sections($course->format)) {
    array_unshift($headings, get_string('sectionname', 'format_'.$course->format));
} else {
    array_unshift($headings, '');
}
array_unshift($align, 'center');

$showing = '';

$table = new html_table();
$table->head = $headings;
$table->align = $align;

// Populate the table with the list of instances.
$currentsection = '';
foreach ($studentquizzes as $studentquiz) {
    $cm = get_coursemodule_from_instance('studentquiz', $studentquiz->id);
    $context = context_module::instance($cm->id);
    $data = array();

    // Section number if necessary.
    $strsection = '';
    if ($studentquiz->section != $currentsection) {
        if ($studentquiz->section) {
            $strsection = $studentquiz->section;
            $strsection = get_section_name($course, $studentquiz->section);
        }
        if ($currentsection) {
            $learningtable->data[] = 'hr';
        }
        $currentsection = $studentquiz->section;
    }
    $data[] = $strsection;

    // Link to the instance.
    $class = '';
    if (!$studentquiz->visible) {
        $class = ' class="dimmed"';
    }
    $data[] = "<a$class href=\"view.php?id=$studentquiz->coursemodule\">" .
        format_string($studentquiz->name, true) . '</a>';

    $table->data[] = $data;
} // End of loop over studentquiz instances.

// Display the table.
echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
