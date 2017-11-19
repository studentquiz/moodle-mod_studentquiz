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
 * English strings for StudentQuiz
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General plugin strings.
$string['modulename'] = 'StudentQuiz';
$string['modulenameplural'] = 'StudentQuizzes';
$string['modulename_help'] = 'The StudentQuiz activity allows students to add questions for the crowd. In the StudentQuiz overview the students can filter questions. They also can use the filtered questions in the crowd to practice. The teacher has an option to anonymize the created by column.<br><br>The StudentQuiz activity awards the students with points to motivate them to add and practice. The Points are listed in a ranking table.<br><br>For more information read the <a href="https://github.com/frankkoch/moodle-mod_studentquiz/blob/master/manuals/User-Manual.pdf">User-Manual</a>.';
$string['studentquizname'] = 'StudentQuiz Name';
$string['studentquizname_help'] = 'The name of this StudentQuiz Activity';
$string['studentquiz'] = 'studentquiz';
$string['pluginname'] = 'StudentQuiz';
$string['pluginadministration'] = 'StudentQuiz Administration';
$string['student'] = 'Student';

// Labels and buttons.
$string['vote_column_name'] = 'Rating';
$string['practice_column_name'] = 'Attempts';
$string['comment_column_name'] = 'Comments';
$string['difficulty_level_column_name'] = 'Difficulty';
$string['approved_column_name'] = 'Approved';
$string['vote_points'] = 'Points';
$string['myattempts_column_name'] = 'My Attempts';
$string['mydifficulty_column_name'] = 'My Difficulty';
$string['mylastattempt_column_name'] = 'My Last Attempt';
$string['start_quiz_button'] = 'Start Quiz';
$string['review_button'] = 'Review';
$string['finish_button'] = 'Finish';
$string['next_button'] = 'Next';
$string['previous_button'] = 'Previous';
$string['nav_report'] = 'Report';
$string['nav_report_rank'] = 'Rank';
$string['nav_export'] = 'Export';
$string['nav_import'] = 'Import';
$string['createnewquestionfirst'] = 'Create first question';
$string['createnewquestion'] = 'Create new question';
$string['no_difficulty_level'] = 'n.a.';
$string['tags'] = 'Tags';
$string['no_tags'] = 'n.a.';
$string['no_votes'] = 'n.a.';
$string['no_practice'] = 'n.a.';
$string['no_comment'] = 'n.a.';
$string['no_myattempts'] = 'n.a.';
$string['no_mydifficulty'] = 'n.a.';
$string['no_mylastattempt'] = 'n.a.';
$string['approved'] = '✓';
$string['not_approved'] = '✗';
$string['lastattempt_right'] = '✓';
$string['lastattempt_wrong'] = '✗';
$string['approve'] = 'Un-/Approve';
$string['approveselectedscheck'] = 'Are you sure you want to un-/approve the following questions?<br /><br />{$a}';
$string['questionsinuse'] = '(* Questions marked by an asterisk are already in use in some quizzes.)';
$string['creator_anonym_firstname'] = 'anonym';
$string['creator_anonym_lastname'] = 'anonym';
$string['no_questions_selected_message'] = 'Please select at least one question to start the quiz.';
$string['progress_bar_caption'] = 'Your progress in this StudentQuiz activity';

// Filters.
$string['filter'] = 'Filter';
$string['filter_label_question'] = 'Question title';
$string['filter_label_approved'] = 'Approved questions';
$string['filter_label_firstname'] = 'Firstname';
$string['filter_label_surname'] = 'Lastname';
$string['filter_label_createdate'] = 'Creation';
$string['filter_label_questiontext'] = 'Question content';
$string['filter_label_tags'] = 'Tag';
$string['filter_label_votes'] = 'Rating';
$string['filter_label_practice'] = 'Attempts';
$string['filter_label_comment'] = 'Comments';
$string['filter_label_difficulty_level'] = 'Difficulty';
$string['filter_label_mylastattempt'] = 'My latest attempt';
$string['filter_label_myattempts'] = 'My attempts';
$string['filter_label_mydifficulty'] = 'My difficulty';
$string['filter_ishigher'] = 'Is higher';
$string['filter_islower'] = 'Is lower';
$string['filter_label_show_mine'] = 'My questions';

