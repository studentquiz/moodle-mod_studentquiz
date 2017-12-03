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
 * This view object represents the state ov the summary page.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the renderer for the StudentQuiz module.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_summary_view {

    protected $cm;

    protected $studentquiz;

    protected $attempt;

    /**
     * @var $userid The user currently viewing this view
     */
    protected $userid;

    /**
     * mod_studentquiz_summary_view constructor.
     * Load state from context
     * @param $cm
     * @param $studentquiz
     * @param $attempt
     * @param $userid
     */
    public function __construct($cm, $studentquiz, $attempt, $userid) {
        $this->cm = $cm;
        $this->studentquiz = $attempt;
        $this->attempt = $attempt;
        $this->userid = $userid;
        $this->load_attempt();
    }

    /**
     *
     */
    private function load_attempt() {

    }
}