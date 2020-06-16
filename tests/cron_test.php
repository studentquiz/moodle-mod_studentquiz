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
 * Cron test.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Cron test.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_cron_testcase extends advanced_testcase {

    /** @var stdClass */
    protected $course;

    /** @var stdClass */
    protected $student1;

    /** @var stdClass */
    protected $student2;

    /** @var stdClass */
    protected $teacher;

    /** @var stdClass */
    protected $studentquizdata;

    /** @var int */
    protected $cmid;

    /** @var stdClass */
    protected $studentquiz;

    /** @var array */
    protected $questions;

    protected function setUp() {
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
                'anonymrank' => false,
                'questionquantifier' => 10,
                'approvedquantifier' => 5,
                'ratequantifier' => 3,
                'correctanswerquantifier' => 2,
                'incorrectanswerquantifier' => -1,
        ];

        $this->cmid = $generator->create_module('studentquiz', $this->studentquizdata)->cmid;
        $this->studentquiz = mod_studentquiz_load_studentquiz($this->cmid, context_module::instance($this->cmid)->id);

        // Prepare question.
        $this->setUser($this->student1);
        $this->setUser($this->student2);
        $this->questions[0] = $questiongenerator->create_question('truefalse', null,
                ['name' => 'Student 1 Question', 'category' => $this->studentquiz->categoryid]);
        $this->questions[1] = $questiongenerator->create_question('truefalse', null,
                ['name' => 'Student 2 Question', 'category' => $this->studentquiz->categoryid]);
        question_bank::load_question($this->questions[0]->id);
        question_bank::load_question($this->questions[1]->id);
        $DB->insert_record('studentquiz_question', (object) ['questionid' => $this->questions[0]->id, 'state' => 0]);
        $DB->insert_record('studentquiz_question', (object) ['questionid' => $this->questions[1]->id, 'state' => 1]);
    }

    /**
     * Test send_no_digest_notification_task
     */
    public function test_send_no_digest_notification_task() {
        global $DB;
        $question = $DB->get_record('question', ['id' => $this->questions[0]->id],
                'id, name, timemodified, createdby, modifiedby');
        $notifydata = mod_studentquiz_prepare_notify_data($question, $this->student1, get_admin(), $this->course,
                get_coursemodule_from_id('studentquiz', $this->cmid));
        $customdata = [
                'eventname' => 'questionchanged',
                'courseid' => $this->course->id,
                'submitter' => get_admin(),
                'recipient' => $this->student1,
                'messagedata' => $notifydata,
                'questionurl' => $notifydata->questionurl,
                'questionname' => $notifydata->questionname,
        ];

        // Execute the cron.
        ob_start();
        cron_setup_user();
        $cron = new \mod_studentquiz\task\send_no_digest_notification_task();
        $cron->set_custom_data($customdata);
        $cron->set_component('mod_studentquiz');
        $cron->execute();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertContains('Sending notification for StudentQuiz for question ' .
                $question->name . ' to ' .
                $notifydata->recepientname, $output);

        $question = $DB->get_record('question', ['id' => $this->questions[1]->id],
                'id, name, timemodified, createdby, modifiedby');
        $notifydata = mod_studentquiz_prepare_notify_data($question, $this->student2, get_admin(), $this->course,
                get_coursemodule_from_id('studentquiz', $this->cmid));
        $customdata = [
                'eventname' => 'questionchanged',
                'courseid' => $this->course->id,
                'submitter' => get_admin(),
                'recipient' => $this->student2,
                'messagedata' => $notifydata,
                'questionurl' => $notifydata->questionurl,
                'questionname' => $notifydata->questionname,
        ];

        // Execute the cron.
        ob_start();
        cron_setup_user();
        $cron = new \mod_studentquiz\task\send_no_digest_notification_task();
        $cron->set_custom_data($customdata);
        $cron->set_component('mod_studentquiz');
        $cron->execute();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertContains('Sending notification for StudentQuiz for question ' .
                $question->name . ' to ' .
                $notifydata->recepientname, $output);
    }

    /**
     * Test send_no_digest_notification_task
     */
    public function test_send_digest_notification_task() {
        global $DB;
        date_default_timezone_set('UTC');

        $question = $DB->get_record('question', ['id' => $this->questions[0]->id],
                'id, name, timemodified, createdby, modifiedby');
        $notifydata = mod_studentquiz_prepare_notify_data($question, $this->student1, get_admin(), $this->course,
                get_coursemodule_from_id('studentquiz', $this->cmid));

        $customdata = [
                'eventname' => 'questionchanged',
                'courseid' => $this->course->id,
                'submitter' => get_admin(),
                'recipient' => $this->student2,
                'messagedata' => $notifydata,
                'questionurl' => $notifydata->questionurl,
                'questionname' => $notifydata->questionname,
        ];

        $notificationqueue = new stdClass();
        $notificationqueue->studentquizid = $notifydata->moduleid;
        $notificationqueue->content = serialize($customdata);
        $notificationqueue->recipientid = $this->student2->id;
        $notificationqueue->timetosend = strtotime('-1 day', strtotime(date('Y-m-d')));
        $DB->insert_record('studentquiz_notification', $notificationqueue);

        // Execute the cron.
        ob_start();
        cron_setup_user();
        $cron = new \mod_studentquiz\task\send_digest_notification_task();
        $cron->set_component('mod_studentquiz');
        $cron->execute();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertContains('Sending digest notification for StudentQuiz', $output);
        $this->assertContains('Sent 1 messages!', $output);
    }
}
