<?php

require_once("$CFG->libdir/formslib.php");

class mod_studentquiz_startattempt_form extends moodleform {

    public function definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $select = $mform->addElement('select', 'categories', get_string('category'), $this->_customdata['categories']);

        $mform->addElement('header', 'studentquiz_practice_behaviour', get_string('practice_question_behaviour', 'studentquiz'));
        $select = $mform->addElement('select', 'behaviour', get_string('practice_behaviour', 'studentquiz'), $this->_customdata['behaviours']);

        $this->add_action_buttons(true, get_string('practice_start', 'studentquiz'));

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'instanceid', $this->_customdata['instanceid']);
        $mform->setType('instanceid', PARAM_INT);
    }
}
