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
 * Data provider tests for booking system module.
 *
 * @package    mod_studentquiz
 * @copyright  2018 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\tests\provider_testcase;
use mod_studentquiz\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use mod_studentquiz\utils;

/**
 * Data provider testcase class.
 *
 * @package    mod_studentquiz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_privacy_testcase extends provider_testcase {

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

    /**
     * @var string
     */
    protected $component = 'studentquiz';

    /**
     * Set up data required for the test case.
     *
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function setUp() {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        // Create two users.
        $this->users = [
                $generator->create_user(),
                $generator->create_user()
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
                mod_studentquiz_load_studentquiz($cmid1, context_module::instance($cmid1)->id),
                mod_studentquiz_load_studentquiz($cmid2, context_module::instance($cmid2)->id),
                mod_studentquiz_load_studentquiz($cmid3, context_module::instance($cmid3)->id),
        ];

        $this->contexts = [
                context_module::instance($this->studentquiz[0]->coursemodule),
                context_module::instance($this->studentquiz[1]->coursemodule),
                context_module::instance($this->studentquiz[2]->coursemodule)
        ];

        // Create questions for StudentQuiz.
        $this->questions = [
                self::create_question('User1 Question1 StudentQuiz1', 'truefalse', $this->studentquiz[0]->categoryid,
                        $this->users[0]),
                self::create_question('User1 Question2 StudentQuiz1', 'truefalse', $this->studentquiz[0]->categoryid,
                        $this->users[0]),
                self::create_question('User1 Question1 StudentQuiz2', 'truefalse', $this->studentquiz[1]->categoryid,
                        $this->users[0]),
                self::create_question('User2 Question1 StudentQuiz2', 'truefalse', $this->studentquiz[1]->categoryid,
                        $this->users[1]),
        ];

        // Create approvals.
        $this->approvals = [
                self::create_question_approval($this->questions[0]->id),
                self::create_question_approval($this->questions[1]->id),
                self::create_question_approval($this->questions[2]->id),
                self::create_question_approval($this->questions[3]->id),
        ];

        // Create rates.
        $this->rates = [
                self::create_rate($this->questions[0]->id, $this->users[1]->id),
                self::create_rate($this->questions[1]->id, $this->users[1]->id),
                self::create_rate($this->questions[2]->id, $this->users[1]->id),
                self::create_rate($this->questions[3]->id, $this->users[0]->id),
        ];

        // Create comments.
        $this->comments = [
                self::create_comment($this->questions[0]->id, $this->users[1]->id),
                self::create_comment($this->questions[1]->id, $this->users[1]->id),
                self::create_comment($this->questions[2]->id, $this->users[1]->id),
                self::create_comment($this->questions[3]->id, $this->users[0]->id, 0, 0, 0, 1 , $this->users[0]->id),
        ];

        // Create 2 replies for second user.
        $rootcomment = $this->comments[3];
        $userreply = $this->users[1];
        $this->comments[] = self::create_comment($rootcomment->questionid, $userreply->id, $rootcomment->id);
        $this->comments[] = self::create_comment($rootcomment->questionid, $userreply->id, $rootcomment->id);

        // Create 2 replies for first user.
        $rootcomment = $this->comments[3];
        $userreply = $this->users[0];
        $this->comments[] = self::create_comment($rootcomment->questionid, $userreply->id, $rootcomment->id);
        $this->comments[] = self::create_comment($rootcomment->questionid, $userreply->id, $rootcomment->id);

        // Create comment histories.
        $this->commenthistory = [
                self::create_comment_history($this->comments[0]->id, $this->users[1]->id, false),
                self::create_comment_history($this->comments[1]->id, $this->users[1]->id, false),
                self::create_comment_history($this->comments[2]->id, $this->users[1]->id, false),
                self::create_comment_history($this->comments[3]->id, $this->users[0]->id, true)
        ];

        // Create Progresses.
        $this->progresses = [
                self::create_progress($this->questions[0]->id, $this->users[0]->id, $this->studentquiz[0]->id),
                self::create_progress($this->questions[1]->id, $this->users[0]->id, $this->studentquiz[0]->id),
                self::create_progress($this->questions[2]->id, $this->users[0]->id, $this->studentquiz[1]->id),
                self::create_progress($this->questions[3]->id, $this->users[1]->id, $this->studentquiz[1]->id),
        ];

        // Create attempts.
        $this->attempts = [
                self::create_attempt($this->studentquiz[0]->id, $this->users[0]->id, $this->studentquiz[0]->categoryid),
                self::create_attempt($this->studentquiz[0]->id, $this->users[0]->id, $this->studentquiz[0]->categoryid),
                self::create_attempt($this->studentquiz[1]->id, $this->users[0]->id, $this->studentquiz[1]->categoryid),
                self::create_attempt($this->studentquiz[1]->id, $this->users[1]->id, $this->studentquiz[1]->categoryid),
        ];

        // Create attempts.
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
     *
     * @throws dml_exception
     */
    public function test_get_contexts_for_userid() {
        // Get contexts for the first user.
        $contextids = provider::get_contexts_for_userid($this->users[0]->id)->get_contextids();

        $this->assertCount(2, $contextids);
        $this->assertContains($this->contexts[0]->id, $contextids);
        $this->assertContains($this->contexts[1]->id, $contextids);

        // Get context for second user.
        $this->create_comment($this->questions[0]->id, $this->users[1]->id);
        $contextids = provider::get_contexts_for_userid($this->users[1]->id)->get_contextids();
        $this->assertCount(2, $contextids);
        $this->assertContains($this->contexts[0]->id, $contextids);
        $this->assertContains($this->contexts[1]->id, $contextids);
    }

    /**
     * Test export data for second user.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_export_first_user_data() {
        $contextids = [$this->contexts[0]->id, $this->contexts[1]->id];
        $appctx = new approved_contextlist($this->users[0], 'mod_studentquiz', $contextids);
        provider::export_user_data($appctx);

        $contextdata = writer::with_context($this->contexts[0]);
        $data = $contextdata->get_data($this->subcontext);

        $questions = $data->questions;
        $this->assertCount(2, $questions);
        $this->assertEquals((object) [
                'name' => $this->questions[0]->name,
                'approved' => transform::yesno($this->approvals[0]->state)
        ], $questions[$this->questions[0]->id]);
        $this->assertEquals((object) [
                'name' => $this->questions[1]->name,
                'approved' => transform::yesno($this->approvals[1]->state)
        ], $questions[$this->questions[1]->id]);

        $progresses = $data->progresses;
        $this->assertCount(2, $progresses);
        $this->assertEquals((object) [
                'userid' => transform::user($this->progresses[0]->userid),
                'studentquizid' => $this->progresses[0]->studentquizid,
                'lastanswercorrect' => transform::yesno($this->progresses[0]->lastanswercorrect),
                'attempts' => $this->progresses[0]->attempts,
                'correctattempts' => $this->progresses[0]->correctattempts
        ], $progresses[$this->progresses[0]->questionid]);
        $this->assertEquals((object) [
                'userid' => transform::user($this->progresses[1]->userid),
                'studentquizid' => $this->progresses[1]->studentquizid,
                'lastanswercorrect' => transform::yesno($this->progresses[1]->lastanswercorrect),
                'attempts' => $this->progresses[1]->attempts,
                'correctattempts' => $this->progresses[1]->correctattempts
        ], $progresses[$this->progresses[1]->questionid]);

        $attempts = $data->attempts;
        $this->assertCount(2, $attempts);
        $this->assertEquals((object) [
                'studentquizid' => $this->attempts[0]->studentquizid,
                'userid' => transform::user($this->attempts[0]->userid),
                'questionusageid' => $this->attempts[0]->questionusageid,
                'categoryid' => $this->attempts[0]->categoryid,
        ], $attempts[$this->attempts[0]->id]);
        $this->assertEquals((object) [
                'studentquizid' => $this->attempts[1]->studentquizid,
                'userid' => transform::user($this->attempts[1]->userid),
                'questionusageid' => $this->attempts[1]->questionusageid,
                'categoryid' => $this->attempts[1]->categoryid,
        ], $attempts[$this->attempts[1]->id]);

        $this->assertEmpty($data->rates);
        $this->assertEmpty($data->comments);

        $contextdata = writer::with_context($this->contexts[1]);
        $data = $contextdata->get_data($this->subcontext);

        $questions = $data->questions;
        $this->assertCount(1, $questions);
        $this->assertEquals((object) [
                'name' => $this->questions[2]->name,
                'approved' => transform::yesno($this->approvals[2]->state)
        ], $questions[$this->questions[2]->id]);

        $rates = $data->rates;
        $this->assertCount(1, $rates);
        $this->assertEquals((object) [
                'rate' => $this->rates[3]->rate,
                'questionid' => $this->rates[3]->questionid,
                'userid' => transform::user($this->rates[3]->userid),
        ], $rates[$this->rates[3]->id]);

        $comments = $data->comments;
        // We created 1 root comments + 2  replies.
        $this->assertCount(3, $comments);
        $this->assertEquals((object) [
                'comment' => $this->comments[3]->comment,
                'questionid' => $this->comments[3]->questionid,
                'userid' => transform::user($this->comments[3]->userid),
                'created' => transform::datetime($this->comments[3]->created),
                'parentid' => $this->comments[3]->parentid,
                'status' => !is_null($this->comments[3]->status) ? $this->comments[3]->status : 0,
                'timemodified' => $this->comments[3]->timemodified > 0 ? transform::datetime($this->comments[3]->timemodified) : 0,
                'usermodified' => !is_null($this->comments[3]->usermodified) ? transform::user($this->comments[3]->usermodified) :
                        null
        ], $comments[$this->comments[3]->id]);

        $progresses = $data->progresses;
        $this->assertCount(1, $progresses);
        $this->assertEquals((object) [
                'userid' => transform::user($this->progresses[2]->userid),
                'studentquizid' => $this->progresses[2]->studentquizid,
                'lastanswercorrect' => transform::yesno($this->progresses[2]->lastanswercorrect),
                'attempts' => $this->progresses[2]->attempts,
                'correctattempts' => $this->progresses[2]->correctattempts
        ], $progresses[$this->progresses[2]->questionid]);

        $attempts = $data->attempts;
        $this->assertCount(1, $attempts);
        $this->assertEquals((object) [
                'studentquizid' => $this->attempts[2]->studentquizid,
                'userid' => transform::user($this->attempts[2]->userid),
                'questionusageid' => $this->attempts[2]->questionusageid,
                'categoryid' => $this->attempts[2]->categoryid,
        ], $attempts[$this->attempts[2]->id]);

        $commenthistory = $data->commenthistory;
        $this->assertCount(1, $commenthistory);
        $this->assertEquals($this->comments[3]->id, current($commenthistory)->commentid);
        $this->assertEquals($this->users[0]->id, current($commenthistory)->userid);
    }

    /**
     * Test export data for second user.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_export_second_user_data() {
        $contextids = [$this->contexts[0]->id, $this->contexts[1]->id];
        $appctx = new approved_contextlist($this->users[1], 'mod_studentquiz', $contextids);
        provider::export_user_data($appctx);

        $contextdata = writer::with_context($this->contexts[0]);
        $data = $contextdata->get_data($this->subcontext);
        $rates = $data->rates;
        $this->assertCount(2, $rates);
        $this->assertEquals((object) [
                'rate' => $this->rates[0]->rate,
                'questionid' => $this->rates[0]->questionid,
                'userid' => transform::user($this->rates[0]->userid),
        ], $rates[$this->rates[0]->id]);
        $this->assertEquals((object) [
                'rate' => $this->rates[1]->rate,
                'questionid' => $this->rates[1]->questionid,
                'userid' => transform::user($this->rates[1]->userid),
        ], $rates[$this->rates[1]->id]);

        $comments = $data->comments;
        $this->assertCount(2, $comments);
        $this->assertEquals((object) [
                'comment' => $this->comments[0]->comment,
                'questionid' => $this->comments[0]->questionid,
                'userid' => transform::user($this->comments[0]->userid),
                'created' => transform::datetime($this->comments[0]->created),
                'parentid' => $this->comments[0]->parentid,
                'status' => !is_null($this->comments[0]->status) ? $this->comments[0]->status : 0,
                'timemodified' => $this->comments[0]->timemodified > 0 ? transform::datetime($this->comments[0]->timemodified) : 0,
                'usermodified' => !is_null($this->comments[0]->usermodified) ? transform::user($this->comments[0]->usermodified) :
                        null
        ], $comments[$this->comments[0]->id]);
        $this->assertEquals((object) [
                'comment' => $this->comments[1]->comment,
                'questionid' => $this->comments[1]->questionid,
                'userid' => transform::user($this->comments[1]->userid),
                'created' => transform::datetime($this->comments[1]->created),
                'parentid' => $this->comments[1]->parentid,
                'status' => !is_null($this->comments[1]->status) ? $this->comments[1]->status : 0,
                'timemodified' => $this->comments[1]->timemodified > 0 ? transform::datetime($this->comments[1]->timemodified) : 0,
                'usermodified' => !is_null($this->comments[1]->usermodified) ? transform::user($this->comments[1]->usermodified) :
                        null
        ], $comments[$this->comments[1]->id]);

        $this->assertEmpty($data->questions);

        $commenthistory = $data->commenthistory;
        $this->assertCount(2, $commenthistory);
        $this->assertEquals((object) [
                'commentid' => $this->comments[1]->id,
                'content' => $this->commenthistory[1]->content,
                'userid' => !is_null($this->comments[1]->usermodified) ? transform::user($this->comments[1]->usermodified) : null,
                'action' => utils::COMMENT_HISTORY_CREATE,
                'timemodified' => transform::datetime($this->commenthistory[1]->timemodified)
        ], $commenthistory[$this->commenthistory[1]->id]);

        $this->assertEmpty($data->progresses);
        $this->assertEmpty($data->attempts);

        $contextdata = writer::with_context($this->contexts[1]);
        $data = $contextdata->get_data($this->subcontext);

        $questions = $data->questions;
        $this->assertCount(1, $questions);
        $this->assertEquals((object) [
                'name' => $this->questions[3]->name,
                'approved' => transform::yesno($this->approvals[3]->state)
        ], $questions[$this->questions[3]->id]);

        $rates = $data->rates;
        $this->assertCount(1, $rates);
        $this->assertEquals((object) [
                'rate' => $this->rates[2]->rate,
                'questionid' => $this->rates[2]->questionid,
                'userid' => transform::user($this->rates[2]->userid),
        ], $rates[$this->rates[2]->id]);

        $comments = $data->comments;
        // We created 1 root comment + 2 replies.
        $this->assertCount(3, $comments);
        $this->assertEquals((object) [
                'comment' => $this->comments[2]->comment,
                'questionid' => $this->comments[2]->questionid,
                'userid' => transform::user($this->comments[2]->userid),
                'created' => transform::datetime($this->comments[2]->created),
                'parentid' => $this->comments[2]->parentid,
                'status' => !is_null($this->comments[2]->status) ? $this->comments[2]->status : 0,
                'timemodified' => $this->comments[2]->timemodified > 0 ? transform::datetime($this->comments[2]->timemodified) : 0,
                'usermodified' => !is_null($this->comments[2]->usermodified) ? transform::user($this->comments[2]->usermodified) :
                        null
        ], $comments[$this->comments[2]->id]);

        // Test replies.
        // Test reply 1.
        $this->assertEquals((object) [
                'comment' => $this->comments[4]->comment,
                'questionid' => $this->comments[4]->questionid,
                'userid' => transform::user($this->comments[4]->userid),
                'created' => transform::datetime($this->comments[4]->created),
                'parentid' => $this->comments[3]->id,
                'status' => !is_null($this->comments[4]->status) ? $this->comments[4]->status : 0,
                'timemodified' => $this->comments[4]->timemodified > 0 ? transform::datetime($this->comments[4]->timemodified) : 0,
                'usermodified' => !is_null($this->comments[4]->usermodified) ? transform::user($this->comments[4]->usermodified) :
                        null
        ], $comments[$this->comments[4]->id]);

        // Test reply 2.
        $this->assertEquals((object) [
                'comment' => $this->comments[5]->comment,
                'questionid' => $this->comments[5]->questionid,
                'userid' => transform::user($this->comments[5]->userid),
                'created' => transform::datetime($this->comments[5]->created),
                'parentid' => $this->comments[3]->id,
                'status' => !is_null($this->comments[5]->status) ? $this->comments[5]->status : 0,
                'timemodified' => $this->comments[5]->timemodified > 0 ? transform::datetime($this->comments[5]->timemodified) : 0,
                'usermodified' => !is_null($this->comments[5]->usermodified) ? transform::user($this->comments[5]->usermodified) :
                        null
        ], $comments[$this->comments[5]->id]);

        $attempts = $data->attempts;
        $this->assertCount(1, $attempts);
        $this->assertEquals((object) [
                'studentquizid' => $this->attempts[3]->studentquizid,
                'userid' => transform::user($this->attempts[3]->userid),
                'questionusageid' => $this->attempts[3]->questionusageid,
                'categoryid' => $this->attempts[3]->categoryid
        ], $attempts[$this->attempts[3]->id]);
    }

    /**
     * Test delete data for all user in the context.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        // Test delete personal data for first content (StudentQuiz1).
        provider::delete_data_for_all_users_in_context($this->contexts[0]);

        list($questionsql, $questionparams) =
                $DB->get_in_or_equal([$this->questions[0]->id, $this->questions[1]->id], SQL_PARAMS_NAMED);

        // Check all personal data belong to first context is deleted.
        $this->assertFalse($DB->record_exists_sql("SELECT 1 FROM {studentquiz_question} WHERE questionid {$questionsql}"
                , $questionparams));
        $this->assertFalse($DB->record_exists_sql("SELECT 1 FROM {studentquiz_rate} WHERE questionid {$questionsql}"
                , $questionparams));
        $this->assertFalse($DB->record_exists_sql("SELECT 1 FROM {studentquiz_comment} WHERE questionid {$questionsql}"
                , $questionparams));
        $this->assertFalse($DB->record_exists_sql("SELECT 1 FROM {studentquiz_progress} WHERE questionid {$questionsql}"
                , $questionparams));
        $this->assertFalse($DB->record_exists_sql("SELECT 1 FROM {question} WHERE id {$questionsql}", $questionparams));
        $this->assertFalse($DB->record_exists_sql("SELECT 1 FROM {studentquiz_attempt} WHERE studentquizid = :studentquizid", [
                'studentquizid' => $this->studentquiz[0]->id
        ]));
        $this->assertFalse($DB->record_exists_sql("SELECT 1 FROM {studentquiz_notification} WHERE studentquizid = :studentquizid", [
                'studentquizid' => $this->studentquiz[0]->id
        ]));

        // Check personal data belong to second context is still existed.
        list($questionsql, $questionparams) =
                $DB->get_in_or_equal([$this->questions[2]->id, $this->questions[3]->id], SQL_PARAMS_NAMED);
        $this->assertTrue($DB->record_exists_sql("SELECT 1 FROM {studentquiz_question} WHERE questionid {$questionsql}"
                , $questionparams));
        $this->assertTrue($DB->record_exists_sql("SELECT 1 FROM {studentquiz_rate} WHERE questionid {$questionsql}"
                , $questionparams));
        $this->assertTrue($DB->record_exists_sql("SELECT 1 FROM {studentquiz_comment} WHERE questionid {$questionsql}"
                , $questionparams));
        $this->assertTrue($DB->record_exists_sql("SELECT 1 FROM {studentquiz_comment_history} WHERE userid = :userid"
                , ['userid' => $this->users[0]->id]));
        $this->assertTrue($DB->record_exists_sql("SELECT 1 FROM {studentquiz_progress} WHERE questionid {$questionsql}"
                , $questionparams));
        $this->assertTrue($DB->record_exists_sql("SELECT 1 FROM {question} WHERE id {$questionsql}", $questionparams));
        $this->assertTrue($DB->record_exists_sql("SELECT 1 FROM {studentquiz_attempt} WHERE studentquizid = :studentquizid", [
                'studentquizid' => $this->studentquiz[1]->id
        ]));
        $this->assertTrue($DB->record_exists_sql("SELECT 1 FROM {studentquiz_notification} WHERE studentquizid = :studentquizid", [
                'studentquizid' => $this->studentquiz[1]->id
        ]));
    }

    /**
     * Test delete personal data for one user.
     *
     * @throws dml_exception
     * @throws coding_exception
     */
    public function test_delete_data_for_user() {
        global $DB;

        $guestid = guest_user()->id;

        // Check data belong to first user is existed.
        $appctx = new approved_contextlist($this->users[0], 'mod_studentquiz', [
                $this->contexts[0]->id,
                $this->contexts[1]->id
        ]);

        $commentparams = ['userid' => $this->users[0]->id, 'parentid' => \mod_studentquiz\commentarea\container::PARENTID];
        $rootcomment = $DB->get_record('studentquiz_comment', $commentparams);

        // Delete data belong to first user.
        // When running the whole cronjob, privacy task for Question plugin will be called before StudentQuiz.
        core_question\privacy\provider::delete_data_for_user($appctx);
        provider::delete_data_for_user($appctx);

        // Check question owner of deleting user is change to guest.
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

        // Test root comment became blank.
        $commentafterdelete = $DB->get_record('studentquiz_comment', ['id' => $rootcomment->id]);
        $this->assertEquals($rootcomment->id, $commentafterdelete->id);
        $this->assertEquals('', $commentafterdelete->comment);
        $this->assertEquals($guestid, $commentafterdelete->userid);
        $this->assertEquals($guestid, $commentafterdelete->usermodified);
        $this->assertEquals(utils::COMMENT_HISTORY_CREATE, $commentafterdelete->status);
        $this->assertTrue($commentafterdelete->timemodified != 0);
        $this->assertFalse($DB->record_exists('studentquiz_progress', $params));

        // Check personal data belong to second user still existed.
        $params = ['userid' => $this->users[1]->id];
        $this->assertEquals($this->users[1]->id, $questions[$this->questions[3]->id]->createdby);
        $this->assertEquals($this->users[1]->id, $questions[$this->questions[3]->id]->modifiedby);
        $this->assertTrue($DB->record_exists('studentquiz_rate', $params));
        $this->assertTrue($DB->record_exists('studentquiz_attempt', $params));
        $this->assertTrue($DB->record_exists('studentquiz_comment', $params));
        $this->assertTrue($DB->record_exists('studentquiz_progress', $params));
    }

    /**
     * Test get users in context with question condition (User created or
     * modified).
     *
     * @throws coding_exception
     */
    public function test_get_users_in_context_question() {
        // Create question for first user, check only one user return for this context.
        self::create_question('Question', 'truefalse', $this->studentquiz[2]->categoryid,
                $this->users[0]);

        $userlist = new userlist($this->contexts[2], $this->component);
        provider::get_users_in_context($userlist);

        $this->assertCount(1, $userlist);
        $this->assertEquals([$this->users[0]->id], $userlist->get_userids());

        // Create question for second user, check two users return for this context.
        self::create_question('Question', 'truefalse', $this->studentquiz[2]->categoryid,
                $this->users[1]);
        provider::get_users_in_context($userlist);

        $this->assertCount(2, $userlist);
        $this->assertEquals([$this->users[0]->id, $this->users[1]->id], $userlist->get_userids());
    }

    /**
     * Test get users in context with question's rating condition.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_get_users_in_context_rating() {
        // Another user create question, then first user rate it.
        $anotheruser = $this->getDataGenerator()->create_user();

        $question = self::create_question('Question', 'truefalse', $this->studentquiz[2]->categoryid, $anotheruser);
        $this->create_rate($question->id, $this->users[0]->id);

        $userlist = new userlist($this->contexts[2], $this->component);
        provider::get_users_in_context($userlist);

        $this->assertCount(2, $userlist);
        $this->assertEquals([$anotheruser->id, $this->users[0]->id], $userlist->get_userids());

        // Second student rate on another user question.
        $this->create_rate($question->id, $this->users[1]->id);
        provider::get_users_in_context($userlist);
        $this->assertCount(3, $userlist);
        $this->assertEquals([$anotheruser->id, $this->users[0]->id, $this->users[1]->id ], $userlist->get_userids());
    }

    /**
     * Test get users in context with question's comment condition.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_get_users_in_context_comment() {
        // Another user create question, then first user comment it.
        $anotheruser = $this->getDataGenerator()->create_user();

        $question = self::create_question('Question', 'truefalse', $this->studentquiz[2]->categoryid, $anotheruser);
        $this->create_comment($question->id, $this->users[0]->id);

        $userlist = new userlist($this->contexts[2], $this->component);
        provider::get_users_in_context($userlist);

        $this->assertCount(2, $userlist);
        $this->assertEquals([$anotheruser->id, $this->users[0]->id], $userlist->get_userids());

        // Second student comment on another user question.
        $this->create_comment($question->id, $this->users[1]->id);
        provider::get_users_in_context($userlist);
        $this->assertCount(3, $userlist);
        $this->assertEquals([$anotheruser->id, $this->users[0]->id, $this->users[1]->id ], $userlist->get_userids());
    }

    /**
     * Test get users in context with question's comment condition.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_get_users_in_context_comment_history() {
        // Another user create question, then first user comment it.
        $anotheruser = $this->getDataGenerator()->create_user();

        $question = self::create_question('Question', 'truefalse', $this->studentquiz[2]->categoryid, $anotheruser);
        $comment = $this->create_comment($question->id, $this->users[0]->id);
        $this->create_comment_history($comment->id, $this->users[0]->id);

        $userlist = new userlist($this->contexts[2], $this->component);
        provider::get_users_in_context($userlist);

        $this->assertCount(2, $userlist);
        $this->assertEquals([$anotheruser->id, $this->users[0]->id], $userlist->get_userids());
    }

    /**
     * Test get users in context with question's attempt condition.
     *
     * @throws dml_exception
     */
    public function test_get_users_in_context_attempt() {
        // Create attempt for the first user.
        $this->create_attempt($this->studentquiz[2]->id, $this->users[0]->id, $this->studentquiz[2]->categoryid);

        $userlist = new userlist($this->contexts[2], $this->component);
        provider::get_users_in_context($userlist);

        $this->assertCount(1, $userlist);
        $this->assertEquals([$this->users[0]->id], $userlist->get_userids());

        // Create attempt for the second student.
        $this->create_attempt($this->studentquiz[2]->id, $this->users[1]->id, $this->studentquiz[2]->categoryid);
        provider::get_users_in_context($userlist);
        $this->assertCount(2, $userlist);
        $this->assertEquals([$this->users[0]->id, $this->users[1]->id], $userlist->get_userids());
    }

    /**
     * Test get users in context with question's notification condition.
     *
     * @throws dml_exception
     */
    public function test_get_users_in_context_notification() {
        // Create attempt for the first user.
        $this->create_notification($this->studentquiz[2]->id, $this->users[0]->id);

        $userlist = new userlist($this->contexts[2], $this->component);
        provider::get_users_in_context($userlist);

        $this->assertCount(1, $userlist);
        $this->assertEquals([$this->users[0]->id], $userlist->get_userids());

        // Create attempt for the second student.
        $this->create_notification($this->studentquiz[2]->id, $this->users[1]->id);
        provider::get_users_in_context($userlist);
        $this->assertCount(2, $userlist);
        $this->assertEquals([$this->users[0]->id, $this->users[1]->id], $userlist->get_userids());
    }

    /**
     * Test delete data for users from one context.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_delete_data_for_users() {
        global $DB;

        $guestid = guest_user()->id;

        $approveduserlist = new \core_privacy\local\request\approved_userlist($this->contexts[0], 'mod_studentquiz', [
            $this->users[0]->id
        ]);

        provider::delete_data_for_users($approveduserlist);

        // Check question owner of deleting user is change to guest.
        $questions = $DB->get_records('question');
        $this->assertEquals($guestid, $questions[$this->questions[0]->id]->createdby);
        $this->assertEquals($guestid, $questions[$this->questions[0]->id]->modifiedby);
        $this->assertEquals($guestid, $questions[$this->questions[1]->id]->createdby);
        $this->assertEquals($guestid, $questions[$this->questions[1]->id]->modifiedby);
        $this->assertEquals($this->users[0]->id, $questions[$this->questions[2]->id]->createdby);
        $this->assertEquals($this->users[0]->id, $questions[$this->questions[2]->id]->modifiedby);
        $this->assertEquals($this->users[1]->id, $questions[$this->questions[3]->id]->createdby);
        $this->assertEquals($this->users[1]->id, $questions[$this->questions[3]->id]->modifiedby);

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

        // Test data belong to the second user still exist.
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
     * Create question for user.
     *
     * @param string $name
     * @param string $qtype
     * @param int $categoryid
     * @param stdClass $user
     * @return question_definition
     * @throws coding_exception
     */
    protected function create_question($name, $qtype, $categoryid, $user) {
        global $USER;

        // Cannot set user by using overrides param, so we will need to change in session.
        $rootuser = $USER;

        $this->setUser($user);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $question = $questiongenerator->create_question($qtype, null, array(
                'name' => $name,
                'category' => $categoryid
        ));

        $this->setUser($rootuser);
        return question_bank::load_question($question->id);
    }

    /**
     * Create approval data for question.
     *
     * @param int $questionid
     * @return object
     * @throws dml_exception
     */
    protected function create_question_approval($questionid) {
        global $DB;

        $data = (object) [
                'id' => 0,
                'questionid' => $questionid,
                'state' => rand(0, 1)
        ];

        $data->id = $DB->insert_record('studentquiz_question', $data);

        return $data;
    }

    /**
     * Create rate data for user.
     *
     * @param int $questionid
     * @param int $userid
     * @return object
     * @throws dml_exception
     */
    protected function create_rate($questionid, $userid) {
        global $DB;

        $data = (object) [
                'id' => 0,
                'rate' => rand(1, 5),
                'questionid' => $questionid,
                'userid' => $userid
        ];

        $data->id = $DB->insert_record('studentquiz_rate', $data);

        return $data;
    }

    /**
     * Create comment data for user.
     *
     * @param int $questionid
     * @param int $userid
     * @param int $parentid
     * @param int $delete
     * @param int $deleteuserid
     * @param int $edit
     * @param int $edituserid
     * @return object
     * @throws dml_exception
     */
    protected function create_comment($questionid, $userid, $parentid = 0, $delete = 0, $deleteuserid = 0, $edit = 0,
        $edituserid = 0) {
        global $DB;

        $data = (object) [
                'id' => 0,
                'comment' => 'Sample comment ' . rand(1, 1000),
                'questionid' => $questionid,
                'userid' => $userid,
                'created' => rand(1000000000, 2000000000),
                'parentid' => $parentid,
                'status' => $delete === true ? utils::COMMENT_HISTORY_DELETE : utils::COMMENT_HISTORY_CREATE,
                'timemodified' => rand(1000000000, 2000000000),
                'usermodified' => $edituserid > 0 ? $edituserid : $userid
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
    protected function create_comment_history($commentid, $userid, $delete = false) {
        global $DB;

        $data = (object) [
                'id' => 0,
                'commentid' => $commentid,
                'content' => 'Sample comment ' . rand(1, 1000),
                'userid' => $userid,
                'action' => $delete === true ? utils::COMMENT_HISTORY_DELETE : utils::COMMENT_HISTORY_CREATE,
                'timemodified' => rand(1000000000, 2000000000)
        ];

        $data->id = $DB->insert_record('studentquiz_comment_history', $data);

        return $DB->get_record('studentquiz_comment_history', ['id' => $data->id]);
    }

    /**
     * Create progress data for user.
     *
     * @param int $questionid
     * @param int $userid
     * @param int $studentquizid
     * @return object
     * @throws dml_exception
     */
    protected function create_progress($questionid, $userid, $studentquizid) {
        global $DB;

        $data = (object) [
                'questionid' => $questionid,
                'userid' => $userid,
                'studentquizid' => $studentquizid,
                'lastanswercorrect' => rand(0, 1),
                'attempts' => rand(1, 1000),
                'correctattempts' => rand(1, 1000),
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
     * @throws dml_exception
     */
    protected function create_attempt($studentquizid, $userid, $categoryid) {
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
     * @throws dml_exception
     */
    protected function create_notification($studentquizid, $userid) {
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
}
