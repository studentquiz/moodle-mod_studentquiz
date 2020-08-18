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
     * Observer for the event question_created - Create new record for studentquiz_questions table.
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
     * Observer for the event question_moved - Create new record for studentquiz_questions table.
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
     * Observer for the event studentquiz_digest_changed.
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

    /**
     * Observer for the event \core\event\capability_assigned. Updates context specific capability overrides
     * if needed.
     *
     * @param \core\event\capability_assigned $event
     */
    public static function capability_assigned(\core\event\capability_assigned $event) {
        if (self::has_capability_changed($event->other['capability'])) {
            self::apply_capabilityoverride($event->courseid);
        }
    }

    /**
     * Observer for the event \core\event\capability_unassigned. Updates context specific capability overrides
     * if needed.
     *
     * @param \core\event\capability_unassigned $event
     */
    public static function capability_unassigned(\core\event\capability_unassigned $event) {
        if (self::has_capability_changed($event->other['capability'])) {
            self::apply_capabilityoverride($event->courseid);
        }
    }

    /**
     * Observer for the event \core\event\user_enrolment_created. Update context specific capability overrides
     * if needed.
     *
     * @param \core\event\user_enrolment_created $event
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event) {
        self::apply_capabilityoverride($event->courseid);
    }

    /**
     * Observer for the event \core\event\user_enrolment_updated. Update context specific capability overrides
     * if needed.
     *
     * @param \core\event\user_enrolment_updated $event
     */
    public static function user_enrolment_updated(\core\event\user_enrolment_updated $event) {
        self::apply_capabilityoverride($event->courseid);
    }

    /**
     * Observer for the event \core\event\user_enrolment_deleted. Update context specific capability overrides
     * if needed.
     *
     * @param \core\event\user_enrolment_deleted $event
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        self::apply_capabilityoverride($event->courseid);
    }

    /**
     * Observer for the event \core\event\course_module_created. Add context specific capability overrides.
     *
     * @param \core\event\course_module_created $event
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        if ($event->other["modulename"] == "studentquiz") {
            self::apply_capabilityoverride_coursemodule($event->objectid);
        }
    }

    /**
     * Check if capability change affects StudentQuizzes
     *
     * @param string $capability
     * @return bool
     */
    private static function has_capability_changed($capability) {
        return ((strpos($capability, "mod/studentquiz:") === 0));
    }

    /**
     * Apply capability override for course (or system if courseid is empty). This function can be called even if you
     * don't know if there are StudentQuizzes at all.
     *
     * @param int $courseid
     */
    private static function apply_capabilityoverride($courseid = 0) {
        global $DB;

        $params = array();
        if (!empty($courseid)) {
            $params['course'] = $courseid;
        }

        $studentquizes = $DB->get_records('studentquiz', $params);

        foreach ($studentquizes as $studentquiz) {
            self::apply_capabilityoverride_coursemodule($studentquiz->coursemodule);
        }
    }

    /**
     * Apply capability override for coursemodule.
     * WARNING: Only suitable for StudentQuiz activities. The caller must verify beforehand.
     *
     * @param int $coursemoduleid
     */
    private static function apply_capabilityoverride_coursemodule($coursemoduleid) {
        $context = \context_module::instance($coursemoduleid);

        mod_studentquiz\permissions\contextoverride::ensurerelation($context,
            mod_studentquiz\permissions\contextoverride::$studentquizrelation
        );
    }

    /**
     * DO NOT USE! Temporarily allow applying of StudentQuiz capability overrides from the module update process for the
     * whole system. This is very likely to be a one-time exception to use such a function from outside the events. This
     * only exists to prevent duplicated code - the called method is intentionally private.
     */
    public static function module_update_backwardsfix_capabilityoverrides() {
        self::apply_capabilityoverride();
    }

    /**
     * DO NOT USE! Temporarily allow applying of StudentQuiz capability overrides from the view main page for moodles
     * not supporting the required events. This is very likely to be a one-time exception to use such a function from
     * outside the events. This only exists to prevent duplicated code - the called method is intentionally private.
     *
     * @param int $coursemoduleid
     */
    public static function backwardscompatibility_moodle_capabilityoverrides($coursemoduleid) {
        self::apply_capabilityoverride_coursemodule($coursemoduleid);
    }
}
