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
/*
 * Does something really useful with the passed things
 *
 * @param array $things
 * @return object
 *function studentquiz_do_something_useful(array $things) {
 *    return new stdClass();
 *}
 */
function get_behaviour_options() {
    $behaviours = array('immediatefeedback' => 'Immediate feedback');
    $archetypalbehaviours = question_engine::get_archetypal_behaviours();

    if(array_key_exists(STUDENTQUIZ_BEHAVIOUR, $archetypalbehaviours)) {
        $behaviours[STUDENTQUIZ_BEHAVIOUR] = $archetypalbehaviours[STUDENTQUIZ_BEHAVIOUR];
    }

    return $behaviours;
}

function get_current_behaviour($cm=null) {
    global $DB;

    if(isset($cm)){
        $rec = $DB->get_record('studentquiz', array('id' => $cm->instance), 'quizpracticebehaviour');

        if(!$rec) return STUDENTQUIZ_BEHAVIOUR;

        return $rec->quizpracticebehaviour;
    } else {
        return STUDENTQUIZ_BEHAVIOUR;
    }
}