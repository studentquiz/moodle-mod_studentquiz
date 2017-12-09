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
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/user/filters/text.php');
require_once($CFG->dirroot.'/user/filters/date.php');

/**
 * Question bank filter form intance
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_question_bank_filter_form extends moodleform {

    /**
     * Filter fields of question bank
     * @var array
     */
    private $fields;

    /**
     * Question_bank_filter_form constructor.
     * @param mixed|null $fields filters
     * @param mixed $action the action attribute for the form. If empty defaults to auto detect the
     *              current url. If a moodle_url object then outputs params as hidden variables.
     * @param mixed $customdata if your form defintion method needs access to data such as $course
     *              $cm, etc. to construct the form definition then pass it in this array. You can
     *              use globals for somethings.
     * @param string $method if you set this to anything other than 'post' then _GET and _POST will
     *               be merged and used as incoming data to the form.
     * @param string $target target frame for form submission. You will rarely use this. Don't use
     *               it if you don't need to as the target attribute is deprecated in xhtml strict.
     * @param mixed $attributes you can pass a string of html attributes here or an array.
     * @param bool $editable
     */
    public function __construct($fields, $action=null, $customdata=null, $method='post', $target='', $attributes=null,
                                $editable=true) {
        $this->_customdata = $customdata;
        $this->fields = $fields;
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);
    }

    /**
     * Get fields
     * @return array fields filter
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'filtertab', get_string('filter', 'studentquiz'));
        $mform->setExpanded('filtertab', true);
        $fastfilters = array();
        foreach ($this->fields as $field) {
            if ($field instanceof toggle_filter_checkbox) {
                $field->setup_form_in_group($mform, $fastfilters);
            }
        }
        $mform->addGroup($fastfilters, 'fastfilters', get_string('filter_label_fast_filters', 'studentquiz'), ' ', false);
        foreach ($this->fields as $field) {
            if (!$field instanceof toggle_filter_checkbox) {
                $field->setupForm($mform);
            }
        }
        $group = array();
        $group[] = $mform->createElement('submit', 'submitbutton', get_string('filter'));
        $group[] = $mform->createElement('submit', 'resetbutton', get_string('reset'));
        $mform->addGroup($group, 'buttons', '', ' ', false);

        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_RAW);
    }

    /**
     * Set form defaults
     */
    public function set_defaults() {
        $submission = array();
        foreach ($this->fields as $field) {
            if (isset($_POST[$field->_name])) {
                $submission[$field->_name] = $_POST[$field->_name];
            }
            if (isset($_POST[$field->_name . '_op'])) {
                $submission[$field->_name . '_op'] = $_POST[$field->_name . '_op'];
            }
        }
        if (isset($_POST['timecreated_sdt'])) {
            $submission['timecreated_sdt'] = $_POST['timecreated_sdt'];
        }
        if (isset($_POST['timecreated_edt'])) {
            $submission['timecreated_edt'] = $_POST['timecreated_edt'];
        }
        if (isset($_POST['createdby'])) {
            $submission['createdby'] = $_POST['createdby'];
        }
        $this->_form->updateSubmission($submission, array());
    }
}

class toggle_filter_checkbox extends user_filter_checkbox {

    protected $operator;

    protected $value;

    protected $helptext;

    /**
     * A toggle filter applies adds a hard coded test to the filter set.
     *
     * @param string $name the name of the filter instance
     * @param string $label the label of the filter instance
     * @param boolean $advanced advanced form element flag
     * @param mixed $field user table field/fields name for comparison
     * @param array $disableelements name of fields which should be disabled if this checkbox is checked.
     * @param int $operator key 0 : >=,
     * @param mixed $value text or number for comparison
     *
     */
    public function __construct($name, $label, $advanced, $field, $disableelements, $operator, $value, $helptext = '') {
        parent::__construct($name, $label, $advanced, $field, $disableelements);
        $this->field   = $field;
        $this->operator = $operator;
        $this->value = $value;
        $this->helptext = $helptext;
    }

