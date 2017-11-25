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
        $tests = array(
            'quiza.studentquizid = ' . $this->studentquizid,
            'quiza.userid = ' . $this->currentuserid,
            'name=\'-submit\'',
            '(qas.state = \'gradedright\' OR state = \'gradedwrong\' OR state=\'gradedpartial\')'
        );
        return array( 'mylastattempt' => 'LEFT JOIN ('
            .' SELECT qa.questionid, qas.state mylastattempt'
            .' FROM {studentquiz_attempt} quiza'
            .'  JOIN {question_usages} qu ON qu.id = quiza.questionusageid'
            .'  JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
            .'   LEFT JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
            .'   AND qas.id = ('
            .'      SELECT MAX(qas2.id)'
            .'      FROM {studentquiz_attempt} quiza2'
            .'      JOIN {question_usages} qu2 ON qu2.id = quiza2.questionusageid'
            .'      JOIN {question_attempts} qa2 ON qa2.questionusageid = qu2.id'
            .'      JOIN {question_attempt_steps} qas2 ON qas2.questionattemptid = qa2.id'
            .'      WHERE qa2.questionid =	qa.questionid'
            .'      AND (qas2.state = \'gradedright\' OR qas2.state =\'gradedwrong\' OR qas2.state=\'gradedpartial\')'
            .'      )'
            .' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
            .' WHERE ' . implode(' AND ', $tests) . ') mylatts ON mylatts.questionid = q.id'
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
