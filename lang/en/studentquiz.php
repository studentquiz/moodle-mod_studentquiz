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
$string['modulename_help'] = 'The StudentQuiz activity allows students to add questions for the crowd. In the StudentQuiz overview the students can filter questions. They also can use the filtered questions in the crowd to practice. The teacher has an option to anonymize the created by column.<br><br>The StudentQuiz activity awards the students with points to motivate them to add and practice. The Points are listed in a ranking table.<br><br>For more information read the <a href="https://studentquiz.hsr.ch/docs/">StudentQuiz documentation</a>.';
$string['studentquizname'] = 'StudentQuiz Name';
$string['studentquizname_help'] = 'The name of this StudentQuiz Activity';
$string['studentquiz'] = 'studentquiz';
$string['pluginname'] = 'StudentQuiz';
$string['pluginadministration'] = 'StudentQuiz Administration';

// Labels and buttons.
$string['rate_column_name'] = 'Rating';
$string['rate_all_column_name'] = 'Community Rating';
$string['practice_column_name'] = 'Attempts';
$string['comment_column_name'] = 'Comments';
$string['difficulty_level_column_name'] = 'Difficulty';
$string['approved_column_name'] = 'Approved';
$string['rate_points'] = 'Points';
$string['number_column_name'] = 'Number';
$string['latest_column_name'] = 'Latest';
$string['average_column_name'] = 'Average';
$string['mine_column_name'] = 'Mine';
$string['myattempts_column_name'] = 'My Attempts';
$string['mydifficulty_column_name'] = 'My Difficulty';
$string['difficulty_all_column_name'] = 'Community Difficulty';
$string['mylastattempt_column_name'] = 'My Last Attempt';
$string['myrate_column_name'] = 'My Rating';
$string['more'] = 'More';
$string['start_quiz_button'] = 'Start Quiz';
$string['review_button'] = 'Review';
$string['finish_button'] = 'Finish';
$string['next_button'] = 'Next';
$string['previous_button'] = 'Previous';
$string['nav_export'] = 'Export';
$string['nav_import'] = 'Import';
$string['createnewquestionfirst'] = 'Create first question';
$string['createnewquestion'] = 'Create new question';
$string['no_difficulty_level'] = 'n.a.';
$string['tags'] = 'Tags';
$string['no_tags'] = 'n.a.';
$string['no_rates'] = 'n.a.';
$string['no_practice'] = 'n.a.';
$string['no_myrate'] = 'n.a.';
$string['no_comment'] = 'n.a.';
$string['no_myattempts'] = 'n.a.';
$string['no_mydifficulty'] = 'n.a.';
$string['no_mylastattempt'] = 'n.a.';
$string['approved'] = '✓';
$string['not_approved'] = '✗';
$string['lastattempt_right'] = '✓';
$string['lastattempt_wrong'] = '✗';
$string['slot_of_slot'] = 'Question {$a->slot} of {$a->slots} in this set';
$string['questions'] = 'questions';
$string['pagesize'] = 'Page size:';
$string['approve'] = 'Approve';
$string['unapprove'] = 'Unapprove';
$string['approve_toggle'] = 'Un/Approve';
$string['approveselectedscheck'] = 'Are you sure you want to un-/approve the following questions?<br /><br />{$a}';
$string['questionsinuse'] = '(* Questions marked by an asterisk are already in use in some quizzes.)';
$string['creator_anonym_firstname'] = 'Anonymous';
$string['creator_anonym_lastname'] = 'Student';
$string['no_questions_selected_message'] = 'Please select at least one question to start the quiz.';
$string['progress_bar_caption'] = 'Your progress in this StudentQuiz activity';
$string['no_questions_filter'] = 'None of the questions matched your filter criteria. Reset the filter to see all.';
$string['no_questions_add'] = 'There are no questions in this StudentQuiz. Feel free to add some questions.';

