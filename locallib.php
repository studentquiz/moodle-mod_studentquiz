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

/*
 * Does something really useful with the passed things
 *
 * @param array $things
 * @return object
 *function studentquiz_do_something_useful(array $things) {
 *    return new stdClass();
 *}
 */
function get_options_behaviour($cm) {
    global $DB, $CFG;
    $behaviour = $DB->get_record('studentquiz', array('id' => $cm->instance), 'behaviour');
    $comma = explode(",", $behaviour->behaviour);
    $currentbehaviour = '';
    $behaviours = question_engine::get_behaviour_options($currentbehaviour);
    $showbehaviour = array();
    foreach ($comma as $id => $values) {

        foreach ($behaviours as $key => $langstring) {
            if ($values == $key) {
                $showbehaviour[$key] = $langstring;
            }
        }
    }
    return $showbehaviour;
}

function get_next_question($sessionid, $quba) {
    global $DB;

    $session = $DB->get_record('studentquiz_practice_session', array('id' => $sessionid));
    $categoryid = $session->question_category_id;
    $results = $DB->get_records_menu('question_attempts', array('questionusageid' => $session->question_usage_id),
        'id', 'id, questionid');
    $questionid = choose_other_question($categoryid, $results);

    if ($questionid == null) {
        $viewurl = new moodle_url('/mod/studentquiz/summary.php', array('id' => $sessionid));
        redirect($viewurl, get_string('practice_no_more_questions', 'studentquiz'));
    }

    $question = question_bank::load_question($questionid->id, false);
    $slot = $quba->add_question($question);
    $quba->start_question($slot);
    question_engine::save_questions_usage_by_activity($quba);
    $DB->set_field('studentquiz_practice_session', 'total_no_of_questions', $slot, array('id' => $sessionid));
    return $slot;
}

function choose_other_question($categoryid, $excludedquestions, $allowshuffle = true) {
    $available = get_available_questions_from_category($categoryid);
    shuffle($available);

    foreach ($available as $questionid) {
        if (in_array($questionid, $excludedquestions)) {
            continue;
        }
        $question = question_bank::load_question($questionid, $allowshuffle);
        return $question;
    }

    return null;
}

function get_available_questions_from_category($categoryid) {

    if (question_categorylist($categoryid)) {
        $categoryids = question_categorylist($categoryid);
    } else {
        $categoryids = array($categoryid);
    }
    $excludedqtypes = null;
    $questionids = question_bank::get_finder()->get_questions_from_categories($categoryids, $excludedqtypes);

    return $questionids;
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
function quiz_add_selected_questions($rawdata, $quba){
    $questionids = get_quiz_ids($rawdata);
    $questions = question_preload_questions($questionids);

    $questionstoprocess = array();
    foreach ($questionids as $id) {
        if (array_key_exists($id, $questions)) {
            $questionstoprocess[$id] = $questions[$id];
        }
    }
    get_question_options($questionstoprocess);

    foreach($questionids as $id){
        $question = question_bank::make_question($questionstoprocess[$id]);
        $slot = $quba->add_question($question);
        $quba->get_question_attempt($slot);
    }
    return count($questionids);
}

function quiz_practice_create_session($data, $quba_id) {
    global $DB, $USER;

    $quiz_practice = new stdClass();
    $quiz_practice->practice_date = time();
    $quiz_practice->question_category_id = $data->categoryid;
    $quiz_practice->user_id = $USER->id;
    $quiz_practice->studentquiz_id = $data->instanceid;

    $quiz_practice->question_usage_id = $quba_id;
    return $DB->insert_record('studentquiz_practice_session', $quiz_practice);
}
function quiz_practice_create_quiz($data, $context, $rawdata) {
    global $DB;

    $quba = question_engine::make_questions_usage_by_activity('mod_studentquiz', $context);
    $quba->set_preferred_behaviour($data->behaviour);

    $rec = new stdClass();
    $rec->contextid = $quba->get_owning_context()->id;
    $rec->component = $quba->get_owning_component();
    $rec->preferredbehaviour = $quba->get_preferred_behaviour();
    $quid = $DB->insert_record('question_usages', $rec);
    $quba->set_id_from_database($quid);

    $sessionid = quiz_practice_create_session($data, $quba->get_id());
    $count = quiz_add_selected_questions($rawdata, $quba);
    $DB->set_field('studentquiz_practice_session', 'total_no_of_questions', $count, array('id' => $sessionid));

    $quba->process_all_actions();
    $quba->start_all_questions();
    question_engine::save_questions_usage_by_activity($quba);

    return $sessionid;
}
