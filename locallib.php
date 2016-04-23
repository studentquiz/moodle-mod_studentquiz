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



function get_quiz_ids($rawdata) {
    $ids = array();
    foreach ($rawdata as $key => $value) { // Parse input for question ids.
        if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
            $ids[] = $matches[1];
        }
    }
    return $ids;
}
function quiz_add_selected_questions($questionids, $quba){
    $questions = question_preload_questions($questionids);

    get_question_options($questions);
    foreach ($questionids as $id) {
        $questionstoprocess = question_bank::make_question($questions[$id]);
        $quba->add_question($questionstoprocess);
    }

    return count($questions);
}

function quiz_practice_create_overview($data) {
    global $DB, $USER;

    $overview = new stdClass();
    $overview->questioncategoryid = $data->categoryid;
    $overview->userid = $USER->id;
    $overview->studentquizid = $data->instanceid;

    return $DB->insert_record('studentquiz_poverview', $overview);
}
function quiz_practice_create_session($overviewid, $data, $qubaid) {
    global $DB;
    $session = new stdClass();
    $session->studentquizpoverviewid = $overviewid;
    $session->questionusageid = $qubaid;
    $session->totalnoofquestions = $data->totalnoofquestions;
    $session->totalmarks = $data->totalmarks;
    $session->practicedate = time();
    return $DB->insert_record('studentquiz_psession', $session);
}

function quiz_practice_get_question_ids($rawdata) {
    if(!isset($rawdata)&& empty($rawdata)) return false;

    $ids = get_quiz_ids($rawdata);

    if(!count($ids)) {
        return false;
    }

    return $ids;
}

function quiz_practice_create_quiz_helper($data, $context, $ids) {
    $qubaid = quiz_practice_create_quiz($data, $context, $ids);
    return quiz_practice_create_session(
       quiz_practice_create_overview($data)
        ,$data
        ,$qubaid
     );
}

function quiz_practice_create_quiz($data, $context, $questionids) {
    $quba = question_engine::make_questions_usage_by_activity('mod_studentquiz', $context);
    $quba->set_preferred_behaviour($data->behaviour);

    $count = quiz_add_selected_questions($questionids, $quba);
    $quba->start_all_questions();

    question_engine::save_questions_usage_by_activity($quba);

    $data->totalmarks = quiz_practice_get_max_marks($quba);
    $data->totalnoofquestions = $count;

    return $quba->get_id();
}

function quiz_practice_retry_quiz($data, $context, $session) {
    return quiz_practice_create_session(
        $session->studentquizpoverviewid
        ,$data
        ,quiz_practice_create_quiz($data, $context, quiz_practice_get_used_question($session))
    );
}

function quiz_practice_get_used_question($session) {
    global $DB;

    $records = $DB->get_records('question_attempts', array('questionusageid' => $session->questionusageid), 'questionid');

    $ids = array();
    foreach($records as $id){
        $ids[] = $id->questionid;;
    }
    return $ids;
}

function quiz_practice_get_max_marks($quba) {
    $maxmarks = 0;
    foreach($quba->get_slots() as $slot) {
        $maxmarks += $quba->get_question_max_mark($slot);
    }
    return $maxmarks;
}