// Blocks.
$string['statistic_block_title'] = 'My Progress';
$string['ranking_block_title'] = 'Ranking';
$string['ranking_block_title_anonymised'] = 'Ranking (anonymised)';
$string['statistic_block_progress_never'] = 'Questions never answered';
$string['statistic_block_progress_last_attempt_correct'] = 'Latest attempt correct';
$string['statistic_block_progress_last_attempt_incorrect'] = 'Latest attempt wrong';
$string['statistic_block_progress_available'] = 'Questions available';
$string['statistic_block_created'] = 'Questions created';
$string['statistic_block_approvals'] = 'Questions approved';

// Filters.
$string['filter'] = 'Filter';
$string['filter_label_question'] = 'Question title';
$string['filter_label_approved'] = 'Approved questions';
$string['filter_label_firstname'] = 'Firstname';
$string['filter_label_surname'] = 'Lastname';
$string['filter_label_createdate'] = 'Creation';
$string['filter_label_questiontext'] = 'Question content';
$string['filter_label_tags'] = 'Tag';
$string['filter_label_rates'] = 'Rating';
$string['filter_label_onlygood'] = 'Good';
$string['filter_label_onlygood_help'] = 'Question with an average rating of at at least {$a} stars';
$string['filter_label_onlynew'] = 'Unanswered';
$string['filter_label_onlynew_help'] = 'Questions you have never answered before';
$string['filter_label_onlymine'] = 'Mine';
$string['filter_label_onlymine_help'] = 'Questions you created.';
$string['filter_label_onlydifficult'] = 'Difficult for all';
$string['filter_label_onlydifficult_help'] = 'Question with an average difficulty of more than {$a}%';
$string['filter_label_onlydifficultforme'] = 'Difficult for me';
$string['filter_label_onlydifficultforme_help'] = 'Question with my difficulty of more than {$a}%';
$string['filter_label_onlyapproved'] = 'Approved';
$string['filter_label_onlyapproved_help'] = 'Questions approved by your teacher';
$string['filter_label_practice'] = 'Attempts';
$string['filter_label_comment'] = 'Comments';
$string['filter_label_difficulty_level'] = 'Difficulty';
$string['filter_label_mylastattempt'] = 'My latest attempt';
$string['filter_label_myattempts'] = 'My attempts';
$string['filter_label_mydifficulty'] = 'My difficulty';
$string['filter_label_myrate'] = 'My Rating';
$string['filter_label_fast_filters'] = 'Fast filter for questions';
$string['filter_ishigher'] = 'Is higher';
$string['filter_islower'] = 'Is lower';
$string['filter_label_show_mine'] = 'My questions';

// General settings.
$string['settings_anonymous'] = 'Student anonymizer';
$string['settings_anonymous_label'] = 'Make students anonymous';
$string['settings_anonymous_help'] = 'Students cannot see each other’s names.';
$string['settings_quizpracticebehaviour'] = 'Rating and commenting';
$string['settings_quizpracticebehaviour_label'] = 'Rating and commenting';
$string['settings_quizpracticebehaviour_help'] = 'Allow students to rate and comment questions during the quiz attempt';
$string['settings_questionquantifier'] = 'Created question factor';
$string['settings_questionquantifier_label'] = 'Points for each question created';
$string['settings_questionquantifier_help'] = 'Points for each created question';
$string['settings_approvedquantifier'] = 'Approved question factor';
$string['settings_approvedquantifier_label'] = 'Points for each question approved';
$string['settings_approvedquantifier_help'] = 'Points for each approved question';
$string['settings_ratequantifier'] = 'Rating factor';
$string['settings_ratequantifier_label'] = 'Multiplier for the average of stars received for a question';
$string['settings_ratequantifier_help'] = 'Points for each star received.';
$string['settings_lastcorrectanswerquantifier'] = 'Latest correct answer factor';
$string['settings_lastcorrectanswerquantifier_label'] = 'Points for latest correct answers';
$string['settings_lastcorrectanswerquantifier_help'] = 'Points for each correct answer on the last attempt';
$string['settings_lastincorrectanswerquantifier'] = 'Latest wrong answer factor';
$string['settings_lastincorrectanswerquantifier_label'] = 'Points for latest wrong answers';
$string['settings_lastincorrectanswerquantifier_help'] = 'Points for each wrong or partially wrong answer on the last attempt';
$string['settings_removeqbehavior'] = 'Remove question behavior plugin StudentQuiz';
$string['settings_removeqbehavior_label'] = 'Remove question behavior plugin StudentQuiz';
$string['settings_removeqbehavior_help'] = 'This info should appear only once during update. We inform you that we detected our question behavior plugin StudentQuiz is installed. This plugin is not required anymore and thus we try to automatically remove it. If you still see this setting, please uninstall the question behavior plugin StudentQuiz manually <a href="{$a}">here</a>.';
$string['settings_allowallqtypes'] = 'Allow all question types';
$string['settings_allowedqtypes'] = 'Allowed question types';
$string['settings_allowedqtypes_help'] = 'Here you specify the type of questions that are allowed';
$string['settings_qtypes_default_new_activity'] = 'The following are default for a new activity';

