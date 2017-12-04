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

/**
 * Unit tests for mod/studentquiz/reportstat.php.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_studentquiz_statistic_testcase extends advanced_testcase {
    /**
     * @var studentquiz_view
     */
    private $report;
    private $studentquiz;
    private $cm;

    /**
     * Setup test
     * @throws coding_exception
     */
    protected function setUp() {
        global $DB;

        // Setup activity
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        $activity = $this->getDataGenerator()->create_module('studentquiz'
            , array('course' => $course->id),  array('anonymrank' => true));
        $this->context = context_module::instance($activity->cmid);
        $this->studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $this->context->id)
        $this->cm = get_coursemodule_from_id('studentquiz', $activity->cmid);

        // Create questions in questionbank
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $layout = array(
            array('TF1', 'truefalse'),
            array('TF2', 'truefalse'),
            array('TF3', 'truefalse'),
        );
        $questions = array();
        foreach($layout as $item) {
            list($name, $qtype) = $item;
            $questiondata = $questiongenerator->create_question($qtype, null,
                array('name' => $name, 'category' => $studentquiz->categoryid));
            $questions[$slot] = question_bank::make_question($questiondata);
        }


        /// HIER


        // Create attempts
        foreach ($questions as $slot => $question) {
            $newslot = $quba->add_question($question, $maxmark[$slot]);
            if ($newslot != $slot) {
                throw new coding_exception('Slot numbers have got confused.');
            }
        }

        /**
         * STRUCTURE
         */


        $headings = array();
        $slot = 1;
        $lastpage = 0;
        foreach ($layout as $item) {
            if (is_string($item)) {
                if (isset($headings[$lastpage + 1])) {
                    throw new coding_exception('Sections cannot be empty.');
                }
                $headings[$lastpage + 1] = $item;

            } else {
                list($name, $page, $qtype) = $item;
                if ($page < 1 || !($page == $lastpage + 1 ||
                        (!isset($headings[$lastpage + 1]) && $page == $lastpage))) {
                    throw new coding_exception('Page numbers wrong.');
                }
                $q = $questiongenerator->create_question($qtype, null,
                    array('name' => $name, 'category' => $cat->id));

                quiz_add_quiz_question($q->id, $quiz, $page);
                $lastpage = $page;
            }
        }

        $quizobj = new quiz($quiz, $cm, $course);
        $structure = \mod_quiz\structure::create_for_quiz($quizobj);
        if (isset($headings[1])) {
            list($heading, $shuffle) = $this->parse_section_name($headings[1]);
            $sections = $structure->get_sections();
            $firstsection = reset($sections);
            $structure->set_section_heading($firstsection->id, $heading);
            $structure->set_section_shuffle($firstsection->id, $shuffle);
            unset($headings[1]);
        }

        foreach ($headings as $startpage => $heading) {
            list($heading, $shuffle) = $this->parse_section_name($heading);
            $id = $structure->add_section_heading($startpage, $heading);
            $structure->set_section_shuffle($id, $shuffle);
        }

        return $quizobj;

        /**
         * QUIZ
         */
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

    }

    public function test_mod_studentquiz_get_user_ranking_table() {

    }

    public function test_mod_studentquiz_community_stats() {

    }

    public function test_mod_studentquiz_user_stats() {

    }

    public function tearDown() {
        parent::tearDown();
        $this->resetAfterTest();
    }
}
