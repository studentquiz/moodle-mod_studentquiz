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

use mod_studentquiz\statistics_calculator;

/**
 * Back-end code for handling data - for the reporting site (rank and quiz). It collects all information together.
 *
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
     * @var int Number of questions available in this StudentQuiz.
     * This includes all questions created by not enrolled people
     * And questions in child categories
     */
    protected $availablequestions;

    /**
     * @var int Group id.
     */
    private $groupid;

    /**
     * Get number of available questions
     *
     * @return int
     */
    public function get_available_questions() {
        return $this->availablequestions;
    }

    /**
     * @var int Number of questions available in this StudentQuiz.
     * This includes all questions created by not enrolled people
     * And questions in child categories
     */
    protected $enrolledusers;

    /**
     * Get number of enrolled users
     *
     * @return int
     */
    public function get_enrolled_users() {
        return $this->enrolledusers;
    }

    /** @var stdClass */
    protected $studentquizstats;

    /**
     * Overall Stats of the studentquiz
     * @return stdClass
     */
    public function get_studentquiz_stats() {
        if (empty($this->studentquizstats)) {
            $this->studentquizstats = statistics_calculator::get_community_stats($this->get_cm_id(), $this->groupid);
            $this->questionstats = statistics_calculator::get_question_stats($this->get_cm_id(), $this->groupid);
            $this->studentquizstats->questions_available = $this->questionstats->questions_available;
            $this->studentquizstats->questions_average_rating = $this->questionstats->average_rating;
            $this->studentquizstats->questions_questions_approved = $this->questionstats->questions_approved;
            return $this->studentquizstats;
        } else {
            return $this->studentquizstats;
        }
    }

    /**
     * @var stdClass @userrankingstats Ranking stats for current user (same as ranking table)
     */
    protected $userrankingstats;

    /**
     * Get user ranking stats
     * @return stdClass
     */
    public function get_user_stats() {
        if (empty($this->userrankingstats)) {
            $this->userrankingstats = statistics_calculator::get_user_stats($this->get_cm_id(), $this->groupid,
                $this->get_quantifiers(), $this->get_user_id());
            return $this->userrankingstats;
        } else {
            return $this->userrankingstats;
        }
    }

    /**
     * Returns a user stats record with all zero varlus
     * @return stdClass
     */
    public function get_zero_user_stats() {
        $r = new stdClass();
        $r->userid = 0;
        $r->points = 0;
        $r->questions_created = 0;
        $r->questions_approved = 0;
        $r->rates_received = 0;
        $r->rates_average = 0;
        $r->question_attempts = 0;
        $r->question_attempts_correct = 0;
        $r->question_attempts_incorrect = 0;
        $r->last_attempt_exists = 0;
        $r->last_attempt_correct = 0;
        $r->last_attempt_incorrect = 0;
        return $r;
    }

    /** @var stdClass */
    protected $useractivitystats;

    /**
     * Personal stats for interaction with studentquiz:
     * @return stdClass: numcomments, numrates, avgrates, numstarts
     */
    public function get_useractivitystats() {
        return $this->useractivitystats;
    }

    /**
     * Constructor assuming we already have the necessary data loaded.
     * @param int|string $cmid course_module id
     * @param int|null $userid user id.
     */
    public function __construct($cmid, ?int $userid = null) {
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
        if ($userid) {
            $this->userid = $userid;
        }
        $this->availablequestions = mod_studentquiz_count_questions($cmid);

        \mod_studentquiz\utils::set_default_group($this->cm);
        $this->groupid = groups_get_activity_group($this->cm, true);

        // Check to see if any roles setup has been changed since we last synced the capabilities.
        \mod_studentquiz\access\context_override::ensure_permissions_are_right($this->context);
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
     * @return int
     */
    public function get_quantifier_question() {
        return $this->studentquiz->questionquantifier;
    }

    /**
     * Get the approved quantifier of this studentquiz
     * @return int
     */
    public function get_quantifier_approved() {
        return $this->studentquiz->approvedquantifier;
    }

    /**
     * Get the rate quantifier of this studentquiz
     * @return int
     */
    public function get_quantifier_rate() {
        return $this->studentquiz->ratequantifier;
    }

    /**
     * Get the correctanswerquantifier of this studentquiz
     * @return int
     */
    public function get_quantifier_correctanswer() {
        return $this->studentquiz->correctanswerquantifier;
    }

    /**
     * Get the correctanswerquantifier of this studentquiz
     * @return int
     */
    public function get_quantifier_incorrectanswer() {
        return $this->studentquiz->incorrectanswerquantifier;
    }

    /**
     * Get all the quantifiers
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
     * Get the group title
     *
     * @return string
     */
    public function get_group_title() {
        $grouptitle = '';
        if ($groupmode = groups_get_activity_groupmode($this->cm)) {
            if ($this->groupid) {
                $groupname = groups_get_group_name($this->groupid);
                if ($groupmode == VISIBLEGROUPS) {
                    $grouplabel = get_string('groupsvisible');
                } else {
                    $grouplabel = get_string('groupsseparate');
                }
                $grouptitle = $grouplabel.': '.$groupname;
            }
        }

        return $grouptitle;
    }

    /**
     * Is admin check
     * @return bool
     */
    public function is_admin() {
        return mod_studentquiz_check_created_permission($this->cm->id);
    }

    /**
     * Get the id of the currently evaluated StudentQuiz.
     * @return int
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
        $excluderoles = $this->get_roles_to_exclude();

        return statistics_calculator::get_user_ranking_table($this->get_cm_id(), $this->groupid, $this->get_quantifiers(),
            $excluderoles, $limitfrom, $limitnum);
    }

    /**
     * Get an array of roles to exclude from the report. The array is based on the global config and the parameters of the activity.
     *
     * @return array The array of roles to exclude.
     */
    public function get_roles_to_exclude() {
        $studentquizexcluderoles = (!empty($this->studentquiz->excluderoles)) ?
            explode(',', $this->studentquiz->excluderoles) : [];
        $configexcluderoles = get_config('studentquiz', 'excluderoles');
        $excluderoles = (!empty($configexcluderoles)) ? explode(',', $configexcluderoles) : [];
        $configrolestoshow = get_config('studentquiz', 'allowedrolestoshow');
        $rolestoshow = (!empty($configrolestoshow)) ? explode(',', $configrolestoshow) : [];
        $rolestoexcludebyconfig = array_diff($excluderoles, $rolestoshow);

        $studentquizexcluderoles = array_unique(array_merge($studentquizexcluderoles, $rolestoexcludebyconfig));
        if (empty(array_filter($studentquizexcluderoles))) {
            $studentquizexcluderoles = [];
        }
        return $studentquizexcluderoles;
    }

    /**
     * Get an array of roles which can be excluded and if those roles are selected by default.
     *
     * @return array The array of roles which can be excluded.
     */
    public static function get_roles_which_can_be_exculded() {
        $defaultexcluderoles = explode(',', get_config('studentquiz', 'excluderoles'));
        $rolestoshow = explode(',', get_config('studentquiz', 'allowedrolestoshow'));
        $rolescanbeexculded = [];
        if (!empty(array_filter($rolestoshow))) {
            foreach (mod_studentquiz_get_roles() as $role => $name) {
                if (in_array($role, $rolestoshow)) {
                    $rolescanbeexculded[$role] = ['name' => $name, 'default' => 0];
                    if (in_array($role, $defaultexcluderoles)) {
                        $rolescanbeexculded[$role]['default'] = 1;
                    }
                }
            }
        }
        return $rolescanbeexculded;
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
     * Check whether current user sees others anonymized
     * @return bool studentquiz is set to anoymize
     */
    public function is_anonymized() {
        if (!$this->studentquiz->anonymrank) {
            return false;
        }
        if (has_capability('mod/studentquiz:unhideanonymous', $this->get_context())) {
            return false;
        }
        // Instance is anonymized and isn't allowed to unhide that.
        return true;
    }
}
