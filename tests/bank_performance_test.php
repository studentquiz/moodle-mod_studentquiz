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
 * Unit tests for the question bank query performance.
 *
 * @package    mod_studentquiz
 * @copyright  2020 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/studentquiz/classes/question/bank/studentquiz_bank_view.php');
require_once($CFG->dirroot . '/mod/studentquiz/reportlib.php');
require_once($CFG->dirroot . '/lib/questionlib.php');
require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Unit tests for the question bank query performance.
 *
 * The question bank query to get all the questions is pretty large and modular. This test wants to provide
 * some visibility to the performance of that query for a set amount of entries in the database.
 *
 * To truly test the question bank query performance, there should be:
 * - Some instances of studentquizzes
 * - Many questions per instance
 * - A few comments per question
 * - Some attempts per question (TODO)
 * - Some ratings per question
 * - Some filter applied (TODO)
 *
 * Right now, there are no hard requirements other than "usuable" even for large moodle sites which heavily use
 * StudentQuiz. This test can only help comparing the query duration to previous versions and in relation to the
 * dataset size. As many performance tests, they rely heavily on the hardware and environment they're run on.
 *
 * @package    mod_studentquiz
 * @copyright  2020 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_bank_performance_test extends advanced_testcase {
    /**
     * @var question generator
     */
    private $questiongenerator;
    /**
     * @var studentquiz generator
     */
    private $studentquizgenerator;

    /**
     * Setup testing scenario
     * @throws coding_exception
     */
    protected function setUp() {
        $this->questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->studentquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_studentquiz');
    }

    /**
     * Run questionbank.
     *
     * @param array $result with the last studentquiz and its relations
     * @return \mod_studentquiz\question\bank\studentquiz_bank_view
     * @throws mod_studentquiz_view_exception
     * @throws moodle_exception
     */
    public function run_questionbank($result) {
        global $PAGE;
        $PAGE->set_url(new moodle_url('/mod/studentquiz/view.php', array('cmid' => $result['cm']->id)));
        $PAGE->set_context($result['ctx']);
        // Hard coded.
        $pagevars = array(
            'recurse' => true,
            'cat' => $result['cat']->id . ',' . $result['ctx']->id,
            'showall' => 1,
            'showallprinted' => 0,
        );

        $report = new mod_studentquiz_report($result['cm']->id);
        $questionbank = new \mod_studentquiz\question\bank\studentquiz_bank_view(
            new question_edit_contexts(context_module::instance($result['cm']->id)),
            new moodle_url('/mod/studentquiz/view.php', array('cmid' => $result['cm']->id)),
            $result['course'], $result['cm'], $result['studentquiz'], $pagevars, $report);
        return $questionbank;
    }

    /**
     * Create a specific amount of instances in a new course.
     *
     * @param int $count of instances to create
     * @return array $result with the last studentquiz and its relations
     */
    protected function create_instances_testset($count) {
        global $DB;
        $result = array();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();
        $result['course'] = $course;
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // In that course create $count studentquiz instances.
        for ($i = 0; $i < $count; $i++) {
            $studentquiz = $this->getDataGenerator()->create_module('studentquiz',
                array('course' => $course->id), array('anonymrank' => true)
            );
            $result['studentquiz'] = $studentquiz;

            // Get the question category for that studentquiz context.
            $cm = get_coursemodule_from_instance('studentquiz', $studentquiz->id);
            $result['cm'] = $cm;
            $ctx = context_module::instance($cm->id);
            $result['ctx'] = $ctx;
            $cat = question_get_default_category($ctx->id);
            $result['cat'] = $cat;

            // Each instance has 20 students which are enrolled to the course.
            $students = array();
            for ($s = 0; $s < 20; $s++) {
                $user = $this->getDataGenerator()->create_user();
                $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);
                $students[] = $user;
            }

            // Each student makes 20 questions.
            $questions = array();
            foreach ($students as $student) {
                for ($q = 1; $q <= 20; $q++) {
                    $questions[] = $this->questiongenerator->create_question('description', null,
                        array('name' => 'perf'.$q, 'category' => $cat->id, 'createdby' => $student->id)
                    );
                }
            }

            // The first 5 students contribute a comment to all questions.
            for ($s = 0; $s < 5; $s++) {
                foreach ($questions as $question) {
                    $this->studentquizgenerator->create_comment(array(
                        'questionid' => $question->id,
                        'userid' => $students[$s]->id,
                    ));
                }
            }

            // All students rate each question.
            foreach ($students as $student) {
                foreach ($questions as $question) {
                    $this->studentquizgenerator->create_rate(array(
                        'questionid' => $question->id,
                        'userid' => $students[$s]->id,
                        'rate' => 5,
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * Test questionbank empty filter
     */
    public function test_questionbank_empty_filter() {
        $this->resetAfterTest(true);

        // If we don't activate the lower two, it doesn't make sense to enable the first one either.
        // Adding 10 instances takes a few minutes, this should not be enabled in CI.
        // Adding 100 instances takes a huuuge amount of time (hours) until ready.
        // Uncomment these lines to run the tests with phpunit.
        // $this->create_instance_measured(1);
        // $this->create_instance_measured(10);
        // $this->create_instance_measured(100);
    }

    /**
     * Create a specific amount of instances in a new course and output how long it took to display the
     * question bank
     *
     * @param int $count of instances to create
     */
    protected function create_instance_measured($count) {
        fwrite(STDERR, "TEST_PERF: create $count instances\n");

        $result = $this->create_instances_testset($count);
        $questionbank = $this->run_questionbank($result);

        fwrite(STDERR, "TEST_PERF: initialization complete\n");

        $start = microtime(true);
        $this->displayqb($questionbank, $result);
        $end = microtime(true);
        $diff = $end - $start;
        fwrite(STDERR, "TEST_PERF: displaying question bank took $diff s\n");

        $this->assertEquals(400, count($questionbank->get_questions()));
    }

    /**
     * Display question bank
     * @param mod_studentquiz\question\bank\studentquiz_bank_view $questionbank
     * @param array $result with the last studentquiz and its relations
     * @param int $qpage
     * @param int $qperpage
     * @param int $recurse
     * @param int $showhidden
     * @param int $qbshowtext
     * @return string
     */
    protected function displayqb($questionbank, $result, $qpage = 0, $qperpage = 20, $recurse = 1, $showhidden = 0,
        $qbshowtext = 0) {
        $cat = $result['cat']->id . "," . $result['ctx']->id;
        $questionbank->display('questions', $qpage, $qperpage,
            $cat, $recurse, $showhidden,
            $qbshowtext);
    }
}