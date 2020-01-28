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
 * Form for editing a comment or reply.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\commentarea\form;

defined('MOODLE_INTERNAL') || die();

use mod_studentquiz\commentarea\container;

require_once($CFG->libdir . '/form/editor.php');

/**
 * Form for editing a comment or reply.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_form {

    /** @var array - Array of data needs for form, generate as hidden inputs. */
    private $params;

    /** @var bool - Show cancel button. */
    private $cancelbutton = false;

    /** @var bool - Force commenting. */
    private $forcecommenting = false;

    /**
     * comment_form constructor.
     *
     * @param array $params - Array of data needs for form.
     */
    public function __construct($params) {
        if (isset($params['cancelbutton'])) {
            $this->cancelbutton = $params['cancelbutton'];
            unset($params['cancelbutton']);
        }
        if (isset($params['forcecommenting'])) {
            $this->forcecommenting = $params['forcecommenting'];
            unset($params['forcecommenting']);
        }
        $this->params = $params;
    }

    /**
     * Get HTML form.
     */
    public function get_html() {
        global $OUTPUT;
        $params = $this->params;

        $questionid = $params['questionid'];
        $replyto = isset($params['replyto']) && $params['replyto'] ? $params['replyto'] : 0;
        $context = \context_module::instance($params['cmid']);

        $formtype = $replyto == container::PARENTID ? 'add_comment' : 'add_reply';
        $submitlabel = \get_string($formtype, 'mod_studentquiz');

        $unique = $questionid . '_' . $replyto;
        $id = 'studentquiz_customeditor_' . $unique;
        $required = \get_string('required');
        $placeholder = \get_string('editorplaceholder', 'mod_studentquiz');
        $html = \html_writer::start_div('comment-area-form', [
                'data-textarea-placeholder' => $placeholder
        ]);

        $html .= \html_writer::start_div();
        foreach ($params as $name => $value) {
            $html .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
        }
        $html .= \html_writer::end_div();

        $html .= \html_writer::start_div('studentquiz_customeditor', [
                'id' => $id
        ]);

        $html .= \html_writer::start_div('form-group row  fitem comment_editor_container');

        $editorid = 'id_editor_question_' . $unique;

        // Write help icon.
        $requiredicon = '';
        if ($this->forcecommenting) {
            $requiredicon = \html_writer::span($OUTPUT->pix_icon('req', $required), 'req');
        }
        $helpicon = $OUTPUT->help_icon('comment_help', 'mod_studentquiz');

        // Write label.
        $labelcontent = \html_writer::tag('label', $submitlabel, [
                'class' => 'add-comment-label',
                'for' => $editorid
        ]);
        $labelcontent .= \html_writer::span($requiredicon . $helpicon, 'text-nowrap');
        $labelcontent .= \html_writer::span(":");;

        // Start first col.
        $html .= \html_writer::div($labelcontent, 'col-md-12 comment-label-content');
        // End first col.

        // Start second col.
        $html .= \html_writer::start_div('col-md-12 form-inline felement', ['data-fieldtype' => 'editor']);
        $html .= (new comment_simple_editor('message', 'message',
                ['id' => $editorid],
                ['context' => $context]))->toHtml();
        $html .= \html_writer::end_div();
        // End second col.

        $html .= \html_writer::end_div();
        $html .= \html_writer::end_div();
        // End studentquiz_customeditor.

        // Write error elements.
        $html .= $this->error_elements();

        // Button Group.
        $html .= \html_writer::start_div('row buttonar');
        $html .= \html_writer::start_div('col-md-12 form-inline felement');

        $submitbtn = \html_writer::tag('button', $submitlabel, [
                'name' => 'submitbutton',
                'id' => 'id_submitbutton',
                'class' => 'btn btn-primary'
        ]);

        $html .= \html_writer::div($submitbtn, 'form-group fitem');

        if ($this->cancelbutton) {
            $cancelbtn = \html_writer::tag('button', \get_string('cancel'), [
                    'name' => 'cancel',
                    'id' => 'id_cancel',
                    'class' => 'btn btn-secondary',
            ]);
            $html .= \html_writer::div($cancelbtn, 'form-group fitem');
        }

        $html .= \html_writer::end_div();
        // End col-md-12.
        $html .= \html_writer::end_div();
        // End button group.
        $html .= \html_writer::end_div();
        // End comment_form_area.

        return $html;
    }

    /**
     * Generate error elements.
     *
     * @return string
     */
    private function error_elements() {
        $html = \html_writer::start_div('row comment-errors');
        $html .= \html_writer::start_div('col-md-12 form-inline felement');
        $html .= \html_writer::div(
            \get_string('comment_error', 'studentquiz')
            , 'hide error comment-error');
        $html .= \html_writer::end_div();
        $html .= \html_writer::end_div();
        return $html;
    }
}
