<?php
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
$string['modulename_help'] = 'The StudentQuiz activity allows students to add questions for the crowd. In the StudentQuiz overview the students can filter questions. They also can use the filtered questions in the crowd to practice. The teacher has an option to anonymize the created by column.<br><br>The StudentQuiz activity awards the students with points to motivate them to add and practice. The Points are listed in a ranking table.<br><br>For more information read the <a href="https://github.com/frankkoch/moodle-mod_studentquiz/blob/master/manuals/User-Manual.pdf">student manual</a>.';
$string['studentquizname'] = 'StudentQuiz Name';
$string['studentquizname_help'] = 'The name of this StudentQuiz Activity';
$string['studentquiz'] = 'studentquiz';
$string['pluginname'] = 'StudentQuiz';
$string['pluginadministration'] = 'StudentQuiz Administration';

// Labels and buttons.
$string['vote_column_name'] = 'Rating';
$string['practice_column_name'] = 'Attempts';
$string['comment_column_name'] = 'Comments';
$string['difficulty_level_column_name'] = 'Difficulty';
$string['approved_column_name'] = 'Approved';
$string['vote_points'] = 'Points';
$string['number_column_name'] = 'Number';
$string['latest_column_name'] = 'Latest';
$string['average_column_name'] = 'Average';
$string['mine_column_name'] = 'Mine';
$string['myattempts_column_name'] = 'My Attempts';
$string['mydifficulty_column_name'] = 'My Difficulty';
$string['mylastattempt_column_name'] = 'My Last Attempt';
$string['myvote_column_name'] = 'My Vote';
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
$string['no_votes'] = 'n.a.';
$string['no_practice'] = 'n.a.';
$string['no_myvote'] = 'n.a.';
$string['no_comment'] = 'n.a.';
$string['no_myattempts'] = 'n.a.';
$string['no_mydifficulty'] = 'n.a.';
$string['no_mylastattempt'] = 'n.a.';
$string['approved'] = '✓';
$string['not_approved'] = '✗';
$string['lastattempt_right'] = '✓';
$string['lastattempt_wrong'] = '✗';

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

// Blocks
$string['statistic_block_title'] = 'My Progress';
$string['ranking_block_title'] = 'Ranking';
$string['statistic_block_progress'] = 'There are {$a->total} questions available. You answered {$a->group} at least once and {$a->one} correctly on your last attempt.';
$string['statistic_block_approvals'] = '{$a->one} of your {$a->total} created questions are approved.';

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
$string['filter_label_onlygood'] = 'Good Questions';
$string['filter_label_onlynew'] = 'New Questions';
$string['filter_label_onlydifficult'] = 'Difficult Questions';
$string['filter_label_onlyapproved'] = 'Approved Questions';
$string['filter_label_practice'] = 'Attempts';
$string['filter_label_comment'] = 'Comments';
$string['filter_label_difficulty_level'] = 'Difficulty';
$string['filter_label_mylastattempt'] = 'My latest attempt';
$string['filter_label_myattempts'] = 'My attempts';
$string['filter_label_mydifficulty'] = 'My difficulty';
$string['filter_label_myvote'] = 'My Rating';
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
$string['settings_votequantifier_help'] = 'Points multiplier for each qustions average vote rating.';
$string['settings_correctanswerquantifier'] = 'Correct answer quantifier';
$string['settings_correctanswerquantifier_label'] = 'Points for each correct answer';
$string['settings_correctanswerquantifier_help'] = 'Points received for answering a question correctly.';
$string['settings_incorrectanswerquantifier'] = 'Incorrect answer quantifier';
$string['settings_incorrectanswerquantifier_label'] = 'Points for each wrong answer';
$string['settings_incorrectanswerquantifier_help'] = 'Points received for answering a question wrongly.';
$string['settings_removeemptysections'] = 'Remove empty sections';
$string['settings_removeemptysections_label'] = 'Remove empty sections at the end of the course';
$string['settings_removeemptysections_help'] = 'StudentQuiz 2.0.3 and prior used a socalled orphaned section (hidden Topic) with number 999. Since Moodle 3.3 the moodle import creates until 999 sections, even if there are no such sections described in the export file. Uncheck this option, if you encounter side effects because of this. You\'ll have to delete then the unwanted sections yourself.';
$string['settings_removeqbehavior'] = 'Remove question behavior plugin StudentQuiz';
$string['settings_removeqbehavior_label'] = 'Remove question behavior plugin StudentQuiz';
$string['settings_removeqbehavior_help'] = 'This info should appear only once during update. We inform you that we detected our question behavior plugin StudentQuiz is installed. This plugin is not required anymore and thus we try to automatically remove it. If you still see this setting, please uninstall the question behavior plugin StudentQuiz manually <a href="{$a}">here</a>.';
$string['settings_allowallqtypes'] = 'Allow all question types';
$string['settings_allowedqtypes'] = 'Allowed question types';
$string['settings_allowedqtypes_help'] = 'Here you specify the type of questions that are allowed';

