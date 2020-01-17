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
 * Validate Form for editing a comment or reply.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\commentarea\form;

defined('MOODLE_INTERNAL') || die();

use mod_studentquiz\commentarea\container;

require_once($CFG->libdir . '/formslib.php');

/**
 * Validate Form for editing a comment or reply.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validate_comment_form extends \moodleform {

    /**
     * Definition of Validate comment form.
     */
    protected function definition() {
        global $CFG;
        $requiredfile = "$CFG->libdir/form/editor.php";
        \MoodleQuickForm::registerElementType('studentquiz_comment_editor', $requiredfile, comment_simple_editor::class);
        $mform = $this->_form;
        $params = $this->_customdata['params'];

        $questionid = $params['questionid'];
        $replyto = isset($params['replyto']) && $params['replyto'] ? $params['replyto'] : 0;
        $context = \context_module::instance($params['cmid']);
        $unique = $questionid . '_' . $replyto;

        $formtype = $replyto == container::PARENTID ? 'add_comment' : 'add_reply';
        $submitlabel = \get_string($formtype, 'mod_studentquiz');
        $mform->addElement('studentquiz_comment_editor', 'message', $submitlabel,
                ['id' => 'id_editor_question_' . $unique],
                ['context' => $context]
        );
        $mform->addElement('html', \html_writer::end_tag('div'));
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', \get_string('required'), 'required', null);
        $mform->addHelpButton('message', 'comment_help', 'mod_studentquiz');

        // Hidden fields.
        foreach ($params as $param => $value) {
            $mform->addElement('hidden', $param, $value);
            $mform->setType($param, PARAM_INT);
        }
    }

    /**
     * Get form's element errors.
     *
     * @return array
     */
    public function get_form_errors() {
        return $this->_form->_errors;
    }
}
