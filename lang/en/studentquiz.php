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
$string['addpubliccomment'] = 'Add public comment';
$string['addprivatecomment'] = 'Add private comment (these are between the student and tutor only)';
$string['addprivatecomment_help'] = 'These comments are strictly between the question author and the person administrating the activity. This thread is more for the activity administrator to give feedback if and when they change the question state.';
$string['abort_button'] = 'Abort';
$string['add_reply'] = 'Add reply';
$string['after_answering_end_date'] = 'This StudentQuiz closed for answering on {$a}.';
$string['after_submission_end_date'] = 'This StudentQuiz closed for question submission on {$a}.';
$string['answeringndbeforestart'] = 'Answering deadline can not be specified before the open for answering date';
$string['anonymous_user_name'] = 'Anonymous User #{$a}';
$string['api_state_change_success_content'] = 'Question state/visibility changed successfully';
$string['api_state_change_success_title'] = 'Success';
$string['api_state_change_error_title'] = 'Error deleting question';
$string['api_state_change_error_content'] = 'This question cannot be deleted because it has been approved.';
$string['approve'] = 'Approve';
$string['approve_toggle'] = 'Un/Approve';
$string['approved'] = '✓';
$string['approved_column_name'] = 'Approved';
$string['approved_veryshort'] = 'A';
$string['approveselectedscheck'] = 'Are you sure you want to un-/approve the following questions?<br /><br />{$a}';
$string['average_column_name'] = 'Average';
$string['back_to_course_button'] = 'Back to course';
$string['before_answering_end_date'] = 'This StudentQuiz closes for answering on {$a}.';
$string['before_answering_start_date'] = 'Open for answering from {$a}.';
$string['before_submission_end_date'] = 'This StudentQuiz closes for question submission on {$a}.';
$string['before_submission_start_date'] = 'Open for question submission from {$a}.';
$string['cachedef_permissionssync'] = 'StudentQuiz permission synchronisation tracking';
$string['cannotcapturecommenthistory'] = 'Can not capture comment history record';
$string['changecurrentstate'] = 'Change state from <b>{$a}</b> to:';
$string['changestateto'] = 'Change state(s) to:';
$string['collapseall'] = 'Collapse all comments';
$string['collapsecomment'] = 'Collapse comment';
$string['comment'] = 'Comment';
$string['commentplural'] = 'Comments';
$string['comment_author'] = 'Author';
$string['comment_column_name'] = 'Comments';
$string['comment_cannot_update'] = 'Cannot update comment';
$string['comment_error'] = 'Please comment';
$string['comment_error_unsaved'] = 'Do you want to save this comment first?';
$string['comment_help'] = 'Write a comment';
$string['comment_help_help'] = 'Write a comment to the question.';
$string['commentcolumnexplainpublic'] = "Number of public comments. A blue background means that you have at least one unread comment.";
$string['commentcolumnexplainprivate'] = "Number of private comments. A blue background means that you have at least one unread comment.";
$string['commenthistory'] = 'Comment history';
$string['comment_veryshort'] = 'C';
$string['completiondetail:approved'] = 'Minimum number of unique approved questions: {$a}';
$string['completiondetail:published'] = 'Minimum number of unique authored questions: {$a}';
$string['completiondetail:point'] = 'Minimum amount of points: {$a}';

$string['completionpoint'] = 'Minimum amount of points required:';
$string['completionpointgroup'] = 'Require points';
$string['completionpointgroup_help'] = 'Students earn points as specified under the Ranking settings, e.g. 10 points for creating a question, 5 points for a teacher approving the students\' question, 3 points for the student rating another\'s question. By entering a numeric value in the field, students will only complete the StudentQuiz once they have accumulated enough points.';

$string['completionquestionapproved'] = 'Minimum number of unique approved questions required:';
$string['completionquestionapprovedgroup'] = 'Require created approved questions';
$string['completionquestionapprovedgroup_help'] = 'The minimum number of unique questions that a student must author and be approved before the activity is completed. This option can be used with either the Question publishing "Requires approval before publishing" or "Auto-approval" setting, but won\'t be as effective with the latter setting, in case auto-approved questions are later hidden, deleted, or otherwise removed.';

