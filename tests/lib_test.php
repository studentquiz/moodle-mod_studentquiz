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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/studentquiz/lib.php');
/**
 * Unit tests for (some of) lib.php
 *
 * @package mod_studentquiz
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {

    /**
     * @var \stdClass
     */
    protected $student;

    /**
     * @var \stdClass
     */
    protected $studentquiz;

    /**
     * @var \stdClass
     */
    protected $course;
    /**
     * @var \calendar_event
     */
    protected $event;

    public function setUp(): void {

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create the activity.
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->studentquiz = $this->getDataGenerator()->create_module('studentquiz', [
            'course' => $this->course->id, 'completion' => 1,
            'completionexpected' => time() + DAYSECS
        ]);
        // Enrol a student in the course.
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        // Create a calendar event.
        $this->event = $this->create_action_event($this->course->id, $this->studentquiz->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);
    }

    /**
     * Test core_calendar hidden when studentquiz hides.
     *
     * @covers ::mod_studentquiz_core_calendar_provide_event_action
     */
    public function test_studentquiz_core_calendar_provide_event_action_in_hidden_section(): void {
        $course = $this->course;
        $student = $this->student;
        $event = $this->event;

        // Set sections 0 as hidden.
        set_section_visible($course->id, 0, 0);

        // Now, log out.
        $this->setUser($student->id);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event for the student.
        $actionevent = mod_studentquiz_core_calendar_provide_event_action($event, $factory, $student->id);

        // Confirm the event is not shown at all.
        $this->assertNull($actionevent);
    }

    /**
     * Test core_calendar provides the events for user.
     *
     * @covers ::mod_studentquiz_core_calendar_provide_event_action
     */
    public function test_studentquiz_core_calendar_provide_event_action_for_user(): void {
        $student = $this->student;
        $event = $this->event;

        // Now, log out.
        $this->setUser($student);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event for the student.
        $actionevent = mod_studentquiz_core_calendar_provide_event_action($event, $factory, $student->id);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test core_calendar provides the event for studentquiz.
     *
     * @covers ::mod_studentquiz_core_calendar_provide_event_action
     */
    public function test_studentquiz_core_calendar_provide_event_action(): void {
        $event = $this->event;

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_studentquiz_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test core_calendar provides the events for non-user.
     *
     * @covers ::mod_studentquiz_core_calendar_provide_event_action
     */
    public function test_studentquiz_core_calendar_provide_event_action_as_non_user(): void {
        global $CFG;
        $event = $this->event;

        // Log out the user and set force login to true.
        \core\session\manager::init_empty_session();
        $CFG->forcelogin = true;

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_studentquiz_core_calendar_provide_event_action($event, $factory);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Test core_calendar provides the action events completed.
     *
     * @covers ::mod_studentquiz_core_calendar_provide_event_action
     */
    public function test_studentquiz_core_calendar_provide_event_action_already_completed(): void {
        global $CFG;
        $CFG->enablecompletion = 1;

        $course = $this->course;
        $event = $this->event;
        $studentquiz = $this->studentquiz;
        // Get some additional data.
        $cm = get_coursemodule_from_instance('studentquiz', $studentquiz->id, $course->id);
        // Mark the activity as completed.
        $completion = new \completion_info($course);
        $completion->update_state($cm, COMPLETION_COMPLETE, 0, true);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_studentquiz_core_calendar_provide_event_action($event, $factory);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid The course id.
     * @param int $instanceid The instance id.
     * @param string $eventtype The event type.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype): \calendar_event {
        $event = new \stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = 'studentquiz';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return \calendar_event::create($event);
    }
}
