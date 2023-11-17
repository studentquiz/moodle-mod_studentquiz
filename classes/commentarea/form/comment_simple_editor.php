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

namespace mod_studentquiz\commentarea\form;

use MoodleQuickForm_editor;

/**
 * Hacky form for a simple editor with custom option.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_simple_editor extends MoodleQuickForm_editor {

    /** @var array - Attributes used for this editor. */
    const ATTRIBUTES = [
            'cols' => 60,
            'rows' => 5,
            'class' => 'comment_editor_container'
    ];

    /** @var array - Options used for this editor. */
    const OPTIONS = [
            'noclean' => VALUE_DEFAULT,
            'trusttext' => VALUE_DEFAULT
    ];

    /**
     * comment_simple_editor constructor.
     *
     * @param null $elementname - Name of element.
     * @param null $elementlabel - Label of element.
     * @param array $attributes - Attributes of element.
     * @param array $options - Options of element.
     */
    public function __construct($elementname = null, $elementlabel = null, $attributes = [], $options = []) {
        global $CFG;

        // HACK: There is no way to correctly force a specific editor. Unfortunately the comment area is highly
        // specific to the atto editor, both here in php and javascript code. We temporarely fake the atto to be the
        // only available editor. ref: https://github.com/frankkoch/moodle-mod_studentquiz/issues/283.
        $CFG->texteditors = 'atto';

        $attributes = array_merge($attributes, self::ATTRIBUTES);
        $options = array_merge($options, self::OPTIONS);
        $this->_options['atto:toolbar'] = get_config('studentquiz', 'comment_editor_toolbar');
        parent::__construct($elementname, $elementlabel, $attributes, $options);
    }
}
