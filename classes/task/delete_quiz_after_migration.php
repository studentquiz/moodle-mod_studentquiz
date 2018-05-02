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
        $orphanedquiz = $DB->get_record_sql('
        select
            q.id as quizid,
            q.name as quizname,
            cmq.course as quizcourseid,
            csq.id as quizsectionid,
            s.id as studentquizid,
            s.name as studentquizname,
            cms.course as studentquizcourseid
        from {modules} ms
        inner join {course_modules} cms on ms.id = cms.module
        inner join {studentquiz} s on cms.instance = s.id
        left join {course_modules} cmq on cms.course = cmq.course
        inner join {quiz} q on cmq.instance = q.id
        inner join {modules} mq on cmq.module = mq.id
        left join {course_sections} csq on cmq.section = csq.id
        where ms.name = \'studentquiz\'
        and mq.name = \'quiz\'
        and csq.id is null
        and q.name like concat(s.name, \'%\')
        order by
            cms.course,
            s.id,
            q.id
        limit 1
        ');

        // We have found a orphaned quiz, remove it.
        if ($orphanedquiz !== false) {
            quiz_delete_instance($orphanedquiz->quizid);
        }
    }
}
