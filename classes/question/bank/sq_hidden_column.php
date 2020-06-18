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
 * Represent sq_hiden column in studentquiz_bank_view
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

use core_question\bank\action_column_base;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent sq_hiden column in studentquiz_bank_view
 *
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sq_hidden_column extends action_column_base {
    /**
     * Column name
     *
     * @return string internal name for this column. Used as a CSS class name,
     *     and to store information about the current sort. Must match PARAM_ALPHA.
     */
    public function get_name() {
        return 'sq_hidden';
    }

    /**
     * Title for this column. Not used if is_sortable returns an array.
     *
     * @return string Title of column
     */
    protected function get_title() {
        return '';
    }

    /**
     * Output the contents of this column.
     *
     * @param object $question the row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
    protected function display_content($question, $rowclasses) {
        if (has_capability('mod/studentquiz:previewothers', $this->qbank->get_most_specific_context())) {
            if ($question->sq_hidden) {
                $url = new \moodle_url($this->qbank->base_url(), ['unhide' => $question->id, 'sesskey' => sesskey()]);
                $this->print_icon('t/show', get_string('show'), $url);
            } else {
                $url = new \moodle_url($this->qbank->base_url(), ['hide' => $question->id, 'sesskey' => sesskey()]);
                $this->print_icon('t/hide', get_string('hide'), $url);
            }
        }
    }

    /**
     * Return an array 'table_alias' => 'JOIN clause' to bring in any data that
     * this column required.
     *
     * The return values for all the columns will be checked. It is OK if two
     * columns join in the same table with the same alias and identical JOIN clauses.
     * If to columns try to use the same alias with different joins, you get an error.
     * The only table included by default is the question table, which is aliased to 'q'.
     *
     * It is important that your join simply adds additional data (or NULLs) to the
     * existing rows of the query. It must not cause additional rows.
     *
     * @return array 'table_alias' => 'JOIN clause'
     */
    public function get_extra_joins() {
        $andhidden = "AND sqh.hidden = 0";
        if (has_capability('mod/studentquiz:previewothers', $this->qbank->get_most_specific_context())) {
            $andhidden = "";
        }
        return array('sqh' => "JOIN {studentquiz_question} sqh ON sqh.questionid = q.id $andhidden");
    }

    /**
     * Required columns
     *
     * @return array fields required. use table alias 'q' for the question table, or one of the
     * ones from get_extra_joins. Every field requested must specify a table prefix.
     */
    public function get_required_fields() {
        return ['sqh.hidden AS sq_hidden'];
    }
}
