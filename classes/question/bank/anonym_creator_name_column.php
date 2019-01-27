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
 * Representing anonym creator column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * A column type for the name of the question creator.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class anonym_creator_name_column extends \core_question\bank\creator_name_column {

    protected $currentuserid;

    protected $anonymize;

    protected $anonymousname;

    protected $renderer;

    /**
     * Loads config of current userid and can see
     */
    public function init() {
        global $USER, $PAGE;
        $this->currentuserid = $USER->id;
        $this->anonymousname = get_string('creator_anonym_fullname', 'studentquiz');
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
    }

    protected function display_content($question, $rowclasses) {
        $this->anonymize = $this->qbank->is_anonymized();
        $output = $this->renderer->render_anonym_creator_name_column(
                $this->anonymize, $question, $this->currentuserid, $this->anonymousname, $rowclasses);
        echo $output;
    }
}
