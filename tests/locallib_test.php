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

namespace mod_studentquiz;
use mod_studentquiz\local\studentquiz_question;
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');
/**
 * Unit tests for (some of) locallib.php
 *
 * @package mod_studentquiz
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class locallib_test extends \advanced_testcase {

    /**
     * List of tutors in group will receive email when student change question to "Reviewable" state.
     *
     * @covers ::mod_studentquiz_notify_reviewable_question
     */
    public function test_mod_studentquiz_notify_reviewable_question(): void {
        global $DB;
        $this->resetAfterTest();

        // Setup groups and activity.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $group1 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $group3 = $this->getDataGenerator()->create_group(['courseid' => $course->id]);

        $student1 = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $student2 = $this->getDataGenerator()->create_and_enrol($course, 'student');

        $teacher1 = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $teacher2 = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $teacher3 = $this->getDataGenerator()->create_and_enrol($course, 'teacher');
        $teacher4 = $this->getDataGenerator()->create_and_enrol($course, 'teacher');

        // Assign users to groups.
        // None : Teacher 4.
        // Group 1 : Student 1, Teacher 1.
        // Group 2 : Teacher 2.
        // Group 3 : Student 2, Teacher 1,2,3.
        $this->getDataGenerator()->create_group_member([
            'userid' => $student1->id,
            'groupid' => $group1->id
        ]);
        $this->getDataGenerator()->create_group_member([
            'userid' => $teacher1->id,
            'groupid' => $group1->id
        ]);
        $this->getDataGenerator()->create_group_member([
            'userid' => $teacher2->id,
            'groupid' => $group2->id
        ]);
        $this->getDataGenerator()->create_group_member([
            'userid' => $student2->id,
            'groupid' => $group3->id
        ]);
        $this->getDataGenerator()->create_group_member([
            'userid' => $student2->id,
            'groupid' => $group3->id
        ]);
        $this->getDataGenerator()->create_group_member([
            'userid' => $teacher1->id,
            'groupid' => $group3->id
        ]);
        $this->getDataGenerator()->create_group_member([
            'userid' => $teacher2->id,
            'groupid' => $group3->id
        ]);
        $this->getDataGenerator()->create_group_member([
            'userid' => $teacher3->id,
            'groupid' => $group3->id
        ]);
        $sq = $this->getDataGenerator()->create_module('studentquiz', [
            'course' => $course->id, 'completion' => 1,
            'completionexpected' => time() + DAYSECS,
            'groupmode' => SEPARATEGROUPS,
            'digesttype' => 2,
        ]);
        $cm = get_coursemodule_from_id('studentquiz', $sq->cmid);
        $contextid = \context_module::instance($sq->cmid);
        $studentquiz = mod_studentquiz_load_studentquiz($sq->cmid, $contextid->id);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $this->setUser($student1->id);
        $question = $questiongenerator->create_question('truefalse', null, [
            'name' => 'Test question',
            'category' => $studentquiz->categoryid
        ]);
        $question = \question_bank::load_question($question->id);
        $sqq = studentquiz_question::get_studentquiz_question_from_question($question, $studentquiz);
        mod_studentquiz_notify_reviewable_question($sqq, $course, $cm);
        $this->assertEquals(1, $DB->count_records('studentquiz_notification', ['recipientid' => $teacher1->id]));

        $this->setUser($student2->id);
        $question2 = $questiongenerator->create_question('truefalse', null, [
            'name' => 'Test question 2',
            'category' => $studentquiz->categoryid
        ]);
        $question2 = \question_bank::load_question($question2->id);
        $sqq = studentquiz_question::get_studentquiz_question_from_question($question2, $studentquiz);
        mod_studentquiz_notify_reviewable_question($sqq, $course, $cm);

        $this->assertEquals(2, $DB->count_records('studentquiz_notification', ['recipientid' => $teacher1->id]));
        $this->assertEquals(1, $DB->count_records('studentquiz_notification', ['recipientid' => $teacher2->id]));
        $this->assertEquals(1, $DB->count_records('studentquiz_notification', ['recipientid' => $teacher3->id]));
        $this->assertEquals(0, $DB->count_records('studentquiz_notification', ['recipientid' => $teacher4->id]));
    }
}
