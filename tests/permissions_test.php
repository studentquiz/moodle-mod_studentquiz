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

use mod_studentquiz\access\context_override;

/**
 * Unit tests permission namespace.
 *
 * @package    mod_studentquiz
 * @copyright  2020 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permissions_test extends \advanced_testcase {

    /**
     * Test cases for the ensure_permissions_are_right tests.
     *
     * Each test case is defined by 5 values.
     *  - 1.   the name of the role to use in this test. If this is not a standard role,
     *         then a new blank role with this name is created.
     *  - 2-4. these are any changes to make to the role and system (2), course (3) or activity (4) level.
     *  - 5.   these are the overrides we expect to exist after the synch process has run.
     *
     * @return array
     */
    public function ensure_permissions_are_right_testcases(): array {
        return [
            'default student role' => [
                'student',
                [],
                [],
                [],
                ['+moodle/question:add', '+moodle/question:editmine', '+moodle/question:tagmine',
                        '+moodle/question:useall', '+moodle/question:viewmine'],
            ],
            'default non-editing teacher role' => [
                'teacher',
                [],
                [],
                [],
                ['+moodle/question:add', '+moodle/question:editmine', '+moodle/question:tagmine',
                        '+moodle/question:useall', '+moodle/question:viewall'],
            ],
            'default editing teacher role' => [
                'editingteacher',
                [],
                [],
                [],
                [],
            ],
            'default manager role' => [
                'manager',
                [],
                [],
                [],
                [],
            ],

            'non-managed capabilties should not be removed' => [
                'teacher',
                [],
                [],
                ['+mod/studentquiz:canselfratecomment', '+moodle/site:accessallgroups'],
                ['+moodle/question:add', '+moodle/question:editmine', '+moodle/question:tagmine',
                        '+moodle/question:useall', '+moodle/question:viewall',
                        '+mod/studentquiz:canselfratecomment', '+moodle/site:accessallgroups'],
            ],
            'non-editing teacher with added manage' => [
                'teacher',
                [],
                ['+mod/studentquiz:manage'],
                [],
                ['+moodle/question:add', '+moodle/question:editall',
                        '+moodle/question:tagmine', '+moodle/question:useall', '+moodle/question:viewall'],
            ],
            'non-editing teacher role set back to default after capabilities were assigned in the past' => [
                'teacher',
                [],
                [],
                ['+moodle/question:add', '+moodle/question:editall',
                        '+moodle/question:tagmine', '+moodle/question:useall', '+moodle/question:viewall'],
                ['+moodle/question:add', '+moodle/question:editmine', '+moodle/question:tagmine',
                        '+moodle/question:useall', '+moodle/question:viewall'],
            ],
        ];
    }

    /**
     * Test the role-synchronisation logic for a particular role.
     *
     * The overrides arrays should look like ['-mod/studentquiz:submit', '+mod/studentquiz:previewothers']
     * where + means add/allow that capability, and - means remove/prevent it.
     *
     * @covers \mod_studentquiz\access\context_override::ensure_permissions_are_right
     * @dataProvider ensure_permissions_are_right_testcases
     *
     * @param string $roleshortname the role name to use in this tests (if not a know role, a new blank role will be used).
     * @param array $systemoverrides any changes to the system level role definition.
     * @param array $courseoverrides any overrides to apply at course level.
     * @param array $sudentquizoverrides any overrides to apply at the activity level level.
     * @param array $epectedsqoverrides the resulting overrides we expect to get at the SQ level
     *      once the synchronisation has run.
     */
    public function test_ensure_permissions_are_right(string $roleshortname,
            array $systemoverrides, array $courseoverrides, array $sudentquizoverrides,
            array $epectedsqoverrides) {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator();

        // Find or create the role to use.
        $role = $DB->get_record('role', ['shortname' => $roleshortname]);
        if (!$role) {
            $roleid = create_role('Test ' . $roleshortname, $roleshortname, 'For testing.');
            $role = $DB->get_record('role', ['id' => $roleid]);
        }

        // Create a course containing a StudentQuiz.
        $course = $generator->create_course();
        $studentquiz = $generator->create_module('studentquiz',
                ['course' => $course->id], ['anonymrank' => true]);
        $systemcontext = \context_system::instance();
        $coursecontext = \context_course::instance($course->id);
        $studentquizcontext = \context_module::instance($studentquiz->coursemodule);

        // Apply any required role definition changes and overrides.
        $this->apply_role_overrides($systemoverrides, $role, $systemcontext);
        $this->apply_role_overrides($courseoverrides, $role, $coursecontext);
        $this->apply_role_overrides($sudentquizoverrides, $role, $studentquizcontext);

        // Call the role sync function.
        context_override::ensure_permissions_are_right($studentquizcontext);

        // Verify which overrides now exist for this role on this studentquiz.
        $actualoverridedata = get_capabilities_from_role_on_context($role, $studentquizcontext);
        $actualoverrides = [];
        foreach ($actualoverridedata as $override) {
            $actualoverrides[$override->capability] =
                    $override->permission == CAP_ALLOW ? 'allow' :
                    ($override->permission == CAP_PREVENT ? 'prevent' : 'error unexpected');
        }
        ksort($actualoverrides);

        $expectedoverrides = [];
        foreach ($epectedsqoverrides as $override) {
            $capability = substr($override, 1);
            if ($override[0] === '-') {
                $expectedoverrides[$capability] = 'prevent';
            } else if ($override[0] === '+') {
                $expectedoverrides[$capability] = 'allow';
            } else {
                throw new \coding_exception('Expected override must start + or -.');
            }
        }
        ksort($expectedoverrides);

        $this->assertEquals($expectedoverrides, $actualoverrides);
    }

    /**
     * Apply a set of overrides to a given role in a given context.
     *
     * @param array $overrides format as above.
     * @param \stdClass $role the role to change.
     * @param \context $context the context to apply the changes at.
     */
    protected function apply_role_overrides(array $overrides, \stdClass $role, \context $context): void {
        foreach ($overrides as $override) {
            $capability = substr($override, 1);
            if ($override[0] === '-') {
                $permission = $context->contextlevel == CONTEXT_SYSTEM ? CAP_INHERIT : CAP_PREVENT;
            } else if ($override[0] === '+') {
                $permission = CAP_ALLOW;
            } else {
                throw new \coding_exception('Override must start + or -.');
            }
            assign_capability($capability, $permission, $role->id, $context->id, true);
        }
    }
}
