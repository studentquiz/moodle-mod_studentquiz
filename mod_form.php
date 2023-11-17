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
require_once(__DIR__ . '/reportlib.php');

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
        $this->standard_intro_elements();

        // Field question publishing.
        $publishingoptions = [
                1 => get_string('setting_question_publishing_automatic', 'studentquiz'),
                0 => get_string('setting_question_publishing_require_approval', 'studentquiz')
        ];
        $mform->addElement('select', 'publishnewquestion', get_string('setting_question_publishing', 'studentquiz'),
                $publishingoptions);
        $mform->addHelpButton('publishnewquestion', 'setting_question_publishing', 'studentquiz');
        $mform->setType('publishnewquestion', PARAM_INT);
        $mform->setDefault('publishnewquestion', 1);

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
        $rolescanbeexculded = mod_studentquiz_report::get_roles_which_can_be_exculded();
        if (!empty($rolescanbeexculded)) {
            $excluderolesgroup = [];
            foreach ($rolescanbeexculded as $role => $roleinfo) {
                $excluderolesgroup[] = $mform->createElement('checkbox', $role, '', $roleinfo['name']);
                // Default question types already defined in Administration setting.
                // This question type was enable by default in Administration setting.
                $mform->setDefault("excluderoles[" . $role . "]", $roleinfo['default']);
            }
            $mform->addGroup($excluderolesgroup, 'excluderoles', get_string('settings_excluderoles', 'studentquiz'));
            $mform->addHelpButton('excluderoles', 'settings_excluderoles', 'studentquiz');
        }

        $mform->addElement('header', 'sectionquestion', get_string('settings_section_header_question', 'studentquiz'));

        // Selection for allowed question types.
        $allowedgroup = array();
        $allowedgroup[] = $mform->createElement('checkbox', "ALL", '', get_string('settings_allowallqtypes', 'studentquiz'));
        foreach (mod_studentquiz_get_question_types() as $qtype => $name) {
            $allowedgroup[] = $mform->createElement('checkbox', $qtype, '', $name);
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

        // Comment and rating sections.
        $mform->addElement('header', 'sectioncomment', get_string('settings_section_header_comment_rating', 'studentquiz'));

        // Field force rating.
        $mform->addElement('checkbox', 'forcerating', get_string('settings_forcerating', 'studentquiz'));
        $mform->setType('forcerating', PARAM_INT);
        $mform->addHelpButton('forcerating', 'settings_forcerating', 'studentquiz');
        $mform->setDefault('forcerating', get_config('studentquiz', 'forcerating'));

        // Field force commenting.
        $mform->addElement('checkbox', 'forcecommenting', get_string('settings_forcecommenting', 'studentquiz'));
        $mform->setType('forcecommenting', PARAM_INT);
        $mform->addHelpButton('forcecommenting', 'settings_forcecommenting', 'studentquiz');
        $mform->setDefault('forcecommenting', get_config('studentquiz', 'forcecommenting'));

        // Field enable private commenting.
        $mform->addElement('checkbox', 'privatecommenting', get_string('settings_privatecommenting', 'studentquiz'));
        $mform->setType('privatecommenting', PARAM_INT);
        $mform->addHelpButton('privatecommenting', 'settings_privatecommenting', 'studentquiz');
        $mform->setDefault('privatecommenting', get_config('studentquiz', 'showprivatecomment'));

        // Comment deletion period.
        $mform->addElement('select', 'commentdeletionperiod',
                get_string('settings_commentdeletionperiod', 'studentquiz'),
                \mod_studentquiz\commentarea\container::get_deletion_period_options()
        );
        $mform->setType('commentdeletionperiod', PARAM_INT);
        $mform->addHelpButton('commentdeletionperiod', 'settings_commentdeletionperiod', 'studentquiz');
        $mform->setDefault('commentdeletionperiod', get_config('studentquiz', 'commentediting_deletionperiod'));

        // Email address for reporting unacceptable comment for this studentquiz, default is blank.
        $mform->addElement('text', 'reportingemail', get_string('settings_reportingemail', 'studentquiz'), ['size' => 64]);
        $mform->setType('reportingemail', PARAM_NOTAGS);
        $mform->addRule('reportingemail', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('reportingemail', 'settings_reportingemail', 'studentquiz');

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

        // Notification.
        $mform->addElement('header', 'notification', get_string('settings_notification', 'studentquiz'));
        // Field email digest type.
        $digesttypes = [
                0 => get_string('settings_email_digest_type_no_digest', 'studentquiz'),
                1 => get_string('settings_email_digest_type_daily_digest', 'studentquiz'),
                2 => get_string('settings_email_digest_type_weekly_digest', 'studentquiz')
        ];
        $mform->addElement('select', 'digesttype', get_string('settings_email_digest_type', 'studentquiz'),
                $digesttypes);
        $mform->addHelpButton('digesttype', 'settings_email_digest_type', 'studentquiz');

        // Field first day of week.
        $daysofweek = [
                0 => get_string('sunday', 'calendar'),
                1 => get_string('monday', 'calendar'),
                2 => get_string('tuesday', 'calendar'),
                3 => get_string('wednesday', 'calendar'),
                4 => get_string('thursday', 'calendar'),
                5 => get_string('friday', 'calendar'),
                6 => get_string('saturday', 'calendar')
        ];
        $mform->addElement('select', 'digestfirstday', get_string('settings_email_digest_first_day', 'studentquiz'), $daysofweek);
        $mform->addHelpButton('digestfirstday', 'settings_email_digest_first_day', 'studentquiz');
        $mform->setDefault('digestfirstday', 1);
        $mform->disabledIf('digestfirstday', 'digesttype', 'neq', 2);

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
        $defaultvalues['completionpointenabled'] = !empty($defaultvalues['completionpoint']) ? 1 : 0;
        if (empty($defaultvalues['completionpoint'])) {
            $defaultvalues['completionpoint'] = 1;
        }
        $defaultvalues['completionquestionpublishedenabled'] = !empty($defaultvalues['completionquestionpublished']) ? 1
            : 0;
        if (empty($defaultvalues['completionquestionpublished'])) {
            $defaultvalues['completionquestionpublished'] = 1;
        }
        $defaultvalues['completionquestionapprovedenabled'] = !empty($defaultvalues['completionquestionapproved']) ? 1
            : 0;
        if (empty($defaultvalues['completionquestionapproved'])) {
            $defaultvalues['completionquestionapproved'] = 1;
        }
    }

    /**
     * List of added element names, or names of wrapping group elements.
     *
     * @return array List of added element names, or names of wrapping group elements.
     */
    public function add_completion_rules(): array {
        $mform =& $this->_form;

        // Require point.
        $group = [];
        $group[] =& $mform->createElement('checkbox', 'completionpointenabled', '',
            get_string('completionpoint', 'mod_studentquiz'));
        $group[] =& $mform->createElement('text', 'completionpoint', '', ['size' => 3]);
        $mform->setType('completionpoint', PARAM_INT);
        $mform->addGroup($group, 'completionpointgroup', get_string('completionpointgroup',
            'mod_studentquiz'), [' '], false);
        $mform->addHelpButton('completionpointgroup', 'completionpointgroup',
            'mod_studentquiz');
        $mform->disabledIf('completionpoint', 'completionpointenabled', 'notchecked');

        // Require published questions.
        $group = [];
        $group[] =& $mform->createElement('checkbox', 'completionquestionpublishedenabled', '',
            get_string('completionquestionpublished', 'mod_studentquiz'));
        $group[] =& $mform->createElement('text', 'completionquestionpublished', '',
            ['size' => 3]);
        $mform->setType('completionquestionpublished', PARAM_INT);
        $mform->addGroup($group, 'completionquestionpublishedgroup',
            get_string('completionquestionpublishedgroup', 'mod_studentquiz'),
                [' '], false);
        $mform->addHelpButton('completionquestionpublishedgroup',
            'completionquestionpublishedgroup', 'mod_studentquiz');
        $mform->disabledIf('completionquestionpublished', 'completionquestionpublishedenabled',
            'notchecked');

        // Require created approved questions.
        $group = [];
        $group[] =& $mform->createElement('checkbox',
            'completionquestionapprovedenabled', '', get_string('completionquestionapproved',
                'mod_studentquiz'));
        $group[] =& $mform->createElement('text',
            'completionquestionapproved', '', ['size' => 3]);
        $mform->setType('completionquestionapproved', PARAM_INT);
        $mform->addGroup($group, 'completionquestionapprovedgroup',
            get_string('completionquestionapprovedgroup', 'mod_studentquiz'), [' '],
            false);
        $mform->addHelpButton('completionquestionapprovedgroup',
            'completionquestionapprovedgroup', 'mod_studentquiz');
        $mform->disabledIf('completionquestionapproved',
            'completionquestionapprovedenabled', 'notchecked');

        return ['completionpointgroup', 'completionquestionpublishedgroup', 'completionquestionapprovedgroup'];
    }

    /**
     * Called during validation to see whether some activity-specific completion rules are selected.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data): bool {
        return (!empty($data['completionpointenabled'])
            && $data['completionpoint'] != 0)
            || (!empty($data['completionquestionpublishedenabled'])
            && $data['completionquestionpublished'] != 0)
            || (!empty($data['completionquestionapprovedenabled'])
            && $data['completionquestionapproved'] != 0);
    }

    /**
     * Validation of studentquiz_mod_form.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

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
        if (!empty($data['reportingemail']) && !$this->validate_emails($data['reportingemail'])) {
            $errors['reportingemail'] = get_string('invalidemail', 'studentquiz');
        }
        if ($data['groupmode'] == VISIBLEGROUPS) {
            $errors['groupmode'] = get_string('visiblegroupnotyetsupport', 'studentquiz');
        }
        return $errors;
    }

    /**
     * Validate string contains validate email or multiple emails.
     *
     * @param string $emails - Example: test@gmail.com;test1@gmail.com.
     * @return bool
     */
    private function validate_emails($emails) {
        $list = explode(';' , $emails);
        foreach ($list as $email) {
            if (!validate_email($email)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * @return bool|object
     */
    public function get_data() {
        $data = parent::get_data();
        // Set the reportingemail to null if empty so that they are consistency.
        if ($data) {
            if (empty($data->reportingemail)) {
                $data->reportingemail = null;
            }
        }

        // Turn off completion settings if the checkboxes aren't ticked.
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completionpointenabled) || !$autocompletion) {
                $data->completionpoint = 0;
            }
            if (empty($data->completionquestionpublishedenabled) || !$autocompletion) {
                $data->completionquestionpublished = 0;
            }
            if (empty($data->completionquestionapprovedenabled) || !$autocompletion) {
                $data->completionquestionapproved = 0;
            }
        }

        return $data;
    }

}
