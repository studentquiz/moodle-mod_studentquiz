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

use advanced_testcase;
use mod_studentquiz\completion\custom_completion;
use mod_studentquiz\local\studentquiz_question;
use cm_info;
use mod_studentquiz\local\studentquiz_helper;

/**
 * Class for unit testing mod_studentquiz/custom_completion.
 *
 * @package   mod_studentquiz
 * @copyright 2023 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_studentquiz\completion\custom_completion
 */
class custom_completion_test extends advanced_testcase {
    /**
     * Test for get_defined_custom_rules().
     *
     * @covers ::get_defined_custom_rules
     */
    public function test_get_defined_custom_rules(): void {
        $rules = custom_completion::get_defined_custom_rules();
        $this->assertEquals(
            ['completionpoint', 'completionquestionpublished', 'completionquestionapproved'],
            $rules
        );
    }

    /**
     * Test for get_defined_custom_rule_descriptions().
     *
     * @covers ::get_custom_rule_descriptions
     */
    public function test_get_custom_rule_descriptions() {
        // Get defined custom rules.
        $rules = custom_completion::get_defined_custom_rules();

        // Build a mock cm_info instance.
        $mockcminfo = $this->getMockBuilder(cm_info::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();
        // Instantiate a custom_completion object using the mocked cm_info.
        $customcompletion = new custom_completion($mockcminfo, 1);

        // Get custom rule descriptions.
        $ruledescriptions = $customcompletion->get_custom_rule_descriptions();

        // Confirm that defined rules and rule descriptions are consistent with each other.
        $this->assertEquals(count($rules), count($ruledescriptions));
        foreach ($rules as $rule) {
            $this->assertArrayHasKey($rule, $ruledescriptions);
        }
    }

    /**
     * Test trigger completion state update function in custom_completion.
     *
     * @covers ::trigger_completion_state_update
     */
    public function test_trigger_completion_state_update(): void {
        $this->resetAfterTest();
        global $DB;

        $generator = $this->getDataGenerator();
        $questiongenerator = $generator->get_plugin_generator('core_question');

        // Prepare course.
        $course = $generator->create_course([
            'enablecompletion' => COMPLETION_ENABLED
        ]);

        // Prepare users.
        $student = $generator->create_user(['firstname' => 'Student', 'lastname' => '1',
            'email' => 'student1@localhost.com']);

        // Users enrolments.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id, 'manual');

        // Prepare studentquiz.
        $studentquizdata = [
            'course' => $course->id,
            'completionquestionapproved' => 1,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
        ];

        $cmid = $generator->create_module('studentquiz', $studentquizdata)->cmid;
        $studentquiz = mod_studentquiz_load_studentquiz($cmid, \context_module::instance($cmid)->id);

        // Prepare question.
        $this->setUser($student);
        $cm = cm_info::create(get_coursemodule_from_id('studentquiz', $cmid));
        $questions = $questiongenerator->create_question('truefalse', null,
            ['name' => 'Student 1 Question', 'category' => $studentquiz->categoryid]);
        $questions = \question_bank::load_question($questions->id);
        $studentquizquestions = studentquiz_question::get_studentquiz_question_from_question($questions);

        // The completion data should be empty.
        $count = $DB->count_records('course_modules_completion');
        $this->assertEquals(0, $count);

        // Approve question.
        $this->setAdminUser();
        $studentquizquestions->change_state_visibility(studentquiz_helper::STATE_APPROVED);
        \mod_studentquiz\completion\custom_completion::trigger_completion_state_update(
            $course, $cm, $student->id
        );

        // The completion data should exist.
        $count = $DB->count_records('course_modules_completion');
        $this->assertEquals(1, $count);
    }
}
