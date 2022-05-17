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
 * Define the complete StudentQuiz structure for backup, with file and id annotations
 *
 * @package   mod_studentquiz
 * @category  backup
 * @copyright 2017 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_studentquiz_activity_structure_step extends backup_questions_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // Check if user info should be backuped.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the StudentQuiz instance.
        $studentquiz = new backup_nested_element('studentquiz', array('id'), array(
                'coursemodule', 'name', 'intro', 'introformat', 'grade', 'anonymrank',
                'questionquantifier', 'approvedquantifier', 'ratequantifier',
                'correctanswerquantifier', 'incorrectanswerquantifier',
                'allowedqtypes', 'aggregated', 'excluderoles', 'forcerating', 'forcecommenting',
                'commentdeletionperiod', 'reportingemail', 'digesttype', 'digestfirstday', 'privatecommenting'
        ));

        // StudentQuiz Attempt -> User, Question usage Id.
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', array('id'),
            array('userid', 'questionusageid', 'categoryid'));

        // Backup question usage data generated by StudentQuiz.
        // TODO: Question usages backuping can be removed if progress works flawless.
        $this->add_question_usages($attempt, 'questionusageid');
        $attempts->add_child($attempt);
        $studentquiz->add_child($attempts);

        // StudentQuiz -> Question.
        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', array('questionid'), array('state', 'hidden', 'groupid', 'pinned'));
        $questions->add_child($question);
        $studentquiz->add_child($questions);

        // StudentQuiz -> Question -> Student.
        $progresses = new backup_nested_element('progresses');
        $progress = new backup_nested_element('progress', array('questionid', 'userid'),
            array('lastanswercorrect', 'attempts', 'correctattempts', 'lastreadprivatecomment', 'lastreadpubliccomment'));
        $progresses->add_child($progress);
        $studentquiz->add_child($progresses);

        // Question -> User -> rate.
        $rates = new backup_nested_element('rates');
        $rate = new backup_nested_element('rate', array('userid', 'questionid'), array('rate'));
        $rates->add_child($rate);
        $studentquiz->add_child($rates);

        // Comment -> Question, User.
        $comments = new backup_nested_element('comments');
        $comment = new backup_nested_element('comment', ['usermodified', 'questionid', 'userid', 'id'], [
                'comment', 'created', 'parentid', 'status', 'type', 'timemodified'
        ]);
        $comments->add_child($comment);
        $studentquiz->add_child($comments);

        // Comment history -> Comment, User.
        $commenthistories = new backup_nested_element('commenthistories');
        $commenthistory = new backup_nested_element('comment_history', ['userid', 'commentid', 'id'], [
                'content', 'action', 'timemodified']);
        $commenthistories->add_child($commenthistory);
        $comment->add_child($commenthistories);

        // Notification -> Question, User.
        $notifications = new backup_nested_element('notifications');
        $notification = new backup_nested_element('notification', ['studentquizid', 'id'], [
                'comment', 'created', 'parentid', 'deleted', 'deleteuserid', 'edited', 'edituserid'
        ]);

        // StudentQuiz -> Notification.
        $notifications = new backup_nested_element('notifications');
        $notification = new backup_nested_element('notification', ['studentquizid'],
                ['content', 'recipientid', 'status', 'timetosend']);
        $notifications->add_child($notification);
        $studentquiz->add_child($notifications);

        // Question -> User -> State history.
        $statehitories = new backup_nested_element('statehistories');
        $statehistory = new backup_nested_element('state_history', ['userid', 'questionid'], ['state', 'timecreated']);
        $statehitories->add_child($statehistory);
        $studentquiz->add_child($statehitories);

        // Define data sources.
        $studentquiz->set_source_table('studentquiz',
            array('id' => backup::VAR_ACTIVITYID, 'coursemodule' => backup::VAR_MODID));

        // StudentQuiz Question meta of this StudentQuiz.
        $questionsql = "SELECT question.*
                          FROM {studentquiz} sq
                          JOIN {context} con ON con.instanceid = sq.coursemodule
                          JOIN {question_categories} qc ON qc.contextid = con.id
                     LEFT JOIN {question} q ON q.category = qc.id
                          JOIN {studentquiz_question} question ON question.questionid = q.id
                         WHERE sq.id = :studentquizid";
        $question->set_source_sql($questionsql, array('studentquizid' => backup::VAR_PARENTID));

        // Define data sources with user data.
        // TODO: Check if user info requested or not.
        if ($userinfo) {
            $attempt->set_source_table('studentquiz_attempt',
                array('studentquizid' => backup::VAR_PARENTID));

            $progress->set_source_table( 'studentquiz_progress',
                array('studentquizid' => backup::VAR_PARENTID));

            // Only select rates to questions of this StudentQuiz.
            $ratesql = "SELECT rate.*
                          FROM {studentquiz} sq
                          JOIN {context} con ON con.instanceid = sq.coursemodule
                          JOIN {question_categories} qc ON qc.contextid = con.id
                     LEFT JOIN {question} q ON q.category = qc.id
                          JOIN {studentquiz_rate} rate ON rate.questionid = q.id
                         WHERE sq.id = :studentquizid";
            $rate->set_source_sql($ratesql, array('studentquizid' => backup::VAR_PARENTID));

            // Only select comments to questions of this StudentQuiz.
            // Need to order by parentid + id (root comments always first).
            $commentsql = "SELECT comment.*
                             FROM {studentquiz} sq
                             JOIN {context} con ON con.instanceid = sq.coursemodule
                             JOIN {question_categories} qc ON qc.contextid = con.id
                        LEFT JOIN {question} q ON q.category = qc.id
                             JOIN {studentquiz_comment} comment ON comment.questionid = q.id
                            WHERE sq.id = :studentquizid
                         ORDER BY comment.parentid, comment.id";
            $comment->set_source_sql($commentsql, array('studentquizid' => backup::VAR_PARENTID));

            // Only select comment histories to questions of this StudentQuiz.
            $commenthistory->set_source_table('studentquiz_comment_history', ['commentid' => backup::VAR_PARENTID]);

            // Only select state histories to questions of this StudentQuiz.
            $statehistorysql = "SELECT sh.*
                                  FROM {studentquiz} sq
                                  JOIN {context} con ON con.instanceid = sq.coursemodule
                                  JOIN {question_categories} qc ON qc.contextid = con.id
                             LEFT JOIN {question} q ON q.category = qc.id
                                  JOIN {studentquiz_state_history} sh ON sh.questionid = q.id
                                 WHERE sq.id = :studentquizid";
            $statehistory->set_source_sql($statehistorysql, ['studentquizid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $progress->annotate_ids('user', 'userid');
        $progress->annotate_ids('question', 'questionid');
        $attempt->annotate_ids('user', 'userid');
        $question->annotate_ids('question', 'questionid');
        $question->annotate_ids('group', 'groupid');
        $rate->annotate_ids('user', 'userid');
        $comment->annotate_ids('user', 'userid');
        $comment->annotate_ids('user', 'usermodified');
        $commenthistory->annotate_ids('user', 'userid');
        $notification->annotate_ids('studentquiz', 'studentquizid');
        $statehistory->annotate_ids('user', 'userid');
        $statehistory->annotate_ids('question', 'questionid');

        // Define file annotations (we do not use itemid in this example).
        $studentquiz->annotate_files('mod_studentquiz', 'intro', null);

        // Return the root element (studentquiz), wrapped into standard activity structure.
        return $this->prepare_activity_structure($studentquiz);
    }
}
