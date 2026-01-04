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
 * Tests for StudentQuiz
 *
 * @package   mod_studentquiz
 * @category  test
 * @copyright 2026 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_studentquiz;

use advanced_testcase;
use cm_info;
use core\activity_dates;

/**
 * Class for unit testing mod_studentquiz\dates.
 *
 * @copyright 2026 Luca Bösch <luca.boesch@bfh.ch>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class dates_test extends advanced_testcase {
    /**
     * Data provider for get_dates_for_module().
     * @return array[]
     */
    public static function get_dates_for_module_provider(): array {
        $now = time();
        $before = $now - DAYSECS;
        $earlier = $before - DAYSECS;
        $after = $now + DAYSECS;
        $later = $after + DAYSECS;

        return [
            'without any dates' => [
                null, null, null, null, [],
            ],
            'only with submission opening time' => [
                $after, null, null, null, [
                    ['label' => 'Opens for question submission:', 'timestamp' => $after, 'dataid' => 'timeopensubmission'],
                ],
            ],
            'only with submission closing time' => [
                null, $after, null, null, [
                    ['label' => 'Closes for question submission:', 'timestamp' => $after, 'dataid' => 'timeclosesubmission'],
                ],
            ],
            'with both submission times' => [
                $after, $later, null, null, [
                    ['label' => 'Opens for question submission:', 'timestamp' => $after, 'dataid' => 'timeopensubmission'],
                    ['label' => 'Closes for question submission:', 'timestamp' => $later, 'dataid' => 'timeclosesubmission'],
                ],
            ],
            'between the submission dates' => [
                $before, $after, null, null, [
                    ['label' => 'Opened for question submission:', 'timestamp' => $before, 'dataid' => 'timeopensubmission'],
                    ['label' => 'Closes for question submission:', 'timestamp' => $after, 'dataid' => 'timeclosesubmission'],
                ],
            ],
            'submission dates are past' => [
                $earlier, $before, null, null, [
                    ['label' => 'Opened for question submission:', 'timestamp' => $earlier, 'dataid' => 'timeopensubmission'],
                    ['label' => 'Closed for question submission:', 'timestamp' => $before, 'dataid' => 'timeclosesubmission'],
                ],
            ],
            'only with answering opening time' => [
                null, null, $after, null, [
                    ['label' => 'Opens for question answering:', 'timestamp' => $after, 'dataid' => 'timeopenanswering'],
                ],
            ],
            'only with answering closing time' => [
                null, null, null, $after, [
                    ['label' => 'Closes for question answering:', 'timestamp' => $after, 'dataid' => 'timecloseanswering'],
                ],
            ],
            'with both answering times' => [
                null, null, $after, $later, [
                    ['label' => 'Opens for question answering:', 'timestamp' => $after, 'dataid' => 'timeopenanswering'],
                    ['label' => 'Closes for question answering:', 'timestamp' => $later, 'dataid' => 'timecloseanswering'],
                ],
            ],
            'between the answering dates' => [
                null, null, $before, $after, [
                    ['label' => 'Opened for question answering:', 'timestamp' => $before, 'dataid' => 'timeopenanswering'],
                    ['label' => 'Closes for question answering:', 'timestamp' => $after, 'dataid' => 'timecloseanswering'],
                ],
            ],
            'answering dates are past' => [
                null, null, $earlier, $before, [
                    ['label' => 'Opened for question answering:', 'timestamp' => $earlier, 'dataid' => 'timeopenanswering'],
                    ['label' => 'Closed for question answering:', 'timestamp' => $before, 'dataid' => 'timecloseanswering'],
                ],
            ],
        ];
    }

    /**
     * Test for get_dates_for_module().
     *
     * @dataProvider get_dates_for_module_provider
     * @covers \mod_studentquiz\local\dates::get_dates_for_module
     * @param int|null $opensubmissionfrom The "allow answers from" time in the feedback activity.
     * @param int|null $closesubmissionfrom The "allow answers to" time in the feedback activity.
     * @param int|null $openansweringfrom The "allow answers from" time in the feedback activity.
     * @param int|null $closeansweringfrom The "allow answers to" time in the feedback activity.
     * @param array $expected The expected value of calling get_dates_for_module()
     */
    public function test_get_dates_for_module(
        ?int $opensubmissionfrom,
        ?int $closesubmissionfrom,
        ?int $openansweringfrom,
        ?int $closeansweringfrom,
        array $expected
    ): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $data = ['course' => $course->id];
        if ($opensubmissionfrom) {
            $data['opensubmissionfrom'] = $opensubmissionfrom;
        }
        if ($closesubmissionfrom) {
            $data['closesubmissionfrom'] = $closesubmissionfrom;
        }
        if ($openansweringfrom) {
            $data['openansweringfrom'] = $openansweringfrom;
        }
        if ($closeansweringfrom) {
            $data['closeansweringfrom'] = $closeansweringfrom;
        }
        $studentquiz = $this->getDataGenerator()->create_module('studentquiz', $data);

        $this->setUser($user);

        $cm = get_coursemodule_from_instance('studentquiz', $studentquiz->id);

        // Make sure we're using a cm_info object.
        $cm = cm_info::create($cm);

        $dates = activity_dates::get_dates_for_module($cm, (int) $user->id);

        $this->assertEquals($expected, $dates);
    }
}
