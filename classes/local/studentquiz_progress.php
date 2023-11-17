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

/**
 *  Studentquiz progress instance
 *
 * @package mod_studentquiz
 * @copyright 2022 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_progress {

    /** @var int ID from studentquiz_progress table */
    public $id;
    /** @var int Question id */
    public $questionid;
    /** @var int User id */
    public $userid;
    /** @var int Id of studentquiz */
    public $studentquizid;
    /** @var int Id of studentquiz_question */
    public $studentquizquestionid;
    /** @var int 0: last answer was wrong or undefined, 1: last answer was correct*/
    public $lastanswercorrect;
    /** @var int number of attempts to answer this question */
    public $attempts;
    /** @var int Number of correct answers */
    public $correctattempts;
    /** @var int Last time read private comment */
    public $lastreadprivatecomment;
    /** @var int Last time read public comment */
    public $lastreadpubliccomment;

    /**
     * studentquiz_progress constructor.
     *
     * @param int $questionid
     * @param int $userid
     * @param int $studentquizid
     * @param int $sqqid
     * @param int $lastanswercorrect
     * @param int $attempts
     * @param int $correctattempts
     * @param int $lastreadprivatecomment
     * @param int $lastreadpubliccomment
     * @param int|null $id
     */
    public function __construct(int $questionid, int $userid, int $studentquizid, int $sqqid, int $lastanswercorrect = 0,
        int $attempts = 0, int $correctattempts = 0, int $lastreadprivatecomment = 0, int $lastreadpubliccomment = 0,
        int $id = null) {
        $this->questionid = $questionid;
        $this->userid = $userid;
        $this->studentquizid = $studentquizid;
        $this->studentquizquestionid = $sqqid;
        $this->lastanswercorrect = $lastanswercorrect;
        $this->attempts = $attempts;
        $this->correctattempts = $correctattempts;
        $this->lastreadprivatecomment = $lastreadprivatecomment;
        $this->lastreadpubliccomment = $lastreadpubliccomment;
        if ($id) {
            $this->id = $id;
        }
    }

    /**
     * Get question_progress instance base on studentquiz_question.
     *
     * @param studentquiz_question $studentquizquestion
     * @param int $userid
     * @return studentquiz_progress
     */
    public static function get_studentquiz_progress_from_studentquiz_question(studentquiz_question $studentquizquestion,
        int $userid): studentquiz_progress {
        return new studentquiz_progress($studentquizquestion->get_question()->id,
            $userid, $studentquizquestion->get_studentquiz()->id, $studentquizquestion->get_id());
    }

    /**
     * Get studentquiz progress.
     *
     * @param studentquiz_question $studentquizquestion studentquiz_question instance.
     * @param int $userid User Id.
     * @return studentquiz_progress
     */
    public static function get_studentquiz_progress(studentquiz_question $studentquizquestion, $userid): studentquiz_progress {
        global $DB;
        $studentquizid = $studentquizquestion->get_studentquiz()->id;
        $sqqid = $studentquizquestion->get_id();
        $studentquizprogress = $DB->get_record('studentquiz_progress', [
            'studentquizquestionid' => $sqqid,
            'userid' => $userid,
            'studentquizid' => $studentquizid
        ]);
        if ($studentquizprogress == false) {
            $studentquizprogress = self::get_studentquiz_progress_from_studentquiz_question($studentquizquestion, $userid);
        } else {
            $studentquizprogress = new studentquiz_progress($studentquizquestion->get_question()->id, $userid, $studentquizid,
                $sqqid, $studentquizprogress->lastanswercorrect, $studentquizprogress->attempts,
                $studentquizprogress->correctattempts, $studentquizprogress->lastreadprivatecomment,
                $studentquizprogress->lastreadpubliccomment, $studentquizprogress->id);
        }

        return $studentquizprogress;
    }

    /**
     * Update studentquiz progress object into db.
     *
     * @param studentquiz_progress $studentquizprogress The studentquiz progress object.
     * @return bool|int
     */
    public static function update_studentquiz_progress(studentquiz_progress $studentquizprogress) {
        global $DB;

        if (!empty($studentquizprogress->id)) {
            $result = $DB->update_record('studentquiz_progress', $studentquizprogress);
        } else {
            $result = $studentquizprogress->id = $DB->insert_record('studentquiz_progress', $studentquizprogress, true);
        }

        return $result;
    }

}
