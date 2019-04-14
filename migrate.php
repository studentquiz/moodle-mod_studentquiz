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
 * This page displays the migration dialoag for /mod/studentquiz.
 * https://github.com/frankkoch/moodle-mod_studentquiz/issues/73
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/viewlib.php');
require_once(__DIR__ . '/renderer.php');

$cmid = optional_param('id', 0, PARAM_INT);

if (!$cmid) {
    $cmid = required_param('cmid', PARAM_INT);
}

// Load course and course module requested.
if ($cmid) {
    if (!$cm = get_coursemodule_from_id('studentquiz', $cmid)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
} else {
    print_error('invalidcoursemodule');
}
// Authentication check.
require_login($cm->course, false, $cm);

// Load context.
$context = context_module::instance($cm->id);

// Load studentquiz.
$studentquiz = mod_studentquiz_load_studentquiz($cm->id, $context->id);

// Load context.
$context = context_module::instance($cm->id);

$justmigrated = false;

// Check capability and redirect if doesn't has the capability.
if (!has_capability('mod/studentquiz:manage', $context)) {
    redirect(new moodle_url('/mod/studentquiz/view.php', array("id" => $cm->id)));
}

// If data is submitted do the data migration.
if (data_submitted()) {
    if (optional_param("do", '', PARAM_RAW) === 'yes') {
        if ($studentquiz->aggregated == 0) {
            $data = mod_studentquiz_get_studentquiz_progress_from_question_attempts_steps($studentquiz->id);

            $DB->insert_records('studentquiz_progress', new ArrayIterator($data));

            $studentquiz->aggregated = 1;

            $DB->update_record('studentquiz', $studentquiz);

            $justmigrated = true;
        }
    }
}

$PAGE->set_title(get_string('migrate_studentquiz_short', 'studentquiz'));
$PAGE->set_heading(get_string('migrate_studentquiz_short', 'studentquiz'));
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/studentquiz/migrate.php', array('cmid' => $cmid)));

$output = $PAGE->get_renderer('mod_studentquiz', 'migration');

echo $OUTPUT->header();

if ($justmigrated) {
    echo $output->view_body_success($cmid, $studentquiz);
} else {
    echo $output->view_body($cmid, $studentquiz);
}

echo $OUTPUT->footer();