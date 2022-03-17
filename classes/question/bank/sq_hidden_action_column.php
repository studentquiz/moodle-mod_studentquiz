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

use core_question\local\bank\menu_action_column_base;

/**
 * Represent sq_hiden action in studentquiz_bank_view
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sq_hidden_action_column extends menu_action_column_base {
    /** @var int */
    protected $currentuserid;

    /**
     * Initialise Parameters for join
     */
    protected function init(): void {
        global $USER;
        $this->currentuserid = $USER->id;
        parent::init();
    }

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

    /**
     * Override method to get url and label for show/hidden action of the studentquiz.
     *
     * @param \stdClass $question The row from the $question table, augmented with extra information.
     * @return array With three elements.
     *      $url - The URL to perform the action.
     *      $icon - The icon for this action.
     *      $label - Text label to display in the UI (either in the menu, or as a tool-tip on the icon)
     */
    protected function get_url_icon_and_label(\stdClass $question): array {
        $courseid = $this->qbank->get_courseid();
        $cmid = $this->qbank->cm->id;
        if (has_capability('mod/studentquiz:previewothers', $this->qbank->get_most_specific_context())) {
            if ($question->sq_hidden) {
                $url = new \moodle_url('/mod/studentquiz/hideaction.php',
                        ['studentquizquestionid' => $question->studentquizquestionid, 'sesskey' => sesskey(),
                                'courseid' => $courseid, 'hide' => 0, 'cmid' => $cmid, 'returnurl' => $this->qbank->base_url()]);
                return [$url, 't/show', get_string('show')];
            } else {
                $url = new \moodle_url('/mod/studentquiz/hideaction.php',
                        ['studentquizquestionid' => $question->studentquizquestionid, 'sesskey' => sesskey(),
                                'courseid' => $courseid, 'hide' => 1, 'cmid' => $cmid, 'returnurl' => $this->qbank->base_url()]);
                return [$url, 't/hide', get_string('hide')];
            }
        }

        return [null, null, null];
    }
}
