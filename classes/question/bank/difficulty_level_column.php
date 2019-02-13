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

    protected $renderer;

    protected $studentquiz;

    /**
     * Initialise Parameters for join
     */
    protected function init() {
        global $DB, $USER, $PAGE;
        $this->currentuserid = $USER->id;
        $cmid = $this->qbank->get_most_specific_context()->instanceid;
        // TODO: Get StudentQuiz id from infrastructure instead of DB!
        // TODO: Exception handling lookup fails somehow.
        $sq = $DB->get_record('studentquiz', array('coursemodule' => $cmid));
        $this->studentquizid = $sq->id;
        $this->studentquiz = $sq;
        // TODO: Sanitize!
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
    }

    protected $sqlparams = array();

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
        if ($this->studentquiz->aggregated) {
            return array('dl' => "LEFT JOIN (
                                              SELECT ROUND(1 - AVG(correctattempts / attempts), 2) AS difficultylevel,
                                                     questionid
                                                FROM {studentquiz_progress}
                                               WHERE studentquizid = " . $this->studentquizid . "
                                            GROUP BY questionid
                                            ) dl ON dl.questionid = q.id");
        } else {
            return array('dl' => "LEFT JOIN (
                                              SELECT ROUND(1-avg(case qas.state when 'gradedright' then 1 else 0 end),2)
                                                         AS difficultylevel,
                                                     questionid
                                                FROM {studentquiz_attempt} sqa
                                                JOIN {question_usages} qu ON qu.id = sqa.questionusageid
                                                JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                                                JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
                                           LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id
                                               WHERE sqa.studentquizid = " . $this->studentquizid . "
                                                     AND qasd.name='-submit'
                                                     AND (qas.state = 'gradedright'
                                                          OR qas.state = 'gradedwrong'
                                                          OR qas.state='gradedpartial')
                                            GROUP BY qa.questionid
                                            ) dl ON dl.questionid = q.id",
                    'mydiffs' => "LEFT JOIN (
                                              SELECT ROUND(1-avg(case state when 'gradedright' then 1 else 0 end),2)
                                                         AS mydifficulty,
                                                     sum(case state when 'gradedright' then 1 else 0 end) AS mycorrectattempts,
                                                     questionid
                                                FROM {studentquiz_attempt} sqa
                                                JOIN {question_usages} qu ON qu.id = sqa.questionusageid
                                                JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                                                JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id
                                           LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id
                                               WHERE sqa.userid = " . $this->currentuserid . "
                                                     AND sqa.studentquizid = " . $this->studentquizid . "
                                                     AND qasd.name='-submit'
                                                     AND (qas.state = 'gradedright'
                                                         OR qas.state = 'gradedwrong'
                                                         OR qas.state = 'gradedpartial')
                                            GROUP BY qa.questionid
                                            ) mydiffs ON mydiffs.questionid = q.id");
        }
    }

    /**
     * Get sql field name
     * @return array fieldname in array
     */
    public function get_required_fields() {
        if ($this->studentquiz->aggregated) {
            return array('dl.difficultylevel', 'ROUND(1 - (sp.correctattempts / sp.attempts),2) AS mydifficulty',
                'sp.correctattempts AS mycorrectattempts');
        } else {
            return array('dl.difficultylevel', 'mydiffs.mydifficulty', 'mydiffs.mycorrectattempts');
        }
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        if ($this->studentquiz->aggregated) {
            return $this->renderer->get_is_sortable_difficulty_level_column(true);
        } else {
            return $this->renderer->get_is_sortable_difficulty_level_column(false);
        }
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
        $output = $this->renderer->render_difficulty_level_column($question, $rowclasses);
        echo $output;
    }
}
