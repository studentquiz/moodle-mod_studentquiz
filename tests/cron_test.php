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

/**
 * Cron test.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_test extends \advanced_testcase {

    /** @var \stdClass */
    protected $course;

    /** @var \stdClass */
    protected $student1;

    /** @var \stdClass */
    protected $student2;

    /** @var \stdClass */
    protected $teacher;

    /** @var \stdClass */
    protected $studentquizdata;

    /** @var int */
    protected $cmid;

    /** @var \stdClass */
    protected $studentquiz;

    /** @var array */
    protected $questions;

    /** @var array */
    protected $studentquizquestions;

    protected function setUp(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $questiongenerator = $generator->get_plugin_generator('core_question');

        // Prepare course.
        $this->course = $generator->create_course();

        // Prepare users.
        $this->student1 =
                $generator->create_user(['firstname' => 'Student', 'lastname' => '1', 'email' => 'student1@localhost.com']);
        $this->student2 =
                $generator->create_user(['firstname' => 'Student', 'lastname' => '2', 'email' => 'student2@localhost.com']);
        $this->teacher = $generator->create_user(['email' => 'teacher@localhost.com']);

        // Users enrolments.
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
        $this->getDataGenerator()->enrol_user($this->student1->id, $this->course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->student2->id, $this->course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $teacherrole->id, 'manual');

        // Prepare studentquiz.
        $this->studentquizdata = [
            'course' => $this->course->id,
            'anonymrank' => true,
            'questionquantifier' => 10,
            'approvedquantifier' => 5,
            'ratequantifier' => 3,
            'correctanswerquantifier' => 2,
            'incorrectanswerquantifier' => -1,
        ];

        $this->cmid = $generator->create_module('studentquiz', $this->studentquizdata)->cmid;
        $this->studentquiz = mod_studentquiz_load_studentquiz($this->cmid, \context_module::instance($this->cmid)->id);

        // Prepare question.
        $this->setUser($this->student1);
        $this->setUser($this->student2);
        $this->questions[0] = $questiongenerator->create_question('truefalse', null,
                ['name' => 'Student 1 Question', 'category' => $this->studentquiz->categoryid]);
        $this->questions[1] = $questiongenerator->create_question('truefalse', null,
                ['name' => 'Student 2 Question', 'category' => $this->studentquiz->categoryid]);
        $this->questions[0] = \question_bank::load_question($this->questions[0]->id);
        $this->questions[1] = \question_bank::load_question($this->questions[1]->id);
        $this->studentquizquestions[0] = studentquiz_question::get_studentquiz_question_from_question($this->questions[0]);
        $this->studentquizquestions[1] = studentquiz_question::get_studentquiz_question_from_question($this->questions[1]);
        // Prepare comment.
        $commentrecord = new \stdClass();
        $commentrecord->studentquizquestionid = $this->studentquizquestions[0]->id;
        $commentrecord->userid = $this->student1->id;
        $this->getDataGenerator()->get_plugin_generator('mod_studentquiz')->create_comment($commentrecord);

        // Prepare rate.
        $raterecord = new \stdClass();
        $raterecord->rate = 5;
        $raterecord->studentquizquestionid = $this->studentquizquestions[0]->id;
        $raterecord->userid = $this->student1->id;
        \mod_studentquiz\utils::save_rate($raterecord);
    }

    /**
     * Test send_no_digest_notification_task
     *
     * @dataProvider state_data_provider
     * @covers \mod_studentquiz\task\send_digest_notification_task
     * @param string $state State of the question.
     */
    public function test_send_no_digest_notification_task(string $state) {
        $question = $this->questions[0];
        $notifydata = mod_studentquiz_prepare_notify_data($this->studentquizquestions[0],
            $this->student1, get_admin(), $this->course,
            get_coursemodule_from_id('studentquiz', $this->cmid),
        );
        $customdata = [
            'eventname' => $state,
            'courseid' => $this->course->id,
            'submitter' => get_admin(),
            'recipient' => $this->student1,
            'messagedata' => $notifydata,
            'questionurl' => $notifydata->questionurl,
            'questionname' => $notifydata->questionname,
            'isstudent' => $notifydata->isstudent,
            'courseshortname' => $notifydata->courseshortname,
        ];

        $this->preventResetByRollback();
        $sink = $this->redirectMessages();
        // Execute the cron.
        $this->cron_setup_user();
        $cron = new task\send_no_digest_notification_task();
        $cron->set_custom_data($customdata);
        $cron->set_component('mod_studentquiz');
        $cron->execute();
        // Get email content.
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
        $this->expectOutputString('Sending notification for StudentQuiz for question ' . $question->name .
            ' to ' . $notifydata->recepientname . "\n");
        $this->assertStringContainsString('Your question <b>' . $question->name .
            '</b> in StudentQuiz activity <b>' . $this->studentquizquestions[0]->get_studentquiz()->name .
            '</b> in course <b>' . $this->course->fullname . '</b> has been ' . $state, $messages[0]->fullmessage);
    }

    /**
     * Test send_no_digest_notification_task
     *
     * @dataProvider state_data_provider
     * @covers \mod_studentquiz\task\send_digest_notification_task
     * @param string $state State of the question.
     */
    public function test_send_digest_notification_task(string $state) {
        global $DB;
        date_default_timezone_set('UTC');

        $notifydata = mod_studentquiz_prepare_notify_data($this->studentquizquestions[0],
                $this->student1, get_admin(), $this->course,
                get_coursemodule_from_id('studentquiz', $this->cmid));

        $customdata = [
            'eventname' => $state,
            'courseid' => $this->course->id,
            'submitter' => get_admin(),
            'recipient' => $this->student2,
            'messagedata' => $notifydata,
            'questionurl' => $notifydata->questionurl,
            'questionname' => $notifydata->questionname,
            'isstudent' => $notifydata->isstudent
        ];

        $notificationqueue = new \stdClass();
        $notificationqueue->studentquizid = $notifydata->moduleid;
        $notificationqueue->content = serialize($customdata);
        $notificationqueue->recipientid = $this->student2->id;
        $notificationqueue->timetosend = strtotime('-1 day', strtotime(date('Y-m-d')));
        $DB->insert_record('studentquiz_notification', $notificationqueue);

        $this->preventResetByRollback();
        $sink = $this->redirectMessages();
        // Execute the cron.
        $this->cron_setup_user();
        $cron = new task\send_digest_notification_task();
        $cron->set_component('mod_studentquiz');
        $cron->execute();
        // Get email content.
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages));
        $this->assertStringContainsString('Your question <b>'. $notifydata->questionname .
            '</b> has been <b>' . $state . '</b>', $messages[0]->fullmessage);
        $this->expectOutputRegex("/^Sending digest notification for StudentQuiz/");
    }

    /**
     * Data provider for state.
     *
     * @coversNothing
     * @return array List data of state.
     */
    public function state_data_provider(): array {
        return [
            'Notifying updated question state to changed' => [
                'state' => studentquiz_helper::$statename[studentquiz_helper::STATE_CHANGED],
            ],
            'Notifying updated question state to disapproved' => [
                'state' => studentquiz_helper::$statename[studentquiz_helper::STATE_DISAPPROVED],
            ],
            'Notifying updated question state to reviewable' => [
                'state' => studentquiz_helper::$statename[studentquiz_helper::STATE_REVIEWABLE],
            ],
            'Notifying updated question state to deleted' => [
                'state' => studentquiz_helper::$statename[studentquiz_helper::STATE_DELETE],
            ],
            'Notifying updated question state to hidden' => [
                'state' => studentquiz_helper::$statename[studentquiz_helper::STATE_HIDE],
            ],
            'Notifying unhide a question' => [
                'state' => 'unhidden',
            ],
            'Notifying pin a question' => [
                'state' => 'pinned',
            ],
            'Notifying unpin a question' => [
                'state' => 'unpinned',
            ],
        ];
    }

    /**
     * Test mod_studentquiz_prepare_notify_data
     *
     * @covers ::mod_studentquiz_prepare_notify_data
     */
    public function test_mod_studentquiz_prepare_notify_data(): void {

        // All data providers are executed the setUp method.
        // Because of that you can't access any variables you create there from within a data provider.
        // So we can't use provider here despite we have similar steps.

        // Recipient is student.
        $notifydata = mod_studentquiz_prepare_notify_data($this->studentquizquestions[0],
            $this->student1, get_admin(), $this->course, get_coursemodule_from_id('studentquiz', $this->cmid));
        $anonstudent = get_string('creator_anonym_fullname', 'studentquiz');
        $anonmanager = get_string('manager_anonym_fullname', 'studentquiz');

        $this->assertEquals(true, $notifydata->isstudent);
        $this->assertEquals($anonstudent, $notifydata->recepientname);
        $this->assertEquals($anonmanager, $notifydata->actorname);

        // Recipient is admin.
        $notifydata = mod_studentquiz_prepare_notify_data($this->studentquizquestions[0],
            get_admin(), $this->student1, $this->course, get_coursemodule_from_id('studentquiz', $this->cmid));

        $this->assertEquals(false, $notifydata->isstudent);
        $this->assertEquals($anonmanager, $notifydata->recepientname);
        $this->assertEquals($anonstudent, $notifydata->actorname);

        // Recipient is teacher enrol in the course.
        $notifydata = mod_studentquiz_prepare_notify_data($this->studentquizquestions[0],
            $this->teacher, get_admin(), $this->course, get_coursemodule_from_id('studentquiz', $this->cmid));

        $this->assertEquals(false, $notifydata->isstudent);
        $this->assertEquals($anonmanager, $notifydata->recepientname);
        $this->assertEquals($anonstudent, $notifydata->actorname);

    }

    /**
     * Test delete_orphaned_questions
     *
     * @covers \mod_studentquiz\task\delete_orphaned_questions
     */
    public function test_delete_orphaned_questions(): void {
        global $DB;
        set_config('deleteorphanedquestions', true, 'studentquiz');
        set_config('deleteorphanedtimelimit', 30, 'studentquiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        // Change the question to disapprove.
        $this->studentquizquestions[0]->change_state_visibility(studentquiz_helper::STATE_DISAPPROVED);

        // Make sure modified time lower than time limit for Q1 version 1.
        $updatedquestion = new \stdClass();
        $updatedquestion->id = $this->questions[0]->id;
        $updatedquestion->timemodified = $this->questions[0]->timemodified - 31;
        $DB->update_record('question', $updatedquestion);

        $q2v1 = $questiongenerator->create_question('truefalse', null,
            ['name' => 'Student 1 Question', 'category' => $this->studentquiz->categoryid]);
        // Make sure modified time lower than time limit for Q2 version 1.
        $updatedquestion = new \stdClass();
        $updatedquestion->id = $q2v1->id;
        $updatedquestion->timemodified = $q2v1->timemodified - 31;
        $DB->update_record('question', $updatedquestion);
        // Create version 2 of Q1.
        $q2v2 = $questiongenerator->update_question($q2v1, null, ['idnumber' => 'id2']);
        // Make sure modified time lower than time limit for Q1 version 2.
        $updatedquestion = new \stdClass();
        $updatedquestion->id = $q2v2->id;
        $updatedquestion->timemodified = $q2v2->timemodified - 31;
        $DB->update_record('question', $updatedquestion);
        // Create an attempt for Q2 version 2.
        mod_studentquiz_generate_attempt([$q2v2->id], $this->studentquiz, $this->student1->id);
        // Create Q2 version 3 so we can use it as latest.
        $q2v3 = $questiongenerator->update_question($q2v2, null, ['idnumber' => 'id3']);
        $sqq = studentquiz_question::get_studentquiz_question_from_question(\question_bank::load_question($q2v1->id));
        $sqq->change_state_visibility(studentquiz_helper::STATE_DISAPPROVED);
        // Execute the cron.
        $this->cron_setup_user();
        $cron = new task\delete_orphaned_questions();
        $cron->set_component('mod_studentquiz');
        $cron->execute();

        // Verify : We should only delete Q1 v1 and Q2 v1.
        // Q1 v1 is disapprove and pass the time limit.
        // Q2 v1 is the only latest version but pass the time limit.
        // Q2 v2 is disapprove but used in an attempt.
        // Q2 v3 is the disapprove, but not pass the time limit.

        $this->assertEquals(0, $DB->count_records('question', ['id' => $q2v1->id]));
        $this->assertEquals(0, $DB->count_records('studentquiz_rate',
            ['studentquizquestionid' => $sqq->id]));
        $this->assertEquals(0, $DB->count_records('studentquiz_comment',
            ['studentquizquestionid' => $sqq->id]));
        $this->assertEquals(0, $DB->count_records('studentquiz_question',
            ['id' => $sqq->id]));

        $this->assertEquals(0, $DB->count_records('question', ['id' => $this->questions[0]->id]));
        $this->assertEquals(0, $DB->count_records('studentquiz_rate',
            ['studentquizquestionid' => $this->studentquizquestions[0]->id]));
        $this->assertEquals(0, $DB->count_records('studentquiz_comment',
            ['studentquizquestionid' => $this->studentquizquestions[0]->id]));
        $this->assertEquals(0, $DB->count_records('studentquiz_question',
            ['id' => $this->studentquizquestions[0]->id]));

        $this->assertEquals(1, $DB->count_records('question', ['id' => $q2v2->id]));
        $this->assertEquals(1, $DB->count_records('question', ['id' => $q2v3->id]));
    }

    /**
     * Run the correct cron setup .
     *
     */
    private function cron_setup_user(): void {
        if (class_exists('\core\cron')) {
            \core\cron::setup_user();
        } else {
            cron_setup_user();
        }
    }
}
