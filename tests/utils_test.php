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

}
