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
 * Representing the question name column
 *
 * @package    mod_studentquiz
 * @copyright  2018 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * A column type for the name of the question name.
 *
 * @package    mod_studentquiz
 * @copyright  2018 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_name_column extends \core_question\bank\question_name_column {

    protected $renderer;
    protected $context;

    /**
     * Loads config of current userid and can see
     */
    public function init() {
        global $PAGE;
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
        $this->context = $this->qbank->get_most_specific_context();
    }

    /**
     * Override of base display_content
     * @param object $question
     * @param string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        $labelfor = $this->label_for($question);
        echo $this->renderer->render_question_name_column($question, $rowclasses, $labelfor);
    }

}