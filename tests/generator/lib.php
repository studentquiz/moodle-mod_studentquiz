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
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_generator extends testing_module_generator {
    /**
     * @var int keep track of how many StudentQuiz have been created.
     */
    protected $studentquizcount = 0;

    /**
     * @var int keep track of how many StudentQuiz comments have been created.
     */
    protected $commentcount = 0;

    /**
     * @var int keep track of how many StudentQuiz rate have been created.
     */
    protected $ratecount = 0;


    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->studentquizcount = 0;
        $this->commentcount = 0;
        $this->ratecount = 0;
        parent::reset();
    }

    /**
     * Create StudentQuiz instance
     * @param stdClass $record
     * @param array $options
     * @return stdClass
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        // TODO for behats I think this is the reason for studentquiz 0!
        if (!isset($record->name)) {
            $record->name = 'studentquiz ' . $this->studentquizcount;
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create StudentQuiz comment on question
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
     * Create StudentQuiz rate on question
     * @param null $record
     * @return object
     */
    public function create_rate($record = null) {
        global $DB;

        $this->ratecount++;

        $record['id'] = $DB->insert_record('studentquiz_rate', $record);
        return (object) $record;
    }
}
