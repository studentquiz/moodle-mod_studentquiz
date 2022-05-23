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

/**
 * Unit tests for SQ when delete SQ instance.
 *
 * @package mod_studentquiz
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_instance_test extends \advanced_testcase {

    /**
     * @var int $studentquizid Id of StudentQuiz instance.
     */
    private $studentquizid;
    /**
     * @var int $questionid Id of question instance.
     */
    private $questionid;
    /**
     * @var int $commentid Id of comment.
     */
    private $commentid;

    /**
     * Setup StudentQuiz sample data for testing.
     * One user, one studentquiz in one course.
     */
    protected function setUp(): void {
        global $DB;
        // Setup course and sq activity.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('studentquiz', [
            'course' => $course->id,
            'anonymrank' => true,
            'forcecommenting' => 1,
        ]);

        $cm = get_coursemodule_from_id('studentquiz', $activity->cmid);
        $context = \context_module::instance($activity->cmid);
        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $context->id);

        $q = $this->getDataGenerator()->get_plugin_generator('core_question')->create_question('truefalse', null,
            ['name' => 'TF1', 'category' => $studentquiz->categoryid]);
        $question = \question_bank::load_question($q->id);

        $commentarea = new commentarea\container($studentquiz, $question, $cm, $context);
        $comment = [
            'message' => [
                'text' => 'Root message',
                'format' => 1
            ],
            'questionid' => $question->id,
            'cmid' => $activity->cmid,
            'replyto' => 0,
            'type' => utils::COMMENT_TYPE_PUBLIC
        ];

        $commentid = $commentarea->create_comment((object)$comment);

        $historyedit = $historycreate = (object) [
            'commentid' => $commentid,
            'content' => 'Sample comment ' . rand(1, 1000),
            'userid' => $user->id,
            'action' => utils::COMMENT_HISTORY_CREATE,
            'timemodified' => rand(1000000000, 2000000000)
        ];
        $historyedit->action = utils::COMMENT_HISTORY_EDIT;

        $DB->insert_record('studentquiz_comment_history', $historycreate);
        $DB->insert_record('studentquiz_comment_history', $historyedit);

        $attempt = (object) [
            'id' => 0,
            'studentquizid' => $studentquiz->id,
            'userid' => $user->id,
            'questionusageid' => rand(1, 100),
            'categoryid' => $studentquiz->categoryid,
        ];
        $DB->insert_record('studentquiz_attempt', $attempt);

        $rate = (object) [
            'id' => 0,
            'rate' => rand(1, 5),
            'questionid' => $question->id,
            'userid' => $user->id
        ];
        $DB->insert_record('studentquiz_rate', $rate);

        $progress = (object) [
            'questionid' => $question->id,
            'userid' => $user->id,
            'studentquizid' => $studentquiz->id,
            'lastanswercorrect' => rand(0, 1),
            'attempts' => rand(1, 1000),
            'correctattempts' => rand(1, 1000),
            'lastreadprivatecomment' => rand(1, 10000),
            'lastreadpubliccomment' => rand(1, 10000)
        ];
        $DB->insert_record('studentquiz_progress', $progress, false);

        $notification = (object) [
            'id' => 0,
            'studentquizid' => $studentquiz->id,
            'recipientid' => $user->id,
            'content' => 'Sample content ' . rand(1, 1000),
        ];
        $DB->insert_record('studentquiz_notification', $notification);

        $this->studentquizid = $studentquiz->id;
        $this->questionid = $question->id;
        $this->commentid  = $commentid;
    }

    /**
     * Test delete instance.
     * @covers \studentquiz_delete_instance
     */
    public function test_delete_instance(): void {
        $this->resetAfterTest();

        $beforedeleteexpected = [
            'comment' => 1,
            'comment_history' => 2,
            'state_history' => 1,
            'rate' => 1,
            'progress' => 1,
            'attempt' => 1,
            'notification' => 1,
            'question' => 1,
            'studentquiz' => 1
        ];
        $afterdeleteexpected = [
            'comment' => 0,
            'comment_history' => 0,
            'state_history' => 0,
            'rate' => 0,
            'progress' => 0,
            'attempt' => 0,
            'notification' => 0,
            'question' => 0,
            'studentquiz' => 0
        ];

        self::check_sq_instance_data($this->questionid, $this->studentquizid, $this->commentid, $beforedeleteexpected);
        studentquiz_delete_instance($this->studentquizid);
        self::check_sq_instance_data($this->questionid, $this->studentquizid, $this->commentid, $afterdeleteexpected);

    }

    /**
     * Check SQ instance number base on expected number.
     *
     * @param int $questionid
     * @param int $studenquizid StudentQuiz id.
     * @param int $commentid Comment id.
     * @param array $expected Number of record exist in the database.
     */
    private function check_sq_instance_data(int $questionid, int $studenquizid, int $commentid, array $expected): void {
        global $DB;

        $comment = $DB->count_records('studentquiz_comment', ['questionid' => $questionid]);
        $commenthistory = $DB->count_records('studentquiz_comment_history', ['commentid' => $commentid]);
        $statehistory = $DB->count_records('studentquiz_state_history', ['questionid' => $questionid]);
        $rate = $DB->count_records('studentquiz_rate', ['questionid' => $questionid]);
        $progress = $DB->count_records('studentquiz_progress', ['questionid' => $questionid,
            'studentquizid' => $studenquizid]);
        $studentquizquestion = $DB->count_records('studentquiz_question', ['questionid' => $questionid]);
        $attempt = $DB->count_records('studentquiz_attempt', ['studentquizid' => $studenquizid]);
        $notification = $DB->count_records('studentquiz_notification', ['studentquizid' => $studenquizid]);
        $studentquizcount = $DB->count_records('studentquiz', ['id' => $studenquizid]);

        $this->assertEquals($expected['comment'], $comment);
        $this->assertEquals($expected['comment_history'], $commenthistory);
        $this->assertEquals($expected['state_history'], $statehistory);
        $this->assertEquals($expected['rate'], $rate);
        $this->assertEquals($expected['progress'], $progress);
        $this->assertEquals($expected['attempt'], $attempt);
        $this->assertEquals($expected['notification'], $notification);
        $this->assertEquals($expected['question'], $studentquizquestion);
        $this->assertEquals($expected['studentquiz'], $studentquizcount);
    }
}
