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
 * Representing rating column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent rate column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rate_column extends \core_question\bank\column_base {

    protected $renderer;

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
        // TODO: Sanitize!
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
    }


    /**
     * Get column name
     * @return string column name
     */
    public function get_name() {
        return 'rates';
    }

    /**
     * Get title
     * @return string column title
     */
    protected function get_title() {
        return get_string('rate_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        $output = $this->renderer->render_rate_column($question, $rowclasses);
        echo $output;
    }

    /**
     * Get the left join for rating
     * @return array modified select left join
     */
    public function get_extra_joins() {
        return array('vo' => 'LEFT JOIN ('
        .'SELECT ROUND(avg(rate), 2) as rate'
        .', questionid FROM {studentquiz_rate} GROUP BY questionid) vo ON vo.questionid = q.id',
        'myrate' => 'LEFT JOIN ('
            . 'SELECT '
            . ' rate myrate, '
            . ' q.id questionid'
            . ' FROM {question} q'
            . ' LEFT JOIN {studentquiz_rate} rate on q.id = rate.questionid'
            . ' AND rate.userid = ' . $this->currentuserid
            . ' ) myrate ON myrate.questionid = q.id'
        );
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('vo.rate', 'myrate.myrate');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return $this->renderer->get_is_sortable_rate_column();
    }
}
