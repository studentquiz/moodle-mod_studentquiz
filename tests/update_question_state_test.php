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

use core_external\external_api;
use core_question\local\bank\question_version_status;
use mod_studentquiz\external\update_question_state;
use mod_studentquiz\local\studentquiz_helper;
use mod_studentquiz\local\studentquiz_question;

/**
 * Tests for the question state web service.
 *
 * @package mod_studentquiz
 * @copyright 2026 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_studentquiz\external\update_question_state
 * @covers \mod_studentquiz\local\studentquiz_question::change_state
 */
final class update_question_state_test extends \advanced_testcase {
    /** @var \stdClass Test course. */
    private $course;

    /** @var \stdClass StudentQuiz activity. */
    private $activity;

    /** @var \stdClass Question author. */
    private $author;

    /** @var \stdClass Manager of the activity. */
    private $manager;

    /** @var \stdClass Question record. */
    private $question;

    /** @var studentquiz_question StudentQuiz question. */
    private $studentquizquestion;

    /**
     * Create an activity, enrolled users and an authored question.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->author = $generator->create_user();
        $this->manager = $generator->create_user();
        $generator->enrol_user($this->author->id, $this->course->id, 'student');
        $generator->enrol_user($this->manager->id, $this->course->id, 'editingteacher');
        $this->activity = $generator->create_module('studentquiz', [
            'course' => $this->course->id,
            'publishnewquestion' => 1,
        ]);
        $context = \context_module::instance($this->activity->cmid);
        $studentquiz = mod_studentquiz_load_studentquiz($this->activity->cmid, $context->id);
        $this->question = $generator->get_plugin_generator('core_question')->create_question('truefalse', null, [
            'category' => $studentquiz->categoryid,
            'createdby' => $this->author->id,
        ]);
        $this->studentquizquestion = studentquiz_question::get_studentquiz_question_from_question(
            \question_bank::load_question($this->question->id)
        );
    }

    /**
     * Unenrolled users must be rejected before either valid or missing questions are loaded.
     *
     * @dataProvider unenrolled_question_provider
     * @param bool $existing Whether to request an existing question.
     */
    public function test_unenrolled_user(bool $existing): void {
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\require_login_exception::class);
        update_question_state::execute(
            $this->course->id,
            $this->activity->cmid,
            $existing ? $this->studentquizquestion->get_id() : -1,
            studentquiz_helper::STATE_CHANGED
        );
    }

    /**
     * Question existence must not affect the access error.
     *
     * @return array Test cases.
     */
    public static function unenrolled_question_provider(): array {
        return ['existing question' => [true], 'missing question' => [false]];
    }

    /**
     * Caller-supplied course and activity ids must match the question.
     *
     * @dataProvider mismatched_context_provider
     * @param bool $wrongcourse Whether to supply a mismatched course instead of an activity.
     */
    public function test_mismatched_context(bool $wrongcourse): void {
        $generator = $this->getDataGenerator();
        $othercourse = $generator->create_course();
        $otheractivity = $generator->create_module('studentquiz', ['course' => $this->course->id]);
        $this->setUser($this->manager);
        $this->expectException(\invalid_parameter_exception::class);
        update_question_state::execute(
            $wrongcourse ? $othercourse->id : $this->course->id,
            $wrongcourse ? $this->activity->cmid : $otheractivity->cmid,
            $this->studentquizquestion->get_id(),
            studentquiz_helper::STATE_APPROVED
        );
    }

    /**
     * Mismatched course and activity identifiers.
     *
     * @return array Test cases.
     */
    public static function mismatched_context_provider(): array {
        return ['wrong course' => [true], 'wrong activity in same course' => [false]];
    }

    /**
     * Enrolment alone does not permit changing another author's question.
     */
    public function test_student_cannot_change_another_authors_question(): void {
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $this->course->id, 'student');
        $this->setUser($student);
        $this->expectException(\required_capability_exception::class);
        update_question_state::execute(
            $this->course->id,
            $this->activity->cmid,
            $this->studentquizquestion->get_id(),
            studentquiz_helper::STATE_CHANGED
        );
    }

    /**
     * A missing author must not cause a type error or grant ownership to an enrolled student.
     */
    public function test_student_cannot_change_question_without_author(): void {
        global $DB;

        $DB->set_field('question', 'createdby', null, ['id' => $this->question->id]);
        $this->setUser($this->author);
        $this->expectException(\required_capability_exception::class);
        update_question_state::execute(
            $this->course->id,
            $this->activity->cmid,
            $this->studentquizquestion->get_id(),
            studentquiz_helper::STATE_CHANGED
        );
    }

    /**
     * Authors cannot submit manager-only or unsupported states.
     *
     * @dataProvider forbidden_author_state_provider
     * @param int $state Requested state.
     */
    public function test_forbidden_author_state(int $state): void {
        global $DB;

        $this->setUser($this->author);
        $historycount = $DB->count_records('studentquiz_state_history');
        try {
            update_question_state::execute(
                $this->course->id,
                $this->activity->cmid,
                $this->studentquizquestion->get_id(),
                $state
            );
            $this->fail('A forbidden state was accepted.');
        } catch (\invalid_parameter_exception $exception) {
            $this->assertEquals($historycount, $DB->count_records('studentquiz_state_history'));
            $this->assertEquals(
                $this->studentquizquestion->get_state(),
                $DB->get_field('studentquiz_question', 'state', ['id' => $this->studentquizquestion->get_id()])
            );
        }
    }

    /**
     * States unavailable to authors.
     *
     * @return array Test cases.
     */
    public static function forbidden_author_state_provider(): array {
        return [
            'approved' => [studentquiz_helper::STATE_APPROVED],
            'disapproved' => [studentquiz_helper::STATE_DISAPPROVED],
            'hidden' => [studentquiz_helper::STATE_HIDE],
            'new' => [studentquiz_helper::STATE_NEW],
            'shown' => [studentquiz_helper::STATE_SHOW],
            'unknown' => [-1],
        ];
    }

    /**
     * Allowed author and manager changes update state, history and the response.
     *
     * @dataProvider allowed_state_provider
     * @param bool $manager Whether to act as the manager.
     * @param int $state Requested state.
     */
    public function test_allowed_state(bool $manager, int $state): void {
        global $DB;

        $actor = $manager ? $this->manager : $this->author;
        $this->setUser($actor);
        $historycount = $DB->count_records('studentquiz_state_history');
        $response = update_question_state::execute(
            $this->course->id,
            $this->activity->cmid,
            $this->studentquizquestion->get_id(),
            $state
        );
        $this->assertSame([
            'status' => get_string('api_state_change_success_title', 'studentquiz'),
            'message' => get_string('api_state_change_success_content', 'studentquiz'),
        ], external_api::clean_returnvalue(update_question_state::execute_returns(), $response));
        $this->assertEquals($historycount + 1, $DB->count_records('studentquiz_state_history'));
        $this->assertTrue($DB->record_exists('studentquiz_state_history', [
            'studentquizquestionid' => $this->studentquizquestion->get_id(),
            'userid' => $actor->id,
            'state' => $state,
        ]));
        $record = $DB->get_record('studentquiz_question', ['id' => $this->studentquizquestion->get_id()], '*', MUST_EXIST);
        if ($state === studentquiz_helper::STATE_HIDE) {
            $this->assertEquals(1, $record->hidden);
        } else if ($state !== studentquiz_helper::STATE_DELETE) {
            $this->assertEquals($state, $record->state);
        }
        $expectedstatus = question_version_status::QUESTION_STATUS_READY;
        if ($state === studentquiz_helper::STATE_DELETE) {
            $expectedstatus = question_version_status::QUESTION_STATUS_HIDDEN;
        } else if ($state === studentquiz_helper::STATE_DISAPPROVED) {
            $expectedstatus = question_version_status::QUESTION_STATUS_DRAFT;
        }
        $this->assertSame($expectedstatus, $DB->get_field(
            'question_versions',
            'status',
            ['questionid' => $this->question->id]
        ));
    }

    /**
     * Supported author and manager state transitions.
     *
     * @return array Test cases.
     */
    public static function allowed_state_provider(): array {
        return [
            'author changed' => [false, studentquiz_helper::STATE_CHANGED],
            'author reviewable' => [false, studentquiz_helper::STATE_REVIEWABLE],
            'author deleted' => [false, studentquiz_helper::STATE_DELETE],
            'manager disapproved' => [true, studentquiz_helper::STATE_DISAPPROVED],
            'manager approved' => [true, studentquiz_helper::STATE_APPROVED],
            'manager changed' => [true, studentquiz_helper::STATE_CHANGED],
            'manager reviewable' => [true, studentquiz_helper::STATE_REVIEWABLE],
            'manager hidden' => [true, studentquiz_helper::STATE_HIDE],
            'manager deleted' => [true, studentquiz_helper::STATE_DELETE],
        ];
    }

    /**
     * The module-level capability is sufficient, without the course-level manage capability.
     */
    public function test_module_capability_allows_managing_other_authors_questions(): void {
        global $DB;

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $this->course->id, 'student');
        $context = \context_module::instance($this->activity->cmid);
        $roleid = create_role('State manager', 'statemanager', '');
        assign_capability('mod/studentquiz:changestate', CAP_ALLOW, $roleid, $context->id);
        role_assign($roleid, $student->id, $context->id);
        $this->setUser($student);
        $this->assertFalse(has_capability('mod/studentquiz:manage', \context_course::instance($this->course->id)));
        update_question_state::execute(
            $this->course->id,
            $this->activity->cmid,
            $this->studentquizquestion->get_id(),
            studentquiz_helper::STATE_APPROVED
        );
        $this->assertEquals(
            studentquiz_helper::STATE_APPROVED,
            $DB->get_field('studentquiz_question', 'state', ['id' => $this->studentquizquestion->get_id()])
        );
    }

    /**
     * Keep the existing error response when an author tries to delete an approved question.
     */
    public function test_author_cannot_delete_approved_question(): void {
        global $DB;

        $this->studentquizquestion->change_state_visibility(studentquiz_helper::STATE_APPROVED);
        $historycount = $DB->count_records('studentquiz_state_history');
        $this->setUser($this->author);
        $response = update_question_state::execute(
            $this->course->id,
            $this->activity->cmid,
            $this->studentquizquestion->get_id(),
            studentquiz_helper::STATE_DELETE
        );
        $this->assertSame([
            'status' => get_string('api_state_change_error_title', 'studentquiz'),
            'message' => get_string('api_state_change_error_content', 'studentquiz'),
        ], $response);
        $this->assertEquals($historycount, $DB->count_records('studentquiz_state_history'));
        $this->assertSame(
            question_version_status::QUESTION_STATUS_READY,
            $DB->get_field('question_versions', 'status', ['questionid' => $this->question->id])
        );
    }
}
