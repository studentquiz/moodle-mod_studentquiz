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
 * Data generator test
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Data generator test
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_generator_testcase extends advanced_testcase {

    /**
     * Test create comment
     * @throws coding_exception
     */
    public function test_create_comment() {
        global $DB;

        $this->resetAfterTest();
        $studentquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_studentquiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('description', null, array('category' => $cat->id));

        $count = $DB->count_records('studentquiz_comment');
        $user = $this->getDataGenerator()->create_user();

        $commentrecord = new stdClass();
        $commentrecord->questionid = $question->id;
        $commentrecord->userid = $user->id;

        $studentquizgenerator->create_comment($commentrecord);
        $this->assertEquals($count + 1, $DB->count_records('studentquiz_comment'));
    }

    /**
     * Test create rate
     * @throws coding_exception
     */
    public function test_create_rate() {
        global $DB;

        $this->resetAfterTest();
        $studentquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_studentquiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('description', null, array('category' => $cat->id));

        $count = $DB->count_records('studentquiz_rate');

        $user = $this->getDataGenerator()->create_user();

        $raterecord = new stdClass();
        $raterecord->rate = 5;
        $raterecord->questionid = $question->id;
        $raterecord->userid = $user->id;

        $rec = $studentquizgenerator->create_comment($raterecord);
        $this->assertEquals($count + 1, $DB->count_records('studentquiz_comment'));
        $this->assertEquals(5, $rec->rate);
    }
}
