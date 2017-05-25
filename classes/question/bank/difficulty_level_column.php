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
 * Representing difficulty level column
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Representing difficulty level column in studentquiz_bank_view
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class difficulty_level_column extends \core_question\bank\column_base {

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
        $tests = array('q.parent = 0');
        $correcttests = array('rightanswer = responsesummary');
        $totaltests = array('responsesummary IS NOT NULL');
        // Have to build params array while building query, due to inability to use single
        // named parameter multiple times. Each call to joincondition->where updates
        // joincondition's params.
        $this->sqlparams = array();
        foreach ($this->joinconditions as $joincondition) {
            if ($joincondition->where()) {
                $tests[] = '((' . $joincondition->where() .'))';
                $this->sqlparams = array_merge($this->sqlparams, $joincondition->params());
                $correcttests[] = '((' . $joincondition->where() .'))';
                $this->sqlparams = array_merge($this->sqlparams, $joincondition->params());
                $totaltests[] = '((' . $joincondition->where() .'))';
                $this->sqlparams = array_merge($this->sqlparams, $joincondition->params());
            }
        }
        return array('dl' => 'LEFT JOIN ('
            . 'SELECT ROUND(1 - (COALESCE(correct.num, 0) / total.num), 2) AS difficultylevel,'
            . 'qa.questionid'
            . ' FROM {question_attempts} qa JOIN {question} q ON q.id = qa.questionid'
            . ' LEFT JOIN  ('
            . ' SELECT COUNT(*) AS num, questionid'
            . '  FROM {question_attempts} qa JOIN {question} q ON q.id = qa.questionid'
            . '  WHERE ' . implode(' AND ', $correcttests)
            . '  GROUP BY questionid'
            . ') correct ON(correct.questionid = qa.questionid)'
            . ' LEFT JOIN ('
            . ' SELECT COUNT(*) AS num, questionid'
            . '  FROM {question_attempts} qa JOIN {question} q ON q.id = qa.questionid'
            . '  WHERE ' . implode(' AND ', $totaltests)
            . '  GROUP BY questionid'
            . ') total ON(total.questionid = qa.questionid)'
            . ' WHERE ' . implode(' AND ', $tests)
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
            echo $question->difficultylevel;
        } else {
            echo get_string('no_difficulty_level', 'studentquiz');
        }
    }
}
