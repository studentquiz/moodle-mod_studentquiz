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
     * @var mod_studentquiz_report the report created in setUp.
     */
    protected $report;

    /**
     * @var array the users created in setUp.
     */
    protected $users;

    /**
     * @var array the questions created in setUp.
     */
    protected $questions;

    /**
     * Setup test
     * @throws coding_exception
     */
    protected function setUp() {
        global $DB;
        $this->resetAfterTest();

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
        $q1 = $questiongenerator->create_question('truefalse', null, ['name' => 'TF1',
            'category' => $this->studentquiz->categoryid]);
        $q2 = $questiongenerator->create_question('truefalse', null, ['name' => 'TF2',
            'category' => $this->studentquiz->categoryid]);
        $q3 = $questiongenerator->create_question('truefalse', null, ['name' => 'TF3',
            'category' => $this->studentquiz->categoryid]);
        $q4 = $questiongenerator->create_question('truefalse', null, ['name' => 'TF4',
            'category' => $this->studentquiz->categoryid]);
        $this->questions = [$q1, $q2, $q3, $q4];

        // Create an attempt by the first user. First question right. Second wrong.
        $this->setUser($users[0]);
        // Third started but not answered. Forth no attempt.
        $questionids = [$q1->id, $q2->id, $q3->id];
        $attempt = mod_studentquiz_generate_attempt($questionids, $this->studentquiz, $users[0]->id);
        $questionids = explode(',', $attempt->ids);

        $questionusage = question_engine::load_questions_usage_by_activity($attempt->questionusageid);
        $post = $questionusage->prepare_simulated_post_data([1 => ['answer' => 1, '-submit' => 1]]);
        $questionusage->process_all_actions(null, $post);

        mod_studentquiz_add_question_to_attempt($questionusage, $this->studentquiz, $questionids, 1);
        $post = $questionusage->prepare_simulated_post_data([2 => ['answer' => 0, '-submit' => 1]]);
        $questionusage->process_all_actions(null, $post);

        mod_studentquiz_add_question_to_attempt($questionusage, $this->studentquiz, $questionids, 2);

        question_engine::save_questions_usage_by_activity($questionusage);
        $this->setAdminUser();
    }

    public function test_mod_studentquiz_get_user_ranking_table() {
        $this->assertTrue(true);
    }

    public function test_mod_studentquiz_community_stats() {
        $this->assertTrue(true);
    }

    public function test_mod_studentquiz_user_stats() {
        $userstats = mod_studentquiz_user_stats($this->cm->id, $this->report->get_quantifiers(), $this->users[0]->id);
        $this->assertEquals(0, $userstats->questions_created);
    }

    public function test_mod_studentquiz_get_studentquiz_progress_from_question_attempts_steps() {
        $studentquizprogresses = mod_studentquiz_get_studentquiz_progress_from_question_attempts_steps(
                $this->studentquiz->id, $this->context);

        // Only data for the two completed questions.
        $this->assertCount(2, $studentquizprogresses);

        $progressbyquestionid = [];
        foreach ($studentquizprogresses as $progress) {
            $progressbyquestionid[$progress->questionid] = $progress;
        }

        // Check stats on Q1.
        $q1stats = $progressbyquestionid[$this->questions[0]->id];
        $this->assertEquals($this->questions[0]->id, $q1stats->questionid);
        $this->assertEquals($this->users[0]->id, $q1stats->userid);
        $this->assertEquals(1, $q1stats->lastanswercorrect);
        $this->assertEquals(1, $q1stats->attempts);
        $this->assertEquals(1, $q1stats->correctattempts);

        // Check stats on Q2.
        $q2stats = $progressbyquestionid[$this->questions[1]->id];
        $this->assertEquals($this->questions[1]->id, $q2stats->questionid);
        $this->assertEquals($this->users[0]->id, $q2stats->userid);
        $this->assertEquals(0, $q2stats->lastanswercorrect);
        $this->assertEquals(1, $q2stats->attempts);
        $this->assertEquals(0, $q2stats->correctattempts);
    }

    /**
     * Debug output db contents
     *
     * @param array $user
     * @return array
     */
    private function debugdb($user=array()) {
        global $DB;
        $tables = array('studentquiz', 'studentquiz_attempt', 'question_usages', 'question_attempts',
            'question_attempt_steps', 'question_attempt_step_data');
        $result = array();
        $result['user'] = $this->users[0];
        foreach ($tables as $table) {
            $result[$table] = $DB->get_records($table);
        }
    }
}
