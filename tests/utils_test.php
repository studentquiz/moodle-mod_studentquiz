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
use core_question\local\bank\question_version_status;

/**
 * Unit tests for (some of) classes/utils.php
 *
 * @package mod_studentquiz
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils_test extends \advanced_testcase {

    /**
     * Test send report function
     *
     * @covers \mod_studentquiz\utils::send_report
     */
    public function test_send_report() {
        global $CFG;
        $this->resetAfterTest();
        $normal = [
            'formdata' => [
                'condition1' => 1,
                'condition2' => 1,
                'conditionmore' => 'Test report comment'
            ],
            'recipients' => ['recipient@samplemail.com'],
            'customdata' => [
                'questionid' => 1,
                'cmid' => 1,
                'commentid' => 1,
                'email' => 'sender@sampleemail.com',
                'username' => 'Test username',
                'studentquizname' => 'Test StudentQuiz',
                'previewurl' => ' http://sample.com',
                'coursename' => 'Test course',
                'fullname' => 'Test username Student',
                'ip' => '192.0.0.1'
            ],
            'options' => [
                'numconditions' => 2,
                'conditions' => [1 => 'Con 1', 2 => 'Con 2']
            ]
        ];
        $error = [
            'formdata' => [
                'condition1' => 1,
                'condition2' => 1,
                'condition3' => 1,
                'conditionmore' => 'Test report comment'
            ],
            'recipients' => ['dsadasda.com'],
            'customdata' => [
                'questionid' => 1,
                'cmid' => 1,
                'commentid' => 1,
                'previewurl' => ' http://sample.com',
                'coursename' => 'Test course',
                'fullname' => 'Test username Student',
                'ip' => '192.0.0.1'
            ],
            'options' => [
                'numconditions' => 2,
                'conditions' => [1 => 'Con 1', 2 => 'Con 2']
            ]
        ];
        // Normal case.
        $formdata = (object)$normal['formdata'];
        $recipients = $normal['recipients'];
        $customdata = $normal['customdata'];
        $options = $normal['options'];
        // Create sink to catch all sent e-mails.
        $sink = $this->redirectEmails();
        utils::send_report($formdata, $recipients, $customdata, $options);
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $sink->close();

        // Exception case.
        $formdata = (object)$error['formdata'];
        $recipients = $error['recipients'];
        $customdata = $error['customdata'];
        $options = $error['options'];
        // Function email_to_user will return false and display a debug message. We don't need test the debug message here.
        $CFG->debugdisplay = 0;
        $CFG->debug = 0;
        $sink = $this->redirectEmails();
        $this->expectException(\moodle_exception::class);
        utils::send_report($formdata, $recipients, $customdata, $options);
        $sink->close();
    }

    /**
     * Test cases for the ensure_studentquiz_question_status_is_always_ready tests.
     *
     * @return array
     */
    public function ensure_studentquiz_question_status_is_always_ready_testcases(): array {
        return [
            'ready' => [
                'ready', false
            ],
            'draft' => [
                'draft', true
            ],
            'hidden' => [
                'hidden', true
            ],
        ];
    }

    /**
     * Test ensure_studentquiz_question_status_is_always_ready function
     *
     * @dataProvider ensure_studentquiz_question_status_is_always_ready_testcases
     * @covers \mod_studentquiz\utils::ensure_studentquiz_question_status_is_always_ready
     *
     * @param string $status question status. E.g: ready, hidden, draft.
     * @param bool $expected Expected result when we run the query.
     */
    public function test_ensure_studentquiz_question_status_is_always_ready(string $status, bool $expected): void {
        global $DB;
        $this->resetAfterTest();
        // Setup.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $activity = $generator->create_module('studentquiz', [
            'course' => $course->id,
            'anonymrank' => true,
            'forcecommenting' => 1,
        ]);
        $context = \context_module::instance($activity->cmid);
        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $context->id);
        $question = $questiongenerator->create_question('truefalse', null,
            ['name' => 'TF1', 'category' => $studentquiz->categoryid]);
        // We can't change the question status due to core hardcode without change the get_question_form_data function.
        // See question/tests/generator/lib.php line 93.
        // So we need to update again.
        $versionrecord = $DB->get_record('question_versions', ['questionid' => $question->id]);
        $versionrecord->status = $status;
        $DB->update_record('question_versions', $versionrecord);
        $oldquestionversion = get_question_version($question->id);
        $oldquestionversion = reset($oldquestionversion);

        // Execute.
        $result = utils::ensure_studentquiz_question_status_is_always_ready($question->id);
        // Load question version again to get the latest status.
        $newquestionversion = get_question_version($question->id);
        $newquestionversion = reset($newquestionversion);

        // Assert.
        $this->assertEquals($status, $oldquestionversion->status);
        $this->assertEquals($expected, $result);
        $this->assertEquals(question_version_status::QUESTION_STATUS_READY, $newquestionversion->status);
    }

}
