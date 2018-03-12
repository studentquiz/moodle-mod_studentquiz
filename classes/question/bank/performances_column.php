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
     * Initialise Parameters for join
     */
    protected function init() {

        global $DB, $USER;
        $this->currentuserid = $USER->id;
        // Build context, categoryid and cmid here for use later.
        $context = $this->qbank->get_most_specific_context();
        $this->categoryid = question_get_default_category($context->id)->id;
        $cmid = $context->instanceid;
        // TODO: Get StudentQuiz id from infrastructure instead of DB!
        // TODO: Exception handling lookup fails somehow.
        $sq = $DB->get_record('studentquiz', array('coursemodule' => $cmid));
        $this->studentquizid = $sq->id;
        // TODO: Sanitize!
    }

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
        return get_string('myattempts_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if (!empty($question->myattempts)) {
            echo $question->myattempts;
        } else {
            echo get_string('no_myattempts', 'studentquiz');
        }

        echo ' | ';

        if (!empty($question->mylastattempt)) {
            // TODO: Refactor magic constant.
            if ($question->mylastattempt == 'gradedright') {
                echo get_string('lastattempt_right', 'studentquiz');
            } else {
                echo get_string('lastattempt_wrong', 'studentquiz');
            }
        } else {
            echo get_string('no_mylastattempt', 'studentquiz');
        }
    }

    /**
     * Get params that this join requires be added to the query.
     * @return array sqlparams required to be added to query
     */
    public function get_sqlparams() {
        $this->sqlparams = array();
        return $this->sqlparams;
    }

    /**
     * Get the left join for practice
     * @return array modified select left join
     */
    public function get_extra_joins() {

        // Add outer WHERE tests here to limit the dataset to just the module question category.
        $tests = array('qa.responsesummary IS NOT NULL', 'q.parent = 0', 'q.hidden = 0', 'q.category = ' . $this->categoryid);
        return array('pr' => 'LEFT JOIN ('
            . 'SELECT COUNT(questionid) as practice'
            . ', questionid FROM {question_attempts} qa JOIN {question} q ON qa.questionid = q.id'
            . ' WHERE ' . implode(' AND ', $tests)
            . ' GROUP BY qa.questionid) pr ON pr.questionid = q.id',
            'myatts' => 'LEFT JOIN ('
            . 'SELECT COUNT(*) myattempts, questionid'
            .' FROM	{studentquiz} sq '
            .' 	JOIN {studentquiz_attempt} sqa on sqa.studentquizid = sq.id'
            . ' JOIN {question_usages} qu ON qu.id = sqa.questionusageid'
            . ' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
            . ' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
            . ' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
            . ' WHERE qasd.name = \'-submit\''
                .'  AND sq.id = ' . $this->studentquizid
                .'  AND sqa.userid = ' . $this->currentuserid
                .'  AND (qas.state = \'gradedright\' OR qas.state = \'gradedwrong\' OR qas.state=\'gradedpartial\')'
            . ' GROUP BY qa.questionid) myatts ON myatts.questionid = q.id',
            'mylastattempt' => 'LEFT JOIN ('
                .'SELECT'
                .' 	qa.questionid,'
                .' 	qas.state mylastattempt'
                .' FROM'
                .' 	{studentquiz} sq '
                .' 	JOIN {studentquiz_attempt} sqa on sqa.studentquizid = sq.id'
                .' 	JOIN {question_usages} qu on qu.id = sqa.questionusageid '
                .' 	JOIN {question_attempts} qa on qa.questionusageid = qu.id '
                .' 	LEFT JOIN {question_attempt_steps} qas on qas.questionattemptid = qa.id'
                .' 	LEFT JOIN {question_attempt_step_data} qasd on qasd.attemptstepid = qas.id'
                .'  INNER JOIN ('
                .' 	 SELECT MAX(qasd.id) maxqasdid'
                .' 	 FROM {studentquiz} sq '
                .' 	 JOIN {studentquiz_attempt} sqa on sqa.studentquizid = sq.id'
                .' 	 JOIN {question_usages} qu on qu.id = sqa.questionusageid '
                .' 	 JOIN {question_attempts} qa on qa.questionusageid = qu.id '
                .' 	 LEFT JOIN {question_attempt_steps} qas on qas.questionattemptid = qa.id'
                .' 	 LEFT JOIN {question_attempt_step_data} qasd on qasd.attemptstepid = qas.id'
                .' 	 WHERE qasd.name = \'-submit\''
                .'   AND sq.id = ' . $this->studentquizid
                .'   AND sqa.userid = ' . $this->currentuserid
                .'   AND (qas.state = \'gradedright\' OR qas.state = \'gradedwrong\' OR qas.state=\'gradedpartial\')'
                .'   GROUP BY qa.questionid'
                .'  ) qasdmax on qasd.id = qasdmax.maxqasdid'
                .' WHERE qasd.name = \'-submit\''
                . ') mylatts ON mylatts.questionid = q.id'
            );
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('pr.practice', 'myatts.myattempts', 'mylatts.mylastattempt');
    }

    /**
     * Get sql sortable name
     * @return array field name
     */
    public function is_sortable() {
        return array(
            'myattempts' => array('field' => 'myatts.myattempts',
                'title' => get_string('number_column_name', 'studentquiz')),
            'mylastattempt' => array('field' => 'mylatts.mylastattempt',
                'title' => get_string('latest_column_name', 'studentquiz')),
        );
    }
}
