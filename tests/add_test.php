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

use mod_studentquiz\commentarea\container;

/**
 * Unit tests for studentquiz add new instance.
 *
 * @package    mod_studentquiz
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_test extends \advanced_testcase {

    /** @var \stdClass - Course. */
    protected $course;

    /**
     * Setup for unit test.
     */
    protected function setUp(): void {
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
    }

    /**
     * Test add studentquiz with deletion period = 0.
     * @coversNothing
     */
    public function test_add_studentquiz_with_zero_period() {
        $studentquiz = $this->create_studentquiz(0);
        $this->assertEquals(0, $studentquiz->commentdeletionperiod);
    }

    /**
     * Test add studentquiz with normal deletion period.
     * @coversNothing
     *
     * @dataProvider period_provider
     * @param int $period - Deletion period number.
     */
    public function test_add_studentquiz_with_normal_period($period) {
        $studentquiz = $this->create_studentquiz($period);
        $this->assertEquals($period, $studentquiz->commentdeletionperiod);
    }

    /**
     * Test add studentquiz with expected completion time.
     *
     * @covers ::studentquiz_process_event
     */
    public function test_add_studentquiz_with_expected_completion() {
        global $DB;
        $futuretime = strtotime('+1 day');
        $studentquiz = $this->create_studentquiz(0, $futuretime);
        $studentquizvevent = $DB->get_record('event', ['courseid' => $this->course->id,
            'modulename' => 'studentquiz', 'instance' => $studentquiz->id]);

        $this->assertEquals($studentquizvevent->timestart, $futuretime);
        $this->assertEquals($studentquizvevent->timesort, $futuretime);
    }

    /**
     * Generate 5 random periods.
     *
     * @see test_add_studentquiz_with_normal_period()
     * @return array
     */
    public function period_provider() {
        $periods = range(container::DELETION_PERIOD_MIN, container::DELETION_PERIOD_MAX);
        shuffle($periods);
        $periods = array_slice($periods, 0, 5);
        $data = [];
        foreach ($periods as $period) {
            $data[] = [$period];
        }
        return $data;
    }

    /**
     * Create new studentquiz.
     *
     * @param int $period
     * @param int|null $completionexpected Unix time for completion expected. E.g: 1667805330.
     * @return \stdClass
     */
    private function create_studentquiz(int $period, ?int $completionexpected = null): \stdClass {
        $course = $this->course;
        return $this->getDataGenerator()->create_module('studentquiz', [
            'course' => $course->id,
            'commentdeletionperiod' => $period,
            'completion' => 2,
            'completionview' => 1,
            'completionexpected' => $completionexpected,
        ]);
    }
}
