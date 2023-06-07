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
use mod_studentquiz\local\studentquiz_helper;
use core_question\local\bank\question_version_status;

/**
 * Test class studentquiz_question
 *
 * @package mod_studentquiz
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_question_test extends \advanced_testcase {

    /**
     * Data provider for test_change_sq_question_visibility.
     *
     * @coversNothing
     * @return array
     */
    public function change_sq_question_visibility_provider(): array {

        return [
            'deleted' => [
                null,
                question_version_status::QUESTION_STATUS_HIDDEN,
                'deleted',
                studentquiz_helper::STATE_DELETE,
                null,
                null
            ],
            'State approved' => [
                studentquiz_helper::STATE_APPROVED,
                question_version_status::QUESTION_STATUS_READY,
                'state',
                studentquiz_helper::STATE_APPROVED,
                null,
                0,
            ],
            'State disapproved' => [
                studentquiz_helper::STATE_DISAPPROVED,
                question_version_status::QUESTION_STATUS_DRAFT,
                'state',
                studentquiz_helper::STATE_DISAPPROVED,
                null,
                null
            ],
            'Pin' => [
                0,
                null,
                'pinned',
                0,
                null,
                null
            ],
        ];
    }

    /**
     * Test change_sq_question_visibility function
     *
     * @dataProvider change_sq_question_visibility_provider
     * @covers \mod_studentquiz\local\studentquiz_question::change_sq_question_visibility
     * @param int|null $expectedvalue
     * @param string|null $expectstatus
     * @param string $type
     * @param int $value
     * @param int|null $expectstatehistory
     * @param int|null $hidden
     */
    public function test_change_sq_question_visibility(?int $expectedvalue, ?string $expectstatus,
            string $type, int $value, ?int $expectstatehistory, ?int $hidden): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        // Setup data.
        $this->studentquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_studentquiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $this->getDataGenerator()->create_course();

        $activity = $this->getDataGenerator()->create_module('studentquiz', [
            'course' => $course->id,
            'anonymrank' => true,
            'forcecommenting' => 1,
        ]);
        $context = \context_module::instance($activity->cmid);

        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $context->id);

        $question = $questiongenerator->create_question('description', null, ['category' => $studentquiz->categoryid]);
        $question = \question_bank::load_question($question->id);
        $studentquizquestion = studentquiz_question::get_studentquiz_question_from_question($question);
        // Execute.
        $studentquizquestion->change_sq_question_visibility($type, $value);
        $reflector = new \ReflectionProperty($studentquizquestion, 'data');
        $reflector->setAccessible(true);
        $sqqdata = $reflector->getValue($studentquizquestion);

        // Verify.
        if ($expectedvalue) {
            // Verify on database.
            $this->assertEquals($expectedvalue, $DB->get_field('studentquiz_question', $type,
                ['id' => $studentquizquestion->get_id()]));
            // Verify on SQQ object.
            $this->assertEquals($sqqdata->$type, $value);
        }
        if (isset($expectstatus)) {
            $this->assertEquals($expectstatus, $DB->get_field('question_versions', 'status',
                ['questionid' => $studentquizquestion->get_question()->id]));
        }
        if (isset($expectstatehistory)) {
            $this->assertEquals(true, $DB->record_exists('studentquiz_state_history',
                ['studentquizquestionid' => $studentquizquestion->get_id(), 'state' => studentquiz_helper::STATE_SHOW]));
        }
        if (isset($hidden)) {
            // Verify on database.
            $this->assertEquals($hidden, $DB->get_field('studentquiz_question', 'hidden',
                ['id' => $studentquizquestion->get_id()]));
            // Verify on SQQ object.
            $this->assertEquals($hidden, $sqqdata->hidden);
        }
    }
}
