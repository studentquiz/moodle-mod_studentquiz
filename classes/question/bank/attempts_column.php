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
 * Represent performances column in studentquiz_bank_view
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempts_column extends studentquiz_column_base {

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
        return 'attempts';
    }

    /**
     * Get title
     * @return string column title
     */
    public function get_title() {
        return get_string('myattempts_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        $output = $this->renderer->render_attempts_column($question, $rowclasses);
        echo $output;
    }

    /**
     * Get the left join for progress
     * @return array modified select left join
     */
    public function get_extra_joins(): array {
        return array('sp' => "LEFT JOIN {studentquiz_progress} sp ON sp.studentquizquestionid = sqq.id
                                    AND sp.userid = " . $this->currentuserid . "
                                    AND sp.studentquizid = " . $this->studentquizid);
    }

    /**
     * Get fields for this column
     * @return array additional fields
     */
    public function get_required_fields(): array {
        return [
            'sp.attempts AS myattempts',
            'sp.lastanswercorrect AS mylastanswercorrect',
            '(CASE WHEN sp.attempts = 0 THEN NULL ELSE sp.lastanswercorrect END) as mylastanswercorrectforsort'
        ];
    }

    /**
     * Get sql sortable name
     * @return array field name
     */
    public function is_sortable() {
        return array(
            'myattempts' => array('field' => 'myattempts',
                'title' => get_string('number_column_name', 'studentquiz')),
            'mylastattempt' => array('field' => 'mylastanswercorrectforsort',
                'title' => get_string('latest_column_name', 'studentquiz')),
        );
    }
}