// Error messages.
$string['needtoallowatleastoneqtype'] = 'You need to allow at least one question type';
$string['please_enrole_message'] = 'Please enroll in this course to see your personal progress';

// Admin settings.
$string['rankingsettingsheader'] = 'Ranking settings';
$string['rankingsettingsdescription'] = 'The values you set here define the ranking default values that are used in the settings form when you create a new studentquiz.';
$string['defaultquestiontypessettingsheader'] = 'Default question types';

// Report Dashboard.
$string['reportquiz_total_attempt'] = 'Times user run the quiz';
$string['reportquiz_total_questions_answered'] = 'Total of answers';
$string['reportquiz_total_questions_right'] = 'Total of correct answers';
$string['reportquiz_total_questions_wrong'] = 'Wrong answers';
$string['reportquiz_total_obtained_marks'] = 'Grade total';
$string['reportquiz_total_users'] = 'Number of participants';
$string['reportquiz_admin_title'] = 'Student statistics';

// Report stat.
$string['reportquiz_stats_title'] = 'Statistics';
$string['reportquiz_stats_all_questions_created'] = 'Number of questions in this StudentQuiz';
$string['reportquiz_stats_all_questions_created_help'] = 'Number of questions created by the community';
$string['reportquiz_stats_own_questions_created'] = 'Number of questions you have contributed';
$string['reportquiz_stats_own_questions_created_help'] = 'Number of questions you have contributed to this StudentQuiz.';
$string['reportquiz_stats_all_questions_approved'] = 'Number of approved questions';
$string['reportquiz_stats_all_questions_approved_help'] = 'Teachers can approve questions to verify correctness. This is the number of all approved questions within this StudentQuiz.';
$string['reportquiz_stats_own_questions_approved'] = 'Number of your approved questions';
$string['reportquiz_stats_own_questions_approved_help'] = 'Teachers can approve questions to verify correctness. This is the number of your approved questions within this StudentQuiz.';
$string['reportquiz_stats_own_rates_average'] = 'Your received rating average';
$string['reportquiz_stats_own_rates_average_help'] = 'The rating of each question is the average of stars it received from the community.  Example: You created the questions A and B. If your question A was rated 3 stars by the community and your question B was rated 4 stars, then your received rating average is 3.5.';
$string['reportquiz_stats_all_rates_average'] = 'Rating average of all questions';
$string['reportquiz_stats_all_rates_average_help'] = 'The rating of each question is the average of stars it received from the community.  Example: The community created 4 questions. If  question A was rated 3 stars by the community, question B = 4 stars, question C = 2 stars and question D = 5 stars, then the rating average of all questions is 3.5.';
$string['reportquiz_stats_own_question_attempts_correct'] = 'Total of your correct answers';
$string['reportquiz_stats_all_question_attempts_correct'] = 'Community average of correct answers';
$string['reportquiz_stats_own_question_attempts_incorrect'] = 'Total of your incorrect answers';
$string['reportquiz_stats_all_question_attempts_incorrect'] = 'Community average of incorrect answers';
$string['reportquiz_stats_own_last_attempt_correct'] = 'Number of your last correct answers';
$string['reportquiz_stats_all_last_attempt_correct'] = 'Community average of last correct answers';
$string['reportquiz_stats_own_last_attempt_incorrect'] = 'Number of your last incorrect answers';
$string['reportquiz_stats_all_last_attempt_incorrect'] = 'Community average of last incorrect answers';
$string['reportquiz_stats_own_questions_answered'] = 'Total of all your answers';
$string['reportquiz_stats_own_questions_answered_help'] = 'Number of all your given answers within this StudentQuiz.';
$string['reportquiz_stats_all_questions_answered'] = 'Community average of all answers';
$string['reportquiz_stats_all_questions_answered_help'] = 'Average number of answers given by all community members.';
$string['reportquiz_stats_own_percentage_correct_answers'] = 'Percentage of your correct answers';
$string['reportquiz_stats_own_percentage_correct_answers_help'] = 'Percentage of all your correct answers from the set of all your given answers in this StudentQuiz. Partly correct answers count as wrong answers.';
$string['reportquiz_stats_all_percentage_correct_answers'] = 'Community percentage of correct answers';
$string['reportquiz_stats_all_percentage_correct_answers_help'] = 'Sum of correct answers / sum of all answers.';
$string['reportrank_table_column_yourstatus'] = 'Personal Statistics';
$string['reportrank_table_column_communitystatus'] = 'Community Statistics';
$string['reportquiz_stats_own_progress'] = 'Personal Progress';
$string['reportquiz_stats_own_progress_help'] = 'Percentage of your last correct answers from the set of all questions within this StudentQuiz. Partly correct answers count as wrong answers.';
$string['reportquiz_stats_all_progress'] = 'Average Community Progress ';
$string['reportquiz_stats_all_progress_help'] = 'Average community progress based on all community members.';
$string['reportrank_table_column_value'] = 'Value';

