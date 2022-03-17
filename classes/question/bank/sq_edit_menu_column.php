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

namespace mod_studentquiz\bank;

use core_question\local\bank\edit_menu_column;

/**
 * Represent edit column in studentquiz_bank_view which gathers together all the actions into a menu.
 *
 * @package mod_studentquiz
 * @author Thong Bui <qktc1422@gmail.com>
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sq_edit_menu_column extends edit_menu_column {
    /**
     * Title for this column.
     *
     * @return string Title of column
     */
    public function get_title() {
        return get_string('actions');
    }
}
