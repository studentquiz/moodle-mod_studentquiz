<?php
/**
 * Representing my attempts column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent my difficulty column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mydifficulty_column extends \core_question\bank\column_base {

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
        return 'mydifficulty';
    }

    /**
     * Get title
     * @return string column title
     */
    protected function get_title() {
        return get_string('mydifficulty_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if (!empty($question->myattempts)) {
            echo $question->mydifficulty;
        } else {
            echo get_string('no_mydifficulty', 'studentquiz');
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
            '(state = \'gradedright\' OR state = \'gradedwrong\' OR state=\'gradedpartial\')'
        );

        return array( 'mydiffs' => 'LEFT JOIN ('
            . 'SELECT '
            . ' ROUND(1-(sum(case state when \'gradedright\' then 1 else 0 end)/count(*)),2) as mydifficulty,'
            . ' sum(case state when \'gradedright\' then 1 else 0 end) as mycorrectattempts,'
            . ' questionid'
            . ' FROM {studentquiz_attempt} quiza '
            . ' JOIN {question_usages} qu ON qu.id = quiza.questionusageid '
            . ' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
            . ' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
            . ' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
            . ' WHERE ' . implode(' AND ', $tests)
            . ' GROUP BY qa.questionid) mydiffs ON mydiffs.questionid = q.id');
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('mydiffs.mydifficulty', 'mydiffs.mycorrectattempts');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return 'mydiffs.mydifficulty';
    }
}
