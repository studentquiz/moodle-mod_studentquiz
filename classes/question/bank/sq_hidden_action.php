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

if (!class_exists('\core_question\local\bank\question_action_base')) {
    class_alias('\core_question\local\bank\menu_action_column_base', '\core_question\local\bank\question_action_base');
}
/**
 * Represent sq_hiden action in studentquiz_bank_view
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sq_hidden_action extends \core_question\local\bank\question_action_base {

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
            if (!empty($question->sq_hidden)) {
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
