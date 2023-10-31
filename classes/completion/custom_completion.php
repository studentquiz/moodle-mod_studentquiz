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
declare(strict_types=1);

namespace mod_studentquiz\completion;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../reportlib.php');

use core_completion\activity_custom_completion;
use mod_studentquiz_report;
use cm_info;
use stdClass;

/**
 * Activity custom completion subclass for the StudentQuiz activity.
 *
 * Class for defining mod_studentquiz's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given data instance and a user.
 *
 * @package   mod_studentquiz
 * @copyright 2023 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    public function get_state(string $rule): int {
        global $DB;

        $studentquizid = $this->cm->instance;
        $studentquiz = $DB->get_record('studentquiz', ['id' => $studentquizid], '*', MUST_EXIST);
        $report = new mod_studentquiz_report($this->cm->id, $this->userid);
        $userstats = $report->get_user_stats();

        if (!$userstats) {
            return COMPLETION_INCOMPLETE;
        }

        switch ($rule) {
            case 'completionpoint':
                $status = $studentquiz->completionpoint <= (int) $userstats->points;
                break;
            case 'completionquestionpublished':
                $status = $studentquiz->completionquestionpublished <= (int) $userstats->questions_created;
                break;
            case 'completionquestionapproved':
                $status = $studentquiz->completionquestionapproved <= (int) $userstats->questions_approved;
                break;
            default:
                $status = false;
                break;
        }

        return $status ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    public static function get_defined_custom_rules(): array {
        return [
            'completionpoint',
            'completionquestionpublished',
            'completionquestionapproved',
        ];
    }

    public function get_custom_rule_descriptions(): array {
        $completionpoint = $this->cm->customdata->customcompletionrules['completionpoint'] ?? 0;
        $completionquestionpublished = $this->cm->customdata->customcompletionrules['completionquestionpublished'] ?? 0;
        $completionquestionapproved = $this->cm->customdata->customcompletionrules['completionquestionapproved'] ?? 0;

        return [
            'completionpoint' => get_string('completiondetail:point', 'studentquiz', $completionpoint),
            'completionquestionpublished' => get_string('completiondetail:published', 'studentquiz',
                $completionquestionpublished),
            'completionquestionapproved' => get_string('completiondetail:approved',
                'studentquiz', $completionquestionapproved),
        ];
    }

    public function get_sort_order(): array {
        $defaults = [
           'completionview',
        ];

        return array_merge($defaults, self::get_defined_custom_rules());
    }

    /**
     * Trigger completion state update for a given user on a given StudentQuiz.
     *
     * @param stdClass $course The course containing the StudentQuiz to update.
     * @param stdClass|cm_info $cm The cm for the StudentQuiz to update.
     * @param int|null $userid The user to update state for.
     */
    public static function trigger_completion_state_update(stdClass $course, $cm, ?int $userid = null): void {
        $completion = new \completion_info($course);
        if ($completion->is_enabled($cm) && $cm->completion != COMPLETION_TRACKING_MANUAL) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $userid);
        }
    }
}
