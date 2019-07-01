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
     * Observer for the even question_created - Create new record for studentquiz_questions table.
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

}
