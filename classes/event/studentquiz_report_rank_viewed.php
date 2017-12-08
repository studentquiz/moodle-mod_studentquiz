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
 * The mod_studentquiz report rank viewed event
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_studentquiz report rank viewed event
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_report_rank_viewed extends \core\event\base {

    /**
     * Init event
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'studentquiz';
    }

    /**
     * Get description
     * @return string get description
     */
    public function get_description() {
        return "On course: {$this->courseid} studentquizid: {$this->objectid} was viewed";
    }

    /**
     * Get url
     * @return \moodle_url view.php url
     */
    public function get_url() {
        return new \moodle_url('/mod/studentquiz/reportrank.php', array('id' => $this->objectid));
    }

    /**
     * This is used when restoring course logs where it is required that we
     * map the objectid to it's new value in the new course.
     * @return array the name of the restore mapping the objectid links to
     */
    public static function get_objectid_mapping() {
        return array('db' => 'studentquiz', 'restore' => 'studentquiz');
    }

    /**
     * This is used when restoring course logs where it is required that we
     * map the information in 'other' to it's new value in the new course.
     * @return array|bool an array of other values and their corresponding mapping
     */
    public static function get_other_mapping() {
        return false;
    }
}
