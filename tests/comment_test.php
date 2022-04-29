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

use mod_studentquiz\commentarea\comment;
use mod_studentquiz\local\studentquiz_question;
use mod_studentquiz\local\studentquiz_progress;

/**
 * Unit tests for comment area.
 *
 * @package    mod_studentquiz
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_test extends \advanced_testcase {

    /**
     * @var \stdClass the StudentQuiz activity created in setUp.
     */
    protected $studentquiz;

    /**
     * @var array of studentquiz_question the studentquizquestion instance created in setUp.
     */
    protected $studentquizquestions;

    /**
     * @var \context_module the corresponding activity context.
     */
    protected $context;

    /**
     * @var \stdClass the corresponding course_module.
     */
    protected $cm;

    /**
     * @var array the users created in setUp.
     */
    protected $users;

    /**
     * @var array the questions created in setUp.
     */
    protected $questions;

    /** @var commentarea\container */
    protected $commentarea;

    /** @var int - Value of Root comment. */
    protected $rootid;

    /** @var \stdClass - Course. */
    protected $course;

    /** @var commentarea\container - Comment area has disabled period setting. */
    protected $commentareanoperiod;

    /** @var commentarea\container - Comment area has enable period setting. */
    protected $commentareahasperiod;

    /** @var array - Users list. */
    protected $userlist = [
            [
                    'firstname' => 'Alex',
                    'lastname' => 'Dan'
            ],
            [
                    'firstname' => 'Chris',
                    'lastname' => 'Bron'
            ],
            [
                    'firstname' => 'Danny',
                    'lastname' => 'Civi'
            ],
            [
                    'firstname' => 'Bob',
                    'lastname' => 'Alex'
            ],
            [
                    'firstname' => 'Ely',
                    'lastname' => 'Potter'
            ],
            [
                    'firstname' => 'Flashy',
                    'lastname' => 'Granger'
            ],
    ];

    /**
     * Setup for unit test.
     */
    protected function setUp(): void {
        $this->setAdminUser();
        $this->resetAfterTest();

        global $DB;

        $this->course = $this->getDataGenerator()->create_course();
        $course = $this->course;

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $activity = $this->getDataGenerator()->create_module('studentquiz', array(
                'course' => $course->id,
                'anonymrank' => true,
                'forcecommenting' => 1,
                'publishnewquestion' => 1
        ));
        $this->context = \context_module::instance($activity->cmid);
        $this->studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $this->context->id);
        $this->cm = get_coursemodule_from_id('studentquiz', $activity->cmid);

        // Create user.
        foreach ($this->userlist as $userdata) {
            $user = $this->getDataGenerator()->create_user($userdata);
            $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);
            $this->users[] = $user;
        }

        // Create questions in questionbank.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $q1 = $questiongenerator->create_question('truefalse', null,
                ['name' => 'TF1', 'category' => $this->studentquiz->categoryid]);
        $q1 = \question_bank::load_question($q1->id);
        $sqq1 = studentquiz_question::get_studentquiz_question_from_question($q1, $this->studentquiz);
        // Create question 2.
        $q2 = $questiongenerator->create_question('truefalse', null,
                ['name' => 'TF2', 'category' => $this->studentquiz->categoryid]);
        $q2 = \question_bank::load_question($q2->id);
        $sqq2 = studentquiz_question::get_studentquiz_question_from_question($q2, $this->studentquiz);
        $this->questions = [$q1, $q2];
        $this->studentquizquestions = [$sqq1, $sqq2];
        $this->commentarea = new commentarea\container($sqq1, $this->users[0]);
        $this->rootid = commentarea\container::PARENTID;

        $this->generate_comment_list_for_sort();

        // Create SQs with period setting.
        $this->set_up_studentquizs_with_period();
    }

    /**
     * Provide comment by id.
     *
     * @param int $id - Comment ID.
     * @param bool $convert - Is convert to object data.
     * @return comment|\stdClass
     */
    private function get_comment_by_id($id, $convert = true) {
        $comment = $this->commentarea->query_comment_by_id($id);
        if ($convert) {
            $comment = $comment->convert_to_object();
        }
        return $comment;
    }

    /**
     * Create a comment.
     *
     * @param int $replyto - Parent ID of comment.
     * @param int $studentquizquestionid - sqq ID.
     * @param string $text - Text of comment.
     * @param bool $convert - Is convert to object data.
     * @return comment
     */
    private function create_comment($replyto, $studentquizquestionid, $text, $convert = true) {
        $data = [
                'message' => [
                        'text' => $text,
                        'format' => 1
                ],
                'studentquizquestionid' => $studentquizquestionid,
                'cmid' => $this->cm->id,
                'replyto' => $replyto,
                'type' => utils::COMMENT_TYPE_PUBLIC
        ];
        $id = $this->commentarea->create_comment((object) $data);
        return $this->get_comment_by_id($id, $convert);
    }

    /**
     * Test init comment area.
     * @covers \mod_studentquiz\commentarea\container
     */
    public function test_initial() {
        $question = $this->questions[0];
        $this->assertEquals($question->id, $this->commentarea->get_question()->id);
        $this->assertEquals($this->cm->id, $this->commentarea->get_cmid());
        $this->assertEquals($this->studentquiz->id, $this->commentarea->get_studentquiz()->id);
        $this->assertEquals($this->context->id, $this->commentarea->get_context()->id);
    }

    /**
     * Test create root comment.
     * @covers \mod_studentquiz\commentarea\container::create_comment
     */
    public function test_create_root_comment() {
        // Create root comment.
        $sqq1 = $this->studentquizquestions[0];
        $text = 'Root comment';
        $comment = $this->create_comment($this->rootid, $sqq1->id, $text);
        $this->assertEquals($text, $comment->content);
        $this->assertEquals($sqq1->id, $comment->studentquizquestionid);
        $this->assertEquals($this->rootid, $comment->parentid);
    }

    /**
     * Test create reply.
     * @covers \mod_studentquiz\commentarea\container::create_comment
     */
    public function test_create_reply_comment() {
        $sqq1 = $this->studentquizquestions[0];
        $text = 'Root comment';
        $textreply = 'Reply root comment';
        $comment = $this->create_comment($this->rootid, $sqq1->id, $text);
        $reply = $this->create_comment($comment->id, $sqq1->id, $textreply);
        // Check text reply.
        $this->assertEquals($textreply, $reply->content);
        // Check question id.
        $this->assertEquals($sqq1->id, $reply->studentquizquestionid);
        // Check if reply belongs to comment.
        $this->assertEquals($comment->id, $reply->parentid);
    }

    /**
     *
     * Test shortcontent of comment in convert_to_object.
     * @dataProvider test_shorten_comment_provider
     * @covers \mod_studentquiz\commentarea\comment::convert_to_object
     * @param string $content Content before convert to shorten content.
     * @param string $expected Expected result.
     * @param int $expectedlength Expected length of the shorten text.
     */
    public function test_shorten_comment(string $content, string $expected, int $expectedlength): void {
        $sq1 = $this->studentquizquestions[0];
        $comment = $this->create_comment($this->rootid, $sq1->id, $content);
        $this->assertEquals($expectedlength, strlen($comment->shortcontent));
        $this->assertEquals($expected, $comment->shortcontent);
    }

    /**
     * Data provider for test_shorten_comment().
     *
     * @coversNothing
     * @return array
     */
    public function test_shorten_comment_provider(): array {

        return [
            'Root comment with newline html content' => [
                '<p>Root comment with html linebreak content</p><p>Line 2</p><p>Line 3</p><p>Line 4: simply dummy text of the.</p>',
                "Root comment with html linebreak content\n\nLine 2\n\nLine 3\n\nLine 4: simply...",
                75
            ],
            'Comment with escape html enity' => [
                '<p dir="ltr" style="text-align: left;">Test shortened text with html enity&lt;br&gt;</p>',
                "Test shortened text with html enity&lt;br&gt;",
                45
            ],
            'Comment with multiple normal line break' => [
                'Comment with multiple normal line break
                Line 2
                Line 3',
                "Comment with multiple normal line break Line 2 Line 3",
                53
            ]
        ];
    }

    /**
     * Test delete comment.
     * @covers \mod_studentquiz\commentarea\comment::delete
     */
    public function test_delete_comment() {
        // Create root comment.
        $sqq1 = $this->studentquizquestions[0];
        $text = 'Root comment';
        // Dont need to convert to use delete.
        $comment = $this->create_comment($this->rootid, $sqq1->id, $text, false);
        // Try to delete.
        $comment->delete();
        // Get new data.
        $commentafterdelete = $this->get_comment_by_id($comment->get_id(), false);
        // Status delete is 2.
        $this->assertEquals(utils::COMMENT_HISTORY_DELETE, $commentafterdelete->get_comment_data()->status);
        // Check correct delete user id.
        $this->assertEquals($this->users[0]->id, $commentafterdelete->get_comment_data()->userid);
    }

    /**
     * Test fetch all comments.
     * @covers \mod_studentquiz\commentarea\container::fetch_all
     */
    public function test_fetch_all_comments() {

        $sqq1 = $this->studentquizquestions[0];
        $text = 'Root comment';
        $textreply = 'Reply root comment';
        $numreplies = 3;
        $comment = $this->create_comment($this->rootid, $sqq1->id, $text);
        for ($i = 0; $i < $numreplies; $i++) {
            $this->create_comment($comment->id, $sqq1->id, $textreply);
        }
        $comments = $this->commentarea->fetch_all(0);
        $data = [];
        foreach ($comments as $comment) {
            $item = $comment->convert_to_object();
            $item->replies = [];
            foreach ($comment->get_replies() as $reply) {
                $item->replies[] = $reply->convert_to_object();
            }
            $data[] = $item;
        }
        // Check total comments.
        $this->assertEquals($numreplies + 1, $this->commentarea->get_num_comments());
        // Check root comment has 3 replies.
        foreach ($data as $v) {
            $this->assertEquals($numreplies, $v->numberofreply);
        }
    }

    /**
     * Test report feature. Turn off by default. Then turn it on.
     * @covers \mod_studentquiz\commentarea\container::get_reporting_emails
     */
    public function test_report_feature() {
        global $DB;
        $sqq1 = $this->studentquizquestions[0];
        // Need to use comment class functions. Don't use convert to response data.
        $comment = $this->create_comment($this->rootid, $sqq1->id, 'Test comment', false);
        // Assume that we didn't input any emails for report. It will return false.
        $this->assertFalse($comment->can_report());
        // Turn on report.
        $inputreportemails = 'admin@domain.com;admin1@domail.com';
        $this->studentquiz->reportingemail = $inputreportemails;
        $DB->update_record('studentquiz', $this->studentquiz);
        // Re-init SQ.
        $this->studentquiz = mod_studentquiz_load_studentquiz($this->cm->id, $this->context->id);
        // Re-init comment area.
        $this->commentarea = new commentarea\container($sqq1);
        $comment = $this->get_comment_by_id($comment->get_id(), false);
        // Now report is turned on. It will return true.
        $this->assertTrue($comment->can_report());
        // Check emails used for report correct.
        $emails = $this->commentarea->get_reporting_emails();
        foreach (explode(';', $inputreportemails) as $k => $v) {
            $this->assertEquals($v, $emails[$k]);
        }
    }

    /**
     * Generate comment list for sort.
     * @coversNothing
     */
    private function generate_comment_list_for_sort() {
        global $DB;
        $sqq2 = $this->studentquizquestions[1];
        // All users will comment once.
        foreach ($this->users as $k => $user) {
            $records[] = (object) [
                    'comment' => 'Test comment ' . $user->firstname . ' ' . $user->lastname,
                    'parentid' => $this->rootid,
                    'userid' => $user->id,
                    'created' => $k + 1,
                    'studentquizquestionid' => $sqq2->id
            ];
        }
        $DB->insert_records('studentquiz_comment', $records);
    }

    /**
     * Test sort feature. (Admin state).
     * @covers \mod_studentquiz\commentarea\container::fetch_all
     */
    public function test_sort_feature() {
        $sqq2 = $this->studentquizquestions[1];
        $base = $this->commentarea;
        // Test sort by date asc.
        $commentarea = new commentarea\container($sqq2, null,
                $base::SORT_DATE_ASC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[0]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[3]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[4]->id, $comments[4]->get_comment_data()->userid);

        // Test sort by desc.
        $commentarea = new commentarea\container($sqq2, null,
                $base::SORT_DATE_DESC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[5]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[4]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[3]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[4]->get_comment_data()->userid);

        // Test sort by first name asc.
        $commentarea = new commentarea\container($sqq2, null,
                $base::SORT_FIRSTNAME_ASC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[0]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[3]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[4]->id, $comments[4]->get_comment_data()->userid);

        // Test sort by first name desc.
        $commentarea = new commentarea\container($sqq2, null,
                $base::SORT_FIRSTNAME_DESC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[5]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[4]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[3]->id, $comments[4]->get_comment_data()->userid);

        // Test sort by last name asc.
        $commentarea = new commentarea\container($sqq2, null,
                $base::SORT_LASTNAME_ASC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[3]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[0]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[5]->id, $comments[4]->get_comment_data()->userid);

        // Test sort by last name desc.
        $commentarea = new commentarea\container($sqq2, null,
                $base::SORT_LASTNAME_DESC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[4]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[5]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[0]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[4]->get_comment_data()->userid);
    }

    /**
     * Test normal user sortable feature.
     * @covers \mod_studentquiz\commentarea\container::get_sortable
     */
    public function test_sortable_feature() {
        // Test if a normal user can sort in anonymous.
        $user = $this->users[0];
        $sqq2 = $this->studentquizquestions[1];
        $this->setUser($user);
        $commentarea = new commentarea\container($sqq2);
        $sortable = $commentarea->get_sortable();
        // Ok we can only sort by date.
        $this->assertEquals([
                $commentarea::SORT_DATE_ASC,
                $commentarea::SORT_DATE_DESC,
        ], $sortable);

        // Try to pass sort firstname asc. Expect FAIL.
        $commentarea = new commentarea\container($sqq2, null,
                $commentarea::SORT_FIRSTNAME_ASC);
        $this->assertNotEquals($commentarea->get_sort_feature(), $commentarea::SORT_FIRSTNAME_ASC);

        // Try to pass sort firstname desc. Expect FAIL.
        $commentarea = new commentarea\container($sqq2, null,
                $commentarea::SORT_FIRSTNAME_DESC);
        $this->assertNotEquals($commentarea->get_sort_feature(), $commentarea::SORT_FIRSTNAME_DESC);

        // Try to pass sort lastname asc. Expect FAIL.
        $commentarea = new commentarea\container($sqq2, null,
                $commentarea::SORT_LASTNAME_ASC);
        $this->assertNotEquals($commentarea->get_sort_feature(), $commentarea::SORT_LASTNAME_ASC);

        // Try to pass sort lastname desc. Expect FAIL.
        $commentarea = new commentarea\container($sqq2, null,
                $commentarea::SORT_LASTNAME_DESC);
        $this->assertNotEquals($commentarea->get_sort_feature(), $commentarea::SORT_LASTNAME_DESC);

        // Try to pass sort date desc. Of course it works!
        $commentarea = new commentarea\container($sqq2, null,
                $commentarea::SORT_DATE_DESC);
        $this->assertEquals($commentarea->get_sort_feature(), $commentarea::SORT_DATE_DESC);

        // Try to pass sort date asc. Of course it works!
        $commentarea = new commentarea\container($sqq2, null,
                $commentarea::SORT_DATE_ASC);
        $this->assertEquals($commentarea->get_sort_feature(), $commentarea::SORT_DATE_ASC);
    }

    /**
     * Setup some SQs with different settings.
     * @coversNothing
     */
    private function set_up_studentquizs_with_period() {
        $this->setUser($this->users[0]);
        $this->commentareanoperiod = $this->seed_studentquiz_period_setting(0);
        $this->commentareahasperiod = $this->seed_studentquiz_period_setting(10);
        $this->setAdminUser();
    }

    /**
     * Set up SQ disabled period setting + seed some comments.
     * @coversNothing
     *
     * @param int $period
     * @return commentarea\container
     */
    private function seed_studentquiz_period_setting($period) {
        global $DB;
        $course = $this->course;
        $activity = $this->getDataGenerator()->create_module('studentquiz', array(
                'course' => $course->id,
                'anonymrank' => true,
                'forcecommenting' => 1,
                'publishnewquestion' => 1
        ));
        // Why did we load incorrect contextid?
        $contextid = \context_module::instance($activity->coursemodule);
        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $contextid->id);
        $studentquiz->commentdeletionperiod = $period;
        $DB->update_record('studentquiz', $studentquiz);
        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $contextid->id);
        // Create questions in questionbank.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $q1 = $questiongenerator->create_question('truefalse', null, [
                'name' => 'TF1',
                'category' => $studentquiz->categoryid
        ]);
        $q1 = \question_bank::load_question($q1->id);
        $sqq1 = studentquiz_question::get_studentquiz_question_from_question($q1);
        $commentarea = new commentarea\container($sqq1, $this->users[0]);

        // Seed a comment.
        $DB->insert_record('studentquiz_comment', (object) [
                'comment' => 'Test comment',
                'parentid' => $this->rootid,
                'userid' => $commentarea->get_user()->id,
                'created' => time(),
                'studentquizquestionid' => $sqq1->id
        ]);
        return $commentarea;
    }

    /**
     * Test edit comment.
     * @covers \mod_studentquiz\commentarea\comment::update_comment
     */
    public function test_edit_comment() {
        // Create root comment.
        $sqq1 = $this->studentquizquestions[0];
        $text = 'Root comment';
        // Dont need to convert to use delete.
        $comment = $this->create_comment($this->rootid, $sqq1->id, $text, false);
        $formdata = new \stdClass();
        $formdata->message['text'] = 'Edited comment';
        $formdata->type = utils::COMMENT_TYPE_PUBLIC;
        // Try to update.
        $comment->update_comment($formdata);
        // Get new data.
        $commentafteredit = $this->get_comment_by_id($comment->get_id(), false);
        // Edit time now is > 0 (edited).
        $this->assertTrue($commentafteredit->get_comment_data()->status == utils::COMMENT_HISTORY_EDIT);
        // Check correct edit user id.
        $this->assertEquals($this->users[0]->id, $commentafteredit->get_comment_data()->userid);
        // Expect new comment is "Edited comment".
        $this->assertEquals($formdata->message['text'], $commentafteredit->get_comment_data()->comment);
    }

    /**
     * Test if setting = 0, we cannot edit a comment.
     * @covers \mod_studentquiz\commentarea\comment::can_edit
     */
    public function test_editable_when_turn_off_period_setting_comment() {
        $this->setUser($this->users[0]);
        $commentarea = $this->commentareanoperiod;
        $comments = $commentarea->fetch_all();
        // Check if setting = 0.
        $this->assertEquals(0, $commentarea->get_studentquiz()->commentdeletionperiod);
        // Ensure we cannot edit it.
        foreach ($comments as $comment) {
            $this->assertFalse($comment->can_edit());
        }
    }

    /**
     * Test if setting > 0, we can edit a comment.
     * @covers \mod_studentquiz\commentarea\comment::can_edit
     */
    public function test_editable_when_turn_on_period_setting_comment() {
        $this->setUser($this->users[0]);
        $commentarea = $this->commentareahasperiod;
        $comments = $commentarea->fetch_all();
        // Check if setting larger than 0.
        $this->assertTrue($commentarea->get_studentquiz()->commentdeletionperiod > 0);
        // Ensure we can edit it.
        foreach ($comments as $comment) {
            $this->assertTrue($comment->can_edit());
        }
    }

    /**
     * Test create comment history.
     * @covers \mod_studentquiz\commentarea\comment::create_history
     */
    public function test_create_comment_history() {
        global $DB;
        // Create root comment.
        $sqq1 = $this->studentquizquestions[0];
        $text = 'Root comment for history';
        $comment = $this->create_comment($this->rootid, $sqq1->id, $text, false);
        $comparestr = 'comment' . $comment->get_id();
        $historyid = $comment->create_history($comment->get_id(), $comment->get_user_id(), 0, $comparestr);
        $history = $DB->get_record('studentquiz_comment_history', ['id' => $historyid]);
        $this->assertEquals($history->commentid, $comment->get_id());
        $this->assertEquals($history->action, 0);
        $this->assertEquals($comparestr, $history->content);
    }

    /**
     * Test create comment history.
     * @covers \mod_studentquiz\commentarea\comment::get_history
     */
    public function test_get_histories() {
        $sqq1 = $this->studentquizquestions[0];
        $comment = $this->create_comment($this->rootid, $sqq1->id, 'demo content', false);
        $comment->create_history($comment->get_id(), $comment->get_user_id(), 1, 'comment1' . $comment->get_id());
        $comment->create_history($comment->get_id(), $comment->get_user_id(), 1, 'comment2' . $comment->get_id());
        $histories = $this->commentarea->get_history($comment->get_id());
        $this->assertCount(2, $histories);
        $this->assertEquals(current($histories)->userid, $comment->get_user_id());
    }

    /**
     * Test extract comment histories to render.
     * @covers \mod_studentquiz\commentarea\container::extract_comment_history_to_render
     */
    public function test_extract_comment_histories_to_render() {
        $mockhistory = new \stdClass();
        $mockhistory->id = 1;
        $mockhistory->timemodified = 1;
        $mockhistory->userid = $this->users[0]->id;
        $mockhistory->content = 'mock content';
        $mockhistory->rownumber = 1;
        $results = $this->commentarea->extract_comment_history_to_render([$mockhistory]);
        $this->assertCount(1, $results);
        $this->assertEquals(fullname($this->users[0]), $results[0]->authorname);
        $this->assertEquals($results[0]->content, 'mock content');
    }

    /**
     * Test user permission for preview mode.
     * @coversNothing
     */
    public function test_user_permission_for_preview_mode() {
        $this->assertTrue(has_capability('mod/studentquiz:canselfratecomment', $this->context));
        $this->setUser($this->users[0]);
        $this->assertFalse(has_capability('mod/studentquiz:canselfratecomment', $this->context));
    }

    /**
     * Test update comment last read.
     * @covers \mod_studentquiz\commentarea\container::update_comment_last_read
     */
    public function test_update_comment_last_read() {
        $time1 = time();
        $time2 = $time1 + 1000;
        $sqq1 = $this->studentquizquestions[0];
        $user = $this->users[0];
        $commentarea1 = new \mod_studentquiz\commentarea\container($sqq1, $this->users[0], '', 0);
        $commentarea2 = new \mod_studentquiz\commentarea\container($sqq1, $this->users[0], '', 1);
        $commentarea1->update_comment_last_read($time1);
        $commentarea2->update_comment_last_read($time2);
        $result = studentquiz_progress::get_studentquiz_progress($sqq1, $user->id);
        $this->assertEquals($time1, $result->lastreadpubliccomment);
        $this->assertEquals($time2, $result->lastreadprivatecomment);
    }
}
