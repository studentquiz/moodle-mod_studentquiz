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

/**
 * Unit tests for SQ when delete SQ instance.
 *
 * @package mod_studentquiz
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_instance_test extends \advanced_testcase {

    /**
     * Test delete instance.
     * @covers \studentquiz_delete_instance
     */
    public function test_delete_instance(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $course = $this->getDataGenerator()->create_course();

        $activity = $this->getDataGenerator()->create_module('studentquiz', array(
                'course' => $course->id,
                'anonymrank' => true,
                'forcecommenting' => 1,
        ));
        $context = \context_module::instance($activity->cmid);

        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $context->id);

        $q = $questiongenerator->create_question('truefalse', null,
                ['name' => 'TF1', 'category' => $studentquiz->categoryid]);
        $question = \question_bank::load_question($q->id);

        $sqq = studentquiz_question::get_studentquiz_question_from_question($question);

        $commentarea = new commentarea\container($sqq, null);
        $commentd = [
                'message' => [
                        'text' => 'Root message',
                        'format' => 1
                ],
                'studentquizquestionid' => $sqq->id,
                'cmid' => $activity->cmid,
                'replyto' => 0,
                'type' => utils::COMMENT_TYPE_PUBLIC
        ];
        $commentid = $commentarea->create_comment((object)$commentd);

        $data = (object) [
                'commentid' => $commentid,
                'content' => 'Sample comment ' . rand(1, 1000),
                'userid' => $user->id,
                'action' => utils::COMMENT_HISTORY_CREATE,
                'timemodified' => rand(1000000000, 2000000000)
        ];

        $DB->insert_record('studentquiz_comment_history', $data);

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
                'studentquizquestionid' => $sqq->id,
                'userid' => $user->id
        ];
        $DB->insert_record('studentquiz_rate', $rate);

        $progress = (object) [
                'studentquizquestionid' => $sqq->id,
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
        // Before deletion.
        self::check_sq_instance_data($sqq->id, $studentquiz->id, $commentid, 1);
        studentquiz_delete_instance($studentquiz->id);
        // After deletion.
        self::check_sq_instance_data($sqq->id, $studentquiz->id, $commentid, 0);

    }

    /**
     * Check SQ instance number base on expected number.
     *
     * @param int $studentquizquestionid StudentQuiz-Question id.
     * @param int $studenquizid StudentQuiz id.
     * @param int $commentid Comment id.
     * @param int $expected Number of record exist in the database.
     */
    private function check_sq_instance_data($studentquizquestionid, $studenquizid, $commentid, $expected): void {
        global $DB;
        $reference = $DB->count_records('question_references', ['itemid' => $studentquizquestionid,
                'component' => 'mod_studentquiz', 'questionarea' => 'studentquiz_question']);
        $comment = $DB->count_records('studentquiz_comment', ['studentquizquestionid' => $studentquizquestionid]);
        $commenthistory = $DB->count_records('studentquiz_comment_history', ['commentid' => $commentid]);
        $statehistory = $DB->count_records('studentquiz_state_history', ['studentquizquestionid' => $studentquizquestionid]);
        $rate = $DB->count_records('studentquiz_rate', ['studentquizquestionid' => $studentquizquestionid]);
        $progress = $DB->count_records('studentquiz_progress', ['studentquizquestionid' => $studentquizquestionid,
                'studentquizid' => $studenquizid]);
        $attempt = $DB->count_records('studentquiz_attempt', ['studentquizid' => $studenquizid]);
        $notification = $DB->count_records('studentquiz_notification', ['studentquizid' => $studenquizid]);
        $studentquizquestion = $DB->count_records('studentquiz_question', ['studentquizid' => $studenquizid]);
        $studentquizcount = $DB->count_records('studentquiz', array('id' => $studenquizid));

        $this->assertEquals($expected, $reference);
        $this->assertEquals($expected, $comment);
        $this->assertEquals($expected, $commenthistory);
        $this->assertEquals($expected, $statehistory);
        $this->assertEquals($expected, $rate);
        $this->assertEquals($expected, $progress);
        $this->assertEquals($expected, $attempt);
        $this->assertEquals($expected, $notification);
        $this->assertEquals($expected, $studentquizquestion);
        $this->assertEquals($expected, $studentquizcount);
    }
}
