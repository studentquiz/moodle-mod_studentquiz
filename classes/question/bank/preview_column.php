<?php

namespace mod_studentquiz\bank;

/**
 * A column type for preview link to mod_studentquiz_preview
 *
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class preview_column extends \core_question\bank\preview_action_column {

    // Current userid
    protected $currentuserid;

    protected $anonymize;

    protected $canpreview;

    protected $renderer;

    /**
     * Loads config of current userid and can see
     */
    public function init() {
        global $USER, $PAGE;
        $this->currentuserid = $USER->id;
        // TODO: Set these values on init.
        $this->anonymize = true;
        $this->canpreview = true;
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
    }

    /**
     * Override of base display_content
     * @param object $question
     * @param string $rowclasses
     * TODO: Check with init members.
     */
    protected function display_content($question, $rowclasses) {
        global $PAGE;
        // Todo: Check question->createdby with currentuserid,
        if ($this->canpreview) {
            // TODO: get our own renderer here (mod_studentquiz) and implement question_preview_link there
            echo $this->renderer->question_preview_link(
                $question, $this->qbank->get_most_specific_context(), false);
        }
    }
}