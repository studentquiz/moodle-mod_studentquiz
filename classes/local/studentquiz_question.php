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

namespace mod_studentquiz\local;

use question_definition;
use stdClass;
use mod_studentquiz\utils;
use core_question\local\bank\question_version_status;

/**
 * Container class for StudentQuiz question.
 *
 * @package mod_studentquiz
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_question {

    /** @var stdClass $data - Data of StudentQuiz question. */
    private $data;

    /** @var question_definition $question - Question class. */
    private $question;

    /** @var stdClass|\cm_info $cm - Module. */
    private $cm;

    /** @var \context_module  $context - Context. */
    protected $context;

    /** @var stdClass - StudentQuiz data. */
    protected $studentquiz;

    /** @var int - StudentQuiz question id. */
    protected $id;


    /**
     * studentquiz_question constructor.
     *
     * @param int $studentquizquestionid
     * @param question_definition|null $question
     * @param stdClass|null $studentquiz
     * @param mixed|null $cm
     * @param mixed|null $context
     */
    public function __construct(int $studentquizquestionid, ?question_definition $question = null,
        ?stdClass $studentquiz = null, $cm = null, $context = null) {
        $this->id = $studentquizquestionid;
        $this->load_studentquiz_question();
        $this->question = $question;
        $this->studentquiz = $studentquiz;
        $this->cm = $cm;
        $this->context = $context;
    }

    /**
     * Get question.
     *
     * @return question_definition
     */
    public function get_question(): question_definition {
        if (!isset($this->question)) {
            $this->question = \question_bank::load_question($this->data->questionid);
        }

        return $this->question;
    }

    /**
     * Get StudentQuiz.
     *
     * @return stdClass
     */
    public function get_studentquiz(): stdClass {
        if (!isset($this->studentquiz)) {
            $this->studentquiz = mod_studentquiz_load_studentquiz($this->data->cmid, $this->get_context()->id);
        }

        return $this->studentquiz;
    }

    /**
     * Get course module.
     * If we already have an existing cm, we will get from the class. Otherwise, get from database will return stdClass.
     *
     * @return stdClass|\cm_info
     */
    public function get_cm(): mixed {
        if (!isset($this->cm)) {
            $this->cm = get_coursemodule_from_id('studentquiz', $this->data->cmid);
        }

        return $this->cm;
    }

    /**
     * Get context.
     *
     * @return stdClass
     */
    public function get_context(): stdClass {
        if (!isset($this->context)) {
            $this->context = \context_module::instance($this->data->cmid);
        }

        return $this->context;
    }

    /**
     * Get StudentQuiz question Id.
     *
     * @return int StudentQuiz question Id
     */
    public function get_id(): int {
        return $this->data->id;
    }

    /**
     * Get the id of the group that was selected when this question was attempted, if any.
     *
     * @return int groupid, or 0.
     */
    public function get_groupid(): int {
        return $this->data->groupid;
    }

    /**
     * Get StudentQuiz question state.
     *
     * @return int StudentQuiz question Id
     */
    public function get_state(): int {
        return $this->data->state;
    }

    /**
     * Get current visibility of question.
     *
     * @return bool Question's visibility hide/show.
     */
    public function is_hidden(): bool {
        return ($this->data->hidden == utils::HIDDEN);
    }

    /**
     * Get StudentQuiz question object from questionid.
     * We should get the studentquiz_question.id first then get the object because the question may not the latest version.
     *
     * @param question_definition $question Question definition object.
     * @param stdClass|null $studentquiz
     * @param mixed $cm
     * @param mixed $context
     * @return studentquiz_question StudentQuiz question object.
     */
    public static function get_studentquiz_question_from_question(question_definition $question, ?stdClass $studentquiz = null,
        mixed $cm = null, mixed $context = null): studentquiz_question {
        global $DB;
        $params = [
            'questionid1' => $question->id,
            'questionid2' => $question->id,
        ];
        $sql = "SELECT sqq.id
                  FROM {studentquiz_question} sqq
             LEFT JOIN {question_references} qr ON qr.itemid = sqq.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
             LEFT JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
             -- This way of getting the latest version for each studentquizquestion is a bit more complicated
             -- than we would like, but the simpler SQL did not work in Oracle 11.2.
             -- (It did work find in Oracle 19.x, so once we have updated our min supported
             -- version we could consider update the simpler sql from lib/questionlib.php:2080.
             LEFT JOIN (
                   SELECT lv.questionbankentryid, MAX(lv.version) AS version
                     FROM {studentquiz_question} lsqq
                     JOIN {question_references} lqr ON lqr.itemid = lsqq.id
                          AND lqr.component = 'mod_studentquiz'
                          AND lqr.questionarea = 'studentquiz_question'
                     JOIN {question_bank_entries} lqbe ON lqr.questionbankentryid = lqbe.id
                     JOIN {question_versions} lv ON lv.questionbankentryid = lqr.questionbankentryid
                     JOIN {question} lq ON lq.id = lv.questionid
                    WHERE lq.id = :questionid1
                          AND lqr.version IS NULL
                 GROUP BY lv.questionbankentryid
                       ) latestversions ON latestversions.questionbankentryid = qr.questionbankentryid
             LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid
                       -- Either specified version, or latest ready version.
                       AND qv.version = COALESCE(qr.version, latestversions.version)
             LEFT JOIN {question} q ON q.id = qv.questionid
                 WHERE q.id = :questionid2";
        $record = $DB->get_record_sql($sql, $params, MUST_EXIST);

        return new studentquiz_question($record->id, $question, $studentquiz, $cm, $context);
    }

    /**
     * Load the StudentQuiz question data into this class instance.
     */
    private function load_studentquiz_question(): void {
        global $DB;
        $sql = "SELECT sqq.id, sqq.studentquizid, sqq.state, sqq.hidden, sqq.pinned, sqq.groupid,
                            q.id questionid, qv.version questionversion, qv.status questionstatus,
                            qr.id questionreferenceid, qbe.id questionbankentryid,
                            sq.course courseid, sq.coursemodule cmid,
                            q.createdby
                  FROM {studentquiz_question} sqq
             LEFT JOIN {studentquiz} sq ON sq.id = sqq.studentquizid
             LEFT JOIN {question_references} qr ON qr.itemid = sqq.id
                       AND qr.component = 'mod_studentquiz'
                       AND qr.questionarea = 'studentquiz_question'
             LEFT JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
             -- This way of getting the latest version for each studentquizquestion is a bit more complicated
             -- than we would like, but the simpler SQL did not work in Oracle 11.2.
             -- (It did work find in Oracle 19.x, so once we have updated our min supported
             -- version we could consider update the simpler sql from lib/questionlib.php:2080.
             LEFT JOIN (
                   SELECT lv.questionbankentryid, MAX(lv.version) AS version
                     FROM {studentquiz_question} lsqq
                     JOIN {question_references} lqr ON lqr.itemid = lsqq.id
                          AND lqr.component = 'mod_studentquiz'
                          AND lqr.questionarea = 'studentquiz_question'
                     JOIN {question_bank_entries} lqbe ON lqr.questionbankentryid = lqbe.id
                     JOIN {question_versions} lv ON lv.questionbankentryid = lqr.questionbankentryid
                     JOIN {question} lq ON lq.id = lv.questionid
                    WHERE lsqq.id = :studentquizquestionid1
                          AND lqr.version IS NULL
                 GROUP BY lv.questionbankentryid
                       ) latestversions ON latestversions.questionbankentryid = qr.questionbankentryid
             LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid
                       -- Either specified version, or latest ready version.
                       AND qv.version = COALESCE(qr.version, latestversions.version)
             LEFT JOIN {question} q ON q.id = qv.questionid
                 WHERE sqq.id = :studentquizquestionid2";
        $params = [
            'studentquizquestionid1' => $this->id,
            'studentquizquestionid2' => $this->id
        ];
        $record = $DB->get_record_sql($sql, $params, MUST_EXIST);
        $this->data = $record;
    }

    /**
     * Change a question state of visibility.
     *
     * @param int $state Student Quiz state in \mod_studentquiz\local\studentquiz_helper::$statename
     */
    public function change_state_visibility(int $state): void {
        global $DB;
        // Update question_versions status depend on state.
        $questionstatus = [
            studentquiz_helper::STATE_DISAPPROVED => question_version_status::QUESTION_STATUS_DRAFT,
            studentquiz_helper::STATE_APPROVED => question_version_status::QUESTION_STATUS_READY,
        ];
        if (isset($questionstatus[$state])) {
            $DB->set_field('question_versions', 'status', $questionstatus[$state], ['questionid' => $this->get_question()->id]);
        }
        // Additionally, always un-hide the question when it got approved.
        if ($state === studentquiz_helper::STATE_APPROVED && $this->is_hidden()) {
            $this->change_hidden_status(0);
            $this->save_action(studentquiz_helper::STATE_SHOW, null);
            $this->data->hidden = 0;
        }

        $DB->set_field('studentquiz_question', 'state', $state, ['id' => $this->get_id()]);
        $this->data->state = $state;
    }

    /**
     * Delete is not a real state, so we will hide the question.
     */
    public function change_delete_state(): void {
        global $DB;
        $DB->set_field('question_versions', 'status', question_version_status::QUESTION_STATUS_HIDDEN,
            ['questionid' => $this->get_question()->id]);
    }

    /**
     * Hide / unhide a question
     *
     * @param int $hide 1:hide 0:unhide
     */
    public function change_hidden_status(int $hide): void {
        global $DB;
        $DB->set_field('studentquiz_question', 'hidden', $hide, ['id' => $this->get_id()]);
        $this->data->hidden = $hide;
    }

    /**
     * Pin / unpin a question
     *
     * @param int $pin 1:pin 0:unpin.
     */
    public function change_pin_status(int $pin): void {
        global $DB;
        $DB->set_field('studentquiz_question', 'pinned', $pin, ['id' => $this->get_id()]);
        $this->data->pinned = $pin;
    }

    /**
     * Saving the action change state.
     *
     * @param int $state The state of the question in the StudentQuiz.
     * @param int|null $userid
     * @param int|null $timecreated The time do action.
     * @return bool|int True or new id
     */
    public function save_action(int $state, ?int $userid, ?int $timecreated = null) {
        global $DB;

        $data = new \stdClass();
        $data->studentquizquestionid = $this->get_id();
        $data->userid = $userid;
        $data->state = $state;
        $data->timecreated = isset($timecreated) ? $timecreated : time();

        return $DB->insert_record('studentquiz_state_history', $data);
    }
}