// Report rank.
$string['reportrank_title'] = 'Ranking';
$string['reportrank_table_quantifier_caption'] = 'How your points are calculated';
$string['reportrank_table_title'] = 'Student ranking - Top 10';
$string['reportrank_table_title_for_manager'] = 'Student ranking';
$string['reportrank_table_column_rank'] = 'Rank';
$string['reportrank_table_column_fullname'] = 'Fullname';
$string['reportrank_table_column_points'] = 'Points';
$string['reportrank_table_column_total_points'] = 'Total Points';
$string['reportrank_table_column_countquestions'] = 'Points for questions created';
$string['reportrank_table_column_approvedquestions'] = 'Points for approved questions';
$string['reportrank_table_column_summeanrates'] = 'Points for stars received';
$string['reportrank_table_column_correctanswers'] = 'Correct answers';
$string['reportrank_table_column_incorrectanswers'] = 'Incorrect Answers';
$string['reportrank_table_column_lastcorrectanswers'] = 'Points for latest correct attemps';
$string['reportrank_table_column_lastincorrectanswers'] = 'Points for latest wrong attempts';
$string['reportrank_table_column_progress'] = 'Personal progress';
$string['reportrank_table_column_quantifier_name'] = 'Name';
$string['reportrank_table_column_factor'] = 'Factor';
$string['reportrank_table_column_description'] = 'Description';

// Task.
$string['task_delete_quiz_after_migration'] = 'Delete quiz activities after data migration from an import or plugin update';

