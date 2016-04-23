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
 * The question bank custom filter
 *
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/user/filters/text.php');
require_once($CFG->dirroot.'/user/filters/date.php');

/**
 * Module instance settings form
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_bank_filter_form extends moodleform {
    private $_fields;

    public function __construct($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true) {
        $this->set_fields();
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);
    }

    public function set_fields() {
        $this->_fields = array();

        $this->_fields[] = new \user_filter_text('name', get_string('filter_label_question', 'studentquiz'), false, 'name');

        if($this->_customdata['isadmin']) {
            $this->_fields[] = new \user_filter_text('creatorfirstname', get_string('filter_label_firstname', 'studentquiz'), true, 'creatorfirstname');
            $this->_fields[] = new \user_filter_text('creatorlastname', get_string('filter_label_surname', 'studentquiz'), true, 'creatorlastname');
        }

        $this->_fields[] = new \user_filter_date('timecreated', get_string('filter_label_createdate', 'studentquiz'), true, 'timecreated');
        $this->_fields[] = new \user_filter_text('tag_name', get_string('filter_label_tags', 'studentquiz'), true, 'tag_name');
        $this->_fields[] = new \user_filter_vote('studentquiz_vote_point', get_string('filter_label_votes', 'studentquiz'), true, 'studentquiz_vote_point');

    }

    public function getFields() {
        return $this->_fields;
    }
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'looking for freedom', get_string('newfilter', 'filters'));

        foreach($this->_fields as $field) {
            $field->setupForm($mform);
        }


        $group = array();
        $group[] = $mform->createElement('submit', 'submitbutton', get_string('filter'));
        $group[] = $mform->createElement('submit', 'resetbutton', get_string('reset'));
        $mform->addGroup($group, 'buttons', '', ' ', false);

        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_RAW);
    }
}

class user_filter_vote extends user_filter_text {
    /**
     * Returns an array of comparison operators
     * @return array of comparison operators
     */
    public function getOperators() {
        return array(0 => get_string('filter_ishigher', 'studentquiz'),
            1 => get_string('filter_islower', 'studentquiz'),
            2 => get_string('isequalto', 'filters'));
    }

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array sql string and $params
     */
    public function get_sql_filter($data) {
        global $DB;
        $name = 'ex_text_vote';

        $operator = $data['operator'];
        $value    = $data['value'];
        $field    = $this->_field;

        $params = array();

        if ($operator != 5 and $value === '') {
            return '';
        }

        switch($operator) {
            case 0: // higher.
                $res = $field . "> :$name";
                $params[$name] = $value;
                break;
            case 1: // lower.
                $res = $field . "< :$name";
                $params[$name] = $value;
                break;
            case 2: // Equal to.
                $res = $DB->sql_like($field, ":$name", false, false);
                $params[$name] = $value;
                break;
            default:
                return '';
        }
        return array($res, $params);
    }
}