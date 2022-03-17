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

use core_question\local\bank\action_column_base;

/**
 * Represent question is pinned or not in studentquiz_bank_view
 *
 * @package mod_studentquiz
 * @copyright 2021 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class state_pin_column extends action_column_base {
    /** @var mod_studentquiz Renderer of student quiz. */
    protected $renderer;

    /**
     * Init method.
     */
    protected function init(): void {
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
        return 'state_pin';
    }

    /**
     * Title for this column. Not used if is_sortable returns an array.
     *
     * @return string Title of column.
     */
    public function get_title(): string {
        return '';
    }

    /**
     * Output the contents of this column.
     *
     * @param object $question The row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
    protected function display_content($question, $rowclasses) {
        $output = $this->renderer->render_state_pin($question);
        echo $output;
    }

}
