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
 * Helper class for StudentQuiz
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for StudentQuiz
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_helper {

    /**
     * @var int STATE_DISAPPROVED state constant for disapproved
     */
    const STATE_DISAPPROVED = 0;

    /**
     * @var int STATE_APPROVED state constant for approved
     */
    const STATE_APPROVED = 1;

    /**
     * @var int STATE_NEW state constant for new
     */
    const STATE_NEW = 2;

    /**
     * @var int STATE_CHANGED state constant for changed
     */
    const STATE_CHANGED = 3;

    /**
     * @var int STATE_HIDE state constant for hidden
     */
    const STATE_HIDE = 4;

    /**
     * @var int STATE_DELETE state constant for deleted
     */
    const STATE_DELETE = 5;

    /**
     * Statename offers string representation for state codes. Probably only use for translation hints.
     * @var array constant to text
     */
    public static $statename = array(
        self::STATE_DISAPPROVED => 'disapproved',
        self::STATE_APPROVED => 'approved',
        self::STATE_NEW => 'new',
        self::STATE_CHANGED => 'changed',
        self::STATE_HIDE => 'hidden',
        self::STATE_DELETE => 'deleted',
    );

    /**
     * Get the total questions of StudentQuiz.
     *
     * @param mixed $cm Course module
     * @param int $contextid Context id
     * @return int Total of questions
     */
    public static function get_studentquiz_total_questions($cm, int $contextid): int {
        global $DB;

        if (is_null($cm)) {
            // New instance. Return 0.
            return 0;
        }

        $studentquiz = mod_studentquiz_load_studentquiz($cm->id, $contextid);

        $sql = "SELECT COUNT(q.id)
                  FROM {studentquiz} sq
                  JOIN {context} con ON con.instanceid = sq.coursemodule
                  JOIN {question_categories} qc ON qc.contextid = con.id
                  JOIN {question} q ON q.category = qc.id
                 WHERE q.hidden = 0
                       AND q.parent = 0
                       AND sq.coursemodule = :coursemodule
                       AND qc.id = :categoryid";

        $params = [
                'coursemodule' => $studentquiz->coursemodule,
                'categoryid' => $studentquiz->categoryid
        ];

        return $DB->count_records_sql($sql, $params);
    }

}
