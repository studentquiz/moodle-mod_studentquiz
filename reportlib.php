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
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/renderer.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->dirroot . '/mod/quiz/accessmanager.php');
require_once($CFG->libdir.'/gradelib.php');

/**
 * Back-end code for handling data - for the reporting site (rank and quiz). It collects all information together.
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_report {
    /**
     * @var stdClass the course_module settings from the database.
     */
    protected $cm;
    /**
     * @var stdClass the course settings from the database.
     */
    protected $course;
    /**
     * @var context the quiz context.
     */
    protected $context;

    /**
     * Constructor assuming we already have the necessary data loaded.
     * @param int $cmid course_module id
     * @throws moodle_studentquiz_view_exception if course module or course can't be retrieved
     */
    public function __construct($cmid) {
        global $DB;
        if (!$this->cm = get_coursemodule_from_id('studentquiz', $cmid)) {
            throw new moodle_studentquiz_view_exception($this, 'invalidcoursemodule');
        }
        if (!$this->course = $DB->get_record('course', array('id' => $this->cm->course))) {
            throw new moodle_studentquiz_view_exception($this, 'coursemisconf');
        }

        $this->context = context_module::instance($this->cm->id);
    }

    /**
     * Get quiz report url
     * @return moodle_url
     */
    public function get_quizreporturl() {
        return new moodle_url('/mod/studentquiz/reportquiz.php', $this->get_urlview_data());
    }

    /**
     * Get quiz report url
     * @return moodle_url
     */
    public function get_rankreporturl() {
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
     * @return int
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
     * Get the title
     * @return string
     */
    public function get_title() {
        return get_string('reportrank_title', 'studentquiz');
    }

    /**
     * Get's the course_section id from the orphan section
     * @return mixed course_section id
     */
    private function get_quiz_course_section_id() {
        global $DB;
        return $DB->get_field('course_sections', 'id', array('course' => $this->course->id, 'section' => COURSE_SECTION_ID));
    }

    /**
     * Get all quiz course_modules from the active studentquiz
     * @param int $userid
     * @return array stdClass course modules
     */
    private function get_quiz_course_modules($userid) {
        global $DB;

        $sql = 'SELECT'
            . '    cm.*'
            . '   FROM {studentquiz_practice} sq'
            . '   JOIN {course_modules} cm'
            . '     ON sq.quizcoursemodule = cm.id'
            . '   WHERE sq.userid = :userid'
            . '   AND sq.studentquizcoursemodule = :studentquizcoursemodule'
            . '   ORDER BY cm.id DESC';

        return $DB->get_records_sql($sql, array(
            'userid' => $userid
            , 'studentquizcoursemodule' => $this->cm->id));
    }

    /**
     * Get quiz tables
     * @return string rendered /mod/quiz/view tables
     */
    public function get_quiz_tables() {
        global $PAGE, $USER;
        $reportrenderer = $PAGE->get_renderer('mod_studentquiz');

        $total = new stdClass();
        $total->numattempts = 0;
        $total->obtainedmarks = 0;
        $total->questionsright = 0;
        $total->questionsanswered = 0;

        $outputsummaries = $this->get_user_quiz_summary($USER->id, $total);

        $output = $reportrenderer->view_quizreport_total($total);
        $output .= $reportrenderer->view_quizreport_summary();
        $output .= $outputsummaries;

        return $output;
    }

    /**
     * Get all users in a course
     * @param int $courseid
     * @return array stdClass userid, courseid, firstname, lastname
     */
    private function get_all_users_in_course($courseid) {
        global $DB;

        $sql = 'SELECT u.id as userid, c.id as courseid, u.firstname, u.lastname'
            . '     FROM {user} u'
            . '     INNER JOIN {user_enrolments} ue ON ue.userid = u.id'
            . '     INNER JOIN {enrol} e ON e.id = ue.enrolid'
            . '     INNER JOIN {course} c ON e.courseid = c.id'
            . '     WHERE c.id = :courseid';

        return $DB->get_records_sql($sql, array(
            'courseid' => $courseid
        ));
    }

    /**
     * Is admin check
     * @return bool
     */
    public function is_admin() {
        return mod_check_created_permission();
    }

    /**
     * Get quiz admin statistic view
     * @return string pre rendered /mod/stundentquiz view_quizreport_table
     */
    public function get_quiz_admin_statistic_view() {
        global $PAGE, $USER;
        $reportrenderer = $PAGE->get_renderer('mod_studentquiz');

        $overalltotal = new stdClass();
        $overalltotal->numattempts = 0;
        $overalltotal->obtainedmarks = 0;
        $overalltotal->questionsright = 0;
        $overalltotal->questionsanswered = 0;
        $usersdata = array();

        $users = $this->get_all_users_in_course($this->course->id);
        $overalltotal->usercount = count($users);
        foreach ($users as $user) {
            $total = new stdClass();
            $total->numattempts = 0;
            $total->obtainedmarks = 0;
            $total->questionsright = 0;
            $total->questionsanswered = 0;
            $this->get_user_quiz_summary($user->userid, $total);

            $overalltotal->numattempts += $total->numattempts;
            $overalltotal->obtainedmarks += $total->obtainedmarks;
            $overalltotal->questionsright += $total->questionsright;
            $overalltotal->questionsanswered += $total->questionsanswered;

            $total->name = $user->firstname . ' ' . $user->lastname;
            $total->id = $user->userid;
            $usersdata[] = $total;
        }

        $output = $reportrenderer->view_quizreport_total($overalltotal, true);
        $output .= $reportrenderer->view_quizreport_table($this, $usersdata);

        $output .= $reportrenderer->view_quizreport_admin_quizzes($this, $this->get_quiz_information($USER->id));

        return $output;
    }

    /**
     * Get quiz information
     * @param int $userid
     * @return array
     */
    public function get_quiz_information($userid) {
        $quizinfos = array();
        foreach ($this->get_quiz_course_modules($userid) as $cm) {
            $quizobj = quiz::create($cm->instance, $userid);
            $quiz = $quizobj->get_quiz();
            $quiz->cmid = $cm->id;
            $quizinfos[] = $quiz;
        }
        return $quizinfos;
    }

    /**
     * Pre render the single user summary table and get quiz stats
     * @param int $userid
     * @param stdClass $total
     * @return mixed|string
     * @throws coding_exception
     */
    public function get_user_quiz_summary($userid, &$total) {
        global $PAGE;
        $outputsummaries = '';
        $coursemodules = $this->get_quiz_course_modules($userid);
        $quizrenderer = $PAGE->get_renderer('mod_quiz');
        foreach ($coursemodules as $cm) {
            $quizobj = quiz::create($cm->instance, $userid);
            $quiz = $quizobj->get_quiz();
            $context = context_module::instance($cm->id);

            /*
             *  modified /mod/quiz/view.php code, simplified and rearranged
             *  ==============================================================
             */

            $canattempt = has_capability('mod/quiz:attempt', $context);
            $canreviewmine = has_capability('mod/quiz:reviewmyattempts', $context);
            $canpreview = has_capability('mod/quiz:preview', $context);

            $accessmanager = new quiz_access_manager($quizobj, time(),
                has_capability('mod/quiz:ignoretimelimits', $context, null, false));

            $viewobj = new mod_quiz_view_object();
            $viewobj->accessmanager = $accessmanager;
            $viewobj->canreviewmine = $canreviewmine;

            $attempts = quiz_get_user_attempts($quiz->id, $userid, 'all', true);
            $lastfinishedattempt = end($attempts);
            $unfinished = false;
            $unfinishedattemptid = null;
            if ($unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $userid)) {
                $attempts[] = $unfinishedattempt;

                $quizobj->create_attempt_object($unfinishedattempt)->handle_if_time_expired(time(), false);

                $unfinished = $unfinishedattempt->state == quiz_attempt::IN_PROGRESS ||
                    $unfinishedattempt->state == quiz_attempt::OVERDUE;
                if (!$unfinished) {
                    $lastfinishedattempt = $unfinishedattempt;
                }
                $unfinishedattemptid = $unfinishedattempt->id;
                $unfinishedattempt = null; // To make it clear we do not use this again.
            }
            $numattempts = count($attempts);
            $viewobj->attempts = $attempts;
            $viewobj->attemptobjs = array();
            $total->numattempts += $numattempts;

            foreach ($attempts as $attempt) {
                $fullattempt = new quiz_attempt($attempt, $quiz, $cm, $this->course, false);
                $viewobj->attemptobjs[] = $fullattempt;

                $this->get_attempt_statistic($fullattempt->get_quizid(), $attempt->uniqueid, $total);
            }

            if (!$canpreview) {
                $mygrade = quiz_get_best_grade($quiz, $userid);
            } else if ($lastfinishedattempt) {
                $mygrade = quiz_rescale_grade($lastfinishedattempt->sumgrades, $quiz, false);
            } else {
                $mygrade = null;
            }

            $mygradeoverridden = false;
            $gradebookfeedback = '';

            $gradinginfo = grade_get_grades($this->course->id, 'mod', 'quiz', $quiz->id, $userid);
            if (!empty($gradinginfo->items)) {
                $item = $gradinginfo->items[0];
                if (isset($item->grades[$userid])) {
                    $grade = $item->grades[$userid];

                    if ($grade->overridden) {
                        $mygrade = $grade->grade + 0; // Convert to number.
                        $mygradeoverridden = true;
                    }
                    if (!empty($grade->str_feedback)) {
                        $gradebookfeedback = $grade->str_feedback;
                    }
                }
            }

            if ($attempts) {
                list($someoptions, $alloptions) = quiz_get_combined_reviewoptions($quiz, $attempts, $context);

                $viewobj->attemptcolumn  = $quiz->attempts != 1;

                $viewobj->gradecolumn    = $someoptions->marks >= question_display_options::MARK_AND_MAX &&
                    quiz_has_grades($quiz);
                $viewobj->markcolumn     = $viewobj->gradecolumn && ($quiz->grade != $quiz->sumgrades);
                $viewobj->overallstats   = $lastfinishedattempt && $alloptions->marks >= question_display_options::MARK_AND_MAX;

                $viewobj->feedbackcolumn = quiz_has_feedback($quiz) && $alloptions->overallfeedback;
            }

            $viewobj->timenow = time();
            $viewobj->numattempts = $numattempts;
            $viewobj->mygrade = $mygrade;
            $viewobj->moreattempts = $unfinished ||
                !$accessmanager->is_finished($numattempts, $lastfinishedattempt);
            $viewobj->mygradeoverridden = $mygradeoverridden;
            $viewobj->gradebookfeedback = $gradebookfeedback;
            $viewobj->lastfinishedattempt = $lastfinishedattempt;
            $viewobj->canedit = false; // Modified to false.
            // Changed url's.
            $viewobj->editurl = new moodle_url('/course/view.php', array('id' => $this->course->id));
            $viewobj->backtocourseurl = new moodle_url('/course/view.php', array('id' => $this->course->id));
            $viewobj->startattempturl = $quizobj->start_attempt_url();

            if ($accessmanager->is_preflight_check_required($unfinishedattemptid)) {
                $viewobj->preflightcheckform = $accessmanager->get_preflight_check_form(
                    $viewobj->startattempturl, $unfinishedattemptid);
            }

            $viewobj->popuprequired = $accessmanager->attempt_must_be_in_popup();
            $viewobj->popupoptions = $accessmanager->get_popup_options();

            $viewobj->infomessages = $viewobj->accessmanager->describe_rules();
            if ($quiz->attempts != 1) {
                $viewobj->infomessages[] = get_string('gradingmethod', 'quiz',
                    quiz_get_grading_option_name($quiz->grademethod));
            }

            $viewobj->quizhasquestions = $quizobj->has_questions();
            $viewobj->preventmessages = array();
            if (!$viewobj->quizhasquestions) {
                $viewobj->buttontext = '';

            } else {
                if ($unfinished) {
                    if ($canattempt) {
                        $viewobj->buttontext = get_string('continueattemptquiz', 'quiz');
                    } else if ($canpreview) {
                        $viewobj->buttontext = get_string('continuepreview', 'quiz');
                    }
                } else {
                    if ($canattempt) {
                        $viewobj->preventmessages = $viewobj->accessmanager->prevent_new_attempt(
                            $viewobj->numattempts, $viewobj->lastfinishedattempt);

                        if ($viewobj->preventmessages) {
                            $viewobj->buttontext = '';
                        } else if ($viewobj->numattempts == 0) {
                            $viewobj->buttontext = get_string('attemptquiznow', 'quiz');
                        } else {
                            $viewobj->buttontext = get_string('reattemptquiz', 'quiz');
                        }
                    } else if ($canpreview) {
                        $viewobj->buttontext = get_string('previewquiznow', 'quiz');
                    }
                }

                if ($viewobj->buttontext) {
                    if (!$viewobj->moreattempts) {
                        $viewobj->buttontext = '';
                    } else if ($canattempt
                        && $viewobj->preventmessages = $viewobj->accessmanager->prevent_access()) {
                        $viewobj->buttontext = '';
                    }
                }
            }

            $viewobj->showbacktocourse = ($viewobj->buttontext === '' &&
                course_get_format($this->course)->has_view_page());

            /*
             *  ==============================================================
             *  custom code
            */

            $outputsummaries .= $quizrenderer->view_table($quiz, $context, $viewobj);
            $outputsummaries = str_replace(get_string('summaryofattempts', 'quiz')
                , $quizrenderer->heading(userdate($quiz->timecreated), 3)
                , $outputsummaries);

            if ($attempts) {
                $outputsummaries .= $quizrenderer->box($quizrenderer->view_page_buttons($viewobj), 'quizattempt');
            }
        }
        return $outputsummaries;
    }

    /**
     * Get the obtainedmarks, questionright, questionanswered total from the attempt
     * @param int $quizid
     * @param int $attemptuniqueid
     * @param stdClass $total
     */
    private function get_attempt_statistic($quizid, $attemptuniqueid, &$total) {
        $quba = question_engine::load_questions_usage_by_activity($attemptuniqueid);

        foreach ($this->get_quiz_slots($quizid) as $slot => $value) {
            $fraction = $quba->get_question_fraction($slot);
            $maxmarks = $quba->get_question_max_mark($slot);
            $total->obtainedmarks += $fraction * $maxmarks;
            if ($fraction > 0) {
                ++$total->questionsright;
            }
            ++$total->questionsanswered;
        }
    }

    /**
     * Get the quiz slots
     * @param int $quizid
     * @return array stdClass slot array
     */
    private function get_quiz_slots($quizid) {
        global $DB;
        return $DB->get_records('quiz_slots',
            array('quizid' => $quizid), 'slot',
            'slot, requireprevious, questionid');
    }

    /**
     * Get the calculcated user ranking from the database
     * @return array user ranking data
     */
    public function get_user_ranking() {
        global $DB;
        $sql = 'SELECT'
            . '    u.id AS userid, u.firstname, u.lastname,'
            . '    MAX(c.id) AS courseid, MAX(c.fullname), MAX(c.shortname),'
            . '    MAX(r.archetype) AS rolename,'
            . '    MAX(countq.countquestions),'
            . '    MAX(votes.meanvotes),'
            . '    COALESCE('
            . '        COALESCE(MAX(countquestions) * :questionquantifier,0) +'
            . '        COALESCE(ROUND(SUM(votes.meanvotes) * :votequantifier), 0) +'
            . '        COALESCE(MAX(correctanswers.countanswer) * :correctanswerquantifier,0) +'
            . '        COALESCE(MAX(incorrectanswers.countanswer) * :incorrectanswerquantifier,0)'
            . '    ,0) AS points'
            . '     FROM {studentquiz} sq'
            . '     JOIN {context} con ON( con.instanceid = sq.coursemodule )'
            . '     JOIN {question_categories} qc ON( qc.contextid = con.id )'
            . '     JOIN {course} c ON( sq.course = c.id )'
            . '     JOIN {enrol} e ON( c.id = e.courseid )'
            . '     JOIN {role} r ON( r.id = e.roleid )'
            . '     JOIN {user_enrolments} ue ON( ue.enrolid = e.id )'
            . '     JOIN {user} u ON( u.id = ue.userid )'
            . '     LEFT JOIN {question} q ON( q.createdby = u.id AND q.category = qc.id )'
            // Answered questions.
            // Correct answers.
            . '    LEFT JOIN'
            . '    ('
            . '       SELECT'
            . '          COUNT(qna.id) AS countanswer,'
            . '          qza.userid, q.category'
            . '       FROM {quiz_attempts} qza'
            . '       LEFT JOIN {quiz_slots} qs ON( qs.quizid = qza.quiz )'
            . '       LEFT JOIN {question_attempts} qna ON('
            . '            qza.uniqueid = qna.questionusageid'
            . '            AND qna.questionid = qs.questionid'
            . '            AND qna.rightanswer = qna.responsesummary'
            . '       )'
            . '       LEFT JOIN {question} q ON( q.id = qna.questionid )'
            . '       GROUP BY q.category, qza.userid'
            . '    ) correctanswers ON( correctanswers.userid = u.id AND correctanswers.category = qc.id )'
            // Incorrect answers.
            . '    LEFT JOIN'
            . '    ('
            . '         SELECT'
            . '            COUNT(qna.id) AS countanswer,'
            . '            qza.userid, q.category'
            . '         FROM {quiz_attempts} qza'
            . '         LEFT JOIN {quiz_slots} qs ON ( qs.quizid = qza.quiz )'
            . '         LEFT JOIN {question_attempts} qna ON ('
            . '              qza.uniqueid = qna.questionusageid'
            . '              AND qna.questionid = qs.questionid'
            . '              AND qna.rightanswer <> qna.responsesummary'
            . '              AND qna.responsesummary IS NOT NULL'
            . '         )'
            . '         LEFT JOIN {question} q ON( q.id = qna.questionid )'
            . '         GROUP BY q.category, qza.userid'
            . '    ) incorrectanswers ON ( incorrectanswers.userid = u.id AND incorrectanswers.category = qc.id )'
            // Questions created.
            . '    LEFT JOIN'
            . '    ('
            . '         SELECT COUNT(*) AS countquestions, createdby, category FROM {question} GROUP BY category, createdby'
            . '    ) countq ON( countq.createdby = u.id AND countq.category = qc.id )'
            // Question votes.
            . '    LEFT JOIN'
            . '    ('
            . '         SELECT'
            . '            ROUND(SUM(sqvote.vote) / COUNT(sqvote.vote),2) AS meanvotes,'
            . '            questionid'
            . '         FROM {studentquiz_vote} sqvote'
            . '         GROUP BY sqvote.questionid'
            . '     ) votes ON( votes.questionid = q.id )'
            . '     WHERE sq.coursemodule = :cmid'
            . '     GROUP BY u.id'
            . '     ORDER BY points DESC';

        return $DB->get_records_sql($sql, array(
            'cmid' => $this->cm->id
            , 'questionquantifier' => get_config('moodle', 'studentquiz_add_question_quantifier')
            , 'votequantifier' => get_config('moodle', 'studentquiz_vote_quantifier')
            , 'correctanswerquantifier' => get_config('moodle', 'studentquiz_correct_answered_question_quantifier')
            , 'incorrectanswerquantifier' => get_config('moodle', 'studentquiz_incorrect_answered_question_quantifier')
        ));
    }

    /**
     * Is the logged in user
     * @param int $userid
     * @return bool is loggedin user
     */
    public function is_loggedin_user($userid) {
        global $USER;

        return $USER->id == $userid;
    }

    /**
     * Is anonym active
     * @return bool
     */
    public function is_anonym() {
        return is_anonym($this->cm->id);
    }
}
