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

namespace mod_studentquiz\bank;

/**
 * Representing difficulty level column in studentquiz_bank_view
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class difficulty_level_column extends studentquiz_column_base {

    /**
     * Renderer
     * @var stdClass
     */
    protected $renderer;

    /** @var \stdClass */
    protected $studentquiz;

    /**
     * Initialise Parameters for join
     */
    protected function init(): void {
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
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_extra_joins(): array {
            return array('dl' => "LEFT JOIN (
                                              SELECT ROUND(1 - AVG(CAST(correctattempts AS DECIMAL) /
                                                       CAST(attempts AS DECIMAL)), 2) AS difficultylevel,
                                                     studentquizquestionid
                                                FROM {studentquiz_progress}
                                               WHERE studentquizid = {$this->studentquizid} AND attempts > 0
                                            GROUP BY studentquizquestionid
                                            ) dl ON dl.studentquizquestionid = sqq.id");
    }

    /**
     * Get sql field name
     * @return array fieldname in array
     */
    public function get_required_fields(): array {
        return ['dl.difficultylevel',
                     '(CASE WHEN sp.attempts > 0 THEN
                            ROUND(1 - (CAST(sp.correctattempts AS DECIMAL) / CAST(sp.attempts  AS DECIMAL)), 2)
                            ELSE 0
                       END) AS mydifficulty
                ',
            'sp.correctattempts AS mycorrectattempts'];
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return $this->renderer->get_is_sortable_difficulty_level_column();
    }

    /**
     * Get column real title
     * @return string translated title
     */
    public function get_title() {
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
