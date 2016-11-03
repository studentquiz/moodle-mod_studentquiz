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
 * mod_studentquiz data generator
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * mod_studentquiz data generator
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_generator extends testing_module_generator {
    /**
     * @var int keep track of how many studentquiz have been created.
     */
    protected $studentquizcount = 0;

    /**
     * @var int keep track of how many studentquiz comments have been created.
     */
    protected $commentcount = 0;

    /**
     * @var int keep track of how many studentquiz votes have been created.
     */
    protected $votecount = 0;


    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->studentquizcount = 0;
        $this->commentcount = 0;
        $this->votecount = 0;
        parent::reset();
    }

    /**
     * Create studentquiz instance
     * @param stdClass $record
     * @param array $options
     * @return stdClass
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once("$CFG->dirroot/mod/studentquiz/locallib.php");

        $record = (object)(array)$record;
        $record->name = 'studentquiz ' . $this->studentquizcount;
        $record->grade = 1;

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create studentquiz comment on question
     * @param null $record
     * @return object
     */
    public function create_comment($record = null) {
        global $DB;

        $this->commentcount++;

        $defaults = array(
            'comment' => 'Test comment ' . $this->commentcount,
            'created' => time()
        );

        $record = $this->datagenerator->combine_defaults_and_record($defaults, $record);
        $record['id'] = $DB->insert_record('studentquiz_comment', $record);
        return (object) $record;
    }

    /**
     * Create studentquiz vote on question
     * @param null $record
     * @return object
     */
    public function create_vote($record = null) {
        global $DB;

        $this->votecount++;

        $record['id'] = $DB->insert_record('studentquiz_comment', $record);
        return (object) $record;
    }
}
