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
 * Defines message providers (types of message sent) for the studentquiz module.
 *
 * @package   mod_studentquiz
 * @copyright 2017 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$messageproviders = array(
    // Notify student that someone has approved, disapproved, hide, unhide, or changed his question. (Info to question author).
    'questionchanged' => array(
        'capability' => 'mod/studentquiz:emailnotifyquestion'
    ),
    // Notify student that someone has commented to his question. (Info to question author).
    'commentadded' => array(
        'capability' => 'mod/studentquiz:emailnotifycommentadded'
    ),
    // Notify student that someone has deleted their comment to his question. (Info to question author).
    'commentdeleted' => array(
        'capability' => 'mod/studentquiz:emailnotifycommentdeleted'
    ),
    // Notify student that someone has deleted his comment to someone's question. (Info to comment author).
    'minecommentdeleted' => array(
        'capability' => 'mod/studentquiz:emailnotifycommentdeleted'
    ),
);
