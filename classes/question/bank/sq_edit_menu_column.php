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

namespace mod_studentquiz\question\bank;

use core_question\local\bank\edit_menu_column;

/**
 * Represent edit column in studentquiz_bank_view which gathers together all the actions into a menu.
 *
 * @package mod_studentquiz
 * @author Thong Bui <qktc1422@gmail.com>
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sq_edit_menu_column extends edit_menu_column {

    /** @var int */
    protected $currentuserid;

    /**
     * Output the contents of this column.
     *
     * @param object $question the row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
    protected function display_content($question, $rowclasses): void {
        global $OUTPUT;
        if (method_exists($this->qbank, 'get_question_actions')) {
            $actions = $this->qbank->get_question_actions();

            $menu = new \action_menu();
            $menu->set_menu_trigger(get_string('edit'));
            foreach ($actions as $action) {
                $action = $action->get_action_menu_link($question);
                if ($action) {
                    $menu->add($action);
                }
            }

            echo $OUTPUT->render($menu);
        } else {
            parent::display_content($question, $rowclasses);
        }
    }

    /**
     * Initialise Parameters for join
     */
    protected function init(): void {
        global $USER;
        $this->currentuserid = $USER->id;
        parent::init();
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
    public function get_extra_joins(): array {
        $hidden = "sqq.hidden = 0";
        $mine = "q.createdby = $this->currentuserid";

        // Without permission, a user can only see non-hidden question or its their own.
        $sqlextra = "AND ($hidden OR $mine)";
        if (has_capability('mod/studentquiz:previewothers', $this->qbank->get_most_specific_context())) {
            $sqlextra = "";
        }

        return ['sqh' => "JOIN {studentquiz_question} sqh ON sqh.id = qr.itemid $sqlextra"];
    }

    /**
     * Required columns
     *
     * @return array fields required. use table alias 'q' for the question table, or one of the
     * ones from get_extra_joins. Every field requested must specify a table prefix.
     */
    public function get_required_fields(): array {
        return ['sqq.hidden AS sq_hidden'];
    }
}
