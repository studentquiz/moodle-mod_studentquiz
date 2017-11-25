<?php
/**
 * The question bank question text row
 *
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * A column type for the name of the question name.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_text_row extends \core_question\bank\row_base {
    /**
     * @var string formatoptions
     */
    protected $formatoptions;

    /**
     * Get the row name
     * @return string questiontext
     */
    public function get_name() {
        return 'questiontext';
    }

    /**
     * Override parent function to don't show the title
     */
    protected function get_title() {
    }

    /**
     * Override parent function to don't show content
     * @param object $question empty
     * @param string $rowclasses empty
     */
    protected function display_content($question, $rowclasses) {
    }

    /**
     * (Copy from parent class - modified several code snippets)
     * Output this column.
     * @param object $question the row from the $question table, augmented with extra information.
     * @param string $rowclasses CSS class names that should be applied to this row of output.
     */
    public function display($question, $rowclasses) {
    }

    /**
     * Get the extra join text
     * @return array join text
     */
    public function get_extra_joins() {
        return array('qc' => 'JOIN {question_categories} qc ON qc.id = q.category');
    }

    /**
     * Get required fields
     * @return array get all required fields
     */
    public function get_required_fields() {
        return array('q.id', 'q.questiontext', 'q.questiontextformat', 'qc.contextid');
    }
}