// General settings.
$string['settings_anonymous'] = 'Student anonymizer';
$string['settings_anonymous_label'] = 'Anonymize students';
$string['settings_anonymous_help'] = 'Anonymize for students the created by column in the question overview and the names of the ranking table.';
$string['settings_quizpracticebehaviour'] = 'Rating and commenting';
$string['settings_quizpracticebehaviour_label'] = 'Rating and commenting';
$string['settings_quizpracticebehaviour_help'] = 'Allow students to rate and comment questions during the quiz attempt';
$string['settings_questionquantifier'] = 'Question quantifier';
$string['settings_questionquantifier_label'] = 'Points for each question created';
$string['settings_questionquantifier_help'] = 'Points received for creating a new question.';
$string['settings_approvedquantifier'] = 'Approved quantifier';
$string['settings_approvedquantifier_label'] = 'Points for each question approved';
$string['settings_approvedquantifier_help'] = 'Points received for each question approved.';
$string['settings_votequantifier'] = 'Vote quantifier';
$string['settings_votequantifier_label'] = 'Multiplier for the average of stars received for a question';
$string['settings_votequantifier_help'] = 'E.g. if the multiplier is 3 and a question is rated with an average of 4.3 stars, the author of the question will receive 13 points (= ROUND(3 * 4.3; 1)).';
$string['settings_correctanswerquantifier'] = 'Correct answer quantifier';
$string['settings_correctanswerquantifier_label'] = 'Points for each correct answer';
$string['settings_correctanswerquantifier_help'] = 'Points received for answering a question correctly.';
$string['settings_incorrectanswerquantifier'] = 'Incorrect answer quantifier';
$string['settings_incorrectanswerquantifier_label'] = 'Points for each wrong answer';
$string['settings_incorrectanswerquantifier_help'] = 'Points received for answering a question wrongly.';
$string['settings_removeemptysections'] = 'Remove empty sections';
$string['settings_removeemptysections_label'] = 'Remove empty sections at the end of the course';
$string['settings_removeemptysections_help'] = 'StudentQuiz 2.0.3 and prior used a socalled orphaned section (hidden Topic) with number 999. Since Moodle 3.3 the moodle import creates until 999 sections, even if there are no such sections described in the export file. Uncheck this option, if you encounter side effects because of this. You\'ll have to delete then the unwanted sections yourself.';

// Admin settings.
$string['rankingsettingsheader'] = 'Ranking settings';
$string['rankingsettingsdescription'] = 'The values you set here define the ranking default values that are used in the settings form when you create a new studentquiz.';
$string['importsettingsheader'] = 'Import settings';
$string['importsettingsdescription'] = 'Here you set various settings to change the behavior of imports';

// Report Dashboard.
$string['reportquiz_dashboard_title'] = 'Statistics';
$string['reportquiz_total_attempt'] = 'Times user run the quiz';
$string['reportquiz_total_questions_answered'] = 'Total of answers';
$string['reportquiz_total_questions_right'] = 'Total of correct answers';
$string['reportquiz_total_questions_wrong'] = 'Wrong answers';
$string['reportquiz_total_obtained_marks'] = 'Grade total';
$string['reportquiz_total_users'] = 'Number of participants';
$string['reportquiz_admin_title'] = 'User statistics';

// Report quiz stats.
$string['reportquiz_stats_title'] = 'Statistics';
$string['reportquiz_stats_nr_of_questions'] = 'Number of questions in this quiz';
$string['reportquiz_stats_right_answered_questions'] = 'You have answered correctly';
$string['reportquiz_stats_nr_of_own_questions'] = 'You have contributed';
$string['reportquiz_stats_nr_of_approved_questions'] = 'Number of approved questions';
$string['reportquiz_stats_avg_rating'] = 'Your received rating average';
$string['reportquiz_stats_learning_quotient'] = 'Your learning quotient';
$string['reportquiz_stats_own_grade_of_max'] = 'Your grade total';
$string['reportquiz_stats_questions_answered'] = 'Total of your answers';
$string['reportquiz_stats_questions_right'] = 'Total of correct answers';

// Report rank.
$string['reportrank_title'] = 'User ranking';
$string['reportrank_table_quantifier_caption'] = 'How your Points are calculated';
$string['reportrank_table_title'] = '- Ranking';
$string['reportrank_table_column_rank'] = 'Rank';
$string['reportrank_table_column_fullname'] = 'Fullname';
$string['reportrank_table_column_points'] = 'Points';
$string['reportrank_table_column_countquestions'] = 'Number of questions';
$string['reportrank_table_column_approvedquestions'] = 'Number of approved questions';
$string['reportrank_table_column_summeanvotes'] = 'Voting Score';
$string['reportrank_table_column_correctanswers'] = 'Correct Answers';
$string['reportrank_table_column_incorrectanswers'] = 'Incorrect Answers';
$string['reportrank_table_column_quantifier_name'] = 'Name';
$string['reportrank_table_column_factor'] = 'Factor';
$string['reportrank_table_column_description'] = 'Description';