$string['completionquestionpublished'] = 'Minimum number of unique authored questions required:';
$string['completionquestionpublishedgroup'] = 'Require published questions';
$string['completionquestionpublishedgroup_help'] = 'The minimum number of unique questions that a student must author before the activity is completed. Note that this is a simple numerical check - two questions that are hidden/deleted have still been authored.';
$string['confirmdeletecomment'] = 'Are you sure you want to delete this comment?';
$string['createnewquestion'] = 'Create new question';
$string['createnewquestionfirst'] = 'Create first question';
$string['creator_anonym_fullname'] = 'Anonymous Student';
$string['current_of_total'] = '{$a->current} of {$a->total}';
$string['current_state'] = 'Current state';
$string['daily'] = 'daily';
$string['difficulty_all_column_name'] = 'Community Difficulty';
$string['difficulty_level_column_name'] = 'Difficulty';
$string['difficulty_title'] = 'Difficulty bar';
$string['delete'] = 'Delete';
$string['deleted'] = 'Deleted';
$string['deletecomment'] = 'Delete comment';
$string['deletedbyuser'] = 'This post was deleted by <a href="{$a->profileurl}" >{$a->fullname}</a> on {$a->date}.';
$string['deletedbyauthor'] = 'This post was deleted on {$a}.';
$string['deletedcomment'] = 'Deleted post.';
$string['describe_already_deleted'] = 'This comment is already deleted.';
$string['describe_not_creator'] = 'This is not your comment.';
$string['describe_out_of_time_delete'] = 'This comment is out of time to delete';
$string['describe_out_of_time_edit'] = 'This comment is out of time to edit';
$string['descriptioncofstate'] = 'Question set to \'{$a->state}\'';
$string['descriptionofstatenew'] = 'Question saved (\'Draft\')';
$string['descriptionofvisibility'] = 'Question visibility set to \'{$a->visibility}\'';
$string['editcomment'] = 'Edit comment';
$string['emailautomationnote'] = 'Please note that this is an automated system message – this email address is not monitored.';
$string['editedcomment_last_edit'] = 'Last edited: ';
$string['editedcommenthistory'] = 'Edited by the {$a->lastesteditedcommentauthorname} on {$a->lastededitedcommenttime}';
$string['editedcommenthistorywithuserlink'] = 'Edited by the <a href="{$a->lastesteditedcommentauthorprofileurl}">{$a->lastesteditedcommentauthorname}</a> on {$a->lastededitedcommenttime}';
$string['editedcommenthistorylinktext'] = 'History';
$string['emailcommentaddedbody'] = 'Dear {$a->recepientname},

Your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been commented by \'{$a->actorname}\' at \'{$a->timestamp}\'.

The comment is: \'{$a->commenttext}\'

You can review this question at: {$a->questionurl}.';
$string['emailcommentaddedsmall'] = 'Your question \'{$a->questionname}\' has been commented by {$a->actorname}.';
$string['emailcommentaddedsubject'] = 'Question has been commented: {$a->questionname}';

$string['emailcommentdeletedbody'] = 'Dear {$a->recepientname},

The comment on \'{$a->commenttime}\' to your question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been deleted by \'{$a->actorname}\' at \'{$a->timestamp}\'.

The comment was: \'{$a->commenttext}\'

You can review this question at: {$a->questionurl}.';
$string['emailcommentdeletedsmall'] = 'The comment to your question \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emailcommentdeletedsubject'] = 'Comment has been deleted to question: {$a->questionname}';
$string['emailminecommentdeletedbody'] = 'Dear {$a->recepientname},

Your comment on \'{$a->commenttime}\' to the question \'{$a->questionname}\' in StudentQuiz activity \'{$a->modulename}\' in course \'{$a->coursename}\' has been deleted by \'{$a->actorname}\' at \'{$a->timestamp}\'.

The comment was: \'{$a->commenttext}\'

