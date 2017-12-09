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
        $myratingtitle = get_string('myrate_column_name', 'studentquiz');
        $ratingtitle = get_string('rate_all_column_name', 'studentquiz');
        $notavailable = get_string('no_rates', 'studentquiz');

        if (!empty($question->rate) || !empty($question->myrate)) {
            $title = $ratingtitle . ": " . round($question->rate, 2) . " ";
            if (!empty($question->myrate)) {
                $title .= ", " . $myratingtitle . ": " . round($question->myrate, 2);
            } else {
                $title .= ", " . $myratingtitle . ": " . $notavailable;
            }
            echo \html_writer::span($this->render_ratingbar($question->rate, $question->myrate), null,
                array('title' => $title));
        } else {
            echo $notavailable;
        }
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
        return array(
            'rate' => array('field' => 'vo.rate', 'title' => get_string('average_column_name', 'studentquiz')),
            'myrate' => array('field' => 'myrate.myrate', 'title' => get_string('mine_column_name', 'studentquiz'))
        );

    }

    /**
     * Renders a svg bar
     * @param number $average float between 1 to 5 for backgroud bar.
     * @param int $mine between 1 to 5 for number of stars to be yellow
     */
    private function render_ratingbar($average, $mine) {
        $mine = intval($mine);
        $average = floatval($average);
        $fillstarson = "#ffc107";
        $fillstarsoff = "#fff";
        $fillbaron = "#fff";
        $fillbaroff = "#007bff";

        if ($average > 0 && $average <= 5) {
            $width = round($average * 20, 0);
        } else {
            $width = 1;
        }

        if ($mine > 0 && $mine <= 5) {
            $stars = $mine;
        } else {
            $stars = 0;
        }

        $output = '';
        $output .= '<svg width="101" height="21" xmlns="http://www.w3.org/2000/svg">'
                .'<!-- Created with Method Draw - http://github.com/duopixel/Method-Draw/ -->'
                .'<g><title>Rating bar</title></g>'
                .'<g>'
                .'<rect id="svg_6" height="20" width="100" rx="5" ry="5" y="0.397703" x="0.396847" '
                .' fill-opacity="null" stroke-opacity="null" stroke-width="0.5" stroke="#868e96" fill="'.$fillbaron .'"/>'
                .'<rect id="svg_7" height="20" width="' . $width . '" rx="5" ry="5" y="0.397703" x="0.396847"'
                .' stroke-opacity="null" stroke-width="0.5" stroke="#868e96" fill="'. $fillbaroff .'"/>';
        $starpath = ',8.514401l5.348972,0l1.652874,-5.081501l1.652875,5.081501l5.348971,0l-4.327402,3.140505l1.652959,'
                    .'5.081501l-4.327403,-3.14059l-4.327402,3.14059l1.65296,-5.081501l-4.327403,-3.140505z';
        for ($i = 1; $i <= $stars; $i++) {
            $output .= '<path stroke="#000" id="svg_'.$i.'" d="m'.(($i * 20) - 15).$starpath.'"'
                    .' stroke-width="0.5" fill="'.$fillstarson.'"/>';
        }
        for ($i = $stars + 1; $i <= 5; $i++) {
            $output .= '<path stroke="#868e96" id="svg_'.$i.'"'
                    .' d="m'.(($i * 20) - 15).$starpath.'" stroke-width="0.5" fill="'.$fillstarsoff.'"/>';
        }
        $output .= '</g></svg>';
        return $output;
    }
}
