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
 * The main StudentQuiz configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once(__DIR__ . '/locallib.php');

/**
 * Module instance settings form
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $defaultqtypes = [];
        $defaultqtypesdefined = false;
        if ($qtypesdata = get_config('studentquiz', 'defaultqtypes')) {
            // Default question types already defined in Administration setting.
            $defaultqtypesdefined = true;
            $defaultqtypes = explode(',', $qtypesdata);
        }

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('studentquizname', 'studentquiz'),
            array('size' => '64'));
        $mform->addHelpButton('name', 'studentquizname', 'studentquiz');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255),
            'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        $mform->addElement('header', 'sectionranking', get_string('settings_section_header_ranking', 'studentquiz'));

        // Field anonymous Ranking.
        $mform->addElement('checkbox', 'anonymrank',
            get_string('settings_anonymous_label', 'studentquiz'));
        $mform->setType('anonymrank', PARAM_INT);
        $mform->addHelpButton('anonymrank', 'settings_anonymous', 'studentquiz');
        $mform->setDefault('anonymrank', 1);

        // Select question behaviour. Removed in v3.0.0, unless a use-case needs this option.
        // Since there's no studentquiz behavior anymore, we could offer a selection.
        // But they have to be of type feedback (or support non-feedback behavior).

        // Field questionquantifier.
        $mform->addElement('text', 'questionquantifier',
            get_string('settings_questionquantifier', 'studentquiz'));
        $mform->setType('questionquantifier', PARAM_INT);
        $mform->addHelpButton('questionquantifier', 'settings_questionquantifier', 'studentquiz');
        $mform->setDefault('questionquantifier',
            get_config('studentquiz', 'addquestion'));

        // Field approvedquantifier.
        $mform->addElement('text', 'approvedquantifier',
            get_string('settings_approvedquantifier', 'studentquiz'));
        $mform->setType('approvedquantifier', PARAM_INT);
        $mform->addHelpButton('approvedquantifier', 'settings_approvedquantifier', 'studentquiz');
        $mform->setDefault('approvedquantifier',
            get_config('studentquiz', 'approved'));

        // Field ratequantifier.
        $mform->addElement('text', 'ratequantifier',
            get_string('settings_ratequantifier', 'studentquiz'));
        $mform->setType('ratequantifier', PARAM_INT);
        $mform->addHelpButton('ratequantifier', 'settings_ratequantifier', 'studentquiz');
        $mform->setDefault('ratequantifier',
            get_config('studentquiz', 'rate'));

        // Field correctanswerquantifier.
        $mform->addElement('text', 'correctanswerquantifier',
            get_string('settings_lastcorrectanswerquantifier', 'studentquiz'));
        $mform->setType('correctanswerquantifier', PARAM_INT);
        $mform->addHelpButton('correctanswerquantifier', 'settings_lastcorrectanswerquantifier', 'studentquiz');
        $mform->setDefault('correctanswerquantifier',
            get_config('studentquiz', 'correctanswered'));

        // Field incorrectanswerquantifier.
        $mform->addElement('text', 'incorrectanswerquantifier',
            get_string('settings_lastincorrectanswerquantifier', 'studentquiz'));
        $mform->setType('incorrectanswerquantifier', PARAM_INT);
        $mform->addHelpButton('incorrectanswerquantifier', 'settings_lastincorrectanswerquantifier', 'studentquiz');
        $mform->setDefault('incorrectanswerquantifier',
            get_config('studentquiz', 'incorrectanswered'));

        // Selection for excluded roles.
        $defaultexcluderoles = explode(',', get_config('studentquiz', 'excluderoles'));
        $excluderolesgroup = array();
        foreach (mod_studentquiz_get_roles() as $role => $name) {
            $excluderolesgroup[] =& $mform->createElement('checkbox', $role, '', $name);
            if (in_array($role, $defaultexcluderoles)) {
                // Default question types already defined in Administration setting.
                // This question type was enable by default in Administration setting.
                $mform->setDefault("excluderoles[" . $role . "]", 1);
            }
        }
        $mform->addGroup($excluderolesgroup, 'excluderoles', get_string('settings_excluderoles', 'studentquiz'));
        $mform->addHelpButton('excluderoles', 'settings_excluderoles', 'studentquiz');

        $mform->addElement('header', 'sectionquestion', get_string('settings_section_header_question', 'studentquiz'));

        // Selection for allowed question types.
        $allowedgroup = array();
        $allowedgroup[] =& $mform->createElement('checkbox', "ALL", '', get_string('settings_allowallqtypes', 'studentquiz'));
        foreach (mod_studentquiz_get_question_types() as $qtype => $name) {
            $allowedgroup[] =& $mform->createElement('checkbox', $qtype, '', $name);
            if ($defaultqtypesdefined && in_array($qtype, $defaultqtypes)) {
                // Default question types already defined in Administration setting.
                // This question type was enable by default in Administration setting.
                $mform->setDefault("allowedqtypes[" . $qtype . "]", 1);
            }
        }
        if (!$defaultqtypesdefined) {
            // Default question types was not defined in Administration setting.
            // Set to ALL question types by default.
            $mform->setDefault("allowedqtypes[ALL]", 1);
        }
        $mform->addGroup($allowedgroup, 'allowedqtypes', get_string('settings_allowedqtypes', 'studentquiz'));
        $mform->disabledIf('allowedqtypes', "allowedqtypes[ALL]", 'checked');
        $mform->addHelpButton('allowedqtypes', 'settings_allowedqtypes', 'studentquiz');

        // Field force rating.
        $mform->addElement('checkbox', 'forcerating', get_string('settings_forcerating', 'studentquiz'));
        $mform->setType('forcerating', PARAM_INT);
        $mform->addHelpButton('forcerating', 'settings_forcerating', 'studentquiz');
        $mform->setDefault('forcerating', get_config('studentquiz', 'forcerating'));

        // Field force commenting
        $mform->addElement('checkbox', 'forcecommenting', get_string('settings_forcecommenting', 'studentquiz'));
        $mform->setType('forcecommenting', PARAM_INT);
        $mform->addHelpButton('forcecommenting', 'settings_forcecommenting', 'studentquiz');
        $mform->setDefault('forcecommenting', get_config('studentquiz', 'forcecommenting'));

        // Availability.
        $mform->addElement('header', 'availability', get_string('availability', 'moodle'));
        $mform->addElement('date_time_selector', 'opensubmissionfrom',
                get_string('settings_availability_open_submission_from', 'studentquiz'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'closesubmissionfrom',
                get_string('settings_availability_close_submission_from', 'studentquiz'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'openansweringfrom',
                get_string('settings_availability_open_answering_from', 'studentquiz'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'closeansweringfrom',
                get_string('settings_availability_close_answering_from', 'studentquiz'), ['optional' => true]);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * TODO: describe this
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        // Comma separated should be fine for our case.
        if (isset($defaultvalues['allowedqtypes'])) {
            $enabled = explode(',', $defaultvalues['allowedqtypes']);
            foreach (array_keys(mod_studentquiz_get_question_types()) as $qtype) {
                $defaultvalues["allowedqtypes[$qtype]"] = (int)in_array($qtype, $enabled);
            }
            $defaultvalues["allowedqtypes[ALL]"] = (int)in_array("ALL", $enabled);
        }
        if (isset($defaultvalues['excluderoles'])) {
            $enabled = explode(',', $defaultvalues['excluderoles']);
            foreach (array_keys(mod_studentquiz_get_roles()) as $role) {
                $defaultvalues["excluderoles[$role]"] = (int)in_array($role, $enabled);
            }
        }
    }

    /**
     * TODO: describe this
     * @param array $data
     * @param array $files
     * @return array $errors
     */
    public function validation($data, $files) {
        $errors = array();
        if (!isset($data['allowedqtypes'])) {
            $errors['allowedqtypes'] = get_string('needtoallowatleastoneqtype', 'studentquiz');
        }
        if ($data['opensubmissionfrom'] > 0 && $data['closesubmissionfrom'] > 0 &&
                $data['opensubmissionfrom'] >= $data['closesubmissionfrom']) {
            $errors['closesubmissionfrom'] = get_string('submissionendbeforestart', 'studentquiz');
        }
        if ($data['openansweringfrom'] > 0 && $data['closeansweringfrom'] > 0 &&
                $data['openansweringfrom'] >= $data['closeansweringfrom']) {
            $errors['closeansweringfrom'] = get_string('answeringndbeforestart', 'studentquiz');
        }
        return $errors;
    }
}
