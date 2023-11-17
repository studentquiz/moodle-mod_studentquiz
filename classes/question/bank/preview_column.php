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
 * A column type for preview link to mod_studentquiz_preview
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preview_column extends \qbank_previewquestion\preview_action_column {

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
    public function init(): void {
        global $PAGE;
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
        $this->context = $this->qbank->get_most_specific_context();
        $this->previewtext = get_string('preview');
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

    /**
     * Override this function and return the appropriate action menu link, or null if it does not apply to this question.
     *
     * @param \stdClass $question Data about the question being displayed in this row.
     * @return \action_menu_link|null The action, if applicable to this question.
     */
    public function get_action_menu_link(\stdClass $question): ?\action_menu_link {
        if ($this->can_preview($question)) {
            $params = ['cmid' => $this->context->instanceid, 'studentquizquestionid' => $question->studentquizquestionid];
            $link = new \moodle_url('/mod/studentquiz/preview.php', $params);

            return new \action_menu_link_secondary($link, new \pix_icon('t/preview', ''),
                $this->previewtext, ['target' => 'questionpreview']);
        }

        return null;
    }
}
