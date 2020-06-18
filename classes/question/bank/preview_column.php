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
 * Representing the preview column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * A column type for preview link to mod_studentquiz_preview
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preview_column extends \core_question\bank\preview_action_column {

    /**
     * Renderer
     * @var stdClass
     */
    protected $renderer;

    /** @var stdClass */
    protected $context;

    /** @var string */
    protected $previewtext;

    /**
     * Loads config of current userid and can see
     */
    public function init() {
        global $PAGE;
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
        $this->context = $this->qbank->get_most_specific_context();
        $this->previewtext = get_string('preview');
    }

    /**
     * Override of base display_content
     * @param object $question
     * @param string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if ($this->can_preview($question)) {
            echo $this->renderer->question_preview_link($question, $this->context, false, $this->previewtext);
        }
    }

    /**
     * Look up if current user is allowed to preview this question
     * @param object $question The current question object
     * @return boolean
     */
    private function can_preview($question) {
        global $USER;
        return ($question->createdby == $USER->id) || has_capability('mod/studentquiz:previewothers', $this->context);
    }
}