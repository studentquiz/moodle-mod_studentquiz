<?php
/**
 * Representing performances column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent performances column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class practice_column extends \core_question\bank\column_base {

    /**
     * Get column name
     * @return string column name
     */
    public function get_name() {
        return 'practice';
    }

    /**
     * Get title
     * @return string column title
     */
    protected function get_title() {
        return get_string('practice_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if (!empty($question->practice)) {
            echo $question->practice;
        } else {
            echo get_string('no_practice', 'studentquiz');
        }
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
        $this->sqlparams = array();
        foreach ($this->joinconditions as $joincondition) {
            if ($joincondition->params()) {
                $this->sqlparams = array_merge($this->sqlparams, $joincondition->params());
            }
        }
        return $this->sqlparams;
    }

    /**
     * Get the left join for practice
     * @return array modified select left join
     */
    public function get_extra_joins() {
        $tests = array('qa.responsesummary IS NOT NULL');
        foreach ($this->joinconditions as $joincondition) {
            if ($joincondition->where()) {
                $tests[] = '((' . $joincondition->where() .'))';
            }
        }
        return array('pr' => 'LEFT JOIN ('
            . 'SELECT COUNT(questionid) as practice'
            . ', questionid FROM {question_attempts} qa JOIN {question} q ON qa.questionid = q.id'
            . ' WHERE ' . implode(' AND ', $tests)
            . ' GROUP BY qa.questionid) pr ON pr.questionid = q.id');
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('pr.practice');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return 'pr.practice';
    }
}
