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
        global $DB,$USER;
        $this->currentuserid = $USER->id;
        $cmid = $this->qbank->get_most_specific_context()->instanceid;
        // @TODO: Get StudentQuiz id from infrastructure instead of DB!
        // @TODO: Exception handling lookup fails somehow
        $sq = $DB->get_record('studentquiz', array('coursemodule' => $cmid));
        $this->studentquizid = $sq->id;
        // @TODO: Sanitize!
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
            'name="-submit"',
            '(state = "gradedright" OR state = "gradedwrong" OR state="gradedpartial")'
        );

        return array( 'mydiffs' => 'LEFT JOIN ('
            . 'SELECT '
            . ' ROUND(1-(sum(case state when "gradedright" then 1 else 0 end)/count(*)),2) as mydifficulty,'
            . ' sum(case state when "gradedright" then 1 else 0 end) as mycorrectattempts,'
            . ' questionid'
            . ' FROM {studentquiz_attempt} quiza '
            . ' JOIN mdl_question_usages qu ON qu.id = quiza.questionusageid '
            . ' JOIN mdl_question_attempts qa ON qa.questionusageid = qu.id'
            . ' JOIN mdl_question_attempt_steps qas ON qas.questionattemptid = qa.id'
            . ' LEFT JOIN mdl_question_attempt_step_data qasd ON qasd.attemptstepid = qas.id'
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
