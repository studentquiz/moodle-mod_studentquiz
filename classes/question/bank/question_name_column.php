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

/**
 * A column type for the name of the question name.
 *
 * @package    mod_studentquiz
 * @copyright  2018 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_name_column extends \qbank_viewquestionname\viewquestionname_column_helper {

    /** @var \stdClass */
    protected $renderer;

    /** @var \stdClass */
    protected $context;

    /** @var array Extra class names to this column. */
    protected $extraclasses = [];

    /**
     * Loads config of current userid and can see
     */
    public function init(): void {
        global $PAGE;
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
        $this->context = $this->qbank->get_most_specific_context();
    }

    /**
     * Override of base display_content
     * @param object $question
     * @param string $rowclasses
     */
    protected function display_content($question, $rowclasses): void {
        $labelfor = $this->label_for($question);
        echo $this->renderer->render_question_name_column($question, $rowclasses, $labelfor);
    }

    /**
     * Output this column.
     * @param object $question The row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
    public function display($question, $rowclasses): void {
        $this->extraclasses = [];
        if (!empty($question->sq_hidden)) {
            $this->extraclasses[] = 'dimmed_text';
        }

        parent::display($question, $rowclasses);
    }

    /**
     * Any extra class names to every cell in this column.
     *
     * @return array
     */
    public function get_extra_classes():array {
        return $this->extraclasses;
    }
}
