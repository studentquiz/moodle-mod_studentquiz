<?php
/**
 * Representing comments column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent comments column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_column extends \core_question\bank\column_base {

    /**
     * Get column name
     * @return string column name
     */
    public function get_name() {
        return 'comment';
    }

    /**
     * Get title
     * @return string column title
     */
    protected function get_title() {
        return get_string('comment_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if (!empty($question->comment)) {
            echo $question->comment;
        } else {
            echo get_string('no_comment', 'studentquiz');
        }
    }

    /**
     * Get the left join for comments
     * @return array modified select left join
     */
    public function get_extra_joins() {
        return array('co' => 'LEFT JOIN ('
            . 'SELECT COUNT(comment) as comment'
            . ', questionid FROM {studentquiz_comment} GROUP BY questionid) co ON co.questionid = q.id');
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('co.comment');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return 'co.comment';
    }
}