    public function setup_form_in_group(&$mform, &$group) {
        $titledlabel = \html_writer::span($this->_label, '', array('title' => $this->helptext));
        $element = $mform->createElement('checkbox', $this->_name, null, $titledlabel, array('class' => 'toggle'));

        if ($this->_advanced) {
            $mform->setAdvanced($this->_name);
        }
        // Check if disable if options are set. if yes then set rules.
        if (!empty($this->disableelements) && is_array($this->disableelements)) {
            foreach ($this->disableelements as $disableelement) {
                $mform->disabledIf($disableelement, $this->_name, 'checked');
            }
        }
        $group[] = $element;
    }

    public function get_sql_filter($data) {
        switch($this->operator) {
            case 0:
                $res = "($this->field IS null OR $this->field = 0)";
                break;
            case 1:
                $res = "$this->field >= $this->value";
                break;
            case 2:
                $res = "$this->field = $this->value";
                break;
            default:
                $res = '';
        }
        return array($res, array());
    }
}


class user_filter_tag extends user_filter_text {

    /**
     * Returns the condition to be used with SQL where
     * @param array $data filter settings
     * @return array sql string and $params
     */
    public function get_sql_filter($data) {
        static $counter = 0;
        $name = 'ex_tag' . $counter++;

        // TODO Override for PoC.
        $name = 'searchtag';

        $operator = $data['operator'];

        // Search is case insensitive!
        $value = strtolower($data['value']);

        $field = $this->_field;

        // TODO: Ugly override for PoC.
        $field = 'tags';

        $params = array();

        switch ($operator) {
            case 0: // Contains.
                $res = ' searchtag > 0 ';
                $params[$name] = "%$value%";
                break;
            case 1: // Does not contain.
                $res = ' (searchtag = 0 or searchtag is null) ';
                $params[$name] = "%$value%";
                break;
            case 2: // Equal to.
                $res = '  searchtag = 1 ';
                $params[$name] = "$value";
                break;
            case 3: // Starts with.
                $res = '  searchtag > 0 ';
                $params[$name] = "$value%";
                break;
            case 4: // Ends with.
                $res = ' searchtag > 0 ';
                $params[$name] = "%$value";
                break;
            case 5: // Empty.
                $res = ' (tags = 0 or tags is null) ';
                $params[$name] = "-ignore-";
                break;
            default:
                return '';
        }
        return array($res, $params);
    }

}

/**
 * Number filter
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_filter_number extends user_filter_text {
    /**
     * Returns an array of comparison operators
     * @return array of comparison operators
     */
    public function getOperators() { // @codingStandardsIgnoreLine
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
        static $counter = 0;
        $name1 = 'ex_text_vo'.$counter++;
        $name2 = 'ex_text_vo'.$counter++;
        $operator = $data['operator'];
        $value    = $data['value'];
        $field    = $this->_name;

        $params = array();

        if ($operator != 5 and $value === '') {
            return '';
        }

        // When a count doesn't find anything, it will return NULL, so we have to account for that.
        switch($operator) {
            case 0: // Higher.
                $res = "$field > :$name1 OR ($field IS NULL AND 0 > :$name2)";
                $params[$name1] = $value;
                $params[$name2] = $value;
                break;
            case 1: // Lower.
                $res = "$field < :$name1 OR ($field IS NULL AND 0 < :$name2)";
                $params[$name1] = $value;
                $params[$name2] = $value;
                break;
            case 2: // Equal to.
                $res = $DB->sql_like($field, ":$name1", false, false) .
                       " OR ($field IS NULL AND " .
                       $DB->sql_like("0", ":$name2", false, false) . ")";
                $params[$name1] = $value;
                $params[$name2] = $value;
                break;
            default:
                return '';
        }
        return array($res, $params);
    }
}

/**
 * Class user_filter_percent Users can enter a number of percent, database is queried for unit value.
 */
class user_filter_percent extends user_filter_number {
    public function get_sql_filter($data) {
        $val = round($data->value, 0);
        if ($val > 100 or $val < 0) {
            return '';
        }
        if ($val > 1) {
            $data->value = $val / 100;
        }
        return parent::get_sql_filter($data);
    }
}
