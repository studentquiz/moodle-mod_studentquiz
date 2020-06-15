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
 * Represent delete action in studentquiz_bank_view
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2020 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

use core_question\bank\delete_action_column;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent delete action in studentquiz_bank_view
 *
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2020 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sq_delete_action_column extends delete_action_column {

    /**
     * Output the contents of this column.
     *
     * @param object $question the row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
    protected function display_content($question, $rowclasses) {
        if ($this->can_delete($question)) {
            parent::display_content($question, $rowclasses);
        }
    }

    /**
     * Look up if current user is allowed to delete this question
     * @param object $question The current question object
     * @return boolean
     */
    private function can_delete($question) {
        global $USER;
        return ($question->createdby == $USER->id) ||
                has_capability('mod/studentquiz:previewothers', $this->qbank->get_most_specific_context());
    }

}
