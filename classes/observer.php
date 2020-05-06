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

use mod_studentquiz\event\studentquiz_digest_changed;
use mod_studentquiz\utils;

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

    /**
     * Observer for the even question_moved - Create new record for studentquiz_questions table.
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
     * Observer for the even studentquiz_digest_changed
     *
     * @param studentquiz_digest_changed $event
     */
    public static function digest_changed(studentquiz_digest_changed $event) {
        global $DB;

        date_default_timezone_set('UTC');
        $olddigesttype = $event->other['olddigesttype'];

        if ($olddigesttype == utils::DAILY_DIGEST_TYPE) {
            $timetosend = strtotime(date('Y-m-d'));
        } else if ($olddigesttype == utils::WEEKLY_DIGEST_TYPE) {
            $digestfirstday = $event->other['olddigestfirstday'];
            $timetosend = utils::calculcate_notification_time_to_send($digestfirstday);
        }
        $DB->execute('UPDATE {studentquiz_notification}
                              SET timetosend = :newtimetosend
                            WHERE studentquizid = :studentquizid
                                  AND timetosend = :oldtimetosend
                                  AND status = :status',
                ['newtimetosend' => strtotime('-1 day', date('Y-m-d')), 'studentquizid' => $event->objectid,
                        'oldtimetosend' => $timetosend, 'status' => 0]);
    }

}
