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

use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\tests\provider_testcase;
use mod_studentquiz\local\studentquiz_helper;
use mod_studentquiz\local\studentquiz_question;
use mod_studentquiz\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;

/**
 * Data provider testcase class.
 *
 * @package    mod_studentquiz
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class privacy_test extends provider_testcase {
    /**
     * @var array
     */
    protected $studentquiz;

    /**
     * @var array
     */
    protected $contexts;

    /**
     * @var array
     */
    protected $users;

    /**
     * @var array
     */
    protected $questions;

    /**
     * @var array
     */
    protected $studentquizquestions;

    /**
     * @var array
     */
    protected $rates;

    /**
     * @var array
     */
    protected $comments;

    /**
     * @var array
     */
    protected $approvals;

    /**
     * @var array
     */
    protected $attempts;

    /**
     * @var array
     */
    protected $progresses;

    /**
     * @var array
     */
    protected $commenthistory;

    /**
     * @var array
     */
    protected $notifications;

    /**
     * @var array
     */
    protected $subcontext;

    /** @var array The state histories record. */
    protected $statehistories;

    /**
     * @var string
     */
    protected $component = 'studentquiz';

    /**
     * Set up data required for the test case.
     */
    public function setUp(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        // Create two users.
        $this->users = [
            $generator->create_user(),
            $generator->create_user(),
        ];

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
        $cmid3 = $generator->create_module('studentquiz', $studentquizdata)->cmid;

        $this->studentquiz = [
            mod_studentquiz_load_studentquiz($cmid1, \context_module::instance($cmid1)->id),
            mod_studentquiz_load_studentquiz($cmid2, \context_module::instance($cmid2)->id),
            mod_studentquiz_load_studentquiz($cmid3, \context_module::instance($cmid3)->id),
        ];

        $this->contexts = [
            \context_module::instance($this->studentquiz[0]->coursemodule),
            \context_module::instance($this->studentquiz[1]->coursemodule),
            \context_module::instance($this->studentquiz[2]->coursemodule),
        ];

        // Create questions for StudentQuiz.
        $this->questions = [
            self::create_question('User1 Question1 StudentQuiz1', 'truefalse', $this->studentquiz[0]->categoryid, $this->users[0]),
            self::create_question('User1 Question2 StudentQuiz1', 'truefalse', $this->studentquiz[0]->categoryid, $this->users[0]),
            self::create_question('User1 Question1 StudentQuiz2', 'truefalse', $this->studentquiz[1]->categoryid, $this->users[0]),
            self::create_question('User2 Question1 StudentQuiz2', 'truefalse', $this->studentquiz[1]->categoryid, $this->users[1]),
        ];

        $this->studentquizquestions = [
            studentquiz_question::get_studentquiz_question_from_question($this->questions[0]),
            studentquiz_question::get_studentquiz_question_from_question($this->questions[1]),
            studentquiz_question::get_studentquiz_question_from_question($this->questions[2]),
            studentquiz_question::get_studentquiz_question_from_question($this->questions[3]),
        ];

        // Create approvals.
        $this->approvals = [
            self::create_question_approval($this->studentquizquestions[0]),
            self::create_question_approval($this->studentquizquestions[1]),
            self::create_question_approval($this->studentquizquestions[2]),
            self::create_question_approval($this->studentquizquestions[3]),
        ];

        // Create state histories.
        $this->statehistories = [
            self::create_state_history($this->studentquizquestions[0]->get_id(), $this->users[0]->id),
            self::create_state_history($this->studentquizquestions[1]->get_id(), $this->users[0]->id),
            self::create_state_history($this->studentquizquestions[2]->get_id(), $this->users[0]->id),
            self::create_state_history($this->studentquizquestions[3]->get_id(), $this->users[1]->id),
        ];

        // Create rates.
        $this->rates = [
            self::create_rate($this->studentquizquestions[0]->get_id(), $this->users[1]->id),
            self::create_rate($this->studentquizquestions[1]->get_id(), $this->users[1]->id),
            self::create_rate($this->studentquizquestions[2]->get_id(), $this->users[1]->id),
            self::create_rate($this->studentquizquestions[3]->get_id(), $this->users[0]->id),
        ];

        // Create comments.
        $this->comments = [
            self::create_comment($this->studentquizquestions[0]->get_id(), $this->users[1]->id),
            self::create_comment($this->studentquizquestions[1]->get_id(), $this->users[1]->id),
            self::create_comment($this->studentquizquestions[2]->get_id(), $this->users[1]->id),
            self::create_comment($this->studentquizquestions[3]->get_id(), $this->users[0]->id, 0, 0, 0, 1, $this->users[0]->id),
        ];

        // Create 2 replies for second user.
        $rootcomment = $this->comments[3];
        $userreply = $this->users[1];
        $this->comments[] = self::create_comment($rootcomment->studentquizquestionid, $userreply->id, $rootcomment->id);
        $this->comments[] = self::create_comment($rootcomment->studentquizquestionid, $userreply->id, $rootcomment->id);

        // Create 2 replies for first user.
        $rootcomment = $this->comments[3];
        $userreply = $this->users[0];
        $this->comments[] = self::create_comment($rootcomment->studentquizquestionid, $userreply->id, $rootcomment->id);
        $this->comments[] = self::create_comment($rootcomment->studentquizquestionid, $userreply->id, $rootcomment->id);

        // Create comment histories.
        $this->commenthistory = [
            self::create_comment_history($this->comments[0]->id, $this->users[1]->id),
            self::create_comment_history($this->comments[1]->id, $this->users[1]->id),
            self::create_comment_history($this->comments[2]->id, $this->users[1]->id),
            self::create_comment_history($this->comments[3]->id, $this->users[0]->id, true),
        ];

        // Create Progresses.
        $this->progresses = [
            self::create_progress($this->studentquizquestions[0]->get_id(), $this->users[0]->id, $this->studentquiz[0]->id),
            self::create_progress($this->studentquizquestions[1]->get_id(), $this->users[0]->id, $this->studentquiz[0]->id),
            self::create_progress($this->studentquizquestions[2]->get_id(), $this->users[0]->id, $this->studentquiz[1]->id),
            self::create_progress($this->studentquizquestions[3]->get_id(), $this->users[1]->id, $this->studentquiz[1]->id),
        ];

        // Create attempts.
        $this->attempts = [
            self::create_attempt($this->studentquiz[0]->id, $this->users[0]->id, $this->studentquiz[0]->categoryid),
            self::create_attempt($this->studentquiz[0]->id, $this->users[0]->id, $this->studentquiz[0]->categoryid),
            self::create_attempt($this->studentquiz[1]->id, $this->users[0]->id, $this->studentquiz[1]->categoryid),
            self::create_attempt($this->studentquiz[1]->id, $this->users[1]->id, $this->studentquiz[1]->categoryid),
        ];

        // Create notifications.
        $this->notifications = [
            self::create_notification($this->studentquiz[0]->id, $this->users[0]->id),
            self::create_notification($this->studentquiz[0]->id, $this->users[0]->id),
            self::create_notification($this->studentquiz[1]->id, $this->users[0]->id),
            self::create_notification($this->studentquiz[1]->id, $this->users[1]->id),
        ];

        $this->subcontext = [get_string('pluginname', 'mod_studentquiz')];
    }

    /**
     * Test get context list for user id.
     * @covers \mod_studentquiz\privacy\provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid(): void {
        // Get contexts for the first user.
        $contextids = provider::get_contexts_for_userid($this->users[0]->id)->get_contextids();

        $this->assertCount(2, $contextids);
        $this->assertContains((string)$this->contexts[0]->id, $contextids);
        $this->assertContains((string)$this->contexts[1]->id, $contextids);

        // Get context for second user.
        $this->create_comment($this->studentquizquestions[0]->get_id(), $this->users[1]->id);
        $contextids = provider::get_contexts_for_userid($this->users[1]->id)->get_contextids();
        $this->assertCount(2, $contextids);
        $this->assertContains((string)$this->contexts[0]->id, $contextids);
        $this->assertContains((string)$this->contexts[1]->id, $contextids);
    }

    /**
     * Test export data for first user.
     * @covers \mod_studentquiz\privacy\provider::export_user_data
     */
    public function test_export_first_user_data(): void {
        $contextids = [$this->contexts[0]->id, $this->contexts[1]->id];
        $appctx = new approved_contextlist($this->users[0], 'mod_studentquiz', $contextids);
        provider::export_user_data($appctx);

        $contextdata = writer::with_context($this->contexts[0]);
        $data = $contextdata->get_data($this->subcontext);

        $questions = $data->questions;
        $this->assertCount(2, $questions);
        $this->assert_exported_question($this->questions[0], $this->approvals[0], $questions[$this->questions[0]->id]);
        $this->assert_exported_question($this->questions[1], $this->approvals[1], $questions[$this->questions[1]->id]);

        $statehistories = $data->statehistory;
        $this->assertCount(4, $statehistories);
        $states = studentquiz_helper::get_state_descriptions();
        $this->assert_exported_state_history($this->statehistories[0], $states, $statehistories[$this->statehistories[0]->id]);
        $this->assert_exported_state_history($this->statehistories[1], $states, $statehistories[$this->statehistories[1]->id]);

        $progresses = $data->progresses;
        $this->assertCount(2, $progresses);
        $this->assert_exported_progress($this->progresses[0], $progresses[$this->progresses[0]->studentquizquestionid]);
        $this->assert_exported_progress($this->progresses[1], $progresses[$this->progresses[1]->studentquizquestionid]);

        $attempts = $data->attempts;
        $this->assertCount(2, $attempts);
        $this->assert_exported_attempt($this->attempts[0], $attempts[$this->attempts[0]->id]);
        $this->assert_exported_attempt($this->attempts[1], $attempts[$this->attempts[1]->id]);

        $this->assertEmpty($data->rates);
        $this->assertEmpty($data->comments);

        $contextdata = writer::with_context($this->contexts[1]);
        $data = $contextdata->get_data($this->subcontext);

        $questions = $data->questions;
        $this->assertCount(1, $questions);
        $this->assert_exported_question($this->questions[2], $this->approvals[2], $questions[$this->questions[2]->id]);

        $statehistories = $data->statehistory;
        $this->assertCount(2, $statehistories);
        $this->assert_exported_state_history($this->statehistories[2], $states, $statehistories[$this->statehistories[2]->id]);

        $rates = $data->rates;
        $this->assertCount(1, $rates);
        $this->assert_exported_rate($this->rates[3], $rates[$this->rates[3]->id]);

        $comments = $data->comments;
        // We created 1 root comment + 2 replies.
        $this->assertCount(3, $comments);
        $this->assert_exported_comment($this->comments[3], $comments[$this->comments[3]->id]);

        $progresses = $data->progresses;
        $this->assertCount(1, $progresses);
        $this->assert_exported_progress($this->progresses[2], $progresses[$this->progresses[2]->studentquizquestionid]);

        $attempts = $data->attempts;
        $this->assertCount(1, $attempts);
        $this->assert_exported_attempt($this->attempts[2], $attempts[$this->attempts[2]->id]);

        $commenthistory = $data->commenthistory;
        $this->assertCount(1, $commenthistory);
        $this->assertEquals($this->comments[3]->id, current($commenthistory)->commentid);
        $this->assertEquals($this->users[0]->id, current($commenthistory)->userid);
    }

    /**
     * Test export data for second user.
     * @covers \mod_studentquiz\privacy\provider::export_user_data
     */
    public function test_export_second_user_data(): void {
        $contextids = [$this->contexts[0]->id, $this->contexts[1]->id];
        $appctx = new approved_contextlist($this->users[1], 'mod_studentquiz', $contextids);
        provider::export_user_data($appctx);

        $contextdata = writer::with_context($this->contexts[0]);
        $data = $contextdata->get_data($this->subcontext);
        $rates = $data->rates;
        $this->assertCount(2, $rates);
        $this->assert_exported_rate($this->rates[0], $rates[$this->rates[0]->id]);
        $this->assert_exported_rate($this->rates[1], $rates[$this->rates[1]->id]);

        $comments = $data->comments;
        $this->assertCount(2, $comments);
        $this->assert_exported_comment($this->comments[0], $comments[$this->comments[0]->id]);
        $this->assert_exported_comment($this->comments[1], $comments[$this->comments[1]->id]);

        $this->assertEmpty($data->questions);
        $this->assertEmpty($data->statehistory);

        $commenthistory = $data->commenthistory;
        $this->assertCount(2, $commenthistory);
        $this->assertEquals((object) [
            'commentid' => $this->comments[1]->id,
            'content' => $this->commenthistory[1]->content,
            'userid' => !is_null($this->comments[1]->usermodified) ? transform::user($this->comments[1]->usermodified) : null,
            'action' => utils::COMMENT_HISTORY_CREATE,
            'timemodified' => transform::datetime($this->commenthistory[1]->timemodified),
        ], $commenthistory[$this->commenthistory[1]->id]);

        $this->assertEmpty($data->progresses);
        $this->assertEmpty($data->attempts);

        $contextdata = writer::with_context($this->contexts[1]);
        $data = $contextdata->get_data($this->subcontext);

        $questions = $data->questions;
        $this->assertCount(1, $questions);
        $this->assert_exported_question($this->questions[3], $this->approvals[3], $questions[$this->questions[3]->id]);

        $statehistories = $data->statehistory;
        $this->assertCount(2, $statehistories);
        $states = studentquiz_helper::get_state_descriptions();
        $this->assert_exported_state_history($this->statehistories[3], $states, $statehistories[$this->statehistories[3]->id]);

        $rates = $data->rates;
        $this->assertCount(1, $rates);
        $this->assert_exported_rate($this->rates[2], $rates[$this->rates[2]->id]);

        $comments = $data->comments;
        // We created 1 root comment + 2 replies.
        $this->assertCount(3, $comments);
        $this->assert_exported_comment($this->comments[2], $comments[$this->comments[2]->id]);
        $this->assert_exported_comment($this->comments[4], $comments[$this->comments[4]->id]);
        $this->assert_exported_comment($this->comments[5], $comments[$this->comments[5]->id]);

        $attempts = $data->attempts;
        $this->assertCount(1, $attempts);
        $this->assert_exported_attempt($this->attempts[3], $attempts[$this->attempts[3]->id]);
    }

    /**
     * Test delete data for all user in the context.
     * @covers \mod_studentquiz\privacy\provider::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        // Test delete personal data for first context (StudentQuiz1).
        provider::delete_data_for_all_users_in_context($this->contexts[0]);

        [$questionsql, $questionparams] =
            $DB->get_in_or_equal([$this->questions[0]->id, $this->questions[1]->id], SQL_PARAMS_NAMED);
        [$sqqsql, $sqqparams] =
            $DB->get_in_or_equal(
                [$this->studentquizquestions[0]->get_id(), $this->studentquizquestions[1]->get_id()],
                SQL_PARAMS_NAMED
            );

        // Check all personal data belong to first context is deleted.
        $this->assertFalse($DB->record_exists_sql("SELECT 1 FROM {studentquiz_question} WHERE id {$sqqsql}", $sqqparams));
        $this->assertFalse(
            $DB->record_exists_sql("SELECT 1 FROM {studentquiz_rate} WHERE studentquizquestionid {$sqqsql}", $sqqparams)
        );
        $this->assertFalse(
            $DB->record_exists_sql("SELECT 1 FROM {studentquiz_comment} WHERE studentquizquestionid {$sqqsql}", $sqqparams)
        );
        $this->assertFalse(
            $DB->record_exists_sql("SELECT 1 FROM {studentquiz_progress} WHERE studentquizquestionid {$sqqsql}", $sqqparams)
        );
        $this->assertFalse($DB->record_exists_sql("SELECT 1 FROM {question} WHERE id {$questionsql}", $questionparams));
        $this->assertFalse($DB->record_exists('studentquiz_attempt', ['studentquizid' => $this->studentquiz[0]->id]));
        $this->assertFalse($DB->record_exists('studentquiz_notification', ['studentquizid' => $this->studentquiz[0]->id]));
        $this->assertFalse(
            $DB->record_exists_sql("SELECT 1 FROM {studentquiz_state_history} WHERE studentquizquestionid {$sqqsql}", $sqqparams)
        );

        // Check personal data belong to second context still exists.
        [$questionsql, $questionparams] =
            $DB->get_in_or_equal([$this->questions[2]->id, $this->questions[3]->id], SQL_PARAMS_NAMED);
        [$sqqsql, $sqqparams] =
            $DB->get_in_or_equal(
                [$this->studentquizquestions[2]->get_id(), $this->studentquizquestions[3]->get_id()],
                SQL_PARAMS_NAMED
            );
        $this->assertTrue($DB->record_exists_sql("SELECT 1 FROM {studentquiz_question} WHERE id {$sqqsql}", $sqqparams));
        $this->assertTrue(
            $DB->record_exists_sql("SELECT 1 FROM {studentquiz_rate} WHERE studentquizquestionid {$sqqsql}", $sqqparams)
        );
        $this->assertTrue(
            $DB->record_exists_sql("SELECT 1 FROM {studentquiz_comment} WHERE studentquizquestionid {$sqqsql}", $sqqparams)
        );
        $this->assertTrue($DB->record_exists('studentquiz_comment_history', ['userid' => $this->users[0]->id]));
        $this->assertTrue(
            $DB->record_exists_sql("SELECT 1 FROM {studentquiz_progress} WHERE studentquizquestionid {$sqqsql}", $sqqparams)
        );
        $this->assertTrue($DB->record_exists_sql("SELECT 1 FROM {question} WHERE id {$questionsql}", $questionparams));
        $this->assertTrue($DB->record_exists('studentquiz_attempt', ['studentquizid' => $this->studentquiz[1]->id]));
        $this->assertTrue($DB->record_exists('studentquiz_notification', ['studentquizid' => $this->studentquiz[1]->id]));
        $this->assertTrue(
            $DB->record_exists_sql("SELECT 1 FROM {studentquiz_state_history} WHERE studentquizquestionid {$sqqsql}", $sqqparams)
        );
    }

    /**
     * Test delete personal data for one user.
     * @covers \mod_studentquiz\privacy\provider::delete_data_for_user
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $guestid = guest_user()->id;

        $appctx = new approved_contextlist($this->users[0], 'mod_studentquiz', [
            $this->contexts[0]->id,
            $this->contexts[1]->id,
        ]);

        $commentparams = ['userid' => $this->users[0]->id, 'parentid' => \mod_studentquiz\commentarea\container::PARENTID];
        $rootcomment = $DB->get_record('studentquiz_comment', $commentparams);

        \core_question\privacy\provider::delete_data_for_user($appctx);
        provider::delete_data_for_user($appctx);

        // Check question owner of deleting user is changed to guest.
        $questions = $DB->get_records('question');
        $this->assertEquals($guestid, $questions[$this->questions[0]->id]->createdby);
        $this->assertEquals($guestid, $questions[$this->questions[0]->id]->modifiedby);
        $this->assertEquals($guestid, $questions[$this->questions[1]->id]->createdby);
        $this->assertEquals($guestid, $questions[$this->questions[1]->id]->modifiedby);
        $this->assertEquals($guestid, $questions[$this->questions[2]->id]->createdby);
        $this->assertEquals($guestid, $questions[$this->questions[2]->id]->modifiedby);

        // Check personal data of other tables are deleted.
        $params = ['userid' => $this->users[0]->id];

        $this->assertFalse($DB->record_exists('studentquiz_rate', $params));
        $this->assertFalse($DB->record_exists('studentquiz_attempt', $params));

        // Deleted all replies.
        $sql = "SELECT 1 FROM {studentquiz_comment} WHERE userid = :userid AND parentid != :parentid";
        $this->assertFalse($DB->record_exists_sql($sql, $commentparams));

        // Deleted all comment history.
        $sql = "SELECT 1 FROM {studentquiz_comment_history} WHERE userid = :userid";
        $this->assertFalse($DB->record_exists_sql($sql, $commentparams));

        // Deleted all notifications.
        $this->assertFalse($DB->record_exists('studentquiz_notification', ['recipientid' => $this->users[0]->id]));
        $this->assertFalse($DB->record_exists('studentquiz_state_history', $params));

        // Test root comment became blank.
        $commentafterdelete = $DB->get_record('studentquiz_comment', ['id' => $rootcomment->id]);
        $this->assertEquals($rootcomment->id, $commentafterdelete->id);
        $this->assertEquals('', $commentafterdelete->comment);
        $this->assertEquals($guestid, $commentafterdelete->userid);
        $this->assertEquals($guestid, $commentafterdelete->usermodified);
        $this->assertEquals(utils::COMMENT_HISTORY_CREATE, $commentafterdelete->status);
        $this->assertTrue($commentafterdelete->timemodified != 0);
        $this->assertFalse($DB->record_exists('studentquiz_progress', $params));

        // Check personal data belonging to second user still exists.
        $params = ['userid' => $this->users[1]->id];
        $this->assertEquals($this->users[1]->id, $questions[$this->questions[3]->id]->createdby);
        $this->assertEquals($this->users[1]->id, $questions[$this->questions[3]->id]->modifiedby);
        $this->assertTrue($DB->record_exists('studentquiz_rate', $params));
        $this->assertTrue($DB->record_exists('studentquiz_attempt', $params));
        $this->assertTrue($DB->record_exists('studentquiz_comment', $params));
        $this->assertTrue($DB->record_exists('studentquiz_progress', $params));
        $this->assertTrue($DB->record_exists('studentquiz_state_history', $params));
    }

    /**
     * Test get users in context with question condition (User created or modified).
     * @covers \mod_studentquiz\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context_question(): void {
        self::create_question('Question', 'truefalse', $this->studentquiz[2]->categoryid, $this->users[0]);
        $this->assert_users_in_context($this->contexts[2], [$this->users[0]->id]);

        self::create_question('Question', 'truefalse', $this->studentquiz[2]->categoryid, $this->users[1]);
        $this->assert_users_in_context($this->contexts[2], [$this->users[0]->id, $this->users[1]->id]);
    }

    /**
     * Test get users in context with question's rating condition.
     * @covers \mod_studentquiz\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context_rating(): void {
        $anotheruser = $this->getDataGenerator()->create_user();
        $sqq = self::create_studentquiz_question('Question', 'truefalse', $this->studentquiz[2]->categoryid, $anotheruser);
        $this->create_rate($sqq->get_id(), $this->users[0]->id);

        $this->assert_users_in_context($this->contexts[2], [$anotheruser->id, $this->users[0]->id]);

        $this->create_rate($sqq->get_id(), $this->users[1]->id);
        $this->assert_users_in_context($this->contexts[2], [$anotheruser->id, $this->users[0]->id, $this->users[1]->id]);
    }

    /**
     * Test get users in context with question's comment condition.
     * @covers \mod_studentquiz\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context_comment(): void {
        $anotheruser = $this->getDataGenerator()->create_user();
        $sqq = self::create_studentquiz_question('Question', 'truefalse', $this->studentquiz[2]->categoryid, $anotheruser);
        $this->create_comment($sqq->get_id(), $this->users[0]->id);

        $this->assert_users_in_context($this->contexts[2], [$anotheruser->id, $this->users[0]->id]);

        $this->create_comment($sqq->get_id(), $this->users[1]->id);
        $this->assert_users_in_context($this->contexts[2], [$anotheruser->id, $this->users[0]->id, $this->users[1]->id]);
    }

    /**
     * Test get users in context with question's comment history condition.
     * @covers \mod_studentquiz\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context_comment_history(): void {
        $anotheruser = $this->getDataGenerator()->create_user();
        $sqq = self::create_studentquiz_question('Question', 'truefalse', $this->studentquiz[2]->categoryid, $anotheruser);

        $comment = $this->create_comment($sqq->get_id(), $this->users[0]->id);
        $this->create_comment_history($comment->id, $this->users[0]->id);

        $this->assert_users_in_context($this->contexts[2], [$anotheruser->id, $this->users[0]->id]);
    }

    /**
     * Test get users in context with question's attempt condition.
     * @covers \mod_studentquiz\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context_attempt(): void {
        $this->create_attempt($this->studentquiz[2]->id, $this->users[0]->id, $this->studentquiz[2]->categoryid);
        $this->assert_users_in_context($this->contexts[2], [$this->users[0]->id]);

        $this->create_attempt($this->studentquiz[2]->id, $this->users[1]->id, $this->studentquiz[2]->categoryid);
        $this->assert_users_in_context($this->contexts[2], [$this->users[0]->id, $this->users[1]->id]);
    }

    /**
     * Test get users in context with question's notification condition.
     * @covers \mod_studentquiz\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context_notification(): void {
        $this->create_notification($this->studentquiz[2]->id, $this->users[0]->id);
        $this->assert_users_in_context($this->contexts[2], [$this->users[0]->id]);

        $this->create_notification($this->studentquiz[2]->id, $this->users[1]->id);
        $this->assert_users_in_context($this->contexts[2], [$this->users[0]->id, $this->users[1]->id]);
    }

    /**
     * Test get users in context with question's change state condition.
     * @covers \mod_studentquiz\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context_change_state(): void {
        $anotheruser = $this->getDataGenerator()->create_user();
        $sqq = self::create_studentquiz_question('Question', 'truefalse', $this->studentquiz[2]->categoryid, $anotheruser);

        $this->create_state_history($sqq->get_id(), $this->users[0]->id);
        $this->assert_users_in_context($this->contexts[2], [$anotheruser->id, $this->users[0]->id]);
    }

    /**
     * Test delete data for users from one context.
     * @covers \mod_studentquiz\privacy\provider::delete_data_for_users
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $guestid = guest_user()->id;
        $adminid = get_admin()->id;

        $approveduserlist = new \core_privacy\local\request\approved_userlist(
            $this->contexts[0],
            'mod_studentquiz',
            [$this->users[0]->id]
        );

        provider::delete_data_for_users($approveduserlist);

        // Check question owner of deleting user is changed to guest.
        $questions = $DB->get_records('question');
        $this->assertEquals($guestid, $questions[$this->questions[0]->id]->createdby);
        $this->assertEquals($guestid, $questions[$this->questions[0]->id]->modifiedby);
        $this->assertEquals($guestid, $questions[$this->questions[1]->id]->createdby);
        $this->assertEquals($guestid, $questions[$this->questions[1]->id]->modifiedby);
        $this->assertEquals($this->users[0]->id, $questions[$this->questions[2]->id]->createdby);
        $this->assertEquals($this->users[0]->id, $questions[$this->questions[2]->id]->modifiedby);
        $this->assertEquals($this->users[1]->id, $questions[$this->questions[3]->id]->createdby);
        $this->assertEquals($this->users[1]->id, $questions[$this->questions[3]->id]->modifiedby);

        // Check question state history owner of deleting user is changed to admin.
        $statehistories = $DB->get_records('studentquiz_state_history');
        $this->assertCount(8, $statehistories);
        $this->assertEquals($adminid, $statehistories[$this->statehistories[0]->id]->userid);
        $this->assertEquals($adminid, $statehistories[$this->statehistories[1]->id]->userid);
        $this->assertEquals($this->users[0]->id, $statehistories[$this->statehistories[2]->id]->userid);
        $this->assertEquals($this->users[1]->id, $statehistories[$this->statehistories[3]->id]->userid);

        // Check personal data of other tables are deleted for first user and first context.
        $sqlparams = ['userid' => $this->users[0]->id];

        $rates = $DB->get_records('studentquiz_rate', $sqlparams);
        $this->assertCount(1, $rates);
        $this->assertArrayHasKey($this->rates[3]->id, $rates);

        $attempts = $DB->get_records('studentquiz_attempt', $sqlparams);
        $this->assertCount(1, $attempts);
        $this->assertArrayHasKey($this->attempts[2]->id, $attempts);

        $comments = $DB->get_records('studentquiz_comment', $sqlparams);
        $this->assertCount(3, $comments);
        $this->assertArrayHasKey($this->comments[3]->id, $comments);

        $commenthistory = $DB->get_records('studentquiz_comment_history', $sqlparams);
        $this->assertCount(0, $commenthistory);

        $notifications = $DB->get_records('studentquiz_notification', ['recipientid' => $this->users[0]->id]);
        $this->assertCount(0, $notifications);

        // Test data belonging to the second user still exists.
        $sqlparams = ['userid' => $this->users[1]->id];
        $this->assertEquals($this->users[1]->id, $questions[$this->questions[3]->id]->createdby);
        $this->assertEquals($this->users[1]->id, $questions[$this->questions[3]->id]->modifiedby);
        $this->assertTrue($DB->record_exists('studentquiz_rate', $sqlparams));
        $this->assertTrue($DB->record_exists('studentquiz_attempt', $sqlparams));
        $this->assertTrue($DB->record_exists('studentquiz_comment', $sqlparams));
        $this->assertTrue($DB->record_exists('studentquiz_comment_history', $sqlparams));
        $this->assertTrue($DB->record_exists('studentquiz_notification', ['recipientid' => $this->users[1]->id]));
    }

    /**
     * Test get_contexts_for_userid covers all UNION subqueries in complete isolation.
     * Guarantees each subquery branch in the UNION correctly retrieves the context on its own.
     *
     * @covers \mod_studentquiz\privacy\provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid_union_branch_isolation(): void {
        // Create distinct isolated users for every branch of the UNION query.
        $userquestion       = $this->getDataGenerator()->create_user();
        $userrate           = $this->getDataGenerator()->create_user();
        $usercomment        = $this->getDataGenerator()->create_user();
        $usercommenthistory = $this->getDataGenerator()->create_user();
        $userprogress       = $this->getDataGenerator()->create_user();
        $userattempt        = $this->getDataGenerator()->create_user();
        $usernotification   = $this->getDataGenerator()->create_user();
        $userstate          = $this->getDataGenerator()->create_user();

        // Helper setup: Create a base question in Context 2 owned by a dummy user to attach sub-records.
        $dummyowner = $this->getDataGenerator()->create_user();
        $sqq = self::create_studentquiz_question('Base Q', 'truefalse', $this->studentquiz[2]->categoryid, $dummyowner);

        // Branch 1: Question table (createdby).
        self::create_question('Isolated Question', 'truefalse', $this->studentquiz[2]->categoryid, $userquestion);

        // Branch 2: Rate table.
        $this->create_rate($sqq->get_id(), $userrate->id);

        // Branch 3: Comment table.
        $this->create_comment($sqq->get_id(), $usercomment->id);

        // Branch 4: Comment History table.
        $dummycomment = $this->create_comment($sqq->get_id(), $dummyowner->id);
        $this->create_comment_history($dummycomment->id, $usercommenthistory->id);

        // Branch 5: Progress table.
        $this->create_progress($sqq->get_id(), $userprogress->id, $this->studentquiz[2]->id);

        // Branch 6: Attempt table.
        $this->create_attempt($this->studentquiz[2]->id, $userattempt->id, $this->studentquiz[2]->categoryid);

        // Branch 7: Notification table.
        $this->create_notification($this->studentquiz[2]->id, $usernotification->id);

        // Branch 8: State History table.
        $this->create_state_history($sqq->get_id(), $userstate->id);

        $isolatedbranches = [
            'Question (createdby)'     => $userquestion,
            'Rate (userid)'            => $userrate,
            'Comment (userid)'         => $usercomment,
            'Comment History (userid)' => $usercommenthistory,
            'Progress (userid)'        => $userprogress,
            'Attempt (userid)'         => $userattempt,
            'Notification (recipient)' => $usernotification,
            'State History (userid)'   => $userstate,
        ];

        foreach ($isolatedbranches as $branchlabel => $user) {
            $contextids = provider::get_contexts_for_userid($user->id)->get_contextids();
            $this->assertCount(1, $contextids, "UNION branch isolation failed for: {$branchlabel}");
            $this->assertEquals(
                (string)$this->contexts[2]->id,
                reset($contextids),
                "Incorrect context returned for UNION branch: {$branchlabel}"
            );
        }
    }

    /**
     * Test get_contexts_for_userid de-duplication when a user exists in ALL UNION subqueries.
     *
     * @covers \mod_studentquiz\privacy\provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid_union_deduplication(): void {
        $user = $this->getDataGenerator()->create_user();
        $sqq = self::create_studentquiz_question('Multi-table Q', 'truefalse', $this->studentquiz[2]->categoryid, $user);

        // Populate records in ALL 8 UNION tables for the same user in Context 2.
        $this->create_rate($sqq->get_id(), $user->id);
        $comment = $this->create_comment($sqq->get_id(), $user->id);
        $this->create_comment_history($comment->id, $user->id);
        $this->create_progress($sqq->get_id(), $user->id, $this->studentquiz[2]->id);
        $this->create_attempt($this->studentquiz[2]->id, $user->id, $this->studentquiz[2]->categoryid);
        $this->create_notification($this->studentquiz[2]->id, $user->id);
        $this->create_state_history($sqq->get_id(), $user->id);

        $contextlist = provider::get_contexts_for_userid($user->id);
        $contextids = $contextlist->get_contextids();

        // Must return Context 2 exactly once (no duplicates from UNION/UNION ALL).
        $this->assertCount(1, $contextids);
        $this->assertEquals([(string)$this->contexts[2]->id], array_values($contextids));
    }

    /**
     * Test get_contexts_for_userid returns empty list for a user with no activity.
     *
     * @covers \mod_studentquiz\privacy\provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid_no_activity(): void {
        $cleanuser = $this->getDataGenerator()->create_user();
        $contextids = provider::get_contexts_for_userid($cleanuser->id)->get_contextids();

        $this->assertEmpty($contextids);
    }

    /**
     * Test get_users_in_context fetches users across all UNION subquery branches and handles de-duplication.
     *
     * @covers \mod_studentquiz\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context_union_all_branches(): void {
        // Create 8 distinct users (one per UNION branch) and 1 multi-branch user.
        $userquestion       = $this->getDataGenerator()->create_user();
        $userrate           = $this->getDataGenerator()->create_user();
        $usercomment        = $this->getDataGenerator()->create_user();
        $usercommenthistory = $this->getDataGenerator()->create_user();
        $userprogress       = $this->getDataGenerator()->create_user();
        $userattempt        = $this->getDataGenerator()->create_user();
        $usernotification   = $this->getDataGenerator()->create_user();
        $userstate          = $this->getDataGenerator()->create_user();
        $usermulti          = $this->getDataGenerator()->create_user();

        // 1. Question (userquestion and usermulti)
        self::create_question('Q1', 'truefalse', $this->studentquiz[2]->categoryid, $userquestion);
        $sqqmulti = self::create_studentquiz_question('Q2', 'truefalse', $this->studentquiz[2]->categoryid, $usermulti);

        // 2. Rate (userrate and usermulti duplicate)
        $this->create_rate($sqqmulti->get_id(), $userrate->id);
        $this->create_rate($sqqmulti->get_id(), $usermulti->id);

        // 3. Comment (usercomment and usermulti duplicate)
        $comment = $this->create_comment($sqqmulti->get_id(), $usercomment->id);
        $this->create_comment($sqqmulti->get_id(), $usermulti->id);

        // 4. Comment History
        $this->create_comment_history($comment->id, $usercommenthistory->id);

        // 5. Progress
        $this->create_progress($sqqmulti->get_id(), $userprogress->id, $this->studentquiz[2]->id);

        // 6. Attempt
        $this->create_attempt($this->studentquiz[2]->id, $userattempt->id, $this->studentquiz[2]->categoryid);

        // 7. Notification
        $this->create_notification($this->studentquiz[2]->id, $usernotification->id);

        // 8. State History
        $this->create_state_history($sqqmulti->get_id(), $userstate->id);

        $expecteduserids = [
            $userquestion->id,
            $userrate->id,
            $usercomment->id,
            $usercommenthistory->id,
            $userprogress->id,
            $userattempt->id,
            $usernotification->id,
            $userstate->id,
            $usermulti->id,
        ];

        $this->assert_users_in_context($this->contexts[2], $expecteduserids);
    }

    /**
     * Test edge cases for secondary query fields (modifiedby / usermodified).
     *
     * @covers \mod_studentquiz\privacy\provider::get_users_in_context
     * @covers \mod_studentquiz\privacy\provider::get_contexts_for_userid
     */
    public function test_union_query_secondary_field_relationships(): void {
        global $DB;

        $creator = $this->getDataGenerator()->create_user();
        $modifier = $this->getDataGenerator()->create_user();
        $commenteditor = $this->getDataGenerator()->create_user();

        // 1. Question modifiedby edge case: User A created, User B modified.
        $question = self::create_question('Modified Q', 'truefalse', $this->studentquiz[2]->categoryid, $creator);
        $DB->set_field('question', 'modifiedby', $modifier->id, ['id' => $question->id]);

        $sqq = studentquiz_question::get_studentquiz_question_from_question($question);

        // 2. Comment usermodified edge case: User A wrote comment, User C edited it.
        $this->create_comment($sqq->get_id(), $creator->id, 0, 0, 0, 1, $commenteditor->id);

        // Verify context retrieval for modifier and commenteditor.
        $modifiercontexts = provider::get_contexts_for_userid($modifier->id)->get_contextids();
        $this->assertContains(
            (string)$this->contexts[2]->id,
            $modifiercontexts,
            'UNION query failed to match question.modifiedby field'
        );

        $editorcontexts = provider::get_contexts_for_userid($commenteditor->id)->get_contextids();
        $this->assertContains(
            (string)$this->contexts[2]->id,
            $editorcontexts,
            'UNION query failed to match studentquiz_comment.usermodified field'
        );

        // Verify users_in_context captures all 3 distinct role users.
        $this->assert_users_in_context($this->contexts[2], [$creator->id, $modifier->id, $commenteditor->id]);
    }

    /**
     * Test export_user_data correctly exports notifications to the recipient.
     *
     * @covers \mod_studentquiz\privacy\provider::export_user_data
     */
    public function test_export_user_data_notification_recipient(): void {
        $this->resetAfterTest();

        // 1. Create a recipient user whose ID is guaranteed NOT equal to the notification record ID.
        for ($i = 0; $i < 10; $i++) {
            $this->getDataGenerator()->create_user();
        }
        $recipient = $this->getDataGenerator()->create_user();

        // 2. Create notification record for this recipient.
        $sq = $this->studentquiz[2];
        $notification = $this->create_notification($sq->id, $recipient->id);
        $notificationid = $notification->id;

        // Confirm notification PK ID is different from recipient user ID.
        $this->assertNotEquals($recipient->id, $notificationid, 'Notification ID and Recipient ID must differ for test validity.');

        // 3. Request privacy export for recipient.
        $context = $this->contexts[2];
        $approvedlist = new \core_privacy\local\request\approved_contextlist(
            $recipient,
            'mod_studentquiz',
            [$context->id]
        );

        writer::reset();
        provider::export_user_data($approvedlist);

        // 4. Assert notification data was exported correctly.
        $subcontext = [get_string('pluginname', 'mod_studentquiz')];
        $exporteddata = writer::with_context($context)->get_data($subcontext);

        $this->assertNotEmpty($exporteddata, 'Exported data should not be empty');
        $this->assertObjectHasProperty('notifications', $exporteddata);
        $this->assertArrayHasKey($notificationid, $exporteddata->notifications);

        $exportednotification = $exporteddata->notifications[$notificationid];
        $this->assertEquals($notification->content, $exportednotification->content);
    }

    /**
     * Test export_user_data includes comments edited by the user (usermodified)
     * even if the comment was originally created by another user (userid).
     *
     * @covers \mod_studentquiz\privacy\provider::export_user_data
     */
    public function test_export_user_data_comment_editor(): void {
        $this->resetAfterTest();

        $author = $this->getDataGenerator()->create_user();
        $editor = $this->getDataGenerator()->create_user();

        // 1. Create a question and a comment created by $author, but last edited by $editor.
        $sqq = self::create_studentquiz_question('Q1', 'truefalse', $this->studentquiz[2]->categoryid, $author);
        $comment = $this->create_comment(
            $sqq->get_id(),
            $author->id,
            0,
            0,
            0,
            1,
            $editor->id
        );

        // 2. Request privacy export specifically for the $editor.
        $context = $this->contexts[2];
        $approvedlist = new \core_privacy\local\request\approved_contextlist(
            $editor,
            'mod_studentquiz',
            [$context->id]
        );

        writer::reset();
        provider::export_user_data($approvedlist);

        // 3. Assert comment is exported for $editor because usermodified matches.
        $subcontext = [get_string('pluginname', 'mod_studentquiz')];
        $exporteddata = writer::with_context($context)->get_data($subcontext);

        $this->assertNotEmpty($exporteddata, 'Exported data should not be empty for comment editor');
        $this->assertObjectHasProperty('comments', $exporteddata);
        $this->assertArrayHasKey($comment->id, $exporteddata->comments);

        $exportedcomment = $exporteddata->comments[$comment->id];
        $this->assertEquals($editor->id, $exportedcomment->usermodified);
    }

    /**
     * Helper to verify user list fetched for a context.
     *
     * @param \context $context
     * @param array $expecteduserids
     */
    protected function assert_users_in_context(\context $context, array $expecteduserids): void {
        $userlist = new userlist($context, $this->component);
        provider::get_users_in_context($userlist);

        $this->assertCount(count($expecteduserids), $userlist);
        $this->assertEqualsCanonicalizing($expecteduserids, $userlist->get_userids());
    }

    /**
     * Assert exported question data matches expected values.
     *
     * @param object $question
     * @param object $approval
     * @param object $actual
     */
    protected function assert_exported_question($question, $approval, $actual): void {
        $this->assertEquals((object) [
            'name' => $question->name,
            'approved' => transform::yesno($approval->state),
            'groupid' => $approval->groupid,
            'pinned' => transform::yesno($approval->pinned),
        ], $actual);
    }

    /**
     * Assert exported state history data matches expected values.
     *
     * @param object $statehistory
     * @param array $states
     * @param object $actual
     */
    protected function assert_exported_state_history($statehistory, array $states, $actual): void {
        $this->assertEquals((object) [
            'state' => $states[$statehistory->state],
            'studentquizquestionid' => $statehistory->studentquizquestionid,
            'userid' => transform::user($statehistory->userid),
            'timecreated' => $statehistory->timecreated > 0 ? transform::datetime($statehistory->timecreated) : 0,
        ], $actual);
    }

    /**
     * Assert exported rate data matches expected values.
     *
     * @param object $rate
     * @param object $actual
     */
    protected function assert_exported_rate($rate, $actual): void {
        $this->assertEquals((object) [
            'rate' => $rate->rate,
            'studentquizquestionid' => $rate->studentquizquestionid,
            'userid' => transform::user($rate->userid),
        ], $actual);
    }

    /**
     * Assert exported comment data matches expected values.
     *
     * @param object $comment
     * @param object $actual
     */
    protected function assert_exported_comment($comment, $actual): void {
        $this->assertEquals((object) [
            'comment' => $comment->comment,
            'studentquizquestionid' => $comment->studentquizquestionid,
            'userid' => transform::user($comment->userid),
            'created' => transform::datetime($comment->created),
            'parentid' => $comment->parentid,
            'status' => !is_null($comment->status) ? $comment->status : 0,
            'type' => !is_null($comment->type) ? $comment->type : 0,
            'timemodified' => $comment->timemodified > 0 ? transform::datetime($comment->timemodified) : 0,
            'usermodified' => !is_null($comment->usermodified) ? transform::user($comment->usermodified) : null,
        ], $actual);
    }

    /**
     * Assert exported progress data matches expected values.
     *
     * @param object $progress
     * @param object $actual
     */
    protected function assert_exported_progress($progress, $actual): void {
        $this->assertEquals((object) [
            'userid' => transform::user($progress->userid),
            'studentquizid' => $progress->studentquizid,
            'lastanswercorrect' => transform::yesno($progress->lastanswercorrect),
            'attempts' => $progress->attempts,
            'correctattempts' => $progress->correctattempts,
            'lastreadprivatecomment' => transform::datetime($progress->lastreadprivatecomment),
            'lastreadpubliccomment' => transform::datetime($progress->lastreadpubliccomment),
        ], $actual);
    }

    /**
     * Assert exported attempt data matches expected values.
     *
     * @param object $attempt
     * @param object $actual
     */
    protected function assert_exported_attempt($attempt, $actual): void {
        $this->assertEquals((object) [
            'studentquizid' => $attempt->studentquizid,
            'userid' => transform::user($attempt->userid),
            'questionusageid' => $attempt->questionusageid,
            'categoryid' => $attempt->categoryid,
        ], $actual);
    }

    /**
     * Create question for user.
     *
     * @param string $name
     * @param string $qtype
     * @param int $categoryid
     * @param \stdClass $user
     * @return \question_definition
     */
    protected function create_question(string $name, string $qtype, int $categoryid, \stdClass $user): \question_definition {
        global $USER;

        // Cannot set user by using overrides param, so we will need to change in session.
        $rootuser = $USER;
        $this->setUser($user);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $question = $questiongenerator->create_question(
            $qtype,
            null,
            [
                'name' => $name,
                'category' => $categoryid,
            ]
        );

        $this->setUser($rootuser);
        return \question_bank::load_question($question->id);
    }

    /**
     * Create question and convert it directly to studentquiz_question.
     *
     * @param string $name
     * @param string $qtype
     * @param int $categoryid
     * @param \stdClass $user
     * @return studentquiz_question
     */
    protected function create_studentquiz_question(
        string $name,
        string $qtype,
        int $categoryid,
        \stdClass $user
    ): studentquiz_question {
        $question = self::create_question($name, $qtype, $categoryid, $user);
        return studentquiz_question::get_studentquiz_question_from_question($question);
    }

    /**
     * Create approval data for question.
     *
     * @param studentquiz_question $studentquizquestion
     * @return object
     */
    protected function create_question_approval(studentquiz_question $studentquizquestion): object {
        global $DB;
        // Change to disapprove to make sure questions can be deleted.
        $studentquizquestion->change_state_visibility(studentquiz_helper::STATE_DISAPPROVED);
        $record = $DB->get_record('studentquiz_question', ['id' => $studentquizquestion->get_id()]);

        return (object) [
            'id' => $record->id,
            'studentquizid' => $record->studentquizid,
            'state' => $record->state,
            'pinned' => $record->pinned,
            'groupid' => $record->groupid,
        ];
    }

    /**
     * Create rate data for user.
     *
     * @param int $studentquizquestionid
     * @param int $userid
     * @return object
     */
    protected function create_rate(int $studentquizquestionid, int $userid): object {
        global $DB;

        $data = (object) [
            'id' => 0,
            'rate' => rand(1, 5),
            'studentquizquestionid' => $studentquizquestionid,
            'userid' => $userid,
        ];

        $data->id = $DB->insert_record('studentquiz_rate', $data);

        return $data;
    }

    /**
     * Create comment data for user.
     *
     * @param int $studentquizquestionid
     * @param int $userid
     * @param int $parentid
     * @param int $delete
     * @param int $deleteuserid
     * @param int $edit
     * @param int $edituserid
     * @return object
     */
    protected function create_comment(
        int $studentquizquestionid,
        int $userid,
        int $parentid = 0,
        int $delete = 0,
        int $deleteuserid = 0,
        int $edit = 0,
        int $edituserid = 0
    ): object {
        global $DB;

        $data = (object) [
            'id' => 0,
            'comment' => 'Sample comment ' . rand(1, 1000),
            'studentquizquestionid' => $studentquizquestionid,
            'userid' => $userid,
            'created' => rand(1000000000, 2000000000),
            'parentid' => $parentid,
            'status' => $delete === true ? utils::COMMENT_HISTORY_DELETE : utils::COMMENT_HISTORY_CREATE,
            'timemodified' => rand(1000000000, 2000000000),
            'usermodified' => $edituserid > 0 ? $edituserid : $userid,
        ];

        $data->id = $DB->insert_record('studentquiz_comment', $data);

        return $DB->get_record('studentquiz_comment', ['id' => $data->id]);
    }

    /**
     * Create comment history data for given user and comment.
     *
     * @param int $commentid Comment id
     * @param int $userid Userid
     * @param bool $delete Is deleted or not
     * @return object
     */
    protected function create_comment_history(int $commentid, int $userid, bool $delete = false): object {
        global $DB;

        $data = (object) [
            'id' => 0,
            'commentid' => $commentid,
            'content' => 'Sample comment ' . rand(1, 1000),
            'userid' => $userid,
            'action' => $delete === true ? utils::COMMENT_HISTORY_DELETE : utils::COMMENT_HISTORY_CREATE,
            'timemodified' => rand(1000000000, 2000000000),
        ];

        $data->id = $DB->insert_record('studentquiz_comment_history', $data);

        return $DB->get_record('studentquiz_comment_history', ['id' => $data->id]);
    }

    /**
     * Create progress data for user.
     *
     * @param int $studentquizquestionid
     * @param int $userid
     * @param int $studentquizid
     * @return object
     */
    protected function create_progress(int $studentquizquestionid, int $userid, int $studentquizid): object {
        global $DB;

        $data = (object) [
            'studentquizquestionid' => $studentquizquestionid,
            'userid' => $userid,
            'studentquizid' => $studentquizid,
            'lastanswercorrect' => rand(0, 1),
            'attempts' => rand(1, 1000),
            'correctattempts' => rand(1, 1000),
            'lastreadprivatecomment' => rand(1, 10000),
            'lastreadpubliccomment' => rand(1, 10000),
        ];

        $DB->insert_record('studentquiz_progress', $data, false);

        return $data;
    }

    /**
     * Create attempt data for user.
     *
     * @param int $studentquizid
     * @param int $userid
     * @param int $categoryid
     * @return object
     */
    protected function create_attempt(int $studentquizid, int $userid, int $categoryid): object {
        global $DB;

        $data = (object) [
            'id' => 0,
            'studentquizid' => $studentquizid,
            'userid' => $userid,
            'questionusageid' => rand(1, 100),
            'categoryid' => $categoryid,
        ];

        $data->id = $DB->insert_record('studentquiz_attempt', $data);

        return $data;
    }

    /**
     * Create notification data for user.
     *
     * @param int $studentquizid
     * @param int $userid
     * @return object
     */
    protected function create_notification(int $studentquizid, int $userid): object {
        global $DB;

        $data = (object) [
            'id' => 0,
            'studentquizid' => $studentquizid,
            'recipientid' => $userid,
            'content' => 'Sample content ' . rand(1, 1000),
        ];

        $data->id = $DB->insert_record('studentquiz_notification', $data);

        return $data;
    }

    /**
     * Create state histories data for user.
     *
     * @param int $studentquizquestionid
     * @param int $userid
     * @return object
     */
    protected function create_state_history(int $studentquizquestionid, int $userid): object {
        global $DB;

        $data = (object) [
            'id' => 0,
            'state' => rand(0, 1),
            'studentquizquestionid' => $studentquizquestionid,
            'userid' => $userid,
            'timecreated' => rand(1000000000, 2000000000),
        ];

        $data->id = $DB->insert_record('studentquiz_state_history', $data);

        return $data;
    }
}
