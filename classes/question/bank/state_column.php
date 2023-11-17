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
 * Represent state column in studentquiz_bank_view
 *
 * @package mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class state_column extends \core_question\local\bank\column_base {

    /**
     * Renderer
     * @var stdClass
     */
    protected $renderer;

    /**
     * Initialise
     */
    public function init(): void {
        global $PAGE;
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
    }

    /**
     * Get column name
     * @return string column name
     */
    public function get_name() {
        return 'state';
    }

    /**
     * Get title to return the very short column name
     * @return string column title
     */
    public function get_title() {
        return get_string('state_column_name_veryshort', 'studentquiz');
    }

    /**
     * Get title tip to return the full column name
     * @return string column title
     */
    public function get_title_tip() {
        return get_string('state_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        $output = $this->renderer->render_state_column($question, $rowclasses);
        echo $output;
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields(): array {
        return array('sqq.state AS state');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return 'sqq.state';
    }
}
