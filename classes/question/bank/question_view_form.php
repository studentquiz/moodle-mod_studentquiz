<?php

namespace mod_studentquiz\question\bank;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");


class question_view_form extends \moodleform {
    function definition() {
        $mform = $this->_form;

        // Text search box.
        $mform->addElement('text', 'search', get_string('search'));
        $mform->setType('search', PARAM_RAW);

        $group = array();
        $group[] = $mform->createElement('submit', 'submitbutton', get_string('filter'));
        $group[] = $mform->createElement('submit', 'resetbutton', get_string('reset'));
        $mform->addGroup($group, 'buttons', '', ' ', false);

        // Add hidden fields required by page.
        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);
    }
}