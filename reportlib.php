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
 * Back-end code for handling data - for the reporting site (rank and quiz)
 * It collects all information together.
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
 * Back-end code for handling data - for the reporting site (rank and quiz)
 * It collects all information together.
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
     * @param $cmid course_module id
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
     * get quiz report url
     * @return moodle_url
     */
    public function get_quizreporturl() {
        return new moodle_url('/mod/studentquiz/reportquiz.php', $this->get_urlview_data());
    }

    /**
     * get quiz report url
     * @return moodle_url
     */
    public function get_rankreporturl() {
        return new moodle_url('/mod/studentquiz/reportrank.php', $this->get_urlview_data());
    }

    /**
     * get the urlview data (includes cmid)
     * @return array
     */
    public function get_urlview_data() {
        return array('cmid' => $this->cm->id);
    }

    /**
     * get activity course module
     * @return stdClass
     */
    public function get_coursemodule() {
        return $this->cm;
    }

    /**
     * get activity course module id
     * @return mixed
     */
    public function get_cm_id() {
        return $this->cm->id;
    }


    /**
     * get activity context id
     * @return int
     */
    public function get_context_id() {
        return $this->context->id;
    }

    /**
     * get activity context
     * @return int
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * get activity course
     * @return int
     */
    public function get_course() {
        return $this->course;
    }

    /**
     * get heading course fullname heading
     * @return int
     */
    public function get_heading() {
        return $this->course->fullname;
    }

    /**
     * get the title
     * @return string
     */
    public function get_title() {
        return get_string('reportrank_title', 'studentquiz');
    }

    /**
     * get's the course_section id from the orphan section
     * @return mixed course_section id
     */
    private function get_quiz_course_section_id() {
        global $DB;
        return $DB->get_field('course_sections', 'id', array('course' => $this->course->id, 'section' => COURSE_SECTION_ID));
    }

    /**
     * get all course_modules from quiz and coursection
     * @param $quizmoduleid
     * @param $coursesectionid
     * @return array stdClass course_modules
     */
    private function get_quiz_course_modules($quizmoduleid, $coursesectionid) {
        global $DB;
        return $DB->get_records('course_modules', array('course' => $this->course->id, 'module' => $quizmoduleid, 'section' => $coursesectionid));
    }

    /**
     * @return string pre rendered /mod/quiz/view tables
     */
    public function get_quiz_tables(){
        global $PAGE, $DB, $USER;
        $output_summaries = '';
        $report_renderer= $PAGE->get_renderer('mod_studentquiz');
        $quiz_renderer = $PAGE->get_renderer('mod_quiz');
        $course_modules = $this->get_quiz_course_modules(get_quiz_module_id() ,$this->get_quiz_course_section_id());

        $total = new stdClass();
        $total->numattempts = 0;
        $total->obtainedmarks = 0;
        $total->questionsright = 0;
        $total->questionsanswered = 0;

        foreach($course_modules as $cm){
            $quizobj = quiz::create($cm->instance, $USER->id);
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

            $attempts = quiz_get_user_attempts($quiz->id, $USER->id, 'all', true);
            $lastfinishedattempt = end($attempts);
            $unfinished = false;
            if ($unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $USER->id)) {
                $attempts[] = $unfinishedattempt;

                $quizobj->create_attempt_object($unfinishedattempt)->handle_if_time_expired(time(), false);

                $unfinished = $unfinishedattempt->state == quiz_attempt::IN_PROGRESS ||
                    $unfinishedattempt->state == quiz_attempt::OVERDUE;
                if (!$unfinished) {
                    $lastfinishedattempt = $unfinishedattempt;
                }
                $unfinishedattempt = null;
            }
            $numattempts = count($attempts);

            $viewobj->attempts = $attempts;
            $viewobj->attemptobjs = array();
            $total->numattempts += $numattempts;

            foreach ($attempts as $attempt) {
                $full_attempt = new quiz_attempt($attempt, $quiz, $cm, $this->course, false);
                $viewobj->attemptobjs[] = $full_attempt;

                $this->get_attempt_statistic($full_attempt->get_quizid(), $attempt->uniqueid, $total);
            }

            if (!$canpreview) {
                $mygrade = quiz_get_best_grade($quiz, $USER->id);
            } else if ($lastfinishedattempt) {
                $mygrade = quiz_rescale_grade($lastfinishedattempt->sumgrades, $quiz, false);
            } else {
                $mygrade = null;
            }

            $mygradeoverridden = false;
            $gradebookfeedback = '';

            $grading_info = grade_get_grades($this->course->id, 'mod', 'quiz', $quiz->id, $USER->id);
            if (!empty($grading_info->items)) {
                $item = $grading_info->items[0];
                if (isset($item->grades[$USER->id])) {
                    $grade = $item->grades[$USER->id];

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
            $viewobj->canedit = false; //modified to false
            //changed url's
            $viewobj->editurl = new moodle_url('/course/view.php', array('id' => $this->course->id));
            $viewobj->backtocourseurl = new moodle_url('/course/view.php', array('id' => $this->course->id));
            $viewobj->startattempturl = $quizobj->start_attempt_url();
            $viewobj->startattemptwarning = $quizobj->confirm_start_attempt_message($unfinished);
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

            $output_summaries .= $quiz_renderer->view_table($quiz, $context, $viewobj);
            $output_summaries = str_replace(get_string('summaryofattempts', 'quiz')
                , $quiz_renderer->heading(userdate($quiz->timecreated), 3)
                , $output_summaries);

            if($attempts) {
                $output_summaries .= $quiz_renderer->box($quiz_renderer->view_page_buttons($viewobj), 'quizattempt');
            }
        }

        $output = $report_renderer->view_quizreport_total($total);
        $output .= $output_summaries;

        return $output;
    }

    /**
     * get the obtainedmarks, questionright, questionanswered total from the attempt
     * @param $quizid
     * @param $attempt_uniqueid
     * @param $total attempt question calculated
     */
    private function get_attempt_statistic($quizid, $attempt_uniqueid, &$total) {
        $quba = question_engine::load_questions_usage_by_activity($attempt_uniqueid);

        foreach($this->get_quiz_slots($quizid) as $slot => $value){
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
     * get the quiz slots
     * @param $quizid
     * @return array stdClass slot array
     */
    private function get_quiz_slots($quizid) {
        global $DB;
        return $DB->get_records('quiz_slots',
            array('quizid' => $quizid), 'slot',
            'slot, requireprevious, questionid');
    }

    /**
     * get the calculcated user ranking from the database
     * @return array user ranking data
     */
    public function get_user_ranking() {
        global $DB;
        $sql = 'SELECT'
            . '    u.id AS userid, u.firstname, u.lastname,'
            . '    c.id AS courseid, c.fullname, c.shortname,'
            . '    r.archetype AS rolename,'
            . '    countq.countquestions,'
            . '    votes.meanvotes,'
            . '    COALESCE('
            . '        countquestions * :questionquantifier +'
            . '        ROUND('
            . '          SUM(votes.meanvotes) / countquestions'
            . '        ) * :votequantifier +'
            . '        correctanswers.countanswer * :correctanswerquantifier +'
            . '        incorrectanswers.countanswer * :incorrectanswerquantifier'
            . '    ,0) AS points'
            . '     FROM mdl_studentquiz sq'
            . '     JOIN mdl_course c ON( sq.course = c.id )'
            . '     JOIN mdl_enrol e ON( c.id = e.courseid )'
            . '     JOIN mdl_role r ON( r.id = e.roleid )'
            . '     JOIN mdl_user_enrolments ue ON( ue.enrolid = e.id )'
            . '     JOIN mdl_user u ON( u.id = ue.userid )'
            . '     LEFT JOIN mdl_question q ON( q.createdby = u.id )'
            // -- answered questions
            // -- correct answers
            . '    LEFT JOIN'
            . '    ('
            . '       SELECT'
            . '          COUNT(qna.id) AS countanswer,'
            . '          qza.userid'
            . '       FROM mdl_quiz_attempts qza'
            . '       LEFT JOIN mdl_quiz_slots qs ON ( qs.quizid = qza.quiz )'
            . '       LEFT JOIN mdl_question_attempts qna ON ('
            . '            qza.uniqueid = qna.questionusageid'
            . '            AND qna.questionid = qs.questionid'
            . '            AND qna.rightanswer = qna.responsesummary'
            . '       )'
            . '       GROUP BY qza.userid'
            . '    ) correctanswers ON ( correctanswers.userid = u.id )'
            // -- incorrect answers
            . '    LEFT JOIN'
            . '    ('
            . '         SELECT'
            . '            COUNT(qna.id) AS countanswer,'
            . '            qza.userid'
            . '         FROM mdl_quiz_attempts qza'
            . '         LEFT JOIN mdl_quiz_slots qs ON ( qs.quizid = qza.quiz )'
            . '         LEFT JOIN mdl_question_attempts qna ON ('
            . '              qza.uniqueid = qna.questionusageid'
            . '              AND qna.questionid = qs.questionid'
            . '              AND qna.rightanswer <> qna.responsesummary'
            . '              AND qna.responsesummary IS NOT NULL'
            . '         )'
            . '         GROUP BY qza.userid'
            . '    ) incorrectanswers ON ( incorrectanswers.userid = u.id )'
            //-- questions created
            . '    LEFT JOIN'
            . '    ('
            . '         SELECT COUNT(*) AS countquestions, createdby FROM mdl_question GROUP BY createdby'
            . '    ) countq ON( countq.createdby = u.id )'
            //-- question votes
            . '    LEFT JOIN'
            . '    ('
            . '         SELECT'
            . '            ROUND(SUM(sqvote.vote) / COUNT(sqvote.vote),2) AS meanvotes,'
            . '            questionid'
            . '         FROM mdl_studentquiz_vote sqvote'
            . '         GROUP BY sqvote.questionid'
            . '     ) votes ON( votes.questionid = q.id )'
            . '     WHERE sq.coursemodule = :cmid'
            . '     GROUP BY u.id'
            . '     ORDER BY points DESC';

        return $DB->get_records_sql($sql, array(
            'cmid' => $this->cm->id
            ,'questionquantifier' => get_config('moodle', 'studentquiz_add_question_quantifier')
            ,'votequantifier' => get_config('moodle', 'studentquiz_vote_quantifier')
            ,'correctanswerquantifier' => get_config('moodle', 'studentquiz_correct_answered_question_quantifier')
            ,'incorrectanswerquantifier' => get_config('moodle', 'studentquiz_incorrect_answered_question_quantifier')
        ));
    }

    /**
     * is the logged in user
     * @param $ur stdClass user ranking object
     * @return bool is loggedin user
     */
    public function is_loggedin_user($ur) {
        global $USER;

        return $USER->id == $ur->userid;
    }

    /**
     * is anonym active
     * @return bool
     */
    public function is_anonym() {
        return is_anonym($this->cm->id);
    }
}