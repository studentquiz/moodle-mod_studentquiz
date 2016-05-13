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
 * English strings for studentquiz
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Student Quiz';
$string['modulenameplural'] = 'Student Quizzes';
$string['modulename_help'] = 'Use the Student Quiz Module for... | The Student Quiz module allows...';
$string['studentquizfieldset'] = 'Custom example fieldset';
$string['studentquizname'] = 'Student Quiz Name';
$string['studentquizname_help'] = 'This is the content of the help tooltip associated with the Student Quiz name field. Markdown syntax is supported.';
$string['anonymous_checkbox_label'] = 'Anonymize creator and ranking for student';
$string['quiz_advanced_settings_header'] = 'Advanced Settings';
$string['quizpracticebehaviour'] = 'Voting and Comment';
$string['quizpracticebehaviourhelp'] = 'Student quiz practice question behaviour';
$string['quizpracticebehaviourhelp_help'] = 'Defines the quiz behaviour in the quiz practices.';


$string['studentquiz'] = 'studentquiz';
$string['pluginadministration'] = 'Student Quiz Administration';
$string['pluginname'] = 'Student Quiz';
$string['vote_column_name'] = 'Votes';
$string['difficulty_level_column_name'] = 'Difficulty';
$string['vote_points'] = 'Points';
$string['tag_column_name'] = 'Tags';
$string['start_quiz_button'] = 'Start Quiz';
$string['nav_question_and_quiz'] = 'Quiz and Questions';
$string['nav_report'] = 'Report';
$string['nav_report_quiz'] = 'Quiz';
$string['nav_report_rank'] = 'Rank';
$string['nav_export'] = 'Export';
$string['nav_import'] = 'Import';
$string['anonymrankhelp'] = 'Anonym Question Creator and Raning';
$string['anonymrankhelp_help'] = 'Defines if the question creators and ranking should be anonym for the students.';
$string['createnewquestionfirst'] = 'Create first question ...';
$string['createnewquizfromfilter'] = 'Start new quiz from search filters ...';
$string['no_difficulty_level'] = 'no difficulty';
$string['no_tags'] = 'no tags';
$string['no_votes'] = 'no votes';
$string['creater_anonym_firstname'] = 'anonym';
$string['creater_anonym_lastname'] = 'anonym';

//filters
$string['filter_label_search'] = 'Search';
$string['filter_label_question'] = 'Question name';
$string['filter_label_firstname'] = 'Firstname';
$string['filter_label_surname'] = 'Surname';
$string['filter_label_createdate'] = 'Creation';
$string['filter_label_tags'] = 'Tag';
$string['filter_label_votes'] = 'Vote';
$string['filter_label_difficulty_level'] = 'Difficulty';
$string['filter_ishigher'] = 'Is higher';
$string['filter_islower'] = 'Is lower';
$string['filter_label_show_mine'] = 'Show my questions';

//admin settings
$string['rankingsettingsheader'] = 'Ranking settings';
$string['settings_add_q_quantifier'] = 'Points for each question created';
$string['config_add_q_quantifier'] = 'Points received for the creation of a new question.';
$string['settings_vote_quantifier'] = 'Multiplier for the average of stars received for a question';
$string['config_vote_quantifier'] = 'E.g. if the multiplier is 2 and a question is rated with an average of 4.3 stars, the author of the question will receive 9 points (= ROUND(2 * 4.3; 1)).';
$string['settings_correct_answered_q_quantifier'] = 'Points for each correct answer';
$string['config_correct_answered_q_quantifier'] = 'Points received for answering a question correctly.';
$string['settings_incorrect_answered_q_quantifier'] = 'Points for each wrong answer';
$string['config_incorrect_answered_q_quantifier'] = 'Points received for answering a question wrongly.';


//report quiz
$string['reportquiz_total_title'] = 'Total';
$string['reportquiz_total_attempt'] = 'Number of attempts';
$string['reportquiz_total_questions_answered'] = 'Answered questions';
$string['reportquiz_total_questions_right'] = 'Questions right';
$string['reportquiz_total_questions_wrong'] = 'Questions wrong';
$string['reportquiz_total_obtained_marks'] = 'Obtained marks';
$string['reportquiz_summary_title'] = 'Quiz summaries';



//report rank
$string['reportrank_title'] = 'User ranking';
$string['reportrank_table_title'] = '- Ranking';
$string['reportrank_table_column_rank'] = 'Rank';
$string['reportrank_table_column_fullname'] = 'Fullname';
$string['reportrank_table_column_points'] = 'Points';

//view
$string['viewlib_please_select_question'] = 'Please select a question.';
$string['viewlib_please_contact_the_admin'] = 'Please contact the admin.';



