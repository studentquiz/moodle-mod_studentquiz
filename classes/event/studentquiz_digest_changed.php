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
 * The mod_studentquiz digest changed event
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\event;

defined('MOODLE_INTERNAL') || die();

use mod_studentquiz\utils;
use moodle_url;

/**
 * The mod_studentquiz digest changed event
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - int newdigesttype: The type of the new digest
 *      - int olddigesttype: The type of the old digest
 *      - int olddigestfirstday: The type of the old digest first day
 * }
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_digest_changed extends \core\event\base {

    /**
     * Initialize the event
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'studentquiz';
    }

    /**
     * Get description
     *
     * @return string get description
     */
    public function get_description() {
        return 'On course: ' . $this->courseid . ' studentquizid: ' . $this->objectid . ' digest type was changed from ' .
                $this->other['olddigesttype'] . ' to ' . $this->other['newdigesttype'];
    }

    /**
     * Get url
     *
     * @return moodle_url view.php url
     */
    public function get_url() {
        return new moodle_url('/mod/studentquiz/view.php', ['id' => $this->objectid]);
    }

    /**
     * This is used when restoring course logs where it is required that we
     * map the objectid to it's new value in the new course.
     *
     * @return array the name of the restore mapping the objectid links to
     */
    public static function get_objectid_mapping() {
        return ['db' => 'studentquiz', 'restore' => 'studentquiz'];
    }

    /**
     * Custom validations.
     *
     * @return void
     */
    protected function validate_data() {
        if (!isset($this->other['olddigesttype'])) {
            throw new \coding_exception('The \'olddigesttype\' must be set in \'other\'.');
        }
        if (!isset($this->other['newdigesttype'])) {
            throw new \coding_exception('The \'newdigesttype\' must be set in \'other\'.');
        }
        if ($this->other['olddigesttype'] == utils::WEEKLY_DIGEST_TYPE && !isset($this->other['olddigestfirstday'])) {
            throw new \coding_exception('The \'olddigestfirstday\' must be set in \'other\'.');
        }
    }

    /**
     * This is used when restoring course logs where it is required that we
     * map the information in 'other' to it's new value in the new course.
     *
     * @return array|bool an array of other values and their corresponding mapping
     */
    public static function get_other_mapping() {
        $othermapped = [];
        $othermapped['olddigesttype'] = ['db' => 'studentquiz', 'restore' => 'studentquiz'];
        $othermapped['newdigesttype'] = ['db' => 'studentquiz', 'restore' => 'studentquiz'];
        $othermapped['newdigesttype'] = ['db' => 'studentquiz', 'restore' => 'studentquiz'];
        $othermapped['olddigestfirstday'] = ['db' => 'studentquiz', 'restore' => 'studentquiz'];

        return $othermapped;
    }
}
