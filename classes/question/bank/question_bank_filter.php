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

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/user/filters/text.php');
require_once($CFG->dirroot . '/user/filters/date.php');

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

}

/**
 * Class studentquiz_user_filter_text
 *
 * @package    mod_studentquiz
 * @copyright  2019 HSR (http://www.hsr.ch)
 * @author     Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_user_filter_text extends user_filter_text {

    /**
     * Adds controls specific to this filter in the form.
     *
     * @param object $mform a MoodleForm object to setup
     * @throws coding_exception
     */
    // @codingStandardsIgnoreLine
    public function setupForm(&$mform) {
        parent::setupForm($mform);
        $group = $mform->getElement($this->_name.'_grp');
        if (!empty($group) && !($group instanceof HTML_QuickForm_Error)) {
            $groupelements = $group->getElements();
            if (count($groupelements) > 0) {
                $select = $groupelements[0];
                $select->setLabel(get_string('filter_advanced_element', 'studentquiz', $select->getLabel()));
            }
        }
    }

}

/**
 * Class studentquiz_user_filter_date
 *
 * @package    mod_studentquiz
 * @copyright  2019 HSR (http://www.hsr.ch)
 * @author     Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_user_filter_date extends user_filter_date {

    /**
     * Adds controls specific to this filter in the form.
     *
     * @param object $mform a MoodleForm object to setup
     */
    // @codingStandardsIgnoreLine
    public function setupForm(&$mform) {
        parent::setupForm($mform);
        $group = $mform->getElement($this->_name . '_grp');
        if (!empty($group) && !($group instanceof HTML_QuickForm_Error)) {
            $groupelements = $group->getElements();
            if (!empty($groupelements)) {
                $dateselector = array();
                foreach ($groupelements as $el) {
                    if ($el->getType() == 'date_selector') {
                        $dateselector = $el->getElements();
                        break;
                    }
                }
                if (!empty($dateselector)) {
                    $isbefore = optional_param('timecreated_sdt', 0, PARAM_INT);
                    if ($isbefore && $isbefore['enabled']) {
                        // The first active element is "Day" selection.
                        $targetelement = $dateselector[0];
                        $targetlabel = $targetelement->getLabel();
                        $targetelement->setLabel(get_string('filter_advanced_element', 'studentquiz', $targetlabel));
                    } else {
                        // The first active element in case optional checkbox is not enabled.
                        $targetelement = $dateselector[3];
                        if ($targetelement instanceof HTML_QuickForm_link) {
                            // It is "Calendar" link in case the calendar is gregorian.
                            $attrs = $targetelement->getAttributes();
                            $attrs['alt'] = get_string('filter_advanced_element', 'studentquiz');
                            $targetelement->setAttributes($attrs);
                        } else if ($targetelement instanceof HTML_QuickForm_input) {
                            // It is optional checkbox in case the calendar isn't gregorian.
                            $targetlabel = $targetelement->getLabel();
                            $targetelement->setLabel(get_string('filter_advanced_element', 'studentquiz', $targetlabel));
                        }
                    }
                }
                $this->screen_reader_helper($group, $groupelements);
            }
        }
    }

    /**
     * Improve screen reader.
     *
     * @param MoodleQuickForm_group $group
     * @param array $groupelements
     */
    private function screen_reader_helper(MoodleQuickForm_group $group, array $groupelements) {

        /** @var MoodleQuickForm_date_selector[] $dategroups */
        $dategroups = [];

        // Define variable for "is after", "is before" text.
        $isafter = $isbefore = null;

        // Loop through group elements to find date_selector array.
        // Has the text "is after", "is before" in array too.
        foreach ($groupelements as $el) {
            // First get all date groups in group elements.
            if ($el->getType() == 'date_selector') {
                $dategroups[] = $el;
                continue;
            }
            // Get 'is after' text.
            if ($el instanceof MoodleQuickForm_static && $el->getName() === $this->_name . '_s2') {
                $isafter = strip_tags($el->_text);
                continue;
            }
            // Get 'is before' text.
            if ($el instanceof MoodleQuickForm_static && $el->getName() === $this->_name . '_s5') {
                $isbefore = strip_tags($el->_text);
                continue;
            }
        }

        // Loop through date_selector array.
        // Some inputs don't have label + title correct, fix them.
        foreach ($dategroups as $dategroup) {
            $inputs = $dategroup->getElements();

            // First need to find the checkbox (to check calendar tab-able).
            $checkbox = false;
            foreach ($inputs as $input) {
                if ($input instanceof MoodleQuickForm_checkbox) {
                    $checkbox = $input;
                    break;
                }
            }

            foreach ($inputs as $input) {
                $rowtext = strpos($dategroup->getName(), '_sdt') ? $isafter : $isbefore;
                $creationtext = $group->getLabel();
                if ($input instanceof HTML_QuickForm_link) {
                    $attrs = $input->getAttributes();
                    $attrs['alt'] = $this->generate_creation_label($creationtext, $rowtext, $input->getName());
                    // Should check is checkbox, in case if cannot find one.
                    if ($checkbox instanceof MoodleQuickForm_checkbox && !$checkbox->getChecked()) {
                        $attrs['tabindex'] = -1;
                    }
                    $input->setAttributes($attrs);
                    continue;
                } else if ($input instanceof MoodleQuickForm_checkbox) {
                    $attrs = $input->getAttributes();
                    $attrs['title'] = $this->generate_creation_label($creationtext, $rowtext, $input->getText(), $input->getType());
                    $input->setAttributes($attrs);
                    continue;
                } else if ($input instanceof MoodleQuickForm_select) {
                    $label = $this->generate_creation_label($creationtext, $rowtext, $input->getLabel());
                    $input->setLabel($label);
                    continue;
                }
            }
        }
    }

    /**
     * Generate label string for screen reader.
     *
     * @param string $creationtext
     * @param string $rowtext
     * @param string $inputtext
     * @param string $inputtype
     */
    private function generate_creation_label($creationtext, $rowtext, $inputtext, $inputtype = '') {
        $data = new \stdClass();
        $data->creationtext = $creationtext;
        $data->rowtext = $rowtext;
        if ($inputtype) {
            $inputtextdata = new stdClass();
            $inputtextdata->inputtext = $inputtext;
            $inputtextdata->inputtype = $inputtype;
            $data->inputtext = \get_string('filter_label_question_creation_item_inputtext', 'studentquiz', $inputtextdata);
        } else {
            $data->inputtext = $inputtext;
        }
        return \get_string('filter_label_question_creation_item', 'studentquiz', $data);
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
        $disableelements = implode(',', $this->disableelements);
        $linktoggle = \html_writer::tag('a', $this->_label, ['href' => '#', 'class' => 'link-toggle',
                'title' => $this->helptext, 'for' => 'id_' . $this->_name, 'data-disableelements' => $disableelements]);
        $element = $mform->createElement('checkbox', $this->_name, null, $linktoggle, ['class' => 'toggle']);

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


class user_filter_tag extends studentquiz_user_filter_text {

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
                $res = ' (searchtag = 0 OR searchtag IS NULL) ';
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
                $res = ' (tags = 0 OR tags IS NULL) ';
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
class user_filter_number extends studentquiz_user_filter_text {
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
                $res = $DB->sql_equal($field, ":$name1", true, false)
                        . " OR ($field IS NULL AND " .
                        $DB->sql_equal("0", ":$name2", true, false) . ")";
                $params[$name1] = floatval($value);
                $params[$name2] = floatval($value);
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
