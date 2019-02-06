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
 * Task for cleaning up obsolete quiz instances
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/lib.php');

class delete_quiz_after_migration extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('task_delete_quiz_after_migration', 'mod_studentquiz');
    }

    /**
     * This tasks removes one orphaned quiz per execution
     */
    public function execute() {
        global $DB;

        // Search if there is an orphaned quiz, which has the same name as an studentquiz and is in the same course,
        // but is in no section anymore.
        $sql = "SELECT q.id AS quizid, q.name AS quizname, cmq.course AS quizcourseid, csq.id AS quizsectionid,
                       s.id AS studentquizid, s.name AS studentquizname, cms.course AS studentquizcourseid
                  FROM {modules} ms
            INNER JOIN {course_modules} cms ON ms.id = cms.module
            INNER JOIN {studentquiz} s ON cms.instance = s.id
             LEFT JOIN {course_modules} cmq ON cms.course = cmq.course
            INNER JOIN {quiz} q ON cmq.instance = q.id
            INNER JOIN {modules} mq ON cmq.module = mq.id
             LEFT JOIN {course_sections} csq ON cmq.section = csq.id
                 WHERE ms.name = 'studentquiz'
                       AND mq.name = 'quiz'
                       AND csq.id IS NULL
                       AND q.name LIKE concat(s.name, '%')
              ORDER BY cms.course, s.id, q.id
                 LIMIT 1";
        $orphanedquiz = $DB->get_record_sql($sql);

        // We have found a orphaned quiz, remove it.
        if ($orphanedquiz !== false) {
            quiz_delete_instance($orphanedquiz->quizid);
        }
    }
}
