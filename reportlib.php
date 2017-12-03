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
 * Back-end code for handling data - for the reporting site (rank and quiz). It collects all information together.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/locallib.php');
//require_once($CFG->dirroot . '/mod/quiz/renderer.php');
//require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
//require_once($CFG->dirroot . '/mod/quiz/accessmanager.php');
//require_once($CFG->libdir . '/gradelib.php');


class mod_studentquiz_report_record {
    // 0: No participants, or user not found.
    // 1: report record represents personalised results
    // >1: report record represents group stats
    public $numparticipants = 0;
    public $points = 0;
    public $questions_created = 0;
    public $questions_approved = 0;
    public $last_attempt_correct = 0;
    public $last_attempt_incorrect = 0;
    public $question_attempts = 0;
    public $question_attempts_correct = 0;
    public $question_attempts_incorrect = 0;
}

/**
 * Back-end code for handling data - for the reporting site (rank and quiz). It collects all information together.
 * TODO: REFACTOR!
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_report {
    /**
     * @var stdClass the course_module settings from the database.
     */
    protected $cm;
    /**
     * @var stdClass the course settings from the database.
     */
    protected $course;
    /**
     * @var context_module the quiz context.
     */
    protected $context;
    /**
     * @var stdClass the studentquiz settings
     */
    protected $studentquiz;

    /**
     * @var $userid of currently viewing user
     */
    protected $userid;

    /**
     * Overall Stats of the studentquiz
     * @return stdClass
     */
    protected $studentquizstats;
    public function get_studentquiz_stats() {
        if (empty($this->studentquizstats)) {
            return $this->studentquizstats = mod_studentquiz_community_stats($this->get_cm_id(), $this->get_quantifiers());
        }else {
            return $this->studentquizstats;
        }
    }

    /**
     * Ranking stats for current user (same as ranking table)
     */
    protected $userrankingstats;
    public function get_user_stats() {
        if (empty($this->userrankingstats)) {
            $this->userrankingstats = mod_studentquiz_user_stats($this->get_cm_id(), $this->get_quantifiers(), $this->get_user_id());
            return $this->userrankingstats;
        }else{
            return $this->userrankingstats;
        }
    }

    /**
     * Personal stats for interaction with studentquiz:
     * @return stdClass: numcomments, numrates, avgrates, numstarts
     */
    protected $useractivitystats;
    public function get_useractivitystats() {
        return $this->useractivitystats;
    }

    /**
     * Constructor assuming we already have the necessary data loaded.
     * @param int $cmid course_module id
     * @throws mod_studentquiz_view_exception if course module or course can't be retrieved
     */
    public function __construct($cmid) {
        global $DB, $USER;
        if (!$this->cm = get_coursemodule_from_id('studentquiz', $cmid)) {
            throw new mod_studentquiz_view_exception($this, 'invalidcoursemodule');
        }
        if (!$this->course = $DB->get_record('course', array('id' => $this->cm->course))) {
            throw new mod_studentquiz_view_exception($this, 'coursemisconf');
        }

        if (!$this->studentquiz = $DB->get_record('studentquiz',
            array('coursemodule' => $this->cm->id, 'course' => $this->course->id))) {
            throw new mod_studentquiz_view_exception($this, 'studentquiznotfound');
        }

        $this->context = context_module::instance($this->cm->id);
        $this->userid = $USER->id;
    }

    /**
     * Returns current user id
     * @return int $user->id
     */
    public function get_user_id() {
        return $this->userid;
    }

    /**
     * Get quiz report url
     * @return moodle_url
     */
    public function get_stat_url() {
        return new moodle_url('/mod/studentquiz/reportstat.php', $this->get_urlview_data());
    }

    /**
     * Get quiz report url
     * @return moodle_url
     */
    public function get_rank_url() {
        return new moodle_url('/mod/studentquiz/reportrank.php', $this->get_urlview_data());
    }

    /**
     * Get the urlview data (includes cmid)
     * @return array
     */
    public function get_urlview_data() {
        return array('cmid' => $this->cm->id);
    }

    /**
     * Get activity course module
     * @return stdClass
     */
    public function get_coursemodule() {
        return $this->cm;
    }

    /**
     * Get activity course module id
     * @return mixed
     */
    public function get_cm_id() {
        return $this->cm->id;
    }

    /**
     * Get activity context id
     * @return int
     */
    public function get_context_id() {
        return $this->context->id;
    }

    /**
     * Get activity context
     * @return context_module
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get activity course
     * @return int
     */
    public function get_course() {
        return $this->course;
    }

    /**
     * Get heading course fullname heading
     * @return int
     */
    public function get_heading() {
        return $this->course->fullname;
    }

    /**
     * Get the question quantifier of this studentquiz
     */
    public function get_quantifier_question() {
        return $this->studentquiz->questionquantifier;
    }

    /**
     * Get the approved quantifier of this studentquiz
     */
    public function get_quantifier_approved() {
        return $this->studentquiz->approvedquantifier;
    }

    /**
     * Get the rate quantifier of this studentquiz
     */
    public function get_quantifier_rate() {
        return $this->studentquiz->ratequantifier;
    }

    /**
     * Get the correctanswerquantifier of this studentquiz
     */
    public function get_quantifier_correctanswer() {
        return $this->studentquiz->correctanswerquantifier;
    }

    /**
     * Get the correctanswerquantifier of this studentquiz
     */
    public function get_quantifier_incorrectanswer() {
        return $this->studentquiz->incorrectanswerquantifier;
    }

    /**
     * @return stdClass of quantifiers
     */
    public function get_quantifiers() {
        $quantifiers = new stdClass();
            $quantifiers->question = $this->studentquiz->questionquantifier;
            $quantifiers->rate = $this->studentquiz->ratequantifier;
            $quantifiers->approved = $this->studentquiz->approvedquantifier;
            $quantifiers->correctanswer = $this->studentquiz->correctanswerquantifier;
            $quantifiers->incorrectanswer = $this->studentquiz->incorrectanswerquantifier;
        return $quantifiers;
    }

    /**
     * Get the ranking title
     * @return string
     */
    public function get_ranking_title() {
        return get_string('reportrank_title', 'studentquiz');
    }

    /**
     * Get the statistic title
     * @return string
     */
    public function get_statistic_title() {
        return get_string('reportquiz_stats_title', 'studentquiz');
    }

    /**
     * Is admin check
     * @return bool
     */
    public function is_admin() {
        return mod_studentquiz_check_created_permission($this->cm->id);
    }

    /**
     * Returns the id of the currently evaluated StudentQuiz.
     */
    public function get_studentquiz_id() {
        return $this->cm->instance;
    }

    /**
     * Get Paginated ranking data ordered (DESC) by points, questions_created, questions_approved, rates_average
     * @param int $limitfrom return a subset of records, starting at this point (optional).
     * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
     * @return moodle_recordset of paginated ranking table
     */
    public function get_user_ranking_table($limitfrom = 0, $limitnum = 0) {
        return mod_studentquiz_get_user_ranking_table($this->get_cm_id(), $this->get_quantifiers(), 0, $limitfrom, $limitnum);
    }

    /**
     * Is the logged in user
     * @param int $userid
     * @return bool is loggedin user
     */
    public function is_loggedin_user($userid) {
        return $this->userid == $userid;
    }

    /**
     * @return bool studentquiz is set to anoymize
     */
    public function is_anonymized() {
        if (!$this->studentquiz->anonymrank) {
            return false;
        }
        if(has_capability('mod/studentquiz:unhideanonymous', $this->get_context())) {
            return false;
        }
        // Instance is anonymized and isn't allowed to unhide that.
        return true;
    }
}