// Error messages.
$string['needtoallowatleastoneqtype'] = 'You need to allow at least one question type';

// Admin settings.
$string['rankingsettingsheader'] = 'Ranking settings';
$string['rankingsettingsdescription'] = 'The values you set here define the ranking default values that are used in the settings form when you create a new studentquiz.';
$string['importsettingsheader'] = 'Import settings';
$string['importsettingsdescription'] = 'Here you set various settings to change the behavior of imports';

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
$string['reportrank_table_progress_caption'] = 'Current progress';
$string['reportquiz_stats_all_questions_created'] = 'Number of questions in this community';
$string['reportquiz_stats_own_questions_created'] = 'Number of questions you have contributed';
$string['reportquiz_stats_all_questions_approved'] = 'Number of approved questions';
$string['reportquiz_stats_own_questions_approved'] = 'Number of your approved questions';
$string['reportquiz_stats_own_votes_average'] = 'Your received rating average';
$string['reportquiz_stats_all_votes_average'] = 'Overall voting average';
$string['reportquiz_stats_own_question_attempts_correct'] = 'You have answered correctly';
$string['reportquiz_stats_all_question_attempts_correct'] = 'Average attempt correct';
$string['reportquiz_stats_own_question_attempts_incorrect'] = 'You have answered incorrectly';
$string['reportquiz_stats_all_question_attempts_incorrect'] = 'Average attempt incorrect';
$string['reportquiz_stats_own_last_attempt_correct'] = 'Your last attempt correct';
$string['reportquiz_stats_all_last_attempt_correct'] = 'Average last attempt correct';
$string['reportquiz_stats_own_last_attempt_incorrect'] = 'Your last attempt incorrect';
$string['reportquiz_stats_all_last_attempt_incorrect'] = 'Average last attempt incorrect';
$string['reportquiz_stats_own_questions_answered'] = 'Number of your answers';
$string['reportquiz_stats_all_questions_answered'] = 'Community number of all answers';
$string['reportrank_table_column_yourstatus'] = 'Personal Status';
$string['reportrank_table_column_communitystatus'] = 'Community Status';
$string['reportquiz_stats_own_progress'] = 'Personal Progress';
$string['reportquiz_stats_all_progress'] = 'Average Community Progress ';
$string['reportrank_table_column_value'] = 'Value';

// Report rank.
$string['reportrank_title'] = 'Ranking';
$string['reportrank_table_quantifier_caption'] = 'How your points are calculated';
$string['reportrank_table_title'] = 'Student ranking';
$string['reportrank_table_column_rank'] = 'Rank';
$string['reportrank_table_column_fullname'] = 'Fullname';
$string['reportrank_table_column_points'] = 'Points';
$string['reportrank_table_column_total_points'] = 'Total Points';
$string['reportrank_table_column_countquestions'] = 'Points for questions';
$string['reportrank_table_column_approvedquestions'] = 'Points for approved questions';
$string['reportrank_table_column_summeanvotes'] = 'Points for votes';
$string['reportrank_table_column_correctanswers'] = 'Points for correct answers';
$string['reportrank_table_column_incorrectanswers'] = 'Points for incorrect Answers';
$string['reportrank_table_column_progress'] = 'Progress';
$string['reportrank_table_column_quantifier_name'] = 'Name';
$string['reportrank_table_column_factor'] = 'Factor';
$string['reportrank_table_column_description'] = 'Description';

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

// Comment deleted notification email for question author
$string['emailcommentdeletedsubject'] = 'Comment has been deleted to question: {$a->questionname}';
$string['emailcommentdeletedsmall'] = 'The comment to your question \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emailcommentdeletedbody'] = 'Dear {$a->recepientname},

The comment on \'{$a->commenttime}\' to your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been deleted by \'{$a->actorname}\' at \'{$a->timestamp}\'. 

The comment was: \'{$a->commenttext}\'

You can review this question at: {$a->questionurl}.';

// Comment deleted notification email for comment author
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
$string['vote_title'] = 'Rate';
$string['vote_help_help'] = "Rate this question. \n 1 star is very bad and 5 stars is very good";
$string['vote_help'] = 'Rate this question';
$string['vote_error'] = 'Please Rate';
$string['comment_help'] = 'Write a comment';
$string['comment_help_help'] = 'Write a comment to the question';
