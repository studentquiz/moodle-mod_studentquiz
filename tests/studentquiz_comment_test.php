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
 * Unit tests for comment area.
 *
 * @package    mod_studentquiz
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct Access is forbidden!');

use mod_studentquiz\commentarea\comment;
use mod_studentquiz\utils;

/**
 * Unit tests for comment area.
 *
 * @package    mod_studentquiz
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_comment_testcase extends advanced_testcase {

    /**
     * @var stdClass the StudentQuiz activity created in setUp.
     */
    protected $studentquiz;

    /**
     * @var context_module the corresponding activity context.
     */
    protected $context;

    /**
     * @var stdClass the corresponding course_module.
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

    /** @var mod_studentquiz\commentarea\container */
    protected $commentarea;

    /** @var int - Value of Root comment. */
    protected $rootid;

    /** @var stdClass - Course. */
    protected $course;

    /** @var mod_studentquiz\commentarea\container - Comment area has disabled period setting. */
    protected $commentareanoperiod;

    /** @var mod_studentquiz\commentarea\container - Comment area has enable period setting. */
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
        $this->context = context_module::instance($activity->cmid);
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

        // Create question 2.
        $q2 = $questiongenerator->create_question('truefalse', null,
                ['name' => 'TF2', 'category' => $this->studentquiz->categoryid]);
        $q2 = \question_bank::load_question($q2->id);

        $this->questions = [$q1, $q2];

        $this->commentarea = new \mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm,
            $this->context, $this->users[0]);
        $this->rootid = \mod_studentquiz\commentarea\container::PARENTID;

        $this->generate_comment_list_for_sort();

        // Create SQs with period setting.
        $this->set_up_studentquizs_with_period();
    }

    /**
     * Provide comment by id.
     *
     * @param int $id - Comment ID.
     * @param bool $convert - Is convert to object data.
     * @return comment|stdClass
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
     * @param int $questionid - Question ID.
     * @param string $text - Text of comment.
     * @param bool $convert - Is convert to object data.
     * @return comment
     */
    private function create_comment($replyto, $questionid, $text, $convert = true) {
        $data = [
                'message' => [
                        'text' => $text,
                        'format' => 1
                ],
                'questionid' => $questionid,
                'cmid' => $this->cm->id,
                'replyto' => $replyto,
                'type' => utils::COMMENT_TYPE_PUBLIC
        ];
        $id = $this->commentarea->create_comment((object) $data);
        return $this->get_comment_by_id($id, $convert);
    }

    /**
     * Test init comment area.
     */
    public function test_initial() {
        $question = $this->questions[0];
        $this->equalTo($question->id, $this->commentarea->get_question()->id);
        $this->equalTo($this->cm->id, $this->commentarea->get_cmid());
        $this->equalTo($this->studentquiz->id, $this->commentarea->get_studentquiz()->id);
        $this->equalTo($this->context->id, $this->commentarea->get_context()->id);
    }

    /**
     * Test create root comment.
     */
    public function test_create_root_comment() {
        // Create root comment.
        $q1 = $this->questions[0];
        $text = 'Root comment';
        $comment = $this->create_comment($this->rootid, $q1->id, $text);
        $this->assertEquals($text, $comment->content);
        $this->assertEquals($q1->id, $comment->questionid);
        $this->assertEquals($this->rootid, $comment->parentid);
    }

    /**
     * Test create reply.
     */
    public function test_create_reply_comment() {
        $q1 = $this->questions[0];
        $text = 'Root comment';
        $textreply = 'Reply root comment';
        $comment = $this->create_comment($this->rootid, $q1->id, $text);
        $reply = $this->create_comment($comment->id, $q1->id, $textreply);
        // Check text reply.
        $this->assertEquals($textreply, $reply->content);
        // Check question id.
        $this->assertEquals($q1->id, $reply->questionid);
        // Check if reply belongs to comment.
        $this->assertEquals($comment->id, $reply->parentid);
    }

    /**
     * Test delete comment.
     */
    public function test_delete_comment() {
        // Create root comment.
        $q1 = $this->questions[0];
        $text = 'Root comment';
        // Dont need to convert to use delete.
        $comment = $this->create_comment($this->rootid, $q1->id, $text, false);
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
     */
    public function test_fetch_all_comments() {
        $q1 = $this->questions[0];
        $text = 'Root comment';
        $textreply = 'Reply root comment';
        $numreplies = 3;
        $comment = $this->create_comment($this->rootid, $q1->id, $text);
        for ($i = 0; $i < $numreplies; $i++) {
            $this->create_comment($comment->id, $q1->id, $textreply);
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
     */
    public function test_report_feature() {
        global $DB;
        $q1 = $this->questions[0];
        // Need to use comment class functions. Don't use convert to response data.
        $comment = $this->create_comment($this->rootid, $q1->id, 'Test comment', false);
        // Assume that we didn't input any emails for report. It will return false.
        $this->assertFalse($comment->can_report());
        // Turn on report.
        $inputreportemails = 'admin@domain.com;admin1@domail.com';
        $this->studentquiz->reportingemail = $inputreportemails;
        $DB->update_record('studentquiz', $this->studentquiz);
        // Re-init SQ.
        $this->studentquiz = mod_studentquiz_load_studentquiz($this->cm->id, $this->context->id);
        // Re-init comment area.
        $this->commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context);
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
     */
    private function generate_comment_list_for_sort() {
        global $DB;
        $question = $this->questions[1];
        // All users will comment once.
        foreach ($this->users as $k => $user) {
            $records[] = (object) [
                    'comment' => 'Test comment ' . $user->firstname . ' ' . $user->lastname,
                    'parentid' => $this->rootid,
                    'userid' => $user->id,
                    'created' => $k + 1,
                    'questionid' => $question->id
            ];
        }
        $DB->insert_records('studentquiz_comment', $records);
    }

    /**
     * Test sort feature. (Admin state).
     */
    public function test_sort_feature() {
        $q1 = $this->questions[1];
        $base = $this->commentarea;
        // Test sort by date asc.
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $base::SORT_DATE_ASC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[0]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[3]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[4]->id, $comments[4]->get_comment_data()->userid);

        // Test sort by desc.
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $base::SORT_DATE_DESC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[5]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[4]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[3]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[4]->get_comment_data()->userid);

        // Test sort by first name asc.
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $base::SORT_FIRSTNAME_ASC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[0]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[3]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[4]->id, $comments[4]->get_comment_data()->userid);

        // Test sort by first name desc.
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $base::SORT_FIRSTNAME_DESC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[5]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[4]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[3]->id, $comments[4]->get_comment_data()->userid);

        // Test sort by last name asc.
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $base::SORT_LASTNAME_ASC);
        $comments = $commentarea->fetch_all(5);
        $this->assertEquals($this->users[3]->id, $comments[0]->get_comment_data()->userid);
        $this->assertEquals($this->users[1]->id, $comments[1]->get_comment_data()->userid);
        $this->assertEquals($this->users[2]->id, $comments[2]->get_comment_data()->userid);
        $this->assertEquals($this->users[0]->id, $comments[3]->get_comment_data()->userid);
        $this->assertEquals($this->users[5]->id, $comments[4]->get_comment_data()->userid);

        // Test sort by last name desc.
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
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
     */
    public function test_sortable_feature() {
        // Test if a normal user can sort in anonymous.
        $user = $this->users[0];
        $q1 = $this->questions[1];
        $this->setUser($user);
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context);
        $sortable = $commentarea->get_sortable();
        // Ok we can only sort by date.
        $this->assertEquals([
                $commentarea::SORT_DATE_ASC,
                $commentarea::SORT_DATE_DESC,
        ], $sortable);

        // Try to pass sort firstname asc. Expect FAIL.
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $commentarea::SORT_FIRSTNAME_ASC);
        $this->assertNotEquals($commentarea->get_sort_feature(), $commentarea::SORT_FIRSTNAME_ASC);

        // Try to pass sort firstname desc. Expect FAIL.
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $commentarea::SORT_FIRSTNAME_DESC);
        $this->assertNotEquals($commentarea->get_sort_feature(), $commentarea::SORT_FIRSTNAME_DESC);

        // Try to pass sort lastname asc. Expect FAIL.
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $commentarea::SORT_LASTNAME_ASC);
        $this->assertNotEquals($commentarea->get_sort_feature(), $commentarea::SORT_LASTNAME_ASC);

        // Try to pass sort lastname desc. Expect FAIL.
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $commentarea::SORT_LASTNAME_DESC);
        $this->assertNotEquals($commentarea->get_sort_feature(), $commentarea::SORT_LASTNAME_DESC);

        // Try to pass sort date desc. Of course it works!
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $commentarea::SORT_DATE_DESC);
        $this->assertEquals($commentarea->get_sort_feature(), $commentarea::SORT_DATE_DESC);

        // Try to pass sort date asc. Of course it works!
        $commentarea = new mod_studentquiz\commentarea\container($this->studentquiz, $q1, $this->cm, $this->context, null,
                $commentarea::SORT_DATE_ASC);
        $this->assertEquals($commentarea->get_sort_feature(), $commentarea::SORT_DATE_ASC);
    }

    /**
     * Setup some SQs with different settings.
     */
    private function set_up_studentquizs_with_period() {
        $this->setUser($this->users[0]);
        $this->commentareanoperiod = $this->seed_studentquiz_period_setting(0);
        $this->commentareahasperiod = $this->seed_studentquiz_period_setting(10);
        $this->setAdminUser();
    }

    /**
     * Set up SQ disabled period setting + seed some comments.
     *
     * @param int $period
     * @return \mod_studentquiz\commentarea\container
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
        $context = context_module::instance($activity->cmid);

        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $this->context->id);
        $studentquiz->commentdeletionperiod = $period;
        $DB->update_record('studentquiz', $studentquiz);
        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $this->context->id);

        $cm = get_coursemodule_from_id('studentquiz', $activity->cmid);

        // Create questions in questionbank.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $q1 = $questiongenerator->create_question('truefalse', null, [
                'name' => 'TF1',
                'category' => $studentquiz->categoryid
        ]);
        $q1 = \question_bank::load_question($q1->id);

        $commentarea = new \mod_studentquiz\commentarea\container($studentquiz, $q1, $cm, $context, $this->users[0]);

        // Seed a comment.
        $DB->insert_record('studentquiz_comment', (object) [
                'comment' => 'Test comment',
                'parentid' => $this->rootid,
                'userid' => $commentarea->get_user()->id,
                'created' => time(),
                'questionid' => $q1->id
        ]);

        return $commentarea;
    }

    /**
     * Test edit comment.
     */
    public function test_edit_comment() {
        // Create root comment.
        $q1 = $this->questions[0];
        $text = 'Root comment';
        // Dont need to convert to use delete.
        $comment = $this->create_comment($this->rootid, $q1->id, $text, false);
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
     */
    public function test_create_comment_history() {
        global $DB;
        // Create root comment.
        $q1 = $this->questions[0];
        $text = 'Root comment for history';
        $comment = $this->create_comment($this->rootid, $q1->id, $text, false);
        $comparestr = 'comment' . $comment->get_id();
        $historyid = $comment->create_history($comment->get_id(), $comment->get_user_id(), 0, $comparestr);
        $history = $DB->get_record('studentquiz_comment_history', ['id' => $historyid]);
        $this->assertEquals($history->commentid, $comment->get_id());
        $this->assertEquals($history->action, 0);
        $this->assertEquals($comparestr, $history->content);
    }

    /**
     * Test create comment history.
     */
    public function test_get_histories() {
        $comment = $this->create_comment($this->rootid, $this->questions[0]->id, 'demo content', false);
        $comment->create_history($comment->get_id(), $comment->get_user_id(), 1, 'comment1' . $comment->get_id());
        $comment->create_history($comment->get_id(), $comment->get_user_id(), 1, 'comment2' . $comment->get_id());
        $histories = $this->commentarea->get_history($comment->get_id());
        $this->assertCount(2, $histories);
        $this->assertEquals(current($histories)->userid, $comment->get_user_id());
    }

    /**
     * Test extract comment histories to render.
     */
    public function test_extract_comment_histories_to_render() {
        $mockhistory = new stdClass();
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
     */
    public function test_user_permission_for_preview_mode() {
        $this->assertTrue(has_capability('mod/studentquiz:canselfratecomment', $this->context));
        $this->setUser($this->users[0]);
        $this->assertFalse(has_capability('mod/studentquiz:canselfratecomment', $this->context));
    }
}
