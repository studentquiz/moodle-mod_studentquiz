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

defined('MOODLE_INTERNAL') || die('Direct Access is forbidden!');

global $CFG;
require_once($CFG->dirroot . '/mod/studentquiz/viewlib.php');
require_once($CFG->dirroot . '/mod/studentquiz/reportlib.php');

/**
 * Unit tests for (some of) mod/studentquiz/viewlib.php.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class viewlib_test extends \advanced_testcase {
    /**
     * @var studentquiz_view
     */
    private $viewlib;

    /** @var \stdClass */
    private $cm;

    /**
     * Setup test
     */
    protected function setUp(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        // Login as this user.
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        $studentquiz = $this->getDataGenerator()->create_module('studentquiz'
            , array('course' => $course->id),  array('anonymrank' => true));

        $this->cm = get_coursemodule_from_id('studentquiz', $studentquiz->cmid);
        $context = \context_module::instance($this->cm->id);

        // Some internal moodle functions (e.g. question_edit_setup()) require the cmid to be found in $_xxx['cmid'].
        $_GET['cmid'] = $this->cm->id;

        // Satisfy codechecker: $course $cm $studentquiz $userid.
        $report = new \mod_studentquiz_report($this->cm->id);
        $this->viewlib = new \mod_studentquiz_view($course, $context, $this->cm,
            $studentquiz, $user->id, $report);
    }

    /**
     * Test has_question_ids
     * @coversNothing
     */
    public function test_has_question_ids() {
        $result = $this->viewlib->has_question_ids();
        self::assertFalse($result);
    }

    // Is testable with setUser in setup to mock login.
    // that is not allowed in testings and unmockable.
    /**
     * Nothing
     * @coversNothing
     */
    public function test_show_questionbank() {

    }

    /**
     * test_get_viewurl
     * @coversNothing
     */
    public function test_get_viewurl() {
        $viewurl = $this->viewlib->get_viewurl();
        $expectedurl = new \moodle_url('/mod/studentquiz/view.php', array('cmid' => $this->cm->id));
        $this->assertEquals('/moodle/mod/studentquiz/view.php', $viewurl->get_path());
        $this->assertTrue($expectedurl->compare($viewurl, URL_MATCH_EXACT));
    }

    /**
     * test_get_title
     * @coversNothing
     */
    public function test_get_title() {
        $result = $this->viewlib->get_title();
        self::assertEquals('StudentQuiz: studentquiz 0', $result);
    }

    /**
     * TODO Write tests for public functions
     * @coversNothing
     * generate_quiz_with_filtered_ids($ids)
     * generate_quiz_with_selected_ids($submitdata)
     * show_questionbank()
     * has_question_ids()
     * get_pageurl()
     * get_viewurl()
     * get_qb_pagevar()
     * get_urlview_data()
     * get_course()
     * has_printableerror()
     * get_errormessage()
     * get_coursemodule()
     * get_cm_id()
     * get_category_id()
     * get_context_id()
     * get_context()
     * get_title()
     * get_questionbank()
     */

    public function test_get_standard_quiz_setup() {

    }

    /**
     * Call protected/private method of a class.
     *
     * @param object $object     Instantiated object that we will run method on.
     * @param string $methodname Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invoke_method(&$object, $methodname, array $parameters = array()) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodname);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function tearDown(): void {
        parent::tearDown();
        $this->resetAfterTest();
    }
}
