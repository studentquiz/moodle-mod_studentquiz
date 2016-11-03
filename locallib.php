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
 * Internal library of functions for module studentquiz
 *
 * All the studentquiz specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

/** @var string default quiz behaviour */
const STUDENTQUIZ_BEHAVIOUR = 'studentquiz';
/** @var int default course section id for the orphaned activities */
const COURSE_SECTION_ID = 999;
/** @var string generated student quiz placeholder */
const GENERATE_QUIZ_PLACEHOLDER = 'quiz';
/** @var string generated student quiz intro */
const GENERATE_QUIZ_INTRO = 'Studentquiz';
/** @var string generated student quiz overduehandling */
const GENERATE_QUIZ_OVERDUEHANDLING = 'autosubmit';
/** @var string default course section name for the orphaned activities */
const COURSE_SECTION_NAME = 'studentquiz quizzes';
/** @var string default course section summary for the orphaned activities */
const COURSE_SECTION_SUMMARY = 'all student quizzes';
/** @var string default course section summaryformat for the orphaned activities */
const COURSE_SECTION_SUMMARYFORMAT = 1;
/** @var string default course section visible for the orphaned activities */
const COURSE_SECTION_VISIBLE = false;
/** @var string default studentquiz quiz practice behaviour */
const DEFAULT_STUDENTQUIZ_QUIZ_BEHAVIOUR = 'immediatefeedback';

/**
 * Checks whether the studentquiz behaviour exists
 *
 * @return bool
 */
function has_studentquiz_behaviour() {
    $archetypalbehaviours = question_engine::get_archetypal_behaviours();

    return array_key_exists(STUDENTQUIZ_BEHAVIOUR, $archetypalbehaviours);
}

/**
 * Returns behaviour option from the course module with fallback
 *
 * @param  stdClass $cm
 * @return string quiz behaviour
 */
function get_current_behaviour($cm=null) {
    global $DB;

    $default = DEFAULT_STUDENTQUIZ_QUIZ_BEHAVIOUR;
    $archetypalbehaviours = question_engine::get_archetypal_behaviours();

    if (array_key_exists(STUDENTQUIZ_BEHAVIOUR, $archetypalbehaviours)) {
        $default = STUDENTQUIZ_BEHAVIOUR;
    }

    if (isset($cm)) {
        $rec = $DB->get_record('studentquiz', array('id' => $cm->instance), 'quizpracticebehaviour');

        if (!$rec) {
            return $default;
        }

        return $rec->quizpracticebehaviour;
    } else {
        return $default;
    }
}

/**
 * Returns quiz module id
 * @return int
 */
function get_quiz_module_id() {
    global $DB;
    return $DB->get_field('modules', 'id', array('name' => 'quiz'));
}

/**
 * Check if user has permission to see creator
 * @return bool
 */
function mod_check_created_permission() {
    global $USER;

    $admins = get_admins();
    foreach ($admins as $admin) {
        if ($USER->id == $admin->id) {
            return true;
        }
    }
    // 1 Manager, 2, Course creator, 3 editing Teacher.
    return user_has_role_assignment($USER->id, 1) ||
        user_has_role_assignment($USER->id, 2) ||
        user_has_role_assignment($USER->id, 3);
}

/**
 * Checks if activity is anonym or not
 * @param  int  $cmid course module id
 * @return boolean
 */
function is_anonym($cmid) {
    global $DB;

    if (mod_check_created_permission()) {
        return 0;
    }

    $field = $DB->get_field('studentquiz', 'anonymrank', array('coursemodule' => $cmid));
    if ($field !== false) {
        return intval($field);
    }
    // If the dont found an entry better set it anonym.
    return 1;
}