// Permission.
$string['studentquiz:addinstance'] = 'Add new instance for StudentQuiz';
$string['studentquiz:view'] = 'View questions on StudentQuiz';
$string['studentquiz:previewothers'] = 'Preview questions of others on StudentQuiz';
$string['studentquiz:submit'] = 'Submit questions on StudentQuiz';
$string['studentquiz:manage'] = 'Moderate questions on StudentQuiz';
$string['studentquiz:unhideanonymous'] = 'Can see real names even when anonymize is active';

// Notifications.
$string['studentquiz:emailnotifychanged'] = 'Question changed notification';
$string['studentquiz:emailnotifydeleted'] = 'Question deleted notification';
$string['studentquiz:emailnotifyapproved'] = 'Question approved notification';
$string['studentquiz:emailnotifycommentadded'] = 'Comment added notification';
$string['studentquiz:emailnotifycommentdeleted'] = 'Comment deleted notification';

// Message provider.
$string['messageprovider:changed'] = 'Question changed notification';
$string['messageprovider:deleted'] = 'Question deleted notification';
$string['messageprovider:approved'] = 'Question approved notification';
$string['messageprovider:unapproved'] = 'Question unapproved notification';
$string['messageprovider:commentadded'] = 'Comment added notification';
$string['messageprovider:commentdeleted'] = 'Comment deleted notification';
$string['messageprovider:minecommentdeleted'] = 'My comment deleted notification';

