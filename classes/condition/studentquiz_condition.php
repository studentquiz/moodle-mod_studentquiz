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
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\condition;
defined('MOODLE_INTERNAL') || die();


/**
 * This class controls from which category questions are listed.
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_condition extends \core_question\bank\search\condition {

    /* Due to fix_sql_params not accepting repeated use of named params,
       we need to get unique names for params that will be used more than
       once...

       init() from parent class duplicated here as we can't call it directly
       (private) :-P

       where() overridden with call to init() followed by call to parent
       where()...

       params() always returns $this->params, which doesn't change between
       calls to get_in_or_equal, so don't need to fix anything there.
       Which is fortunate, as there'd be no way to keep where() and params()
       in sync.
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

    protected $cm;
    // Search condition depends on filterform.
    protected $filterform;

    protected $studentquiz;

    /** @var  \mod_studentquiz_report */
    protected $report;

    protected $tests;

    protected $params;

    protected $isfilteractive = false;

    public function is_filter_active() {
        return $this->isfilteractive;
    }

    protected $istagfilteractive = false;

    public function is_tag_filter_active() {
        return $this->istagfilteractive;
    }

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

                // Disable filtering by firstname if anonymized
                if ($field->_name == 'firstname' && !(mod_studentquiz_check_created_permission($this->cm->id) || !$this->report->is_anonymized())) {
                    continue;
                }

                // Disable filtering by firstname if anonymized
                if ($field->_name == 'lastname' && !(mod_studentquiz_check_created_permission($this->cm->id) || !$this->report->is_anonymized())) {
                    continue;
                }

                if ($field->_name == 'tagname') {
                    $this->istagfilteractive = true;
                    $this->tagnamefield = $sqldata;
                    // TODO: ugly override for PoC!
                    $field->_name = 'tags';
                }

                // TODO: cleanup that buggy filter function to remove this!
                // The user_filter_checkbox class has a buggy get_sql_filter function.
                if ($field->_name == 'createdby' || $field->_name == 'approved') {
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

    private function get_special_sql($sqldata, $name) {
        if (substr($sqldata, 0, 12) === 'mydifficulty') {
            return str_replace('mydifficulty', 'ROUND(1 - (sp.correctattempts / sp.attempts),2)', $sqldata);
        }
        if ($name == "onlynew") {
            return str_replace('myattempts', 'sp.attempts', $sqldata);
        }
        return $sqldata;
    }

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
     * @param string $name
     * @return string return sql prefix
     */
    private function get_sql_table_prefix($name) {
        switch($name){
            case 'difficultylevel':
                return 'dl.';
            case 'rate':
                return 'vo.';
            case 'practice':
                return 'pr.';
            case 'comment':
                return 'co.';
            case 'approved':
                return 'ap.';
            case 'firstname':
            case 'lastname':
                return 'uc.';
            case 'mylastattempt':
                return 'mylatts.';
            case 'mydifficulty':
                return 'mydiffs.';
            case 'myattempts':
                return 'myatts.';
            case 'myrate':
                return 'myrate.';
            case 'tags':
                return 'tags.';
            case 'searchtag':
                return 'tags.';
            default;
                return 'q.';
        }
    }

    /**
     * @Return an SQL fragment to be ANDed into the WHERE clause to filter which questions are shown.
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