// Permission.
$string['studentquiz:submit'] = 'Submit on StudentQuiz';
$string['studentquiz:view'] = 'View StudentQuiz';
$string['studentquiz:addinstance'] = 'Add new instance';
$string['studentquiz:change'] = 'Question change notification';
$string['studentquiz:approved'] = 'Question approve notification';
$string['studentquiz:unapproved'] = 'Question unapprove notification';
$string['studentquiz:emailnotifychange'] = 'Question change notification';
$string['studentquiz:emailnotifyapproved'] = 'Question approve notification';
$string['studentquiz:emailnotifyunapproved'] = 'Question unapprove notification';

// Message provider.
$string['messageprovider:change'] = 'Question change notification';
$string['messageprovider:approved'] = 'Question approve notification';
$string['messageprovider:unapproved'] = 'Question unapprove notification';

// Change notification email.
$string['emailchangebody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in course \'{$a->coursename}\' in StudentQuiz activity \'{$a->modulename}\'
has been modified by \'{$a->actorname}\' at \'{$a->questiontime}\'.

You can review this question at: {$a->questionurl}.';
$string['emailchangesmall'] = 'Your question \'{$a->questionname}\' has been modified by {$a->actorname}.';
$string['emailchangesubject'] = 'Question modification: {$a->questionname}';

// Approve notification email.
$string['emailapprovedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in course \'{$a->coursename}\' in StudentQuiz activity \'{$a->modulename}\'
has been approved by {$a->actorname} at \'{$a->timestamp}\'.

You can review this question at: {$a->questionurl}.';
$string['emailapprovedsmall'] = 'Your question \'{$a->questionname}\' has been approved by {$a->actorname}.';
$string['emailapprovedsubject'] = 'Question approved: {$a->questionname}';

// Unapprove notification email.
$string['emailunapprovedbody'] = 'Dear {$a->recepientname},

The approval of your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' 
in course \'{$a->coursename}\' was revoked by {$a->actorname} at \'{$a->timestamp}\'. 

You can review this question at: {$a->questionurl}.';
$string['emailunapprovedsmall'] = 'Your question \'{$a->questionname}\' has been unapproved by {$a->actorname}.';
$string['emailunapprovedsubject'] = 'Question unapproved: {$a->questionname}';

// Comment added notification email.
$string['emailcommentedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' 
in course \'{$a->coursename}\' was commented by {$a->actorname} at \'{$a->timestamp}\'. 

The comment was: 
\'{$a->comment}\'

You can review this question at: {$a->questionurl}.';
$string['emailcommendedsmall'] = 'Your question \'{$a->questionname}\' has been commented by {$a->username}.';
$string['emailcommentedsubject'] = 'Question commented: {$a->questionname}';

// Comment deleted notification email.
$string['emailcommentdeletedbody'] = 'Dear {$a->recepientname},

Your comment \'{$a->comment}\' to the question \'{$a->questionname}\' on \'{$a->commenttime}\' 
in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' was deleted by {$a->actorname} 
at \'{$a->timestamp}\'. 

You can review this question at: {$a->questionurl}.';
$string['emailcommentdeletedsmall'] = 'Your comment to \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emailcommentdeletedsubject'] = 'Question comment deleted: {$a->questionname}';

// Question deleted notification email.
$string['emailquestiondeletedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' 
in course \'{$a->coursename}\' was deleted by {$a->actorname} at \'{$a->timestamp}\'. 

You can review this question at: {$a->questionurl}.';
$string['emailquestiondeletedsmall'] = 'Your question \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emailquestiondeletedsubject'] = 'Question deleted: {$a->questionname}';

// Question behavior.
$string['no_comments'] = 'No comments';
$string['add_comment'] = 'Add comment';
$string['show_more'] = 'Show more';
$string['show_less'] = 'Show less';
$string['vote_title'] = 'Rate';
$string['vote_help_help'] = "Rate this question. \n 1 star is very bad and 5 stars is very good";
$string['vote_help'] = 'Rate this question';
$string['vote_error'] = 'Please Rate';
$string['comment_help'] = 'Write a comment';
$string['comment_help_help'] = 'Write a comment to the question';
