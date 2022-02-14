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
     * Setup test
     *
     * @throws \coding_exception
     */
    protected function setUp(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->create_module('studentquiz'
            , array('course' => $course->id),  array('anonymrank' => true));
    }

    /**
     * Tear down test
     *
     * @throws \coding_exception
     */
    public function tearDown(): void {
        parent::tearDown();
        $this->resetAfterTest();
    }

    /**
     * Test context_override function by first testing permissions initially, then
     * after adding a capability and then after removing that capability again.
     */
    public function test_context_override() {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $roleid = $this->getDataGenerator()->create_role();
        $contextsystem = \context_system::instance();
        $studentquiz = $this->getDataGenerator()->create_module('studentquiz',
                ['course' => $course->id], ['anonymrank' => true]);
        $contextstudentquiz = \context_module::instance($studentquiz->coursemodule);

        $this->getDataGenerator()->role_assign($roleid, $user->id, $contextsystem->id);
        $this->setUser($user);

        // First the user doesn't have the context specific capability.
        $this->assertFalse(has_capability('moodle/question:editall', $contextstudentquiz));

        // Then we assign a capability in context system.
        assign_capability('mod/studentquiz:manage', CAP_ALLOW, $roleid, $contextsystem, true);

        // Un-assign the needed capabilities.
        assign_capability('moodle/question:editall', CAP_PREVENT, $roleid, $contextsystem, true);

        // We assign another capabilities which is not in the context_override list to check that it will not be affected.
        assign_capability('mod/studentquiz:canselfratecomment', CAP_ALLOW, $roleid, $contextstudentquiz, true);
        context_override::ensure_permissions_are_right($contextstudentquiz);

        $this->assertTrue(has_capability('mod/studentquiz:manage', $contextstudentquiz));
        $this->assertTrue(has_capability('moodle/question:editall', $contextstudentquiz));
        $this->assertTrue(has_capability('mod/studentquiz:canselfratecomment', $contextstudentquiz));

        // Then we remove that capability in context system and the user has not anymore the context specific capability.
        unassign_capability('mod/studentquiz:manage', $roleid, $contextsystem);
        context_override::ensure_permissions_are_right($contextstudentquiz);

        $this->assertFalse(has_capability('mod/studentquiz:manage', $contextstudentquiz));
        $this->assertFalse(has_capability('moodle/question:editall', $contextstudentquiz));
        $this->assertTrue(has_capability('mod/studentquiz:canselfratecomment', $contextstudentquiz));

        // Assign the capability in context module.
        assign_capability('mod/studentquiz:manage', CAP_ALLOW, $roleid, $contextstudentquiz, true);
        context_override::ensure_permissions_are_right($contextstudentquiz);

        $this->assertTrue(has_capability('mod/studentquiz:manage', $contextstudentquiz));
        $this->assertTrue(has_capability('moodle/question:editall', $contextstudentquiz));
        $this->assertTrue(has_capability('mod/studentquiz:canselfratecomment', $contextstudentquiz));
    }
}
