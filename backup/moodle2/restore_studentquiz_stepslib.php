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
 * Define all the restore steps that will be used by the restore_studentquiz_activity_task
 *
 * @package   mod_studentquiz
 * @category  backup
 * @copyright 2016 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Structure step to restore one studentquiz activity
 *
 * @package   mod_studentquiz
 * @category  backup
 * @copyright 2016 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_studentquiz_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('studentquiz', '/activity/studentquiz');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_studentquiz($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->coursemodule = 0; // Will be updated later.

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        if ($data->grade < 0) {
            // Scale found, get mapping.
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        // Create the studentquiz instance.
        $newitemid = $DB->insert_record('studentquiz', $data);

        $this->apply_activity_instance($newitemid);
    }

    /**
     * Post-execution actions
     */
    protected function after_execute() {
        global $DB;

        // Add studentquiz related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_studentquiz', 'intro', null);

        // Update the coursemodule id on the studentquiz table.
        $courseid = $this->get_courseid();
        $moduleid = $DB->get_field('modules', 'id', array('name' => 'studentquiz'));

        $cms = $DB->get_records('course_modules', array('course' => $courseid, 'module' => $moduleid));

        foreach ($cms as $cm) {
            $studentquiz = $DB->get_record('studentquiz', array('id' => $cm->instance));
            $studentquiz->coursemodule = $cm->id;
            $DB->update_record('studentquiz', $studentquiz);
        }
    }
}
