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

use qbank_editquestion\edit_action_column;
use mod_studentquiz\local\studentquiz_helper;

/**
 * Represent edit action in studentquiz_bank_view
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sq_edit_action_column extends edit_action_column {

    /**
     * Override method to get url and label for edit action of the studentquiz.
     *
     * @param \stdClass $question The row from the $question table, augmented with extra information.
     * @return array With three elements.
     *      $url - The URL to perform the action.
     *      $icon - The icon for this action.
     *      $label - Text label to display in the UI (either in the menu, or as a tool-tip on the icon)
     */
    protected function get_url_icon_and_label(\stdClass $question): array {

        if (($question->state == studentquiz_helper::STATE_APPROVED || $question->state == studentquiz_helper::STATE_DISAPPROVED) &&
                !has_capability('mod/studentquiz:previewothers', $this->qbank->get_most_specific_context())) {
            // Do not render Edit icon if Question is in approved/disapproved state for Student.
            return [null, null, null];
        }

        return parent::get_url_icon_and_label($question);
    }

}
