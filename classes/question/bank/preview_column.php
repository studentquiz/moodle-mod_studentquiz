<?php

namespace mod_studentquiz\bank;

/**
 * A column type for preview link to mod_studentquiz_preview
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class preview_column extends \core_question\bank\preview_action_column {

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
        if ($this->can_preview($question)) {
            echo $this->renderer->question_preview_link($question, $this->context, false);
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