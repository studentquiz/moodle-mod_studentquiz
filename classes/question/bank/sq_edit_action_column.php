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
 * Represent edit action in studentquiz_bank_view
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

use core_question\bank\edit_action_column;
use mod_studentquiz\local\studentquiz_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent edit action in studentquiz_bank_view
 *
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sq_edit_action_column extends edit_action_column {

    /**
     * Output the contents of this column.
     *
     * @param object $question the row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
    protected function display_content($question, $rowclasses) {
        $canedit = true;
        if (($question->state == studentquiz_helper::STATE_APPROVED || $question->state == studentquiz_helper::STATE_DISAPPROVED) &&
                !has_capability('mod/studentquiz:previewothers', $this->qbank->get_most_specific_context())) {
            // Do not render Edit icon if Question is in approved/disapproved state for Student.
            $canedit = false;
        }
        if ($canedit) {
            parent::display_content($question, $rowclasses);
        }
    }

}
