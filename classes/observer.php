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
 * Event observers supported by this module
 *
 * @package mod_studentquiz
 * @copyright 2019 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers supported by this module
 *
 * @package mod_studentquiz
 * @copyright 2019 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_observer {

    /**
     * Observer for the event question_created - Create new record for studentquiz_question table.
     *
     * @param \core\event\question_created $event
     * @throws moodle_exception
     */
    public static function question_created(\core\event\question_created $event) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');
        if ($event->contextlevel == CONTEXT_MODULE) {
            $modinfo = get_fast_modinfo($event->courseid);
            $cm = $modinfo->get_cm($event->contextinstanceid);
            if ($cm->modname == 'studentquiz') {
                mod_studentquiz_ensure_studentquiz_question_record($event->objectid, $event->contextinstanceid);
            }
        }
    }

    /**
     * Observer for the event question_moved - Create new record for studentquiz_question table if the question is moved
     * into a studenquiz question category.
     *
     * @param \core\event\question_moved $event
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function question_moved(\core\event\question_moved $event) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');

        $newcategory = $DB->get_record('question_categories', ['id' => $event->other['newcategoryid']]);
        if (!$newcategory) {
            print_error('invalidcategoryid', 'error');
        }
        $context = context::instance_by_id($newcategory->contextid);
        if ($context->contextlevel == CONTEXT_MODULE) {
            $cm = get_coursemodule_from_id(false, $context->instanceid);
            if ($cm && $cm->modname == 'studentquiz') {
                mod_studentquiz_ensure_studentquiz_question_record($event->objectid, $context->instanceid);
            }
        }
    }

    /**
     * Observer for the event question_updated - Update record in studentquiz_question table.
     *
     * @param \core\event\question_updated $event
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function question_updated(\core\event\question_updated $event) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');
        if ($event->contextlevel == CONTEXT_MODULE) {
            $modinfo = get_fast_modinfo($event->courseid);
            $cm = $modinfo->get_cm($event->contextinstanceid);
            if ($cm->modname == 'studentquiz') {
                var_dump('question_updated', $event);
                // TODO: shouldn't this change the state of the question to "changed"?
            }
        }
    }

    /**
     * Observer for the event question_deleted - Remove record from the studentquiz_question table.
     *
     * @param \core\event\question_deleted $event
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function question_deleted(\core\event\question_deleted $event) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');
        if ($event->contextlevel == CONTEXT_MODULE) {
            $modinfo = get_fast_modinfo($event->courseid);
            $cm = $modinfo->get_cm($event->contextinstanceid);
            if ($cm->modname == 'studentquiz') {
                $DB->delete_records('studentquiz_question', array('questionid' => $event->objectid));
            }
        }
    }

}
