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
 * Representing approved column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent approved column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class approved_column extends \core_question\bank\column_base {

    /**
     * Get column name
     * @return string column name
     */
    public function get_name() {
        return 'approved';
    }

    /**
     * Get title
     * @return string column title
     */
    protected function get_title() {
        return get_string('approved_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        $class = 'question-unapproved';
        $content = get_string('not_approved', 'studentquiz');
        $title = get_string('approve', 'studentquiz');

        if (!empty($question->approved)) {
            $class = 'question-approved';
            $content = get_string('approved', 'studentquiz');
            $title = get_string('unapprove', 'studentquiz');
        }

        if (question_has_capability_on($question, 'editall')) {
            $url = new \moodle_url($this->qbank->base_url(), array('approveselected' => $question->id, 'q' . $question->id => 1,
                                   'sesskey' => sesskey()));

            $content = '<a title="' . $title . '" href="' . $url . '" class="' . $class . '">' . $content . '</a>';
        }

        echo $content;
    }

    /**
     * Get the left join for approved
     * @return array modified select left join
     */
    public function get_extra_joins() {
        return array('ap' => ' LEFT JOIN (SELECT questionid, approved FROM {studentquiz_question}) ap ON ap.questionid = q.id');
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('ap.approved');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return 'ap.approved';
    }
}
