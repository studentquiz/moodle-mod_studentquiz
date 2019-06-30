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
 * Unit tests for (some of) mod/studentquiz/viewlib.php.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/studentquiz/classes/question/bank/studentquiz_bank_view.php');
require_once($CFG->dirroot . '/mod/studentquiz/reportlib.php');
require_once($CFG->dirroot . '/lib/questionlib.php');
require_once($CFG->dirroot . '/question/editlib.php');

/** @var string question name filter */
const QUESTION_NAME_FILTER = 'name';
/** @var string question name operation filter */
const QUESTION_NAME_OP_FILTER = 'name_op';
/** @var string tagname filter */
const QUESTION_TAGNAME_FILTER = 'tagname';
/** @var string tagname operation filter */
const QUESTION_TAGNAME_OP_FILTER = 'tagname_op';
/** @var string rate filter */
const QUESTION_RATE_FILTER = 'rate';
/** @var string rate operation filter */
const QUESTION_RATE_OP_FILTER = 'rate_op';
/** @var string difficultylevel filter */
const QUESTION_DIFFICULTYLEVEL_FILTER = 'difficultylevel';
/** @var string diffcultylevel operation filter */
const QUESTION_DIFFICULTYLEVEL_OP_FILTER = 'difficultylevel_op';
/** @var string firstname filter */
const QUESTION_FIRSTNAME_FILTER = 'firstname';
/** @var string firstname operation filter */
const QUESTION_FIRSTNAME_OP_FILTER = 'firstname_op';
/** @var string lastname filter */
const QUESTION_LASTNAME_FILTER = 'lastname';
/** @var string lastname operation filter */
const QUESTION_LASTNAME_OP_FILTER = 'lastname_op';
/** @var string question default name */
const QUESTION_DEFAULT_NAME = 'Question';

/**
 * Unit tests for (some of) mod/studentquiz/viewlib.php.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_bank_view_test extends advanced_testcase {
    /**
     * @var course module
     */
    private $cm;
    /**
     * @var course
     */
    private $course;
    /**
     * @var context
     */
    private $ctx;
    /**
     * @var category
     */
    private $cat;
    /**
     * @var studentquiz
     */
    private $studentquiz;
    /**
     * @var question generator
     */
    private $questiongenerator;
    /**
     * @var stundetquiz generator
     */
    private $studentquizgenerator;

    /**
     * @return \mod_studentquiz\question\bank\studentquiz_bank_view
     * @throws mod_studentquiz_view_exception
     * @throws moodle_exception
     */
    public function run_questionbank() {
        global $PAGE;
        $PAGE->set_url(new moodle_url('/mod/studentquiz/view.php', array('cmid' => $this->cm->id)));
        $PAGE->set_context($this->ctx);
        // Hard coded.
        $pagevars = array(
            'recurse' => true,
            'cat' => $this->cat->id . ',' . $this->ctx->id,
            'showall' => 0,
            'showallprinted' => 0,
        );

        $report = new mod_studentquiz_report($this->cm->id);
        $questionbank = new \mod_studentquiz\question\bank\studentquiz_bank_view(
            new question_edit_contexts(context_module::instance($this->cm->id))
            , new moodle_url('/mod/studentquiz/view.php', array('cmid' => $this->cm->id))
            , $this->course
            , $this->cm
            , $this->studentquiz
            , $pagevars, $report);
        return $questionbank;
    }

    /**
     * Setup testing scenario
     * One user, one studentquiz in one course.
     * @throws coding_exception
     */
    protected function setUp() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, $studentrole->id);

        $this->studentquiz = $this->getDataGenerator()->create_module('studentquiz',
            array('course' => $this->course->id),  array('anonymrank' => true));
        $this->cm = get_coursemodule_from_instance('studentquiz', $this->studentquiz->id);

        $this->questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->ctx = context_module::instance($this->cm->id);

        // Retrieve created category by context.
        $this->cat = question_get_default_category($this->ctx->id);

        $this->studentquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_studentquiz');

        $this->create_random_questions(20, $user->id);
    }

    /**
     * Create random questions
     * @param int $count
     * @param int $userid
     */
    protected function create_random_questions($count, $userid) {
        global $DB;
        for ($i = 0; $i < $count; ++$i) {
            $question = $this->questiongenerator->create_question('description', null, array('category' => $this->cat->id));
            $question->name = QUESTION_DEFAULT_NAME . ' ' . $i;
            $DB->update_record('question', $question);

            $this->create_comment($question, $userid);
            $this->create_rate($question, $userid);
        }
    }

    /**
     * Create question rate
     * @param stdClass $question
     * @param int $userid
     */
    protected function create_rate($question, $userid) {
        $raterecord = new stdClass();
        $raterecord->rate = 5;
        $raterecord->questionid = $question->id;
        $raterecord->userid = $userid;
    }

    /**
     * Create question comment
     * @param stdClass $question
     * @param int $userid
     */
    protected function create_comment($question, $userid) {
        $commentrecord = new stdClass();
        $commentrecord->questionid = $question->id;
        $commentrecord->userid = $userid;

        $this->studentquizgenerator->create_comment($commentrecord);
    }

    /**
     * Test questionbank empty filter
     */
    public function test_questionbank_empty_filter() {
        $this->resetAfterTest(true);
        $questionbank = $this->run_questionbank();

        $this->displayqb($questionbank);
        $this->assertEquals(20, count($questionbank->get_questions()));
    }

    /**
     * Test questionbank filter question name
     */
    public function test_questionbank_filter_question_name() {
        $this->resetAfterTest(true);

        $this->set_filter(QUESTION_NAME_FILTER, 'Question 1');
        $this->set_filter(QUESTION_NAME_OP_FILTER, '0');

        // Hard coded.
        $questionbank = $this->run_questionbank();

        $this->displayqb($questionbank);
        $this->assertEquals(11, count($questionbank->get_questions()));
    }

    /**
     * Display question bank
     * @param mod_studentquiz\question\bank\studentquiz_bank_view $questionbank
     * @param int $qpage
     * @param int $qperpage
     * @param int $recurse
     * @param int $showhidden
     * @param int $qbshowtext
     * @return string
     */
    protected function displayqb($questionbank, $qpage = 0, $qperpage = 20, $recurse = 1, $showhidden = 0, $qbshowtext = 0) {
        $cat = $this->cat->id . "," . $this->ctx->id;
        $questionbank->display('questions', $qpage, $qperpage,
            $cat, $recurse, $showhidden,
            $qbshowtext);
    }

    /**
     * Set questionbank filter
     * @param string $which
     * @param mixed $value
     */
    protected function set_filter($which, $value) {
        // Usually we set POST for a form, but since we have two forms merged and exchanging their values
        // using GET params, we can't use that.
        $_GET[$which] = $value;
        $_GET["submitbutton"] = "Filter";
        // session key is required, otherwise it won't try to load and filter POSTed data
        $_GET["_qf__mod_studentquiz_question_bank_filter_form"] = "1";
        $_GET["sesskey"] = sesskey();
    }
}