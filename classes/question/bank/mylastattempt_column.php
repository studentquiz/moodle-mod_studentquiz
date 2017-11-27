<?php
/**
 * Representing my last attempt column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent mylastattempt column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mylastattempt_column extends \core_question\bank\column_base {

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
        // TODO: Sanitize
    }

    /**
     * Get column name
     * @return string column name
     */
    public function get_name() {
        return 'mylastattempt';
    }

    /**
     * Get title
     * @return string column title
     */
    protected function get_title() {
        return get_string('mylastattempt_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
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
     * Get the left join for myattempts
     * @return array modified select left join
     */
    public function get_extra_joins() {
        return array( 'mylastattempt' => 'LEFT JOIN ('
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
        return array('mylatts.mylastattempt');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return 'mylatts.mylastattempt';
    }
}
