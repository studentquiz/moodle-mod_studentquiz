<?php
/**
 * Representing difficulty level column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Representing difficulty level column in studentquiz_bank_view
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class difficulty_level_column extends \core_question\bank\column_base {

    protected $sqlparams =  array();

    /**
     * Return name of column
     * @return string columnname
     */
    public function get_name() {
        return 'difficultylevel';
    }

    /**
     * Set conditions to apply to join.
     * @param  array $joinconditions Conditions to apply to join (via WHERE clause)
     */
    public function set_joinconditions($joinconditions) {
        $this->joinconditions = $joinconditions;
    }

    /**
     * Get params that this join requires be added to the query.
     * @return array sqlparams required to be added to query
     */
    public function get_sqlparams() {
        return $this->sqlparams;
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_extra_joins() {
        return array('dl' => 'LEFT JOIN ('
            . 'SELECT ROUND(1 - (COALESCE(correct.num, 0) / total.num), 2) AS difficultylevel,'
            . 'qa.questionid'
            . ' FROM {question_attempts} qa JOIN {question} q ON q.id = qa.questionid'
            . ' LEFT JOIN  ('
            . ' SELECT COUNT(*) AS num, questionid'
            . '  FROM {question_attempts} qa'
            . '  JOIN {question} q ON q.id = qa.questionid'
            . '  WHERE rightanswer = responsesummary'
            . '  GROUP BY questionid'
            . ') correct ON(correct.questionid = qa.questionid)'
            . ' LEFT JOIN ('
            . ' SELECT COUNT(*) AS num, questionid'
            . '  FROM {question_attempts} qa JOIN {question} q ON q.id = qa.questionid'
            . '  WHERE responsesummary IS NOT NULL'
            . '  GROUP BY questionid'
            . ') total ON(total.questionid = qa.questionid)'
            . ' WHERE q.parent = 0'
            . ' GROUP BY qa.questionid, correct.num, total.num'
            . ') dl ON dl.questionid = q.id');
    }

    /**
     * Get sql field name
     * @return array fieldname in array
     */
    public function get_required_fields() {
        return array('dl.difficultylevel');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return 'dl.difficultylevel';
    }

    /**
     * Get column real title
     * @return string translated title
     */
    protected function get_title() {
        return get_string('difficulty_level_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if (!empty($question->difficultylevel)) {
            echo round(100 * $question->difficultylevel, 1) . ' %';
        } else {
            echo get_string('no_difficulty_level', 'studentquiz');
        }
    }
}
