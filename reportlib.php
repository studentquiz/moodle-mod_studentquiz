<?php
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
     * @var $users array of user ids enrolled in this course
     * @deprecated TODO: REFACTOR We don't want to load all users into memory!
     */
    protected $users;

    protected $admintotal;

    public function get_admintotal() {
        return $this->admintotal;
    }

    protected $outputstats;

    public function get_outputstats() {
        return $this->outputstats;
    }

    protected $usergrades;

    public function get_usergrades() {
        return $this->usergrades;
    }

    protected $usersdata;

    public function get_usersdata() {
        return $this->usersdata;
    }

    protected $overalltotal;

    public function get_overalltotal() {
        return $this->overalltotal;
    }

    /**
     *
     */
    protected $questionscount;
    public function get_questions_count() {
        return $this->questionscount;
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

        $this->questionscount = mod_studentquiz_count_questions($cmid);

        $this->context = context_module::instance($this->cm->id);
        $this->userid = $USER->id;
    }

    /**
     * @param $users enrolled in this course
     * @deprecated TODO REFACTOR: We don't want to load all the users into memory
     */
    public function set_users($users) {
        $this->users = $users;
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
     * Get the vote quantifier of this studentquiz
     */
    public function get_quantifier_vote() {
        return $this->studentquiz->votequantifier;
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
     * Get the array of quanitifers
     */
    public function get_quantifiers() {
        $quantifiers = new stdClass();
            $quantifiers->question = $this->studentquiz->questionquantifier;
            $quantifiers->vote = $this->studentquiz->votequantifier;
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
     * TODO: We don't want to have all users in memory!
     * @deprecated
     */
    public function get_users() {
        return $this->users;
    }

    /**
     * TODO: Don't calc stats one by one, using a clever sql query should be faster and less resource hungry
     * @deprecated
     */
    public function calc_stats() {
        $overalltotal = new stdClass();
        $overalltotal->numattempts = 0;
        $overalltotal->obtainedmarks = 0;
        $overalltotal->questionsright = 0;
        $overalltotal->questionsanswered = 0;
        $usersdata = array();
        $overalltotal->usercount = count($this->users);

        $admintotal = new stdClass();
        $admintotal->numattempts = 0;
        $admintotal->obtainedmarks = 0;
        $admintotal->questionsright = 0;
        $admintotal->questionsanswered = 0;



        foreach ($this->users as $user) {
            $total = new stdClass();
            $total->numattempts = 0;
            $total->obtainedmarks = 0;
            $total->questionsright = 0;
            $total->questionsanswered = 0;
            $this->get_user_attempt_summary($user->userid, $total);
            $userstats = $this->get_user_quiz_grade($user->userid, $this->get_cm_id());
            $total->attemptedgrade = $userstats->usermark;
            $total->maxgrade = $userstats->stuquizmaxmark;

            $overalltotal->numattempts += $total->numattempts;
            $overalltotal->obtainedmarks += $total->obtainedmarks;
            $overalltotal->questionsright += $total->questionsright;
            $overalltotal->questionsanswered += $total->questionsanswered;

            $total->name = $user->firstname . ' ' . $user->lastname;
            $total->id = $user->userid;
            $usersdata[] = $total;
            if ($user->userid == $this->userid) {
                $this->admintotal = $total;
            }
        }
        $this->overalltotal = $overalltotal;
        $this->usersdata = $usersdata;
        $this->outputstats = $this->get_user_quiz_stats($this->userid, $this->get_cm_id());
        $this->usergrades = $this->get_user_quiz_grade($this->userid, $this->get_cm_id());
    }

    /**
     * Returns the id of the currently evaluated StudentQuiz.
     */
    public function get_studentquiz_id() {
        return $this->cm->instance;
    }

    /**
     * @param $userid
     * @param $cmid
     * @return array
     * @deprecated
     * TODO: We dont want fetch these for each user in course!
     */
    protected function get_user_quiz_stats($userid, $cmid) {
        return mod_studentquiz_get_user_quiz_stats($userid, $cmid);
    }

    /**
     * @param $userid
     * @param $cmid
     * @return array
     * @deprecated
     * TODO: We dont want to featch these for each user in the course!
     */
    protected function get_user_quiz_grade($userid, $cmid) {
        return mod_studentquiz_get_user_quiz_grade($userid, $cmid);
    }

    /**
     * @param $studentquizid
     * @param $userid
     * @return array
     * @deprecated
     * TODO: We dont want to load all attempts for each user into memory!
     */
    public function get_studentquiz_attempts($studentquizid, $userid) {
        return mod_studentquiz_get_user_attempts($studentquizid, $userid);
    }

    /**
     * Pre render the single user summary table and get quiz stats
     * @param int $userid
     * @return stdClass $total aggregated result of attempt statistics
     * @throws coding_exception
     * @deprecated
     */
    public function get_user_attempt_summary($userid, &$total) {
        // TODO: Refactor to not scale DB requests with number of attempts!
        $total = new stdClass();
        $total->numattempts = 0;
        $total->obtainedmarks = 0;
        $total->questionsright = 0;
        $total->questionsanswered = 0;
        $studentquizid = $this->get_studentquiz_id();
        // Get all attempts of this user in this StudentQuiz.
        $studentquizattempts = $this->get_studentquiz_attempts($studentquizid, $userid);
        $numattempts = count($studentquizattempts);
        $total->numattempts += $numattempts;
        foreach ($studentquizattempts as $studentquizattempt) {
               $this->get_attempt_statistic($studentquizattempt->questionusageid, $total);
        }
        return $total;
    }

    /**
     * Get the obtainedmarks, questionright, questionanswered total from the attempt
     * @param int $attemptuniqueid
     * @param stdClass $total
     * @deprecated
     * TODO:
     */
    private function get_attempt_statistic($attemptuniqueid, &$total) {
        return mod_studentquiz_get_attempt_stats($attemptuniqueid, $total);
    }

    /**
     * @return recordset
     * TODO: Refactor: Use Pagination with record sets!
     */
    public function get_user_ranking($limitfrom = 0, $limitnum = 0) {
        return mod_studentquiz_get_user_ranking($this->get_cm_id(), $this->get_quantifiers(), $limitfrom, $limitnum);
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
     * TODO: rename function and apply (there is duplicate method)
     * @return bool studentquiz is set to anoymize ranking.
     */
    public function is_anonym() {
        if (!$this->studentquiz->anonymrank) {
            return false;
        }
        $context = context_module::instance($this->studentquiz->coursemodule);
        if(has_capability('mod/studentquiz:unhideanonymous', $context)) {
            return false;
        }
        // Instance is anonymized and isn't allowed to unhide that.
        return true;
    }


    /**
     *
     */
    public function get_points_by_ranking_record($ur) {
        return
        $ur->questions_created * $this->get_quantifier_question() +
        $ur->questions_approved * $this->get_quantifier_approved() +
        $ur->votes_average * $this->get_quantifier_vote() +
        $ur->question_attempts_correct * $this->get_quantifier_correctanswer() +
        $ur->question_attempts_incorrect * $this->get_quantifier_incorrectanswer();
    }
}
