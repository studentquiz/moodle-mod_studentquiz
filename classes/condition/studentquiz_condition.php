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
 * Modify stuff conditionally
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\condition;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/studentquiz/classes/local/db.php');
use mod_studentquiz\local\db;

/**
 * Conditionally modify question bank queries.
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_condition extends \core_question\bank\search\condition {

    /**
     * Due to fix_sql_params not accepting repeated use of named params,
     * we need to get unique names for params that will be used more than
     * once...
     *
     * init() from parent class duplicated here as we can't call it directly
     * (private) :-P
     *
     * where() overridden with call to init() followed by call to parent
     * where()...
     *
     * params() always returns $this->params, which doesn't change between
     * calls to get_in_or_equal, so don't need to fix anything there.
     * Which is fortunate, as there'd be no way to keep where() and params()
     * in sync.
     *
     * @param stdClass $cm
     * @param stdClass $filterform
     * @param \mod_studentquiz_report $report
     * @param stdClass $studentquiz
     */
    public function __construct($cm, $filterform, $report, $studentquiz) {
        $this->cm = $cm;
        $this->filterform = $filterform;
        $this->tests = array();
        $this->params = array();
        $this->report = $report;
        $this->studentquiz = $studentquiz;
        $this->init();
    }

    /** @var stdClass */
    protected $cm;

    /** @var stdClass $filterform Search condition depends on filterform */
    protected $filterform;

    /** @var stdClass */
    protected $studentquiz;

    /** @var \mod_studentquiz_report */
    protected $report;

    /** @var array */
    protected $tests;

    /** @var array */
    protected $params;

    /** @var bool */
    protected $isfilteractive = false;

    /**
     * Whether the filter is active.
     * @return bool
     */
    public function is_filter_active() {
        return $this->isfilteractive;
    }

    /**
     * Initialize.
     */
    protected function init() {
        if ($adddata = $this->filterform->get_data()) {

            $this->tests = array();
            $this->params = array();

            foreach ($this->filterform->get_fields() as $field) {

                // Validate input.
                $data = $field->check_data($adddata);

                // If input is valid, at least one filter was activated.
                if ($data === false) {
                    continue;
                } else {
                    $this->isfilteractive = true;
                }

                $sqldata = $field->get_sql_filter($data);

                // Disable filtering by firstname if anonymized.
                if ($field->_name == 'firstname' && !(mod_studentquiz_check_created_permission($this->cm->id) ||
                    !$this->report->is_anonymized())) {
                    continue;
                }

                // Disable filtering by firstname if anonymized.
                if ($field->_name == 'lastname' && !(mod_studentquiz_check_created_permission($this->cm->id) ||
                    !$this->report->is_anonymized())) {
                    continue;
                }

                // Respect leading and ending ',' for the tagarray as provided by tag_column.php.
                if ($field->_name == 'tagarray') {
                    foreach ($sqldata[1] as $key => $value) {
                        if (!empty($value)) {
                            $sqldata[1][$key] = "%,$value,%";
                        } else {
                            $sqldata[0] = "$field->_name IS NULL";
                        }
                    }
                }

                // TODO: cleanup that buggy filter function to remove this!
                // The user_filter_checkbox class has a buggy get_sql_filter function.
                if ($field->_name == 'createdby') {
                    $sqldata = array($field->_name . ' = ' . intval($data['value']), array());
                }

                if (is_array($sqldata)) {
                    $sqldata[0] = str_replace($field->_name, $this->get_sql_field($field->_name)
                        , $sqldata[0]);
                    $sqldata[0] = $this->get_special_sql($sqldata[0], $field->_name);
                    $this->tests[] = '((' . $sqldata[0] . '))';
                    $this->params = array_merge($this->params, $sqldata[1]);
                }
            }
        }
    }

    /**
     * Replaces special fields with additional sql instructions in the query
     *
     * @param string $sqldata the sql query
     * @param string $name affected field name
     * @return string modified sql query
     */
    private function get_special_sql($sqldata, $name) {
        if (substr($sqldata, 0, 12) === 'mydifficulty') {
            return str_replace('mydifficulty', 'ROUND(1 - (sp.correctattempts / sp.attempts),2)', $sqldata);
        }
        if ($name == "onlynew") {
            return str_replace('myattempts', 'sp.attempts', $sqldata);
        }
        return $sqldata;
    }

    /**
     * Replaces fields with additional sql instructions in place of the field
     *
     * @param string $name affected field name
     * @return string modified sql query
     */
    private function get_sql_field($name) {
        if (substr($name, 0, 12) === 'mydifficulty') {
            return str_replace('mydifficulty', 'ROUND(1 - (sp.correctattempts / sp.attempts),2)', $name);
        }
        if (substr($name, 0, 10) === 'myattempts') {
            return 'sp.attempts';
        }
        return $this->get_sql_table_prefix($name) . $name;
    }


    /**
     * Get the sql table prefix
     *
     * @param string $name
     * @return string return sql prefix
     */
    private function get_sql_table_prefix($name) {
        switch($name){
            case 'difficultylevel':
                return 'dl.';
            case 'rate':
                return 'vo.';
            case 'comment':
                return 'co.';
            case 'state':
                return 'sqs.';
            case 'firstname':
            case 'lastname':
                return 'uc.';
            case 'lastanswercorrect':
                return 'sp.';
            case 'mydifficulty':
                return 'mydiffs.';
            case 'myattempts':
                return 'myatts.';
            case 'myrate':
                return 'myrate.';
            case 'tagarray':
                return 'tags.';
            default;
                return 'q.';
        }
    }

    /**
     * Provide SQL fragment to be ANDed into the WHERE clause to filter which questions are shown.
     * @return string SQL fragment. Must use named parameters.
     */
    public function where() {
        return implode(' AND ', $this->tests);
    }

    /**
     * Return parameters to be bound to the above WHERE clause fragment.
     * @return array parameter name => value.
     */
    public function params() {
        return $this->params;
    }
}
