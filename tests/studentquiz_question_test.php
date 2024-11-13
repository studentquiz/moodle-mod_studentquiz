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
     * @var \question_definition $question
     */
    private $question;

    /**
     * @var studentquiz_question $studentquizquestion
     */
    private $studentquizquestion;

    /**
     * Setup testing data
     */
    protected function setUp(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        // Setup data.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('studentquiz', [
            'course' => $course,
            'anonymrank' => true,
            'forcecommenting' => 1,
        ]);

        $context = \context_module::instance($activity->cmid);
        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $context->id);
        $questioncreated = $questiongenerator->create_question('description', null,
            ['category' => $studentquiz->categoryid]);

        $this->question = \question_bank::load_question($questioncreated->id);
        $this->studentquizquestion = studentquiz_question::get_studentquiz_question_from_question($this->question);

    }

    /**
     * Test change state when approve a hidden question.
     *
     * @covers \mod_studentquiz\local\studentquiz_question::change_state_visibility
     */
    public function test_change_state_approve_on_hidden_question(): void {
        global $DB;
        // Execute.
        $this->studentquizquestion->change_hidden_status(1);
        $this->studentquizquestion->change_state_visibility(studentquiz_helper::STATE_APPROVED);

        // Verify question status.
        $this->assertEquals(question_version_status::QUESTION_STATUS_READY,
            $DB->get_field('question_versions', 'status', ['questionid' => $this->studentquizquestion->get_question()->id]));

        // Verify state on studentquiz_question db.
        $this->assertEquals(studentquiz_helper::STATE_APPROVED,
            $DB->get_field('studentquiz_question', 'state', ['id' => $this->studentquizquestion->get_id()]));

        // Verify on state history.
        $this->assertEquals(true, $DB->record_exists('studentquiz_state_history',
            ['studentquizquestionid' => $this->studentquizquestion->get_id(), 'state' => studentquiz_helper::STATE_SHOW]));

        // Verify on SQQ object.
        $sqqdata = self::get_data_properties_on_studentquiz_question();
        $this->assertEquals($sqqdata->state, studentquiz_helper::STATE_APPROVED);
        $this->assertEquals($sqqdata->hidden, 0);
    }

    /**
     * Test change state to delete for a question.
     *
     * @covers \mod_studentquiz\local\studentquiz_question::change_state_visibility
     */
    public function test_change_state_delete_question(): void {
        global $DB;
        // Execute.
        $this->studentquizquestion->change_delete_state();

        // Verify question status.
        $this->assertEquals(question_version_status::QUESTION_STATUS_HIDDEN,
            $DB->get_field('question_versions', 'status', ['questionid' => $this->studentquizquestion->get_question()->id]));
    }

    /**
     * Test pin a question.
     *
     * @covers \mod_studentquiz\local\studentquiz_question::change_pin_status
     */
    public function test_change_pin_status(): void {
        global $DB;
        // Execute.
        $this->studentquizquestion->change_pin_status(1);

        // Verify state on studentquiz_question db.
        $this->assertEquals(1, $DB->get_field('studentquiz_question', 'pinned', ['id' => $this->studentquizquestion->get_id()]));

        // Verify on SQQ object.
        $sqqdata = self::get_data_properties_on_studentquiz_question();
        $this->assertEquals($sqqdata->pinned, 1);
    }

    /**
     * Get the private properties on data object in studentquiz_question.
     *
     * @return object
     */
    private function get_data_properties_on_studentquiz_question(): object {
        $reflector = new \ReflectionProperty($this->studentquizquestion, 'data');
        $reflector->setAccessible(true);
        return $reflector->getValue($this->studentquizquestion);
    }

    /**
     * Test move question in studentquiz to another studentquiz.
     */
    public function test_move_studentquiz_question(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        // Create two StudentQuiz activity.
        $studentquizdata = [
            'course' => $course->id,
            'anonymrank' => true,
            'questionquantifier' => 10,
            'approvedquantifier' => 5,
            'ratequantifier' => 3,
            'correctanswerquantifier' => 2,
            'incorrectanswerquantifier' => -1,
        ];

        $cmid1 = $generator->create_module('studentquiz', $studentquizdata)->cmid;
        $cmid2 = $generator->create_module('studentquiz', $studentquizdata)->cmid;

        $context1 = \context_module::instance($cmid1);
        $context2 = \context_module::instance($cmid2);
        $studentquiz = [
            mod_studentquiz_load_studentquiz($cmid1, $context1->id),
            mod_studentquiz_load_studentquiz($cmid2, $context2->id),
        ];

        $question1 = $questiongenerator->create_question('truefalse', null,
            ['name' => 'Student quiz 1 Question', 'category' => $studentquiz[0]->categoryid]);

        // Move question to Studentquiz 2 category.
        question_move_questions_to_category([$question1->id], $studentquiz[1]->categoryid);
        $question1 = \question_bank::load_question($question1->id);
        // Ensure it question 1 is in the Studentquiz 2 category.
        $this->assertEquals($question1->category, $studentquiz[1]->categoryid);
        $this->assertNotEquals($question1->category, $studentquiz[0]->categoryid);

        // Ensure the contextid is correct.
        $this->assertEquals($question1->contextid, $context2->id);

        $questionrefrecord = $DB->get_records('question_references', [
            'usingcontextid' => $context2->id,
            'questionbankentryid' => $question1->questionbankentryid,
            'component' => 'mod_studentquiz',
            'questionarea' => 'studentquiz_question',
        ]);

        // Ensure the question_references record is updated.
        $this->assertCount(1, $questionrefrecord);
        $questionrefrecord = reset($questionrefrecord);
        $this->assertEquals($questionrefrecord->usingcontextid, $context2->id);
        $this->assertEquals($questionrefrecord->questionbankentryid, $question1->questionbankentryid);
    }
}
