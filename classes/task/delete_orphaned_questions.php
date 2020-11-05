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
 * A scheduled task for sending digest notification.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\task;

defined('MOODLE_INTERNAL') || die();

/**
 * A scheduled task for sending digest notification.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_orphaned_questions extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('deleteorphanedquestions', 'mod_studentquiz');
    }

    /**
     * Execute scheduled task
     *
     * @return boolean
     */
    public function execute() {
        global $CFG, $DB, $USER;

        if (get_config('studentquiz', 'deleteorphanedquestions') == true
            && get_config('studentquiz', 'deleteorphanedtimelimit') == true) {

            require_once($CFG->libdir . '/questionlib.php');
            set_time_limit(0);

            $timelimit = time() - intval(abs(get_config('studentquiz', 'deleteorphanedtimelimit')));

            $questions = $DB->get_records_sql(
                    "SELECT *
                    FROM {studentquiz_question} sq
                    JOIN {question} q ON sq.questionid = q.id
                    WHERE (sq.state = 0 OR q.hidden = 1) AND :timelimit - q.timemodified > 0
                    ORDER BY sq.questionid ASC", array('timelimit' => $timelimit));

            // Process questionids and generate output.
            $output = "";

            if (count($questions) == 0) {

                $output .= get_string('deleteorphanedquestionsnonefound', 'mod_studentquiz');

            } else {

                foreach ($questions as $question) {

                    if (isset($question->questionid)) {

                        try {

                            unset($transaction);
                            $transaction = $DB->start_delegated_transaction();

                            $a = [
                                'name' => format_string($question->name),
                                'qtype' => format_string($question->qtype),
                                'questionid' => format_string($question->questionid),
                            ];

                            $output .= get_string('deleteorphanedquestionsquestioninfo', 'mod_studentquiz', $a);

                            // Delete from question table.
                            question_delete_question($question->questionid);

                            if (!$DB->record_exists_sql("SELECT * FROM {question} WHERE id = :questionid",
                                array('questionid' => $question->questionid))) {

                                // Delete from mdl_studentquiz_comment_history.
                                $success = $DB->delete_records_select('studentquiz_comment_history',
                                                        "commentid IN (SELECT id FROM {studentquiz_comment}
                                                        WHERE questionid = :questionid)",
                                                        array('questionid' => $question->questionid));

                                // Delete from mdl_studentquiz_comment.
                                $success = $success && $DB->delete_records('studentquiz_comment',
                                                        array('questionid' => $question->questionid));

                                // Delete from mdl_studentquiz_progress.
                                $success = $success && $DB->delete_records('studentquiz_progress',
                                                        array('questionid' => $question->questionid));

                                // Delete from mdl_studentquiz_question.
                                $success = $success && $DB->delete_records('studentquiz_question',
                                                        array('questionid' => $question->questionid));

                                // Delete from mdl_studentquiz_rate.
                                $success = $success && $DB->delete_records('studentquiz_rate',
                                                        array('questionid' => $question->questionid));

                                $output .= get_string('deleteorphanedquestionssuccessmdlquestion', 'mod_studentquiz');

                                if ($success) {
                                    $output .= get_string('deleteorphanedquestionssuccessstudentquiz', 'mod_studentquiz');
                                } else {
                                    $output .= get_string('deleteorphanedquestionserrorstudentquiz', 'mod_studentquiz');
                                }
                            } else {
                                $output .= get_string('deleteorphanedquestionserrormdlquestion', 'mod_studentquiz');
                            }

                            $transaction->allow_commit();

                        } catch (Exception $e) {
                            $transaction->rollback($e);
                        }
                    }
                }
            }

            // Generate and send notification.
            $message = new \core\message\message();
            $message->courseid = SITEID;
            $message->component = 'mod_studentquiz';
            $message->name = 'deleteorphanedquestions';
            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $USER;
            $message->notification = 1;
            $message->subject = get_string('deleteorphanedquestionssubject', 'mod_studentquiz');
            $message->fullmessage = $output;
            $message->fullmessageformat = FORMAT_MOODLE;
            $message->fullmessagehtml = get_string('deleteorphanedquestionsfullmessage', 'mod_studentquiz',
                                                    ['fullmessage' => $output]) . PHP_EOL;
            $message->smallmessage = get_string('deleteorphanedquestionssmallmessage', 'mod_studentquiz');
            $message->contexturl = new \moodle_url("/admin/tool/task/scheduledtasks.php");
            $message->contexturlname = get_string('scheduledtasks', 'tool_task');

            message_send($message);
        }

        return true;
    }
}