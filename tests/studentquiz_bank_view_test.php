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
 * @category   phpunit
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/studentquiz/classes/question/bank/studentquiz_bank_view.php');
require_once($CFG->dirroot . '/lib/questionlib.php');
require_once($CFG->dirroot . '/question/editlib.php');

const QUESTION_NAME_FILTER = 'name';
const QUESTION_NAME_OP_FILTER = 'name_op';
const QUESTION_TAGNAME_FILTER = 'tagname';
const QUESTION_TAGNAME_OP_FILTER = 'tagname_op';
const QUESTION_VOTE_FILTER = 'vote';
const QUESTION_VOTE_OP_FILTER = 'vote_op';
const QUESTION_DIFFICULTYLEVEL_FILTER = 'difficultylevel';
const QUESTION_DIFFICULTYLEVEL_OP_FILTER = 'difficultylevel_op';
const QUESTION_FIRSTNAME_FILTER = 'firstname';
const QUESTION_FIRSTNAME_OP_FILTER = 'firstname_op';
const QUESTION_LASTNAME_FILTER = 'lastname';
const QUESTION_LASTNAME_OP_FILTER = 'lastname_op';
const QUESTION_DEFAUT_NAME = 'Question';

/**
 * Unit tests for (some of) mod/studentquiz/viewlib.php.
 *
 * @package    mod_studentquiz
 * @category   phpunit
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_bank_view_test extends advanced_testcase {
    private $cm;
    private $course;
    private $ctx;
    private $cat;
    private $questiongenerator;
    private $studentquizgenerator;

    protected function setUp() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $this->course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, $studentrole->id);

        $studentquiz = $this->getDataGenerator()->create_module('studentquiz', array('course' => $this->course->id),  array('anonymrank' => true));
        $this->cm = get_coursemodule_from_id('studentquiz', $studentquiz->cmid);

        $this->questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->ctx = context_module::instance($this->cm->id);
        $this->cat = $this->questiongenerator->create_question_category($this->ctx);
        $this->studentquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_studentquiz');

        $this->createRandomQuestions(20, $user->id);
    }

    protected function createRandomQuestions($count, $userid){
        global $DB;
        for($i = 0; $i < $count; ++$i) {
            $question = $this->questiongenerator->create_question('description', null, array('category' => $this->cat->id));
            $question->name = QUESTION_DEFAUT_NAME . ' ' . $i;
            $DB->update_record('question', $question);

            $this->create_comment($question, $userid);
            $this->create_vote($question, $userid);
        }
    }

    protected function create_vote($question, $userid) {
        $voterecord = new stdClass();
        $voterecord->vote = 5;
        $voterecord->questionid = $question->id;
        $voterecord->userid = $userid;
    }

    protected function create_comment($question, $userid) {
        $commentrecord = new stdClass();
        $commentrecord->questionid = $question->id;
        $commentrecord->userid = $userid;

        $this->studentquizgenerator->create_comment($commentrecord);
    }


    public function test_questionbank_empty_filter() {
        global $DB;
        $this->resetAfterTest(true);

        $questionbank = new \mod_studentquiz\question\bank\studentquiz_bank_view(
            new question_edit_contexts(context_module::instance($this->cm->id))
            , new moodle_url('/mod/studentquiz/view.php' , array('cmid' => $this->cm->id))
            , $this->course
            , $this->cm);

        $this->displayQb($questionbank);
        $this->assertEquals(20, count($questionbank->get_questions()));
    }

    public function test_questionbank_filter_question_name() {
        global $DB;
        $this->resetAfterTest(true);

        $this->setFilter(QUESTION_NAME_FILTER, 'Question 1');
        $this->setFilter(QUESTION_NAME_OP_FILTER, '0');

        $questionbank = new \mod_studentquiz\question\bank\studentquiz_bank_view(
            new question_edit_contexts(context_module::instance($this->cm->id))
            , new moodle_url('/mod/studentquiz/view.php' , array('cmid' => $this->cm->id))
            , $this->course
            , $this->cm);

        $this->displayQb($questionbank);
        $this->assertEquals(11, count($questionbank->get_questions()));
    }


    public function test_questionbank_filter_question_vote() {
        global $DB;
        $this->resetAfterTest(true);

        //$this->setFilter(QUESTION_VOTE_FILTER, '4.9');
        //$this->setFilter(QUESTION_VOTE_OP_FILTER, '0');

        $questionbank = new \mod_studentquiz\question\bank\studentquiz_bank_view(
            new question_edit_contexts(context_module::instance($this->cm->id))
            , new moodle_url('/mod/studentquiz/view.php' , array('cmid' => $this->cm->id))
            , $this->course
            , $this->cm);

        $this->displayQb($questionbank);
        $this->assertEquals(20, count($questionbank->get_questions()));
    }

    protected function displayQb($questionbank, $qpage = 0, $qperpage = 20, $recurse = 1, $showhidden = 0, $qbshowtext = 0){
        $cat =  $this->cat->id ."," .$this->ctx->id;
        $output = '';
        ob_start();
        $questionbank->display('questions', $qpage, $qperpage,
            $cat, $recurse, $showhidden,
            $qbshowtext);
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    protected function setFilter($which, $value) {
        $_POST[$which] = $value;
        $_POST["submitbutton"] = "Filter";
    }
}