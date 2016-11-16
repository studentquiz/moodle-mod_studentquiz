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
 * Representing tag column
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent tag column in studentquiz_bank_view
 *
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tag_column extends \core_question\bank\column_base {

    /**
     * Get column name
     * @return string
     */
    public function get_name() {
        return 'tags';
    }

    /**
     * Get column title
     * @return string translated title
     */
    protected function get_title() {
        return get_string('tag_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if (!empty($question->tagname)) {
            echo $question->tagname;
        } else {
            echo get_string('no_tags', 'studentquiz');
        }
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_extra_joins() {
        return array();
    }

}
