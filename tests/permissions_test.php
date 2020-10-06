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
 * Unit tests permission namespace.
 *
 * @package    mod_studentquiz
 * @copyright  2020 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('Direct Access is forbidden!');

/**
 * Unit tests permission namespace.
 *
 * @package    mod_studentquiz
 * @copyright  2020 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_permissions extends advanced_testcase {

    /**
     * Setup test
     *
     * @throws coding_exception
     */
    protected function setUp() {
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
     * @throws coding_exception
     */
    public function tearDown() {
        parent::tearDown();
        $this->resetAfterTest();
    }

    /**
     * Test context_override function by first testing permissions initially, then
     * after adding a capability and then after removing that capability again.
     */
    public function test_context_override() {
        global $CFG;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $roleid = $this->getDataGenerator()->create_role();
        $context = \context_system::instance();
        $studentquiz = $this->getDataGenerator()->create_module('studentquiz',
                ['course' => $course->id], ['anonymrank' => true]);
        $contextstudentquiz = context_module::instance($studentquiz->coursemodule);

        $this->getDataGenerator()->role_assign($roleid, $user->id, $context->id);
        $this->setUser($user);

        // First the user doesn't have the context specific capability.
        $this->assertFalse(has_capability('moodle/question:editall', $contextstudentquiz));

        // Then we assign a capability and run the the ensure function so the context specific capability is added.
        assign_capability('mod/studentquiz:manage', CAP_ALLOW, $roleid, $context, true);
        // Events take over for applying context_override::ensure_relation. Looks like events in unit tests are only
        // processed in Moodle 38 and later, so we have to manually call the context capability overrides.
        if ($CFG->branch < 38) {
            mod_studentquiz_observer::module_test_backwardsfix_capability_override($studentquiz->coursemodule);
        }
        $this->assertTrue(has_capability('moodle/question:editall', $contextstudentquiz));

        // Then we remove that capability again and the user has not anymore the context specific capability.
        unassign_capability('mod/studentquiz:manage', $roleid, $context);
        // Events take over for applying context_override::ensure_relation. Looks like events in unit tests are only
        // processed in Moodle 38 and later, so we have to manually call the context capability overrides.
        if ($CFG->branch < 38) {
            mod_studentquiz_observer::module_test_backwardsfix_capability_override($studentquiz->coursemodule);
        }
        $this->assertFalse(has_capability('moodle/question:editall', $contextstudentquiz));
    }
}
