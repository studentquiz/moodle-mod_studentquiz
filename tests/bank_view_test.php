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
use mod_studentquiz\question\bank\studentquiz_bank_view;

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
class bank_view_test extends \advanced_testcase {
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
     * @var studentquiz generator
     */
    private $studentquizgenerator;

    /**
     * Run questionbank.
     *
     * @return studentquiz_bank_view
     * @throws \mod_studentquiz_view_exception
     * @throws \moodle_exception
     */
    public function run_questionbank() {
        global $PAGE;
        $PAGE->set_url(new \moodle_url('/mod/studentquiz/view.php', array('cmid' => $this->cm->id)));
        $PAGE->set_context($this->ctx);
        $PAGE->set_cm($this->cm);
        // Hard coded.
        $pagevars = array(
                'recurse' => true,
                'cat' => $this->cat->id . ',' . $this->ctx->id,
                'showall' => 0,
                'showallprinted' => 0,
        );

        $report = new \mod_studentquiz_report($this->cm->id);
        $questionbank = new studentquiz_bank_view(
                new \core_question\local\bank\question_edit_contexts(\context_module::instance($this->cm->id))
                , new \moodle_url('/mod/studentquiz/view.php', array('cmid' => $this->cm->id))
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
    protected function setUp(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, $studentrole->id);

        $this->studentquiz = $this->getDataGenerator()->create_module('studentquiz',
                array('course' => $this->course->id),  array('anonymrank' => true));
        $this->cm = get_coursemodule_from_instance('studentquiz', $this->studentquiz->id);

        $this->questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->ctx = \context_module::instance($this->cm->id);

        // Retrieve created category by context.
        $this->cat = question_get_default_category($this->ctx->id);

        $this->studentquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_studentquiz');

        $this->create_random_questions(20, $user->id);
    }

    /**
     * Test mod_studentquiz\question\bank\studentquiz_bank_view::wanted_columns.
     */
    public function test_wanted_columns() {
        $this->resetAfterTest(true);

        $questionbank = $this->run_questionbank();
        $reflector = new \ReflectionClass('mod_studentquiz\question\bank\studentquiz_bank_view');
        $method = $reflector->getMethod('wanted_columns');
        $method->setAccessible(true);
        $requiredcolumns = $method->invokeArgs($questionbank, [$questionbank]);

        $this->assertInstanceOf('core_question\bank\checkbox_column', $requiredcolumns[0]);
        $this->assertInstanceOf('core_question\bank\question_type_column', $requiredcolumns[1]);
        $this->assertInstanceOf('mod_studentquiz\bank\state_column', $requiredcolumns[2]);
        $this->assertInstanceOf('mod_studentquiz\bank\state_pin_column', $requiredcolumns[3]);
        $this->assertInstanceOf('mod_studentquiz\bank\question_name_column', $requiredcolumns[4]);
        $this->assertInstanceOf('mod_studentquiz\bank\sq_edit_action_column', $requiredcolumns[5]);
        $this->assertInstanceOf('mod_studentquiz\bank\preview_column', $requiredcolumns[6]);
        $this->assertInstanceOf('mod_studentquiz\bank\sq_delete_action_column', $requiredcolumns[7]);
        $this->assertInstanceOf('mod_studentquiz\bank\sq_hidden_action_column', $requiredcolumns[8]);
        $this->assertInstanceOf('mod_studentquiz\bank\sq_pin_action_column', $requiredcolumns[9]);
        $this->assertInstanceOf('mod_studentquiz\bank\sq_edit_menu_column', $requiredcolumns[10]);
        $this->assertInstanceOf('mod_studentquiz\bank\anonym_creator_name_column', $requiredcolumns[11]);
        $this->assertInstanceOf('mod_studentquiz\bank\tag_column', $requiredcolumns[12]);
        $this->assertInstanceOf('mod_studentquiz\bank\attempts_column', $requiredcolumns[13]);
        $this->assertInstanceOf('mod_studentquiz\bank\difficulty_level_column', $requiredcolumns[14]);
        $this->assertInstanceOf('mod_studentquiz\bank\rate_column', $requiredcolumns[15]);
        $this->assertInstanceOf('mod_studentquiz\bank\comment_column', $requiredcolumns[16]);
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
            $q1 = \question_bank::load_question($question->id);
            $sqq = studentquiz_question::get_studentquiz_question_from_question($q1, $this->studentquiz);

            $this->create_comment($sqq, $userid);
            $this->create_rate($sqq, $userid);
        }
    }

    /**
     * Create question rate
     * @param \studentquiz_question $sqq
     * @param int $userid
     */
    protected function create_rate($sqq, $userid) {
        $raterecord = new \stdClass();
        $raterecord->rate = 5;
        $raterecord->studentquizquestionid = $sqq->id;
        $raterecord->userid = $userid;
    }

    /**
     * Create question comment
     * @param studentquiz_question $sqq
     * @param int $userid
     */
    protected function create_comment($sqq, $userid) {
        $commentrecord = new \stdClass();
        $commentrecord->studentquizquestionid = $sqq->id;
        $commentrecord->userid = $userid;

        $this->studentquizgenerator->create_comment($commentrecord);
    }

    /**
     * Test questionbank empty filter
     * @covers \mod_studentquiz\question\bank\studentquiz_bank_view
     */
    public function test_questionbank_empty_filter() {
        $this->resetAfterTest(true);
        $questionbank = $this->run_questionbank();
        ob_start();
        $this->displayqb($questionbank);
        $html = ob_get_clean();

        $this->assertEquals(20, count($questionbank->get_questions()));
    }

    /**
     * Test questionbank filter question name
     * @covers \mod_studentquiz\question\bank\studentquiz_bank_view
     */
    public function test_questionbank_filter_question_name() {
        $this->resetAfterTest(true);

        $this->set_filter(QUESTION_NAME_FILTER, 'Question 1');
        $this->set_filter(QUESTION_NAME_OP_FILTER, '0');

        // Hard coded.
        $questionbank = $this->run_questionbank();
        ob_start();
        $this->displayqb($questionbank);
        $html = ob_get_clean();

        $this->assertEquals(11, count($questionbank->get_questions()));
    }

    /**
     * Display question bank
     * @param studentquiz_bank_view $questionbank
     * @param int $qpage
     * @param int $qperpage
     * @param int $recurse
     * @param int $showhidden
     * @param int $qbshowtext
     * @return html Output.
     */
    protected function displayqb($questionbank, $qpage = 0, $qperpage = 20, $recurse = 1, $showhidden = 0, $qbshowtext = 0) {
        $cat = $this->cat->id . "," . $this->ctx->id;
        $pagevars = [
                'qpage' => $qpage,
                'qperpage' => $qperpage,
                'recurse' => $recurse,
                'showhidden' => $showhidden,
                'qbshowtext' => $qbshowtext,
                'cat' => $cat,
        ];
        $questionbank->display($pagevars, 'questions');
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
        // Session key is required, otherwise it won't try to load and filter POSTed data.
        $_GET["_qf__mod_studentquiz_question_bank_filter_form"] = "1";
        $_GET["sesskey"] = sesskey();
    }
}
