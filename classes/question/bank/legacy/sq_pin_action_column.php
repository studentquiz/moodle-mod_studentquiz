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
use moodle_url;

/**
 * Represent pin action in studentquiz_bank_view
 *
 * @package mod_studentquiz
 * @copyright 2021 The Open University.
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sq_pin_action_column extends menu_action_column_base {
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
        return 'pin_toggle';
    }

    /**
     * Get required fields.
     *
     * @return array Fields required.
     */
    public function get_required_fields(): array {
        return array('sqq.pinned AS pinned');
    }


    /**
     * Override method to get url and label for pin action of the studentquiz.
     *
     * @param \stdClass $question The row from the $question table, augmented with extra information.
     * @return array With three elements.
     *      $url - The URL to perform the action.
     *      $icon - The icon for this action.
     *      $label - Text label to display in the UI (either in the menu, or as a tool-tip on the icon)
     */
    protected function get_url_icon_and_label(\stdClass $question): array {
        $output = '';
        $courseid = $this->qbank->get_courseid();
        $cmid = $this->qbank->cm->id;
        if (has_capability('mod/studentquiz:pinquestion', $this->qbank->get_most_specific_context())) {
            if ($question->pinned) {
                $url = new moodle_url('/mod/studentquiz/pinaction.php',
                        ['studentquizquestionid' => $question->studentquizquestionid,
                                'pin' => 0, 'sesskey' => sesskey(), 'cmid' => $cmid,
                                'returnurl' => $this->qbank->base_url(), 'courseid' => $courseid]);
                return [$url, 'i/star', get_string('unpin', 'studentquiz'), 'courseid' => $courseid];
            } else {
                $url = new moodle_url('/mod/studentquiz/pinaction.php',
                        ['studentquizquestionid' => $question->studentquizquestionid,
                                'pin' => 1, 'sesskey' => sesskey(), 'cmid' => $cmid,
                                'returnurl' => $this->qbank->base_url(), 'courseid' => $courseid]);
                return [$url, 't/emptystar', get_string('pin', 'studentquiz')];
            }
        }

        return [null, null, null];
    }

}
