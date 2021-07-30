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


namespace mod_studentquiz\bank;

use core_question\bank\edit_action_column;
use mod_studentquiz\local\studentquiz_helper;
use core_question\bank\action_column_base;
use moodle_url;

/**
 * Represent toggle pin action in studentquiz_bank_view
 *
 * @package mod_studentquiz
 * @copyright 2021 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggle_pin_column extends action_column_base {
    /** @var mod_studentquiz Renderer of student quiz. */
    protected $renderer;

    /**
     * Init method.
     */
    protected function init() {
        global $USER, $PAGE;
        $this->currentuserid = $USER->id;
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
    }

    /**
     * Get the internal name for this column.
     *
     * @return string Column name.
     */
    public function get_name() {
        return 'pin_toggle';
    }

    /**
     * Title for this column. Not used if is_sortable returns an array.
     *
     * @return string Title of column.
     */
    protected function get_title() {
        return '';
    }

    /**
     * Get required fields.
     *
     * @return array Fields required.
     */
    public function get_required_fields() {
        return array('sqh.pinned AS pinned');
    }

    /**
     * Output the contents of this column.
     *
     * @param object $question The row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
    protected function display_content($question, $rowclasses) {
        $output = '';
        if (has_capability('mod/studentquiz:pinquestion', $this->qbank->get_most_specific_context())) {
            if ($question->pinned) {
                $url = new moodle_url($this->qbank->base_url(), ['unpin' => $question->id, 'sesskey' => sesskey()]);
                $output = $this->print_icon('i/star', get_string('unpin', 'studentquiz'), $url);
            } else {
                $url = new moodle_url($this->qbank->base_url(), ['pin' => $question->id, 'sesskey' => sesskey()]);
                $output = $this->print_icon('t/emptystar', get_string('pin', 'studentquiz'), $url);
            }
        }

        echo $output;
    }

}
