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
 * Defines services for the studentquiz module.
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
        'mod_studentquiz_set_state' => [
                'classname' => 'mod_studentquiz_external',
                'classpath' => 'mod/studentquiz/externallib.php',
                'methodname' => 'change_question_state',
                'description' => 'Copy a students previous attempt to a new attempt.',
                'type' => 'write',
                'ajax' => true
        ]
];
$services = [
        'StudentQuiz services' => [
                'shortname' => 'studentquizservices',
                'functions' => [
                        'mod_studentquiz_set_state'
                ],
                'requiredcapability' => '',
                'enabled' => 1,
        ]
];