You can review this question at: {$a->questionurl}.';
$string['emailminecommentdeletedsmall'] = 'Your comment to question \'{$a->questionname}\' has been deleted by {$a->actorname}.';
$string['emailminecommentdeletedsubject'] = 'Comment has been deleted to question: {$a->questionname}';
$string['emailnoityfyreviewablequestionsinglebody'] = '<b>{$a->courseshortname}</b> StudentQuiz activity (<b>"{$a->modulename}"</b>): question <b>"{$a->questionname}"</b> was set to "<b>Reviewable</b>" by <b>{$a->actorname}, {$a->timestamp}.</b>';
$string['emailnoityfyreviewablequestion_section_content'] = 'The question "<b>{$a->questionname}</b>" was set to "<b>Reviewable</b>" by <b>{$a->actorname}</b>';
$string['emailsinglebody'] = 'Your question <b>{$a->questionname}</b> in StudentQuiz activity <b>{$a->modulename}</b> in course <b>{$a->coursename}</b> has been {$a->eventname} by <b>{$a->actorname}</b> at <b>{$a->timestamp}</b>.';
$string['emailsinglebody_reviewlink'] = 'You can review this question at: ';
$string['emaildigestbody'] = 'This is your {$a->digesttype} digest of notifications for the <b>{$a->modulename}</b> StudentQuiz activity, available here:';
$string['emaildigestbody_section_title'] = 'Notification {$a->seq}, {$a->timestamp}';
$string['emaildigestbody_section_content'] = 'Your question <b>{$a->questionname}</b> has been <b>{$a->actiontype}</b> by <b>{$a->actorname}</b>';
$string['emaildigestsubject'] = 'StudentQuiz Digest Notification';
$string['emailsalutation'] = 'Dear {$a},';
$string['editorplaceholder'] = 'Enter your comment here ...';
$string['error_form_validation'] = '{$a}';
$string['error_sendalert'] = 'There was an error sending your report to {$a}.
Report could not be sent.';
$string['error_permission'] = 'Sorry, but you need to be part of a group to see this page.';
$string['expandcomment'] = 'Expand comment';
$string['expandall'] = 'Expand all comments';
$string['filter'] = 'Filter';
$string['filter_advanced_element'] = '{$a} (Advanced element)';
$string['filter_comment_label_forename'] = 'Forename';
$string['filter_comment_label_surname'] = 'Surname';
$string['filter_comment_label_date'] = 'Date';
$string['filter_comment_label_sort_by'] = 'Sort by:';
$string['filter_comment_label_sort_toggle'] = 'Sort by {$a->field} {$a->type}';
$string['filter_ishigher'] = 'Is higher';
$string['filter_islower'] = 'Is lower';
$string['filter_label_approved'] = 'Approved questions';
$string['filter_label_comment'] = 'Comments';
$string['filter_label_createdate'] = 'Creation';
$string['filter_label_difficulty_level'] = 'Difficulty';
$string['filter_label_fast_filters'] = 'Fast filter for questions';
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
$string['filter_label_question'] = 'Question title';
$string['filter_label_questiontext'] = 'Question content';
$string['filter_label_question_creation_item'] = '{$a->creationtext} {$a->rowtext} {$a->inputtext}';
$string['filter_label_question_creation_item_inputtext'] = '{$a->inputtext} {$a->inputtype}';
$string['filter_label_rates'] = 'Rating';
$string['filter_label_show_mine'] = 'My questions';
$string['filter_label_tags'] = 'Tag';
$string['finish_button'] = 'Finish';
$string['history'] = 'History';
$string['hidden'] = 'Hidden';
$string['includingunread'] = ' (including unread)';
$string['invalidcomment'] = 'invalidcomment';
$string['invalidemail'] = 'This email address is not valid. Please enter a single email address.';
$string['lastattempt_right'] = '✓';
$string['lastattempt_right_label'] = 'Last attempt correct';
$string['lastattempt_wrong'] = '✗';
$string['lastattempt_wrong_label'] = 'Last attempt incorrect';
$string['latest_column_name'] = 'Latest';
$string['manager_anonym_fullname'] = 'Anonymous Manager';
$string['message'] = 'Message';
$string['messageprovider:questionchanged'] = 'Question event notification';
$string['messageprovider:commentadded'] = 'Comment added notification';
$string['messageprovider:commentdeleted'] = 'Comment deleted notification';
$string['messageprovider:deleteorphanedquestions'] = 'Question deleted notification';
$string['messageprovider:minecommentdeleted'] = 'My comment deleted notification';
$string['migrate_already_done'] = 'Nothing was done because this activity has been migrated already!';
$string['migrate_ask'] = 'The speed of StudentQuiz improved with version 3.2.1, but this question set is still based on a prior version.
Questions and quizzes will be loaded faster if you run this speed-up migration. You will experience faster loading; nothing else will change.';
$string['migrate_studentquiz'] = 'Migrate StudentQuiz questions prior to version 3.2.1 to the faster version with aggregated values';
$string['migrate_studentquiz_short'] = 'Speed-up this question set';
$string['migrated_successful'] = 'This activity has been migrated successfully!';
$string['mine_column_name'] = 'Mine';
$string['missingparam'] = 'A required parameter is missing or wrong';
$string['moderator'] = 'Moderator';
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
$string['nav_question_no'] = 'Question {$a->current} of {$a->total}';
$string['needtoallowatleastoneqtype'] = 'You need to allow at least one question type';
$string['next_button'] = 'Next';
$string['nocommenthistoryexist'] = 'There is no comment history yet for this comment.';
$string['no_comment'] = 'n.a.';
$string['no_comments'] = 'No comments';
$string['no_difficulty_level'] = 'n.a.';
$string['no_myattempts'] = 'n.a.';
$string['no_mylastattempt'] = 'n.a.';
$string['no_mylastattempt_label'] = 'The question is not attempted';
$string['no_questions_add'] = 'There are no questions in this StudentQuiz. Feel free to add some questions.';
$string['no_questions_filter'] = 'None of the questions matched your filter criteria. Reset the filter to see all.';
$string['no_questions_selected_message'] = 'Please select at least one question to start the quiz.';
$string['noquestionsselectedtodoaction'] = 'Please select one or more questions before selecting this action.';
$string['no_rates'] = 'n.a.';
$string['no_tags'] = 'n.a.';
$string['nofurtherprivatecomments'] = 'No further private comments are allowed once a question is \'Approved\'';
$string['not_approved'] = '✗';
$string['num_questions'] = '{$a} questions';
$string['number_column_name'] = 'Number';
$string['numberreply'] = '{$a} Replies';
$string['onlyrootcommentcanreply'] = 'Only first level of comment can be reply';
$string['pagesize'] = 'Page size:';
$string['pagesize_invalid_input'] = 'Error: a specified page size must be a valid numeric value.';
$string['pin'] = 'Pin question';
$string['please_enrole_message'] = 'Please enroll in this course to see your personal progress';
$string['pluginadministration'] = 'StudentQuiz Administration';
$string['pluginname'] = 'StudentQuiz';
$string['previous_button'] = 'Previous';
$string['privacy:metadata:studentquiz_attempt'] = 'Represents a users attempt to answer a set of questions.';
$string['privacy:metadata:studentquiz_attempt:categoryid'] = 'ID of the category.';
$string['privacy:metadata:studentquiz_attempt:questionusageid'] = 'ID of the question usage.';
$string['privacy:metadata:studentquiz_attempt:studentquizid'] = 'ID of the StudentQuiz.';
$string['privacy:metadata:studentquiz_attempt:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_comment'] = 'Store comments for questions.';
$string['privacy:metadata:studentquiz_comment:comment'] = 'Comment of the question.';
$string['privacy:metadata:studentquiz_comment:created'] = 'Time created time comment.';
$string['privacy:metadata:studentquiz_comment:studentquizquestionid'] = 'ID of the studentquizquestion.';
$string['privacy:metadata:studentquiz_comment:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_comment:parentid'] = 'ID of parent comment. 0: top level comment.';
$string['privacy:metadata:studentquiz_comment:deleted'] = 'Time deleted time comment.';
$string['privacy:metadata:studentquiz_comment:deleteuserid'] = 'ID of the user deleted comment.';
$string['privacy:metadata:studentquiz_comment:edited'] = 'Time edited time comment.';
$string['privacy:metadata:studentquiz_comment:edituserid'] = 'ID of the user edited comment.';
$string['privacy:metadata:studentquiz_comment:status'] = 'Status of comment';
$string['privacy:metadata:studentquiz_comment:type'] = 'Type of comment';
$string['privacy:metadata:studentquiz_comment:timemodified'] = 'Comment modified time';
$string['privacy:metadata:studentquiz_comment:usermodified'] = 'ID of comment modified user';
$string['privacy:metadata:studentquiz_comment_history:commentid'] = 'ID of comment';
$string['privacy:metadata:studentquiz_comment_history:content'] = 'Comment history content';
$string['privacy:metadata:studentquiz_comment_history:userid'] = 'ID of user that edit comment';
$string['privacy:metadata:studentquiz_comment_history:action'] = 'Type of history 0 - Create | 1 - Edit | 2 - Delete';
$string['privacy:metadata:studentquiz_comment_history:timemodified'] = 'Modified time of comment';
$string['privacy:metadata:studentquiz_comment_history'] = 'Store comment histories of comments';
$string['privacy:metadata:studentquiz_question'] = 'Store question related properties';
$string['privacy:metadata:studentquiz_question:studentquizid'] = 'ID of the StudentQuiz.';
$string['privacy:metadata:studentquiz_question:state'] = 'Property whether the question is approved, disapprove, new or changed';
$string['privacy:metadata:studentquiz_question:hidden'] = 'Property whether the question hidden or not';
$string['privacy:metadata:studentquiz_question:pinned'] = 'Property whether the question pinned or not';
$string['privacy:metadata:studentquiz_question:groupid'] = 'ID of group that question belong to';
$string['privacy:metadata:mod_studentquiz_comment_sort'] = 'A user preference for comment filter type.';
$string['privacy:metadata:mod_studentquiz_question_active_tab'] = 'A user preference for current active tab in question pages.';
$string['privacy:metadata:studentquiz_notification'] = 'Notification queue';
$string['privacy:metadata:studentquiz_notification:studentquizid'] = 'StudentQuiz ID';
$string['privacy:metadata:studentquiz_notification:content'] = 'Notification content';
$string['privacy:metadata:studentquiz_notification:recipientid'] = 'Recipient ID';
$string['privacy:metadata:studentquiz_notification:status'] = 'Status of the notification';
$string['privacy:metadata:studentquiz_notification:timetosend'] = 'Time to send the notification';
$string['privacy:metadata:studentquiz_progress'] = 'Store progress information of student with this question.';
$string['privacy:metadata:studentquiz_progress:attempts'] = 'Number of attempts to answer this question.';
$string['privacy:metadata:studentquiz_progress:correctattempts'] = 'Number of correct answers.';
$string['privacy:metadata:studentquiz_progress:lastanswercorrect'] = '0: last answer was wrong or undefined, 1: last answer was correct.';
$string['privacy:metadata:studentquiz_progress:lastreadprivatecomment'] = 'Last time user read the private comments';
$string['privacy:metadata:studentquiz_progress:lastreadpubliccomment'] = 'Last time user read the public comments';
$string['privacy:metadata:studentquiz_progress:studentquizquestionid'] = 'ID of the studentquizquestion.';
$string['privacy:metadata:studentquiz_progress:studentquizid'] = 'ID of the StudentQuiz.';
$string['privacy:metadata:studentquiz_progress:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_rate'] = 'Store rates for questions.';
$string['privacy:metadata:studentquiz_rate:studentquizquestionid'] = 'ID of the studentquizquestion.';
$string['privacy:metadata:studentquiz_rate:rate'] = 'Rate for the question.';
$string['privacy:metadata:studentquiz_rate:userid'] = 'ID of the user.';
$string['private'] = 'Private';
$string['privatecomments'] = 'Private comments';
$string['privacy:metadata:studentquiz_state_history:state'] = 'Property whether the question is approved, disapprove, new or changed';
$string['privacy:metadata:studentquiz_state_history:userid'] = 'ID of the user.';
$string['privacy:metadata:studentquiz_state_history:studentquizquestionid'] = 'ID of the studentquizquestion.';
$string['privacy:metadata:studentquiz_state_history:timecreated'] = 'Time to create action.';
$string['progress_bar_caption'] = 'Your progress in this StudentQuiz activity';
$string['public'] = 'Public';
$string['publiccomments'] = 'Public comments';
$string['questionchangedlowercase'] = 'changed';
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
$string['reportrank_table_column_countquestions'] = 'Points for questions published';
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
$string['reportcomment'] = 'Report';
$string['reportcomment_title'] = 'Report comment as unacceptable';
$string['replycomment'] = 'Reply';
$string['reply'] = 'Reply';
$string['replies'] = 'Replies';
$string['report_comment_not_available'] = 'The report comment function is not available.';
$string['report_comment_pagename'] = 'Report a comment as unacceptable';
$string['report_comment_info'] = "The 'Report' feature can send this comment to a staff member who will
investigate. <strong>Please use this feature only if you think the comment breaks the
rules</strong>.";
$string['report_comment_reasons'] = 'Reasons for reporting comment:';
$string['report_comment_condition1'] = 'It is abusive';
$string['report_comment_condition2'] = 'It is harassment';
$string['report_comment_condition3'] = 'It contains obscene content such as pornography';
$string['report_comment_condition4'] = 'It is libellous or defamatory';
$string['report_comment_condition5'] = 'It infringes copyright';
$string['report_comment_condition6'] = 'It is against the rules for some other reason';
$string['report_comment_condition_more'] = 'Other information (optional)';
$string['report_comment_reporter_info'] = "<strong>Reporter's details</strong>:";
$string['report_comment_reporter_detail'] = '{$a->fullname} ({$a->username}; {$a->email}; {$a->ip})';
$string['report_comment_invalid'] = 'You need to specify the reason for reporting this comment.';
$string['report_comment_invalid_checkbox'] = 'You need to tick at least one of the boxes.';
$string['report_comment_submit'] = 'Send report';
$string['report_comment_emailsubject'] = 'Report comment {$a->commentid}: {$a->coursename} {$a->studentquizname}';
$string['report_comment_emailpreface'] = 'A comment has been reported by {$a->fullname} ({$a->username},
{$a->email}).';
$string['report_comment_feedback'] = 'Your report has been sent successfully. A member of staff will
investigate this issue.';
$string['report_comment_emailappendix'] = 'You are receiving this email because your email address has been
used on the StudentQuiz for reporting unacceptable comment.';
$string['report_comment_link_text'] = 'Preview here';
$string['review_button'] = 'Review';
$string['savechanges'] = 'Save changes';
$string['deleteorphanedquestions'] = 'Delete orphaned questions';
$string['deleteorphanedquestionserrormdlquestion'] = '<font color="red">error</font>: could not delete from mdl_question table. The question probably is in use somewhere.<br><font color="red">error</font>: delete from mdl_studentquiz* tables has been skipped.<br>';
$string['deleteorphanedquestionserrorstudentquiz'] = '<font color="red">error</font>: could not delete from mdl_studentquiz* tables.<br>';
$string['deleteorphanedquestionsfullmessage'] = 'Questions that are disapproved/flagged for deletion:<ul>{$a->fullmessage}</ul>';
$string['deleteorphanedquestionsnonefound'] = '<b>none found</b>';
$string['deleteorphanedquestionsquestioninfo'] = '<li><b>{$a->name}</b> (Questiontype: {$a->qtype}, ID: {$a->questionid})</li>';
$string['deleteorphanedquestionssmallmessage'] = 'StudentQuiz: Task to delete orphaned questions has run';
$string['deleteorphanedquestionssubject'] = 'StudentQuiz';
$string['deleteorphanedquestionssuccessmdlquestion'] = '<font color="green">success</font>: deleted from mdl_question table<br>';
$string['deleteorphanedquestionssuccessstudentquiz'] = '<font color="green">success</font>: deleted from mdl_studentquiz* tables.<br>';
$string['scheduled_task_send_digest_notification'] = 'Send digest notification';
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
$string['settings_comment_editor_toolbar'] = 'Comment editor toolbar config';
$string['settings_comment_editor_toolbar_des'] = 'The list of plugins and the order they are displayed can be configured here';
$string['settingsdeleteorphaned'] = 'Delete orphaned questions';
$string['settingsdeleteorphaned_help'] = 'Activates a scheduled task that will run every day to delete all orphaned/not approved questions from the database.';
$string['settingsdeleteorphanedtimelimit'] = 'Orphan question deletion time boundary';
$string['settingsdeleteorphanedtimelimit_help'] = 'Set the time boundary for the deletion of orphaned/not approved questions. Questions that are older will be deleted.';
$string['settingsdeleteorphanedtime6m'] = '6 months';
$string['settingsdeleteorphanedtime1y'] = '1 year';
$string['settingsdeleteorphanedtime2y'] = '2 year';
$string['settingsdeleteorphanedtime3y'] = '3 years';
$string['settings_allowedrolestoshow'] = 'Exclude roles which can be changed in each activity';
$string['settings_allowedrolestoshow_help'] = 'This relates to the previous setting. Not all roles in the system are relevant to StudentQuiz, so you can use this setting to reudce the number of roles listed on the activity settings form. Roles selected here will appear on the form for each activity, so the teacher can change the setting. For roles not selected here will be excluded from the reports depending on whether they are exluded by the default above.';
$string['settings_email_digest_type'] = 'Email digest type';
$string['settings_email_digest_type_help'] = 'StudentQuiz has various notifications that you can enable, such as informing the student question-author of a state change (e.g. a teacher has approved one of their questions). You can use this setting to specify the frequency of these notifications. Digest emails will only be sent when there is at least one notification in the set period';
$string['settings_email_digest_type_no_digest'] = 'No digest (single email per action)';
$string['settings_email_digest_type_daily_digest'] = 'Daily digest';
$string['settings_email_digest_type_weekly_digest'] = 'Weekly digest';
$string['settings_email_digest_first_day'] = 'First day of week?';
$string['settings_email_digest_first_day_help'] = 'If you have selected a weekly digest, this option allows you to define the first day (beginning at 00h:00m:00s of that day) of the seven day period. This is especially useful if the activity starts mid-week, for example.';
$string['settings_excluderoles'] = 'Default roles to exclude from rankings';
$string['settings_excluderoles_label'] = 'Roles in ranking to exclude';
$string['settings_excluderoles_help'] = 'In each StudentQuiz, the teacher can control which roles are excluded from the rankings. The list of roles set here is the default used for any newly created StudentQuiz';
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
$string['settings_notification'] = 'Notification settings';
$string['settings_privatecommenting'] = 'Enable private commenting';
$string['settings_privatecommenting_help'] = 'The private discussion thread, accessible when Previewing a question until a question is Approved, allows discussion between the question author and the activity administrator(s). If the StudentQuiz mode is set to \'Automatically publish new questions\', then the question author will not necessarily ever see this specific thread. However, it remains available to the activity administrator(s) regardless, who might use it to explain why they have hidden or deleted a question. The private commenting default is set in the server-level plugin settings.';
$string['settings_questionquantifier'] = 'Published question factor';
$string['settings_questionquantifier_help'] = 'Points for each published question';
$string['settings_questionquantifier_label'] = 'Points for each question published';
$string['settings_ratequantifier'] = 'Rating factor';
$string['settings_ratequantifier_help'] = 'Points for each star received.';
$string['settings_ratequantifier_label'] = 'Multiplier for the average of stars received for a question';
$string['settings_removeqbehavior'] = 'Remove question behavior plugin StudentQuiz';
$string['settings_removeqbehavior_help'] = 'This info should appear only once during update. We inform you that we detected our question behavior plugin StudentQuiz is installed. This plugin is not required anymore and thus we try to automatically remove it. If you still see this setting, please uninstall the question behavior plugin StudentQuiz manually <a href="{$a}">here</a>.';
$string['settings_removeqbehavior_label'] = 'Remove question behavior plugin StudentQuiz';
$string['settings_section_description_default'] = 'These values define the default values when creating a new studentquiz activity.';
$string['settings_section_header_question'] = 'Question settings';
$string['settings_section_header_commenting'] = 'Commenting settings';
$string['settings_section_header_comment_rating'] = 'Comment and rating settings';
$string['settings_section_header_ranking'] = 'Ranking settings';
$string['settings_privatecomment'] = 'Private comment explanation';
$string['settings_privatecomment_help'] = 'You can amend the explanatory text here, depending on how your organisation uses discussions between a student and advisor/teacher/activity administrator.';
$string['setting_question_publishing'] = 'Question publishing';
$string['setting_question_publishing_help'] = 'Published questions appear in the question pool for other students to take them. Either allow all questions to be published automatically, or require approval before they can be published.<br>Note that this setting only applies to newly created questions.';
$string['setting_question_publishing_automatic'] = 'Automatically publish new questions';
$string['setting_question_publishing_require_approval'] = 'Require approval before publishing';
$string['settings_showprivatecomment'] = 'Enable private comment discussion';
$string['settings_showprivatecomment_help'] = 'This option enables advanced discussion between a student and tutor (names may vary depending on organisation) in the question Preview.';
$string['settings_commentdeletionperiod'] = 'Comment editing/deletion period (minutes)';
$string['settings_commentdeletionperiod_help'] = 'Set the time period (in minutes) that the Edit/Delete button will be available to students to edit/delete their own comment (or response to a comment) once it is posted. Values between 0-60 minutes are allowed. If the deletion period is set to 0, students are unable to edit/delete their own comments. Note that teachers and administrators will always be able to edit/delete student comments, and also see the content of any deleted comment.';
$string['settings_reportingemail'] = 'Email for reporting offensive comments';
$string['settings_reportingemail_help'] = 'If this email address is supplied, then a Report link appears
next to each comment. Users can click the link to report offensive comments. The information will be sent to this address.