// Change notification email.
$string['emailchangedsubject'] = 'Question has been modified: {$a->questionname}';
$string['emailchangedsmall'] = 'Your question \'{$a->questionname}\' has been modified by {$a->actorname}.';
$string['emailchangedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in course \'{$a->coursename}\' in StudentQuiz activity \'{$a->modulename}\' has been modified by \'{$a->actorname}\' at \'{$a->timestamp}\'.

You can review this question at: {$a->questionurl}.';

// Question deleted notification email.
$string['emaildeletedsubject'] = 'Question has been deleted: {$a->questionname}';
$string['emaildeletedsmall'] = 'Your question \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emaildeletedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been deleted by \'{$a->actorname}\' at \'{$a->timestamp}\'.';

// Approve notification email.
$string['emailapprovedsubject'] = 'Question has been approved: {$a->questionname}';
$string['emailapprovedsmall'] = 'Your question \'{$a->questionname}\' has been approved by {$a->actorname}.';
$string['emailapprovedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in course \'{$a->coursename}\' in StudentQuiz activity \'{$a->modulename}\' has been approved by \'{$a->actorname}\' at \'{$a->timestamp}\'.

You can review this question at: {$a->questionurl}.';

// Unapprove notification email.
$string['emailunapprovedsubject'] = 'Question approval has been revoked: {$a->questionname}';
$string['emailunapprovedsmall'] = 'The approval of your question \'{$a->questionname}\' has been revoked by {$a->actorname}.';
$string['emailunapprovedbody'] = 'Dear {$a->recepientname},

The approval of your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been revoked by \'{$a->actorname}\' at \'{$a->timestamp}\'.

You can review this question at: {$a->questionurl}.';

// Comment added notification email.
$string['emailcommentaddedsubject'] = 'Question has been commented: {$a->questionname}';
$string['emailcommentaddedsmall'] = 'Your question \'{$a->questionname}\' has been commented by {$a->username}.';
$string['emailcommentaddedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been commented by \'{$a->actorname}\' at \'{$a->timestamp}\'.

The comment is: \'{$a->commenttext}\'

You can review this question at: {$a->questionurl}.';

// Comment deleted notification email for question author.
$string['emailcommentdeletedsubject'] = 'Comment has been deleted to question: {$a->questionname}';
$string['emailcommentdeletedsmall'] = 'The comment to your question \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emailcommentdeletedbody'] = 'Dear {$a->recepientname},

The comment on \'{$a->commenttime}\' to your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been deleted by \'{$a->actorname}\' at \'{$a->timestamp}\'.

The comment was: \'{$a->commenttext}\'

You can review this question at: {$a->questionurl}.';

// Comment deleted notification email for comment author.
$string['emailminecommentdeletedsubject'] = 'Comment has been deleted to question: {$a->questionname}';
$string['emailminecommentdeletedsmall'] = 'Your comment to question \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emailminecommentdeletedbody'] = 'Dear {$a->recepientname},

Your comment on \'{$a->commenttime}\' to the question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been deleted by \'{$a->actorname}\' at \'{$a->timestamp}\'.

The comment was: \'{$a->commenttext}\'

You can review this question at: {$a->questionurl}.';

// Question behavior.
$string['no_comments'] = 'No comments';
$string['add_comment'] = 'Add comment';
$string['show_more'] = 'Show more';
$string['show_less'] = 'Show less';
$string['rate_title'] = 'Rate';
$string['rate_help_help'] = "Rate this question. \n 1 star is very bad and 5 stars is very good";
$string['rate_help'] = 'Rate this question';
$string['rate_error'] = 'Please Rate';
$string['comment_help'] = 'Write a comment';
$string['comment_help_help'] = 'Write a comment to the question';

$string['ratingbar_title'] = 'Rating bar';
$string['difficulty_title'] = 'Difficulty bar';

// Privacy.
$string['privacy:metadata:studentquiz_rate'] = 'Store rates for questions.';
$string['privacy:metadata:studentquiz_rate:rate'] = 'Rate for the question.';
$string['privacy:metadata:studentquiz_rate:questionid'] = 'ID of the question.';
$string['privacy:metadata:studentquiz_rate:userid'] = 'ID of the user.';

$string['privacy:metadata:studentquiz_progress'] = 'Store progress information of student with this question.';
$string['privacy:metadata:studentquiz_progress:questionid'] = 'ID of the question.';
$string['privacy:metadata:studentquiz_progress:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_progress:studentquizid'] = 'ID of the StudentQuiz.';
$string['privacy:metadata:studentquiz_progress:lastanswercorrect'] = '0: last answer was wrong or undefined, 1: last answer was correct.';
$string['privacy:metadata:studentquiz_progress:attempts'] = 'Number of attempts to answer this question.';
$string['privacy:metadata:studentquiz_progress:correctattempts'] = 'Number of correct answers.';

$string['privacy:metadata:studentquiz_comment'] = 'Store comments for questions.';
$string['privacy:metadata:studentquiz_comment:comment'] = 'Comment of the question.';
$string['privacy:metadata:studentquiz_comment:questionid'] = 'ID of the question.';
$string['privacy:metadata:studentquiz_comment:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_comment:created'] = 'Time created time comment.';

$string['privacy:metadata:studentquiz_practice'] = 'Store quiz practices.';
$string['privacy:metadata:studentquiz_practice:quizcoursemodule'] = 'Quiz course module.';
$string['privacy:metadata:studentquiz_practice:studentquizcoursemodule'] = 'StudentQuiz course module.';
$string['privacy:metadata:studentquiz_practice:userid'] = 'ID of the user.';

$string['privacy:metadata:studentquiz_attempt'] = 'Represents a users attempt to answer a set of questions.';
$string['privacy:metadata:studentquiz_attempt:studentquizid'] = 'ID of the StudentQuiz.';
$string['privacy:metadata:studentquiz_attempt:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_attempt:questionusageid'] = 'ID of the question usage.';
$string['privacy:metadata:studentquiz_attempt:categoryid'] = 'ID of the category.';

$string['migrate_studentquiz_short'] = 'Speed-up this question set';
$string['migrate_studentquiz'] = 'Migrate StudentQuiz questions prior to version 3.2.1 to the faster version with aggregated values';
$string['migrate_ask'] = 'The speed of StudentQuiz improved with version 3.2.1, but this question set is still based on a prior version.
Questions and quizzes will be loaded faster if you run this speed-up migration. You will experience faster loading; nothing else will change.';
$string['migrate_already_done'] = 'Nothing was done because this activity has been migrated already!';
$string['migrated_successful'] = 'This activity has been migrated successfully!';