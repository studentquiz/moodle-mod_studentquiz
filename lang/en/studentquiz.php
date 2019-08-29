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

$string['add_comment'] = 'Add comment';
$string['abort_button'] = 'Abort';
$string['after_answering_end_date'] = 'This StudentQuiz closed for answering on {$a}.';
$string['after_submission_end_date'] = 'This StudentQuiz closed for question submission on {$a}.';
$string['answeringndbeforestart'] = 'Answering deadline can not be specified before the open for answering date';
$string['api_state_change_success_content'] = 'Question state/visibility changed successfully';
$string['api_state_change_success_title'] = 'Success';
$string['approve'] = 'Approve';
$string['approve_toggle'] = 'Un/Approve';
$string['approved'] = '✓';
$string['approved_column_name'] = 'Approved';
$string['approved_veryshort'] = 'A';
$string['approveselectedscheck'] = 'Are you sure you want to un-/approve the following questions?<br /><br />{$a}';
$string['average_column_name'] = 'Average';
$string['before_answering_end_date'] = 'This StudentQuiz closes for answering on {$a}.';
$string['before_answering_start_date'] = 'Open for answering from {$a}.';
$string['before_submission_end_date'] = 'This StudentQuiz closes for question submission on {$a}.';
$string['before_submission_start_date'] = 'Open for question submission from {$a}.';
$string['changeselectedsstate'] = 'Change the state of the following questions:<br /><br />{$a}';
$string['comment_column_name'] = 'Comments';
$string['comment_error'] = 'Please comment';
$string['comment_error_unsaved'] = 'Do you want to save this comment first?';
$string['comment_help'] = 'Write a comment';
$string['comment_help_help'] = 'Write a comment to the question';
$string['comment_veryshort'] = 'C';
$string['createnewquestion'] = 'Create new question';
$string['createnewquestionfirst'] = 'Create first question';
$string['creator_anonym_fullname'] = 'Anonymous Student';
$string['difficulty_all_column_name'] = 'Community Difficulty';
$string['difficulty_level_column_name'] = 'Difficulty';
$string['difficulty_title'] = 'Difficulty bar';
$string['emailapprovedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in course \'{$a->coursename}\' in StudentQuiz activity \'{$a->modulename}\' has been approved by \'{$a->actorname}\' at \'{$a->timestamp}\'.

You can review this question at: {$a->questionurl}.';
$string['emailapprovedsmall'] = 'Your question \'{$a->questionname}\' has been approved by {$a->actorname}.';
$string['emailapprovedsubject'] = 'Question has been approved: {$a->questionname}';
$string['emailchangedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in course \'{$a->coursename}\' in StudentQuiz activity \'{$a->modulename}\' has been modified by \'{$a->actorname}\' at \'{$a->timestamp}\'.

You can review this question at: {$a->questionurl}.';
$string['emailchangedsmall'] = 'Your question \'{$a->questionname}\' has been modified by {$a->actorname}.';
$string['emailchangedsubject'] = 'Question has been modified: {$a->questionname}';
$string['emailcommentaddedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been commented by \'{$a->actorname}\' at \'{$a->timestamp}\'.

The comment is: \'{$a->commenttext}\'

You can review this question at: {$a->questionurl}.';
$string['emailcommentaddedsmall'] = 'Your question \'{$a->questionname}\' has been commented by {$a->username}.';
$string['emailcommentaddedsubject'] = 'Question has been commented: {$a->questionname}';

$string['emailcommentdeletedbody'] = 'Dear {$a->recepientname},

The comment on \'{$a->commenttime}\' to your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been deleted by \'{$a->actorname}\' at \'{$a->timestamp}\'.

The comment was: \'{$a->commenttext}\'

You can review this question at: {$a->questionurl}.';
$string['emailcommentdeletedsmall'] = 'The comment to your question \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emailcommentdeletedsubject'] = 'Comment has been deleted to question: {$a->questionname}';
$string['emaildeletedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been deleted by \'{$a->actorname}\' at \'{$a->timestamp}\'.';
$string['emaildeletedsmall'] = 'Your question \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emaildeletedsubject'] = 'Question has been deleted: {$a->questionname}';
$string['emailminecommentdeletedbody'] = 'Dear {$a->recepientname},

Your comment on \'{$a->commenttime}\' to the question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been deleted by \'{$a->actorname}\' at \'{$a->timestamp}\'.

The comment was: \'{$a->commenttext}\'

You can review this question at: {$a->questionurl}.';
$string['emailminecommentdeletedsmall'] = 'Your comment to question \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emailminecommentdeletedsubject'] = 'Comment has been deleted to question: {$a->questionname}';
$string['emaildisapprovedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in course \'{$a->coursename}\' in StudentQuiz activity \'{$a->modulename}\' has been disapproved by \'{$a->actorname}\' at \'{$a->timestamp}\'.

You can review this question at: {$a->questionurl}.';
$string['emaildisapprovedsmall'] = 'Your question \'{$a->questionname}\' has been disapproved by {$a->actorname}.';
$string['emaildisapprovedsubject'] = 'Question has been disapproved: {$a->questionname}';

$string['emailhiddenbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in course \'{$a->coursename}\' in StudentQuiz activity \'{$a->modulename}\' has been hidden by \'{$a->actorname}\' at \'{$a->timestamp}\'.

You can review this question at: {$a->questionurl}.';
$string['emailhiddensmall'] = 'Your question \'{$a->questionname}\' has been hidden by {$a->actorname}.';
$string['emailhiddensubject'] = 'Question has been hidden: {$a->questionname}';

$string['emailunhiddenbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in course \'{$a->coursename}\' in StudentQuiz activity \'{$a->modulename}\' has been unhidden by \'{$a->actorname}\' at \'{$a->timestamp}\'.

You can review this question at: {$a->questionurl}.';
$string['emailunhiddensmall'] = 'Your question \'{$a->questionname}\' has been unhidden by {$a->actorname}.';
$string['emailunhiddensubject'] = 'Question has been unhidden: {$a->questionname}';

$string['filter'] = 'Filter';
$string['filter_advanced_element'] = '{$a} (Advanced element)';
$string['filter_ishigher'] = 'Is higher';
$string['filter_islower'] = 'Is lower';
$string['filter_label_approved'] = 'Approved questions';
$string['filter_label_comment'] = 'Comments';
$string['filter_label_createdate'] = 'Creation';
$string['filter_label_difficulty_level'] = 'Difficulty';
$string['filter_label_fast_filters'] = 'Fast filter for questions';
$string['filter_label_firstname'] = 'Firstname';
$string['filter_label_myattempts'] = 'My attempts';
$string['filter_label_mydifficulty'] = 'My difficulty';
$string['filter_label_mylastattempt'] = 'My latest attempt';
$string['filter_label_myrate'] = 'My Rating';
$string['filter_label_onlyapproved'] = 'Approved';
$string['filter_label_onlyapproved_help'] = 'Questions approved by your teacher';
$string['filter_label_onlydifficult'] = 'Difficult for all';
$string['filter_label_onlydifficult_help'] = 'Question with an average difficulty of more than {$a}%';
$string['filter_label_onlydifficultforme'] = 'Difficult for me';
$string['filter_label_onlydifficultforme_help'] = 'Question with my difficulty of more than {$a}%';
$string['filter_label_onlygood'] = 'Good';
$string['filter_label_onlygood_help'] = 'Question with an average rating of at at least {$a} stars';
$string['filter_label_onlymine'] = 'Mine';
$string['filter_label_onlymine_help'] = 'Questions you created.';
$string['filter_label_onlynew'] = 'Unanswered';
$string['filter_label_onlynew_help'] = 'Questions you have never answered before';
$string['filter_label_practice'] = 'Attempts';
$string['filter_label_question'] = 'Question title';
$string['filter_label_questiontext'] = 'Question content';
$string['filter_label_rates'] = 'Rating';
$string['filter_label_show_mine'] = 'My questions';
$string['filter_label_surname'] = 'Lastname';
$string['filter_label_tags'] = 'Tag';
$string['finish_button'] = 'Finish';
$string['lastattempt_right'] = '✓';
$string['lastattempt_right_label'] = 'Last attempt correct';
$string['lastattempt_wrong'] = '✗';
$string['lastattempt_wrong_label'] = 'Last attempt incorrect';
$string['latest_column_name'] = 'Latest';
$string['manager_anonym_fullname'] = 'Anonymous Manager';
$string['messageprovider:approved'] = 'Question approved notification';
$string['messageprovider:changed'] = 'Question changed notification';
$string['messageprovider:commentadded'] = 'Comment added notification';
$string['messageprovider:commentdeleted'] = 'Comment deleted notification';
$string['messageprovider:deleted'] = 'Question deleted notification';
$string['messageprovider:disapproved'] = 'Question disapproved notification';
$string['messageprovider:hidden'] = 'Question hidden notification';
$string['messageprovider:unhidden'] = 'Question unhidden notification';
$string['messageprovider:minecommentdeleted'] = 'My comment deleted notification';
$string['migrate_already_done'] = 'Nothing was done because this activity has been migrated already!';
$string['migrate_ask'] = 'The speed of StudentQuiz improved with version 3.2.1, but this question set is still based on a prior version.
Questions and quizzes will be loaded faster if you run this speed-up migration. You will experience faster loading; nothing else will change.';
$string['migrate_studentquiz'] = 'Migrate StudentQuiz questions prior to version 3.2.1 to the faster version with aggregated values';
$string['migrate_studentquiz_short'] = 'Speed-up this question set';
$string['migrated_successful'] = 'This activity has been migrated successfully!';
$string['mine_column_name'] = 'Mine';
$string['modulename'] = 'StudentQuiz';
$string['modulename_help'] = 'The StudentQuiz activity allows students to add questions for the crowd. In the StudentQuiz overview the students can filter questions. They also can use the filtered questions in the crowd to practice. The teacher has an option to anonymize the created by column.<br><br>The StudentQuiz activity awards the students with points to motivate them to add and practice. The Points are listed in a ranking table.';
$string['modulename_link'] = 'mod/studentquiz/view';
$string['modulenameplural'] = 'StudentQuizzes';
$string['more'] = 'More';
$string['myattempts_column_name'] = 'My Attempts';
$string['mydifficulty_column_name'] = 'My Difficulty';
$string['mylastattempt_column_name'] = 'My Last Attempt';
$string['myrate_column_name'] = 'My Rating';
$string['nav_export'] = 'Export';
$string['nav_import'] = 'Import';
$string['needtoallowatleastoneqtype'] = 'You need to allow at least one question type';
$string['next_button'] = 'Next';
$string['no_comment'] = 'n.a.';
$string['no_comments'] = 'No comments';
$string['no_difficulty_level'] = 'n.a.';
$string['no_myattempts'] = 'n.a.';
$string['no_mydifficulty'] = 'n.a.';
$string['no_mylastattempt'] = 'n.a.';
$string['no_mylastattempt_label'] = 'The question is not attempted';
$string['no_myrate'] = 'n.a.';
$string['no_practice'] = 'n.a.';
$string['no_questions_add'] = 'There are no questions in this StudentQuiz. Feel free to add some questions.';
$string['no_questions_filter'] = 'None of the questions matched your filter criteria. Reset the filter to see all.';
$string['no_questions_selected_message'] = 'Please select at least one question to start the quiz.';
$string['no_rates'] = 'n.a.';
$string['no_tags'] = 'n.a.';
$string['not_approved'] = '✗';
$string['num_questions'] = '{$a} questions';
$string['number_column_name'] = 'Number';
$string['pagesize'] = 'Page size:';
$string['please_enrole_message'] = 'Please enroll in this course to see your personal progress';
$string['pluginadministration'] = 'StudentQuiz Administration';
$string['pluginname'] = 'StudentQuiz';
$string['practice_column_name'] = 'Attempts';
$string['previous_button'] = 'Previous';
$string['privacy:metadata:studentquiz_attempt'] = 'Represents a users attempt to answer a set of questions.';
$string['privacy:metadata:studentquiz_attempt:categoryid'] = 'ID of the category.';
$string['privacy:metadata:studentquiz_attempt:questionusageid'] = 'ID of the question usage.';
$string['privacy:metadata:studentquiz_attempt:studentquizid'] = 'ID of the StudentQuiz.';
$string['privacy:metadata:studentquiz_attempt:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_comment'] = 'Store comments for questions.';
$string['privacy:metadata:studentquiz_comment:comment'] = 'Comment of the question.';
$string['privacy:metadata:studentquiz_comment:created'] = 'Time created time comment.';
$string['privacy:metadata:studentquiz_comment:questionid'] = 'ID of the question.';
$string['privacy:metadata:studentquiz_comment:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_practice'] = 'Store quiz practices.';
$string['privacy:metadata:studentquiz_practice:quizcoursemodule'] = 'Quiz course module.';
$string['privacy:metadata:studentquiz_practice:studentquizcoursemodule'] = 'StudentQuiz course module.';
$string['privacy:metadata:studentquiz_practice:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_progress'] = 'Store progress information of student with this question.';
$string['privacy:metadata:studentquiz_progress:attempts'] = 'Number of attempts to answer this question.';
$string['privacy:metadata:studentquiz_progress:correctattempts'] = 'Number of correct answers.';
$string['privacy:metadata:studentquiz_progress:lastanswercorrect'] = '0: last answer was wrong or undefined, 1: last answer was correct.';
$string['privacy:metadata:studentquiz_progress:questionid'] = 'ID of the question.';
$string['privacy:metadata:studentquiz_progress:studentquizid'] = 'ID of the StudentQuiz.';
$string['privacy:metadata:studentquiz_progress:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_rate'] = 'Store rates for questions.';
$string['privacy:metadata:studentquiz_rate:questionid'] = 'ID of the question.';
$string['privacy:metadata:studentquiz_rate:rate'] = 'Rate for the question.';
$string['privacy:metadata:studentquiz_rate:userid'] = 'ID of the user.';
$string['progress_bar_caption'] = 'Your progress in this StudentQuiz activity';
$string['questionsinuse'] = '(* Questions marked by an asterisk are already in use in some quizzes.)';
$string['ranking_block_title'] = 'Ranking';
$string['ranking_block_title_anonymised'] = 'Ranking (anonymised)';
$string['rate_all_column_name'] = 'Community Rating';
$string['rate_column_name'] = 'Rating';
$string['rate_error'] = 'Please rate';
$string['rate_help'] = 'Rate this question';
$string['rate_help_help'] = "Rate this question.<br />1 star is very bad and 5 stars is very good";
$string['rate_one_star_desc'] = '1 star selected';
$string['rate_multi_stars_desc'] = '{$a} stars selected';
$string['rate_points'] = 'Points';
$string['rate_title'] = 'Rate';
$string['ratingbar_title'] = 'Rating bar';
$string['remove_comment'] = 'Remove';
$string['remove_comment_label'] = 'Remove comment';
$string['reportquiz_admin_title'] = 'Student statistics';
$string['reportquiz_stats_all_last_attempt_correct'] = 'Community average of last correct answers';
$string['reportquiz_stats_all_last_attempt_incorrect'] = 'Community average of last incorrect answers';
$string['reportquiz_stats_all_percentage_correct_answers'] = 'Community percentage of correct answers';
$string['reportquiz_stats_all_percentage_correct_answers_help'] = 'Sum of correct answers / sum of all answers.';
$string['reportquiz_stats_all_progress'] = 'Average Community Progress ';
$string['reportquiz_stats_all_progress_help'] = 'Average community progress based on all community members.';
$string['reportquiz_stats_all_question_attempts_correct'] = 'Community average of correct answers';
$string['reportquiz_stats_all_question_attempts_incorrect'] = 'Community average of incorrect answers';
$string['reportquiz_stats_all_questions_answered'] = 'Community average of all answers';
$string['reportquiz_stats_all_questions_answered_help'] = 'Average number of answers given by all community members.';
$string['reportquiz_stats_all_questions_approved'] = 'Number of approved questions';
$string['reportquiz_stats_all_questions_approved_help'] = 'Teachers can approve questions to verify correctness. This is the number of all approved questions within this StudentQuiz.';
$string['reportquiz_stats_all_questions_created'] = 'Number of questions in this StudentQuiz';
$string['reportquiz_stats_all_questions_created_help'] = 'Number of questions created by the community';
$string['reportquiz_stats_all_rates_average'] = 'Rating average of all questions';
$string['reportquiz_stats_all_rates_average_help'] = 'The rating of each question is the average of stars it received from the community.  Example: The community created 4 questions. If  question A was rated 3 stars by the community, question B = 4 stars, question C = 2 stars and question D = 5 stars, then the rating average of all questions is 3.5.';
$string['reportquiz_stats_own_last_attempt_correct'] = 'Number of your last correct answers';
$string['reportquiz_stats_own_last_attempt_incorrect'] = 'Number of your last incorrect answers';
$string['reportquiz_stats_own_percentage_correct_answers'] = 'Percentage of your correct answers';
$string['reportquiz_stats_own_percentage_correct_answers_help'] = 'Percentage of all your correct answers from the set of all your given answers in this StudentQuiz. Partly correct answers count as wrong answers.';
$string['reportquiz_stats_own_progress'] = 'Personal Progress';
$string['reportquiz_stats_own_progress_help'] = 'Percentage of your last correct answers from the set of all questions within this StudentQuiz. Partly correct answers count as wrong answers.';
$string['reportquiz_stats_own_question_attempts_correct'] = 'Total of your correct answers';
$string['reportquiz_stats_own_question_attempts_incorrect'] = 'Total of your incorrect answers';
$string['reportquiz_stats_own_questions_answered'] = 'Total of all your answers';
$string['reportquiz_stats_own_questions_answered_help'] = 'Number of all your given answers within this StudentQuiz.';
$string['reportquiz_stats_own_questions_approved'] = 'Number of your approved questions';
$string['reportquiz_stats_own_questions_approved_help'] = 'Teachers can approve questions to verify correctness. This is the number of your approved questions within this StudentQuiz.';
$string['reportquiz_stats_own_questions_created'] = 'Number of questions you have contributed';
$string['reportquiz_stats_own_questions_created_help'] = 'Number of questions you have contributed to this StudentQuiz.';
$string['reportquiz_stats_own_rates_average'] = 'Your received rating average';
$string['reportquiz_stats_own_rates_average_help'] = 'The rating of each question is the average of stars it received from the community.  Example: You created the questions A and B. If your question A was rated 3 stars by the community and your question B was rated 4 stars, then your received rating average is 3.5.';
$string['reportquiz_stats_title'] = 'Statistics';
$string['reportquiz_total_attempt'] = 'Times user run the quiz';
$string['reportquiz_total_obtained_marks'] = 'Grade total';
$string['reportquiz_total_questions_answered'] = 'Total of answers';
$string['reportquiz_total_questions_right'] = 'Total of correct answers';
$string['reportquiz_total_questions_wrong'] = 'Wrong answers';
$string['reportquiz_total_users'] = 'Number of participants';
$string['reportrank_table_column_approvedquestions'] = 'Points for approved questions';
$string['reportrank_table_column_communitystatus'] = 'Community Statistics';
$string['reportrank_table_column_correctanswers'] = 'Correct answers';
$string['reportrank_table_column_countquestions'] = 'Points for questions created';
$string['reportrank_table_column_description'] = 'Description';
$string['reportrank_table_column_factor'] = 'Factor';
$string['reportrank_table_column_fullname'] = 'Fullname';
$string['reportrank_table_column_incorrectanswers'] = 'Incorrect Answers';
$string['reportrank_table_column_lastcorrectanswers'] = 'Points for latest correct attemps';
$string['reportrank_table_column_lastincorrectanswers'] = 'Points for latest wrong attempts';
$string['reportrank_table_column_points'] = 'Points';
$string['reportrank_table_column_progress'] = 'Personal progress';
$string['reportrank_table_column_quantifier_name'] = 'Name';
$string['reportrank_table_column_rank'] = 'Rank';
$string['reportrank_table_column_summeanrates'] = 'Points for stars received';
$string['reportrank_table_column_total_points'] = 'Total Points';
$string['reportrank_table_column_value'] = 'Value';
$string['reportrank_table_column_yourstatus'] = 'Personal Statistics';
$string['reportrank_table_quantifier_caption'] = 'How your points are calculated';
$string['reportrank_table_title'] = 'Student ranking - Top 10';
$string['reportrank_table_title_for_manager'] = 'Student ranking';
$string['reportrank_title'] = 'Ranking';
$string['review_button'] = 'Review';
$string['settings_allowallqtypes'] = 'Allow all question types';
$string['settings_allowedqtypes'] = 'Allowed question types';
$string['settings_allowedqtypes_help'] = 'Limit the allowed question types to the selected entries';
$string['settings_anonymous'] = 'Student anonymizer';
$string['settings_anonymous_help'] = 'Students cannot see each other’s names.';
$string['settings_anonymous_label'] = 'Make students anonymous';
$string['settings_approvedquantifier'] = 'Approved question factor';
$string['settings_approvedquantifier_help'] = 'Points for each approved question';
$string['settings_approvedquantifier_label'] = 'Points for each question approved';
$string['settings_availability_close_answering_from'] = 'Closed for answering from';
$string['settings_availability_close_submission_from'] = 'Closed for question submission from';
$string['settings_availability_open_answering_from'] = 'Open for answering from';
$string['settings_availability_open_submission_from'] = 'Open for question submission from';
$string['settings_excluderoles'] = 'Exclude roles in ranking';
$string['settings_excluderoles_label'] = 'Roles in ranking to exclude';
$string['settings_excluderoles_help'] = 'Selected roles are hidden in the rankings, enrolled users in these roles can still participate normally in the activity';
$string['settings_forcerating'] = 'Enforce rating';
$string['settings_forcerating_help'] = 'Enforce rating in the question attempt';
$string['settings_forcecommenting'] = 'Enforce commenting';
$string['settings_forcecommenting_help'] = 'Enforce commenting in the question attempt';
$string['settings_lastcorrectanswerquantifier'] = 'Latest correct answer factor';
$string['settings_lastcorrectanswerquantifier_help'] = 'Points for each correct answer on the last attempt';
$string['settings_lastcorrectanswerquantifier_label'] = 'Points for latest correct answers';
$string['settings_lastincorrectanswerquantifier'] = 'Latest wrong answer factor';
$string['settings_lastincorrectanswerquantifier_help'] = 'Points for each wrong or partially wrong answer on the last attempt';
$string['settings_lastincorrectanswerquantifier_label'] = 'Points for latest wrong answers';
$string['settings_questionquantifier'] = 'Created question factor';
$string['settings_questionquantifier_help'] = 'Points for each created question';
$string['settings_questionquantifier_label'] = 'Points for each question created';
$string['settings_quizpracticebehaviour'] = 'Rating and commenting';
$string['settings_quizpracticebehaviour_help'] = 'Allow students to rate and comment questions during the quiz attempt';
$string['settings_quizpracticebehaviour_label'] = 'Rating and commenting';
$string['settings_ratequantifier'] = 'Rating factor';
$string['settings_ratequantifier_help'] = 'Points for each star received.';
$string['settings_ratequantifier_label'] = 'Multiplier for the average of stars received for a question';
$string['settings_removeqbehavior'] = 'Remove question behavior plugin StudentQuiz';
$string['settings_removeqbehavior_help'] = 'This info should appear only once during update. We inform you that we detected our question behavior plugin StudentQuiz is installed. This plugin is not required anymore and thus we try to automatically remove it. If you still see this setting, please uninstall the question behavior plugin StudentQuiz manually <a href="{$a}">here</a>.';
$string['settings_removeqbehavior_label'] = 'Remove question behavior plugin StudentQuiz';
$string['settings_section_description_default'] = 'These values define the default values when creating a new studentquiz activity.';
$string['settings_section_header_question'] = 'Question settings';
$string['settings_section_header_ranking'] = 'Ranking settings';
$string['settings_publish_new_questions'] = 'Publish new questions';
$string['settings_publish_new_questions_help'] = 'Automatically publish new created questions';
$string['show_less'] = 'Show less';
$string['show_more'] = 'Show more';
$string['slot_of_slot'] = 'Question {$a->slot} of {$a->slots} in this set';
$string['start_quiz_button'] = 'Start Quiz';
$string['state_changed'] = 'Changed';
$string['state_change_tooltip'] = 'Question is {$a}. Click here to change the state of this question';
$string['state_column_name'] = 'State';
$string['state_column_name_veryshort'] = 'S';
$string['state_disapproved'] = 'Disapproved';
$string['state_toggle'] = 'Change state';
$string['state_approved'] = 'Approved';
$string['state_new'] = 'New';
$string['statistic_block_approvals'] = 'Questions approved';
$string['statistic_block_created'] = 'Questions created';
$string['statistic_block_disapprovals'] = 'Questions disapproved';
$string['statistic_block_new_changed'] = 'Questions new/changed';
$string['statistic_block_progress_available'] = 'Questions available';
$string['statistic_block_progress_last_attempt_correct'] = 'Latest attempt correct';
$string['statistic_block_progress_last_attempt_incorrect'] = 'Latest attempt wrong';
$string['statistic_block_progress_never'] = 'Questions never answered';
$string['statistic_block_title'] = 'My Progress';
$string['studentquiz'] = 'studentquiz';
$string['studentquiz:addinstance'] = 'Add new instance for StudentQuiz';
$string['studentquiz:emailnotifyapproved'] = 'Question approved notification';
$string['studentquiz:emailnotifychanged'] = 'Question changed notification';
$string['studentquiz:emailnotifycommentadded'] = 'Comment added notification';
$string['studentquiz:emailnotifycommentdeleted'] = 'Comment deleted notification';
$string['studentquiz:emailnotifydeleted'] = 'Question deleted notification';
$string['studentquiz:manage'] = 'Moderate questions on StudentQuiz';
$string['studentquiz:previewothers'] = 'Preview questions of others on StudentQuiz';
$string['studentquiz:submit'] = 'Submit questions on StudentQuiz';
$string['studentquiz:unhideanonymous'] = 'Can see real names even when anonymize is active';
$string['studentquiz:view'] = 'View questions on StudentQuiz';
$string['studentquizname'] = 'StudentQuiz Name';
$string['studentquizname_help'] = 'The name of this StudentQuiz Activity';
$string['submissionendbeforestart'] = 'Submissions deadline can not be specified before the open for submissions date';
$string['tags'] = 'Tags';
$string['unapprove'] = 'Unapprove';
