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
     * Initialise Parameters for join
     */
    protected function init() {

        global $DB, $USER;
        $this->currentuserid = $USER->id;
        $cmid = $this->qbank->get_most_specific_context()->instanceid;
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
        if (!empty($question->practice)) {
            // echo $question->practice;
        } else {
            // echo get_string('no_practice', 'studentquiz');
        }

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
        $testsmyatts = array(
            'quiza.studentquizid = ' . $this->studentquizid,
            'quiza.userid = ' . $this->currentuserid,
            'name=\'-submit\'',
            '(state = \'gradedright\' OR state = \'gradedwrong\' OR state=\'gradedpartial\')'
        );
        $tests = array('qa.responsesummary IS NOT NULL');
        return array('pr' => 'LEFT JOIN ('
            . 'SELECT COUNT(questionid) as practice'
            . ', questionid FROM {question_attempts} qa JOIN {question} q ON qa.questionid = q.id'
            . ' WHERE ' . implode(' AND ', $tests)
            . ' GROUP BY qa.questionid) pr ON pr.questionid = q.id',
            'myatts' => 'LEFT JOIN ('
            . 'SELECT COUNT(*) myattempts, questionid'
            . ' FROM {studentquiz_attempt} quiza'
            . ' JOIN {question_usages} qu ON qu.id = quiza.questionusageid'
            . ' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
            . ' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
            . ' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
            . ' WHERE ' . implode(' AND ', $testsmyatts)
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
                .' WHERE qasd.name = \'answer\''
                .' AND qasd.id IN ('
                .' 	SELECT MAX(qasd.id)'
                .' 	FROM {studentquiz} sq '
                .' 	JOIN {studentquiz_attempt} sqa on sqa.studentquizid = sq.id'
                .' 	JOIN {question_usages} qu on qu.id = sqa.questionusageid '
                .' 	JOIN {question_attempts} qa on qa.questionusageid = qu.id '
                .' 	LEFT JOIN {question_attempt_steps} qas on qas.questionattemptid = qa.id'
                .' 	LEFT JOIN {question_attempt_step_data} qasd on qasd.attemptstepid = qas.id'
                .' 	WHERE qasd.name = \'answer\''
                .'  AND sq.id = ' . $this->studentquizid
                .'  AND sqa.userid = ' . $this->currentuserid
                .' 	GROUP BY qa.questionid'
                .' )'
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
     * @return string field name
     */
    public function is_sortable() {
        return array(
            'myattempts' => array('field' => 'myatts.myattempts', 'title' => get_string('number_column_name', 'studentquiz')),
            'mylastattempt' => array('field' => 'mylatts.mylastattempt', 'title' => get_string('latest_column_name', 'studentquiz')),
        );
    }
}
