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

/**
 * Container class for studentquiz question.
 *
 * @package mod_studentquiz
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_question {

    /** @var stdClass $data - Data of studentquiz question. */
    private $data;

    /** @var question_definition $question - Question class. */
    private $question;

    /** @var stdClass $cm - Module. */
    private $cm;

    /** @var stdClass $context - Context. */
    private $context;

    /** @var object|stdClass - Studentquiz data. */
    private $studentquiz;

    /**
     * studentquiz_question constructor.
     *
     * @param int $studentquizquestionid
     * @param question_definition|null $question
     * @param stdClass|null $studentquiz
     * @param mixed|null $cm
     * @param mixed|null $context
     * @throws \dml_exception
     */
    public function __construct(int $studentquizquestionid, question_definition $question = null,
        stdClass $studentquiz = null, $cm = null, $context = null) {
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
     * Get studentquiz.
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
     *
     * @return stdClass
     */
    public function get_cm(): stdClass {
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
     * Get studentquiz question Id.
     *
     * @return int Studentquiz question Id
     */
    public function get_id(): int {
        return $this->data->id;
    }

    /**
     * Get studentquiz question state.
     *
     * @return int Studentquiz question Id
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
     * Get studentquiz question object from questionid.
     * We should get the studentquiz_question.id first then get the object because the question may not the latest version.
     *
     * @param question_definition $question Question definition object.
     * @param stdClass|null $studentquiz
     * @param mixed $cm
     * @param mixed $context
     * @return studentquiz_question Studentquiz question object.
     * @throws \dml_exception
     */
    public static function get_studentquiz_question_from_question($question, stdClass $studentquiz = null,
        $cm = null, $context = null): studentquiz_question {
        global $DB;
        $sql = 'SELECT sqq.id
                  FROM {studentquiz_question} sqq
             LEFT JOIN {question_references} qr ON qr.itemid = sqq.id
                       AND qr.component = \'' . STUDENTQUIZ_COMPONENT_QR . '\'
                       AND qr.questionarea = \'' . STUDENTQUIZ_QUESTIONAREA_QR . '\'
             LEFT JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
             LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                          SELECT MAX(version)
                                            FROM {question_versions}
                                           WHERE questionbankentryid = qbe.id
                                      )
             LEFT JOIN {question} q ON q.id = qv.questionid
                 WHERE q.id = :questionid';
        $record = $DB->get_record_sql($sql, ['questionid' => $question->id], MUST_EXIST);

        return new studentquiz_question($record->id, $question, $studentquiz, $cm, $context);
    }

    /**
     * Get studentquiz question data.
     *
     * @return void
     * @throws \dml_exception
     */
    private function load_studentquiz_question(): void {
        global $DB;
        $sql = 'SELECT sqq.id, sqq.studentquizid, sqq.state, sqq.hidden, sqq.pinned, sqq.groupid,
                            q.id questionid, qv.version questionversion, qv.status questionstatus,
                            qr.id questionreferenceid, qbe.id questionbankentryid,
                            sq.course courseid, sq.coursemodule cmid,
                            q.createdby
                  FROM {studentquiz_question} sqq
             LEFT JOIN {studentquiz} sq ON sq.id = sqq.studentquizid
             LEFT JOIN {question_references} qr ON qr.itemid = sqq.id
                       AND qr.component = \'' . STUDENTQUIZ_COMPONENT_QR . '\'
                       AND qr.questionarea = \'' . STUDENTQUIZ_QUESTIONAREA_QR . '\'
             LEFT JOIN {question_bank_entries} qbe ON qr.questionbankentryid = qbe.id
             LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qr.questionbankentryid AND qv.version = (
                                          SELECT MAX(version)
                                            FROM {question_versions}
                                           WHERE questionbankentryid = qbe.id
                                      )
             LEFT JOIN {question} q ON q.id = qv.questionid
                 WHERE sqq.id = :studentquizquestionid';
        $record = $DB->get_record_sql($sql, ['studentquizquestionid' => $this->id], MUST_EXIST);
        $this->data = $record;
    }

    /**
     * Change a question state of visibility.
     *
     * @param int $type int Type.
     * @param int $value int Value
     * @throws \dml_exception
     */
    public function change_state_visibility($type, $value): void {
        global $DB;
        if ($type == 'deleted') {
            $DB->set_field('question_versions', 'status',
                \core_question\local\bank\question_version_status::QUESTION_STATUS_HIDDEN,
                ['questionid' => $this->get_question()->id]);
        } else {
            $DB->set_field('studentquiz_question', $type, $value, ['id' => $this->get_id()]);
        }
    }

    /**
     * Saving the action change state.
     *
     * @param int $state The state of the question in the StudentQuiz.
     * @param int|null $userid
     * @param int $timecreated The time do action.
     * @return bool|int True or new id
     */
    public function save_action(int $state, int $userid = null, int $timecreated = null) {
        global $DB, $USER;

        $data = new \stdClass();
        $data->studentquizquestionid = $this->get_id();
        $data->userid = isset($userid) ? $userid : $USER->id;
        $data->state = $state;
        $data->timecreated = isset($timecreated) ? $timecreated : time();

        return $DB->insert_record('studentquiz_state_history', $data);
    }
}
