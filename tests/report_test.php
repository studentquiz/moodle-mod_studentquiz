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
 * Unit tests for mod/studentquiz/reportstat.php.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct Access is forbidden!');

global $CFG;
require_once($CFG->dirroot . '/mod/studentquiz/locallib.php');
require_once($CFG->dirroot . '/mod/studentquiz/viewlib.php');
require_once($CFG->dirroot . '/mod/studentquiz/reportlib.php');

/**
 * Unit tests for mod/studentquiz/reportstat.php.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_studentquiz_report_testcase extends advanced_testcase {

    /**
     * Setup test
     * @throws coding_exception
     */
    protected function setUp() {
        global $DB;

        // Setup activity.
        $course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $activity = $this->getDataGenerator()->create_module('studentquiz', array(
            'course' => $course->id,
            'anonymrank' => true,
            'questionquantifier' => 10,
            'approvedquantifier' => 5,
            'ratequantifier' => 3,
            'correctanswerquantifier' => 2,
            'incorrectanswerquantifier' => -1,
        ));
        $this->context = context_module::instance($activity->cmid);
        $this->studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $this->context->id);
        $this->cm = get_coursemodule_from_id('studentquiz', $activity->cmid);
        $this->report = new mod_studentquiz_report($activity->cmid);

        // Create users.
        $usernames = array('Peter', 'Lisa', 'Sandra', 'Tobias', 'Gabi', 'Sepp');
        $users = array();
        foreach ($usernames as $username) {
            $user = $this->getDataGenerator()->create_user(array('firstname' => $username));
            $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);
            $users[] = $user;
        }
        $this->users = $users;

        // Create questions in questionbank.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $layout = array(
            array('TF1', 'truefalse'),
            array('TF2', 'truefalse'),
            array('TF3', 'truefalse'),
        );
        $questionids = array();
        foreach ($layout as $item) {
            list($name, $qtype) = $item;
            $q = $questiongenerator->create_question($qtype, null, array(
                'name' => $name,
                'category' => $this->studentquiz->categoryid,
                // Set further properties like created by here.
            ));
            $questionids[] = $q->id;
        }

        // Load questions.
        $questions = array();
        foreach ($questionids as $questionid) {
            $questions[] = question_bank::load_question($questionid);
        }

        // Load studentquiz view to ensure questions are in DB now ?
        // TODO: Unable to do, as questionbank tries to redirect!
        // $studentquizview = new mod_studentquiz_view($course, $this->context, $this->cm, $this->studentquiz, $user->id);
        // var_dump($studentquizview->get_questionbank()->get_questions());

        // Load attempt data
        $attempt = mod_studentquiz_generate_attempt($questionids, $this->studentquiz, $users[0]->id);
        $questionusage = question_engine::load_questions_usage_by_activity($attempt->questionusageid); // THIS internally does also load_from_records!

        $questions[0]->start_attempt(new question_attempt_step(array('answer' => '0'), time(), $users[0]->id), 1);
        $questionusage->process_all_actions();
        // TODO $questions[0]->classify_response(array('answer' => '0')); ?
        $questions[1]->start_attempt(new question_attempt_step(), 1);
        // TODO $questions[1]->classify_response(array('answer' => '1'));  ?
        $questions[2]->start_attempt(new question_attempt_step(), 1);
        // TODO $questions[2]->classify_response(array()); // = no response ?
        /*
         *
         Attention! Here userid = userid with attempt -> speciality studentquiz, just in case...
         $records = new question_test_recordset(array(
            array('questionattemptid', 'contextid', 'questionusageid', 'slot', 'behaviour',         'timecreated', 'userid', 'name', 'value'),
            array($attempt->id, $this->context->id, $attempt->questionusageid, 1, STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR,
                $questions[0]->id, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 1, 0, 'todo',
                      null, 1256233700, $users[0]->id,       null, null),
            array($attempt->id, $this->context->id, $attempt->questionusageid, 1, STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR,
                $questions[0]->id, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 2, 1, 'complete',
                 null, 1256233705, $users[0]->id,   'answer',  '1'),
            array($attempt->id, $this->context->id, $attempt->questionusageid, 1, STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR,
                $questions[0]->id, 1, 2.0000000, 0.0000000, 1.0000000, 1, '', '', '', 1256233790, 3, 2, 'complete',
                  null, 1256233710, $users[0]->id,   'answer',  '0'),
            array($attempt->id, $this->context->id, $attempt->questionusageid, 1, STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR,
               $questions[0]->id, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 4, 3, 'complete',
                  null, 1256233715, $users[0]->id,   'answer',  '1'),
            array($attempt->id, $this->context->id, $attempt->questionusageid, 1, STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR,
                $questions[0]->id, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 5, 4, 'gradedright',
                1.0000000, 1256233720, $users[0]->id,  '-finish',  '1'),
            array($attempt->id, $this->context->id, $attempt->questionusageid, 1, STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR,
                $questions[0]->id, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 6, 5, 'mangrpartial',
                0.5000000, 1256233790, $users[0]->id, '-comment', 'Not good enough!'),
            array($attempt->id, $this->context->id, $attempt->questionusageid, 1, STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR,
                $questions[0]->id, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 6, 5, 'mangrpartial',
                0.5000000, 1256233790, $users[0]->id,    '-mark',  '1'),
            array($attempt->id, $this->context->id, $attempt->questionusageid, 1, STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR,
                $questions[0]->id, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 6, 5, 'mangrpartial',
                0.5000000, 1256233790, $users[0]->id, '-maxmark',  '2'),
        ));
        TODO: Save attempt to DB -> load mod_studentquiz_report to query results
        question_bank::start_unit_test();
        $qa = question_attempt::load_from_records($records, $attempt->id, new question_usage_null_observer(), STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR);
        question_bank::end_unit_test();
        */
        // Probably not needed, but was a try.
        // Exercise SUT - no exception yet.
        foreach ($questionusage->get_slots() as $slot) {
            $questionusage->finish_question($slot);
        }

        // save to db, was the hope
        question_engine::save_questions_usage_by_activity($questionusage);

        /*
         * Load attempt data 2. I believe this is to test without the DB, not the way around.
        $records = new question_test_recordset(array(
            array('questionattemptid', 'contextid', 'questionusageid', 'slot',
                'behaviour', 'questionid', 'variant', 'maxmark', 'minfraction', 'maxfraction', 'flagged',
                'questionsummary', 'rightanswer', 'responsesummary', 'timemodified',
                'attemptstepid', 'sequencenumber', 'state', 'fraction',
                'timecreated', 'userid', 'name', 'value'),
            array(1, 123, 1, 1, 'deferredfeedback', -1, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 1, 0,
                'todo',              null, 1256233700, 1,       null, null),
            array(1, 123, 1, 1, 'deferredfeedback', -1, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 2, 1,
                'complete',          null, 1256233705, 1,   'answer',  '1'),
            array(1, 123, 1, 1, 'deferredfeedback', -1, 1, 2.0000000, 0.0000000, 1.0000000, 1, '', '', '', 1256233790, 3, 2,
                'complete',          null, 1256233710, 1,   'answer',  '0'),
            array(1, 123, 1, 1, 'deferredfeedback', -1, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 4, 3,
                'complete',          null, 1256233715, 1,   'answer',  '1'),
            array(1, 123, 1, 1, 'deferredfeedback', -1, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 5, 4,
                'gradedright',  1.0000000, 1256233720, 1,  '-finish',  '1'),
            array(1, 123, 1, 1, 'deferredfeedback', -1, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 6, 5,
                'mangrpartial', 0.5000000, 1256233790, 1, '-comment', 'Not good enough!'),
            array(1, 123, 1, 1, 'deferredfeedback', -1, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 6, 5,
                'mangrpartial', 0.5000000, 1256233790, 1,    '-mark',  '1'),
            array(1, 123, 1, 1, 'deferredfeedback', -1, 1, 2.0000000, 0.0000000, 1.0000000, 0, '', '', '', 1256233790, 6, 5,
                'mangrpartial', 0.5000000, 1256233790, 1, '-maxmark',  '2'),
        ));
        $question = test_question_maker::make_question('truefalse', 'true');
        $question->id = -1;

        question_bank::start_unit_test();
        question_bank::load_test_question_data($question);
        $qa = question_attempt::load_from_records($records, 1, new question_usage_null_observer(), 'deferredfeedback');
        question_bank::end_unit_test();
        */
    }

    public function test_mod_studentquiz_get_user_ranking_table() {
        $this->assertTrue(true);
    }

    public function test_mod_studentquiz_community_stats() {
        $this->assertTrue(true);
    }

    public function test_mod_studentquiz_user_stats() {
        $userstats = mod_studentquiz_user_stats($this->cm->id, $this->report->get_quantifiers(), $this->users[0]->id, 0);
        $this->assertEquals(0, $userstats->questions_created);
    }

    public function tearDown() {
        parent::tearDown();
        $this->resetAfterTest();
    }

    private function debugdb($user=array()) {
        global $DB;
        $tables = array('studentquiz', 'studentquiz_attempt', 'question_usages', 'question_attempts', 'question_attempt_steps', 'question_attempt_step_data');
        $result = array();
        $result['user'] = $this->users[0];
        foreach ($tables as $table) {
            $result[$table] = $DB->get_records($table);
        }
    }

    /**
     * Convert an array of data destined for one question to the equivalent POST data.
     * @param array $data the data for the quetsion.
     * @return array the complete post data.
     */
    // From question\engine\tests\questionusage_autosave_test.php.
    // TODO fix some $this usage to correct one!
    // Usage:
    // Post data: $postdata = $this->response_data_to_post(array('answer' => 'obsolete response'));.
    // Post data: $postdata[$this->quba->get_field_prefix($this->slot) . ':sequencecheck'] = $this->get_question_attempt()->get_sequence_check_count() - 1;.
    // Question Usage: $this->quba->process_all_actions(null/*time()*/, $postdata);.
    protected function response_data_to_post($data) {
        $prefix = $this->quba->get_field_prefix($this->slot);
        $fulldata = array(
            'slots' => $this->slot,
            $prefix . ':sequencecheck' => $this->get_question_attempt()->get_sequence_check_count(),
        );
        foreach ($data as $name => $value) {
            $fulldata[$prefix . $name] = $value;
        }
        return $fulldata;
    }


}
/*
        // QUIZ

        $quizobj = quiz::create($quiz->id, $user1->id);

        // Start the attempt.
        $quba = question_engine::make_questions_usage_by_activity('mod_student', $studentquiz->get_context());
        //$quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $user1->id);

        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        $this->assertEquals('1,2,0', $attempt->layout);

        quiz_attempt_save_started($quizobj, $quba, $attempt);

        // Process some responses from the student.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertFalse($attemptobj->has_response_to_at_least_one_graded_question());

        $prefix1 = $quba->get_field_prefix(1);
        $prefix2 = $quba->get_field_prefix(2);

        $tosubmit = array(1 => array('answer' => 'frog'),
            2 => array('answer' => '3.14'));

        $attemptobj->process_submitted_actions($timenow, false, $tosubmit);

        // Finish the attempt.
        $attemptobj = quiz_attempt::create($attempt->id);
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());
        $attemptobj->process_finish($timenow, false);

        // Re-load quiz attempt data.
        $attemptobj = quiz_attempt::create($attempt->id);

        // Check that results are stored as expected.
        $this->assertEquals(1, $attemptobj->get_attempt_number());
        $this->assertEquals(2, $attemptobj->get_sum_marks());
        $this->assertEquals(true, $attemptobj->is_finished());
        $this->assertEquals($timenow, $attemptobj->get_submitted_date());
        $this->assertEquals($user1->id, $attemptobj->get_userid());
        $this->assertTrue($attemptobj->has_response_to_at_least_one_graded_question());

        // Check quiz grades.
        $grades = quiz_get_user_grades($quiz, $user1->id);
        $grade = array_shift($grades);
        $this->assertEquals(100.0, $grade->rawgrade);
*/