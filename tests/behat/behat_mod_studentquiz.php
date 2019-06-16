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
 * Steps definitions related to mod_studentquiz.
 *
 * @package    mod_studentquiz
 * @category   test
 * @copyright  2019 HSR (http://www.hsr.ch)
 * @author     2019 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps definitions related to mod_studentquiz.
 *
 * @package    mod_studentquiz
 * @category   test
 * @copyright  2019 HSR (http://www.hsr.ch)
 * @author     2019 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_studentquiz extends behat_base {

    /**
     * @Given /^I set the availability field "(?P<field_string>(?:[^"]|\\")*)" to "(?P<days_value_integer>(?:[^"]|\\")*)" days from now$/
     * @param string $field Field name.
     * @param string $days Number of days from now.
     */
    public function i_set_availability_field_to($field, $days) {
        $date = strtotime($days . ' day');
        $day = date('j', $date);
        $month = date('F', $date);
        $year = date('Y', $date);
        $this->set_field_value('id_' . $field . '_day', $day);
        $this->set_field_value('id_' . $field . '_month', $month);
        $this->set_field_value('id_' . $field . '_year', $year);
    }

    /**
     * Generic field setter.
     *
     * Internal API method, a generic *I set "VALUE" to "FIELD" field*
     * could be created based on it.
     *
     * @param string $fieldlocator The pointer to the field, it will depend on the field type.
     * @param string $value
     * @return void
     */
    protected function set_field_value($fieldlocator, $value) {
        // We delegate to behat_form_field class, it will
        // guess the type properly as it is a select tag.
        $field = behat_field_manager::get_form_field_from_label($fieldlocator, $this);
        $field->set_value($value);
    }

    /**
     * @Given /^I make sure the current Moodle version is greater than 3.4$/
     *
     */
    public function i_check_moodle_version() {
        global $CFG;
        if (!$CFG->branch < 35) {
            throw new \Moodle\BehatExtension\Exception\SkippedException();
        }
    }

}
