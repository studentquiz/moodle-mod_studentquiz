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

        return array('dl' => 'LEFT JOIN ('
                . 'SELECT '
                . ' ROUND(1-avg(case qas.state when \'gradedright\' then 1 else 0 end),2) as difficultylevel,'
                . ' questionid'
                . ' FROM {studentquiz_attempt} sqa '
                . ' JOIN {question_usages} qu ON qu.id = sqa.questionusageid '
                . ' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
                . ' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
                . ' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
                . ' WHERE sqa.studentquizid = ' . $this->studentquizid
                . ' AND qasd.name=\'-submit\''
                . ' AND (qas.state = \'gradedright\' OR qas.state = \'gradedwrong\' OR qas.state=\'gradedpartial\')'
                . ' GROUP BY qa.questionid) dl ON dl.questionid = q.id',
            'mydiffs' => 'LEFT JOIN ('
                . 'SELECT '
                . ' ROUND(1-avg(case state when \'gradedright\' then 1 else 0 end),2) as mydifficulty,'
                . ' sum(case state when \'gradedright\' then 1 else 0 end) as mycorrectattempts,'
                . ' questionid'
                . ' FROM {studentquiz_attempt} sqa '
                . ' JOIN {question_usages} qu ON qu.id = sqa.questionusageid '
                . ' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
                . ' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
                . ' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
                . ' WHERE sqa.userid = ' . $this->currentuserid
                . ' AND sqa.studentquizid = ' . $this->studentquizid
                . ' AND qasd.name=\'-submit\''
                . ' AND (qas.state = \'gradedright\' OR qas.state = \'gradedwrong\' OR qas.state=\'gradedpartial\')'
                . ' GROUP BY qa.questionid) mydiffs ON mydiffs.questionid = q.id'
        );
    }

    /**
     * Get sql field name
     * @return array fieldname in array
     */
    public function get_required_fields() {
        return array('dl.difficultylevel', 'mydiffs.mydifficulty', 'mydiffs.mycorrectattempts');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return array(
            'difficulty' => array('field' => 'dl.difficultylevel', 'title' => get_string('average_column_name', 'studentquiz')),
            'mydifficulty' => array('field' => 'mydiffs.mydifficulty', 'title' => get_string('mine_column_name', 'studentquiz'))
        );
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
        $nodifficultylevel = get_string('no_difficulty_level', 'studentquiz');
        $difficultytitle = get_string('difficulty_all_column_name', 'studentquiz');
        $mydifficultytitle = get_string('mydifficulty_column_name', 'studentquiz');
        if (!empty($question->difficultylevel) || !empty($question->mydifficulty)) {
            $title = $difficultytitle . ": " . (100 * round($question->difficultylevel, 2)) . "% ";
            if (!empty($question->mydifficulty)) {
                $title .= ", " . $mydifficultytitle . ": " . (100 * round($question->mydifficulty, 2)) . '%';
            } else {
                $title .= ", " . $mydifficultytitle . ": " . $nodifficultylevel;
            }
            echo \html_writer::span(
                $this->render_difficultybar($question->difficultylevel, $question->mydifficulty),
                null,  array('title' => $title ));
        } else {
            echo $nodifficultylevel;
        }
    }

    /**
     * @param $average
     * @param $mine
     * @return string
     */
    private function render_difficultybar($average, $mine) {
        $mine = floatval($mine);
        $average = floatval($average);

        $fillboltson = "#ffc107";
        $fillboltsoff = "#fff";
        $fillbaron = "#fff";
        $fillbaroff = "#007bff";

        if ($average > 0 && $average <= 1) {
            $width = round($average * 100, 0);
        } else {
            $width = 0;
        }

        if ($mine > 0 && $mine <= 1) {
            $bolts = ceil($mine * 5);
        } else {
            $bolts = 0;
        }
        $output = '';
        $output .= '<svg width="101" height="21" xmlns="http://www.w3.org/2000/svg">'
                 . '<!-- Created with Method Draw - http://github.com/duopixel/Method-Draw/ -->'
                 . '<g><title>Difficulty bar</title></g>'
                 . '<g>'
                 . '<rect id="svg_6" height="20" width="100" y="0.397703" rx="5" ry="5" x="0.396847"'
                 . '      fill-opacity="null" stroke-opacity="null" stroke-width="0.5" stroke="#868e96" fill="'.$fillbaron .'"/>'
                 . '<rect id="svg_7" height="20" width="' . $width . '" rx="5" ry="5" y="0.397703" x="0.396847"'
                 . '      stroke-opacity="null" stroke-width="0.5" stroke="#868e96" fill="'. $fillbaroff .'"/>';
        $boltpath = ',1.838819l3.59776,4.98423l-1.4835,0.58821l4.53027,4.2704l-1.48284,0.71317l5.60036,7.15099l-9.49921,'
                 .  '-5.48006l1.81184,-0.76102l-5.90211,-3.51003l2.11492,-1.08472l-6.23178,-3.68217l6.94429,-3.189z';
        for ($i = 1; $i <= $bolts; $i++) {
            $output .= '<path stroke="'.$fillboltson.'" id="svg_'.$i.'" d="m'.(($i * 20) - 12).$boltpath.'"'
                    . ' stroke-width="0.5" fill="'.$fillboltson.'"/>';
        }
        for ($i = $bolts + 1; $i <= 5; $i++) {
            $output .= '<path stroke="#868e96" id="svg_'.$i.'" d="m'.(($i * 20) - 12).$boltpath.'"'
                    .  ' stroke-width="0.5" fill="'.$fillboltsoff.'"/>';
        }
        $output .= '</g></svg>';
        return $output;

    }
}