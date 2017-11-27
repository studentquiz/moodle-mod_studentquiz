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
 * Represent my rating column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class myvote_column extends \core_question\bank\column_base {

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
        return 'myvote';
    }

    /**
     * Get title
     * @return string column title
     */
    protected function get_title() {
        return get_string('myvote_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if (!empty($question->myvote)) {
            echo $question->myvote;
        } else {
            echo get_string('no_myvote', 'studentquiz');
        }
    }

    /**
     * Get the left join
     * @return array modified select left join
     */
    public function get_extra_joins() {
        return array( 'myvote' => 'LEFT JOIN ('
            . 'SELECT '
            . ' vote myvote, '
            . ' q.id questionid'
            . ' FROM {question} q'
            . ' LEFT JOIN {studentquiz_vote} vote on q.id = vote.questionid'
            . ' AND vote.userid = ' . $this->currentuserid
            . ' ) myvote ON myvote.questionid = q.id');
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('myvote.myvote');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return 'myvote.myvote';
    }
}
