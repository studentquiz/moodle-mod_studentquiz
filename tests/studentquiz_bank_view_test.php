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

/**
 * Unit tests for (some of) mod/studentquiz/viewlib.php.
 *
 * @package    mod_studentquiz
 * @category   phpunit
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_bank_view_test extends advanced_testcase {
    private $questionbank;
    private $cmid;

    protected function setUp() {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        $studentquiz = $this->getDataGenerator()->create_module('studentquiz', array('course' => $course->id),  array('anonymrank' => true));
        $cm = get_coursemodule_from_id('studentquiz', $studentquiz->cmid);
        $this->questionbank = new \mod_studentquiz\question\bank\studentquiz_bank_view(
            new question_edit_contexts(context_module::instance($cm->id))
            , new moodle_url('/mod/studentquiz/view.php' , array('cmid' => $cm->id))
            , $course
            , $cm);
        $this->cmid = $cm->id;
    }

    public function test_questionbank_empty_filter() {
        global $DB;

        $this->resetAfterTest(true);

        $ctx = new stdClass();
        $ctx->contextid = $this->cmid;

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category($ctx);
        $question = $questiongenerator->create_question('description', null, array('category' => $cat->id));


        $qpage = 0;
        $qperpage = 20;
        $cat =  "$cat->id,$ctx->contextid";
        $recurse = 1;
        $showhidden = 0;
        $qbshowtext = 0;

        $this->questionbank->display('questions', $qpage, $qperpage,
            $cat, $recurse, $showhidden,
            $qbshowtext);

        $this->assertEquals(1, count($this->questionbank->get_questions()));
    }

}