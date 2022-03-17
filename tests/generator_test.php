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

/**
 * Data generator test
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator_test extends \advanced_testcase {

    /**
     * @var \stdClass
     */
    protected $studentquizgenerator;

    /**
     * @var studentquiz_question
     */
    protected $studentquizquestion;

    /**
     * Setup test
     * @throws \coding_exception
     */
    protected function setUp(): void {
        $this->studentquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_studentquiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $this->getDataGenerator()->create_course();

        $activity = $this->getDataGenerator()->create_module('studentquiz', array(
                'course' => $course->id,
                'anonymrank' => true,
                'forcecommenting' => 1,
                'publishnewquestion' => 1
        ));
        $context = \context_module::instance($activity->cmid);

        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $context->id);

        $question = $questiongenerator->create_question('description', null, array('category' => $studentquiz->categoryid));
        $question = \question_bank::load_question($question->id);
        $this->studentquizquestion = studentquiz_question::get_studentquiz_question_from_question($question);

    }

    /**
     * Test create comment
     * @covers \mod_studentquiz\commentarea\container::create_comment
     * @throws \coding_exception
     */
    public function test_create_comment() {
        global $DB;

        $this->resetAfterTest();

        $count = $DB->count_records('studentquiz_comment');
        $user = $this->getDataGenerator()->create_user();

        $commentrecord = new \stdClass();
        $commentrecord->studentquizquestionid = $this->studentquizquestion->id;
        $commentrecord->userid = $user->id;

        $this->studentquizgenerator->create_comment($commentrecord);
        $this->assertEquals($count + 1, $DB->count_records('studentquiz_comment'));
    }

    /**
     * Test create rate
     * @coversNothing
     * @throws \coding_exception
     */
    public function test_create_rate() {
        global $DB;

        $this->resetAfterTest();
        $count = $DB->count_records('studentquiz_rate');

        $user = $this->getDataGenerator()->create_user();

        $raterecord = new \stdClass();
        $raterecord->rate = 5;
        $raterecord->studentquizquestionid = $this->studentquizquestion->id;
        $raterecord->userid = $user->id;

        $rec = $this->studentquizgenerator->create_comment($raterecord);
        $this->assertEquals($count + 1, $DB->count_records('studentquiz_comment'));
        $this->assertEquals(5, $rec->rate);
    }
}
