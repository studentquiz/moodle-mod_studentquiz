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
            'maxlength',255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        $mform->addElement('header', 'studentranking', get_string('quiz_advanced_settings_header', 'studentquiz'));

        // Field anonymous Ranking.
        $mform->addElement('checkbox', 'anonymrank',
            get_string('anonymous_checkbox_label', 'studentquiz'));
        $mform->setType('anonymrank', PARAM_INT);
        $mform->addHelpButton('anonymrank', 'anonymrankhelp', 'studentquiz');
        $mform->setDefault('anonymrank', 1);

        // Field questionquantifier.
        $mform->addElement('text', 'questionquantifier',
            get_string('settings_questionquantifier_label', 'studentquiz'));
        $mform->setType('questionquantifier', PARAM_FLOAT);
        $mform->addHelpButton('questionquantifier', 'settings_questionquantifier', 'studentquiz');
        $mform->setDefault('questionquantifier',
            get_config('studentquiz', 'studentquiz_add_question_quantifier'));

        // Field votequantifier.
        $mform->addElement('text', 'votequantifier',
            get_string('settings_votequantifier_label', 'studentquiz'));
        $mform->setType('votequantifier', PARAM_FLOAT);
        $mform->addHelpButton('votequantifier', 'settings_votequantifier', 'studentquiz');
        $mform->setDefault('votequantifier',
            get_config('studentquiz', 'studentquiz_vote_quantifier'));

        // Field correctanswerquantifier.
        $mform->addElement('text', 'correctanswerquantifier',
            get_string('settings_correctanswerquantifier_label', 'studentquiz'));
        $mform->setType('correctanswerquantifier', PARAM_FLOAT);
        $mform->addHelpButton('correctanswerquantifier', 'settings_correctanswerquantifier', 'studentquiz');
        $mform->setDefault('correctanswerquantifier',
            get_config('studentquiz', 'studentquiz_correct_answered_question_quantifier'));

        // Field incorrectanswerquantifier.
        $mform->addElement('text', 'incorrectanswerquantifier',
            get_string('settings_incorrectanswerquantifier_label', 'studentquiz'));
        $mform->setType('incorrectanswerquantifier', PARAM_FLOAT);
        $mform->addHelpButton('incorrectanswerquantifier', 'settings_incorrectanswerquantifier', 'studentquiz');
        $mform->setDefault('incorrectanswerquantifier',
            get_config('studentquiz', 'studentquiz_incorrect_answered_question_quantifier'));

        // Select question behaviour.
        if (mod_studentquiz_has_behaviour()) {
            $mform->addElement('advcheckbox', 'quizpracticebehaviour',
                get_string('quizpracticebehaviour', 'studentquiz')
                , null, null, array(STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR, STUDENTQUIZ_BEHAVIOUR));
            $mform->setType('quizpracticebehaviour', PARAM_RAW);
            $mform->addHelpButton('quizpracticebehaviour', 'quizpracticebehaviourhelp', 'studentquiz');

            $mform->setDefault('quizpracticebehaviour', STUDENTQUIZ_BEHAVIOUR);
        } else {
            $mform->addElement('hidden', 'quizpracticebehaviour', STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR);
            $mform->setType('quizpracticebehaviour', PARAM_RAW);
        }

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