If this email is left blank then the Report feature will not be shown (unless a site-level
reporting  address has been supplied).

More than one email address can be added so long as they are separated by a semi-colon.';
$string['show_less'] = 'Show less';
$string['show_more'] = 'Show more';
$string['slot_of_slot'] = 'Question {$a->slot} of {$a->slots} in this set';
$string['start_quiz_button'] = 'Start Quiz';
$string['state_changed'] = 'Changed';
$string['state_changedlowercase'] = 'changed';
$string['state_changedplural'] = 'Changed';
$string['state_change_tooltip_disapproved'] = 'Question is disapproved. Click here to change the state of this question';
$string['state_change_tooltip_approved'] = 'Question is approved. Click here to change the state of this question';
$string['state_change_tooltip_new'] = 'Question is new. Click here to change the state of this question';
$string['state_change_tooltip_changed'] = 'Question is changed. Click here to change the state of this question';
$string['state_change_tooltip_reviewable'] = 'Question is reviewable. Click here to change the state of this question';
$string['state_column_name'] = 'State';
$string['state_column_name_veryshort'] = 'S';
$string['state_deleted'] = 'Deleted';
$string['state_deletedplural'] = 'Deleted';
$string['state_deletedlowercase'] = 'deleted';
$string['state_disapproved'] = 'Disapproved';
$string['state_disapprovedplural'] = 'Disapproved';
$string['state_disapprovedlowercase'] = 'disapproved';
$string['state_hidden'] = 'Hidden';
$string['state_hiddenplural'] = 'Hidden';
$string['state_hiddenlowercase'] = 'hidden';
$string['state_pinned'] = 'Pinned';
$string['state_pinnedplural'] = 'Pinned';
$string['state_pinnedlowercase'] = 'pinned';
$string['state_toggle'] = 'Change state';
$string['state_approved'] = 'Approved';
$string['state_approvedlowercase'] = 'approved';
$string['state_approvedplural'] = 'Approved';
$string['state_new'] = 'New';
$string['state_newplural'] = 'New';
$string['state_shown'] = 'Shown';
$string['state_shownlowercase'] = 'shown';
$string['state_shownplural'] = 'Shown';
$string['state_unhiddenlowercase'] = 'unhidden';
$string['state_unpinnedlowercase'] = 'unpinned';
$string['statehistory'] = 'State History';
$string['statistic_block_approvals'] = 'Questions approved';
$string['statistic_block_created'] = 'Questions created';
$string['statistic_block_disapprovals'] = 'Questions disapproved';
$string['statistic_block_new_changed'] = 'Questions new/changed';
$string['statistic_block_progress_available'] = 'Questions available';
$string['statistic_block_progress_last_attempt_correct'] = 'Latest attempt correct';
$string['statistic_block_progress_last_attempt_incorrect'] = 'Latest attempt wrong';
$string['statistic_block_progress_never'] = 'Questions never answered';
$string['statistic_block_title'] = 'My Progress';
$string['state_reviewable'] = 'Reviewable';
$string['state_reviewablelowercase'] = 'reviewable';
$string['state_reviewableplural'] = 'Reviewable';
$string['studentquiz'] = 'studentquiz';
$string['studentquiz:addinstance'] = 'Add new instance for StudentQuiz';
$string['studentquiz:canselfratecomment'] = 'Rate and publicly comment own questions in preview';
$string['studentquiz:cancommentprivately'] = 'Comment privately on any question';
$string['studentquiz:canselfcommentprivately'] = 'Comment privately on own questions';
$string['studentquiz:changestate'] = 'Set the state of a question on StudentQuiz';
$string['studentquiz:emailnotifyapproved'] = 'Question approved notification';
$string['studentquiz:emailnotifychanged'] = 'Question changed notification';
$string['studentquiz:emailnotifycommentadded'] = 'Comment added notification';
$string['studentquiz:emailnotifycommentdeleted'] = 'Comment deleted notification';
$string['studentquiz:emailnotifyquestion'] = 'User receives email notification of their questions\' state change';
$string['studentquiz:emailnotifyreviewablequestion'] = 'The user receives an email notification when student change their questions\' state to reviewable';
$string['studentquiz:manage'] = 'Edit and delete questions on StudentQuiz';
$string['studentquiz:organize'] = 'Move questions into categories on StudentQuiz';
$string['studentquiz:pinquestion'] = 'Pin questions in StudentQuiz';
$string['studentquiz:preview'] = 'Preview questions';
$string['studentquiz:previewothers'] = 'Preview questions of others on StudentQuiz';
$string['studentquiz:submit'] = 'Create questions on StudentQuiz';
$string['studentquiz:systemnotifytaskdeleteorphanedquestions'] = 'Orphaned questions deleted notification';
$string['studentquiz:unhideanonymous'] = 'Can see real names even when anonymize is active';
$string['studentquiz:view'] = 'See and use questions on StudentQuiz';
$string['studentquizname'] = 'StudentQuiz Name';
$string['studentquizname_help'] = 'The name of this StudentQuiz Activity';
$string['submissionendbeforestart'] = 'Submissions deadline can not be specified before the open for submissions date';
$string['tags'] = 'Tags';
$string['unapprove'] = 'Unapprove';
$string['unpin'] = 'Unpin question';
$string['visiblegroupnotyetsupport'] = '\'Visible groups\' is not yet supported. Please choose another group mode.';
$string['weekly'] = 'weekly';
$string['notshowratingcomment'] = 'Rating and public commenting are not available for your own question in Preview mode.';
