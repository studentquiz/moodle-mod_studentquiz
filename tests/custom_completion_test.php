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
use cm_info;

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
     * Test update state function in custom_completion.
     *
     * @dataProvider test_update_provider
     * @covers ::update_state
     * @param int $completion The completion type: 0 - Do not indicate activity completion,
     * 1 - Students can manually mark the activity as completed, 2 - Show activity as complete when conditions are met.
     * @param bool $expected Expected result when run update.
     */
    public function test_update_state(int $completion, bool $expected): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);

        $user = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        $studentquiz = $this->getDataGenerator()->create_module('studentquiz', [
            'course' => $course->id,
            'completion' => $completion,
        ]);

        $cm = cm_info::create(get_coursemodule_from_id('studentquiz', $studentquiz->cmid));
        $completioninfo = new \completion_info($course);
        $customcompletion = new custom_completion($cm, $user->id, $completioninfo->get_core_completion_state($cm, $user->id));
        $status = $customcompletion::update_state($course, $cm, $user->id);

        $this->assertEquals($expected, $status);
    }

    /**
     * Data provider for test_update_state() test cases.
     *
     * @coversNothing
     * @return array List of data sets (test cases)
     */
    public static function test_update_provider(): array {
        return [
            'Do not indicate activity completion' => [
                0,
                false,
            ],
            'Students can manually mark the activity as completed' => [
                1,
                false,
            ],
            'Show activity as complete when conditions are met' => [
                2,
                true,
            ],
        ];
    }
}
