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

    protected $renderer;

    /** @var \stdClass */
    protected $studentquiz;

    /**
     * Initialise Parameters for join
     */
    protected function init() {

        global $DB, $USER, $PAGE;
        $this->currentuserid = $USER->id;
        // Build context, categoryid and cmid here for use later.
        $context = $this->qbank->get_most_specific_context();
        $this->categoryid = question_get_default_category($context->id)->id;
        $cmid = $context->instanceid;
        // TODO: Get StudentQuiz id from infrastructure instead of DB!
        // TODO: Exception handling lookup fails somehow.
        $sq = $DB->get_record('studentquiz', array('coursemodule' => $cmid));
        $this->studentquizid = $sq->id;
        $this->studentquiz = $sq;
        // TODO: Sanitize!
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
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
        $output = $this->renderer->render_practice_column($question, $rowclasses);
        echo $output;
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
        return array('sp' => "LEFT JOIN {studentquiz_progress} sp ON sp.questionid = q.id
                                    AND sp.userid = " . $this->currentuserid . "
                                    AND sp.studentquizid = " . $this->studentquizid);
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('sp.attempts practice', 'sp.attempts AS myattempts',
            "(
               CASE WHEN sp.attempts IS NULL
                    THEN ''
                    ELSE CASE WHEN sp.lastanswercorrect = 1
                              THEN 'gradedright'
                              ELSE 'gradedwrong'
                    END
               END
             ) AS mylastattempt");
    }

    /**
     * Get sql sortable name
     * @return array field name
     */
    public function is_sortable() {
        return array(
            'myattempts' => array('field' => 'myattempts',
                'title' => get_string('number_column_name', 'studentquiz')),
            'mylastattempt' => array('field' => 'mylastattempt',
                'title' => get_string('latest_column_name', 'studentquiz')),
        );
    }
}
