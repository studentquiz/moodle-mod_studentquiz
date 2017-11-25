<?php
/**
 * Representing vote column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent vote column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vote_column extends \core_question\bank\column_base {

    /**
     * Get column name
     * @return string column name
     */
    public function get_name() {
        return 'votes';
    }

    /**
     * Get title
     * @return string column title
     */
    protected function get_title() {
        return get_string('vote_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if (!empty($question->vote)) {
            echo $question->vote;
        } else {
            echo get_string('no_votes', 'studentquiz');
        }
    }

    /**
     * Get the left join for voteing
     * @return array modified select left join
     */
    public function get_extra_joins() {
        return array('vo' => 'LEFT JOIN ('
        .'SELECT ROUND(SUM(vote)/COUNT(vote), 2) as vote'
        .', questionid FROM {studentquiz_vote} GROUP BY questionid) vo ON vo.questionid = q.id');
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('vo.vote');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return 'vo.vote';
    }
}
