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
require_once($CFG->dirroot . '/mod/quiz/renderer.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->dirroot . '/mod/quiz/accessmanager.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Back-end code for handling data - for the reporting site (rank and quiz). It collects all information together.
 *
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
     * @var context the quiz context.
     */
    protected $context;
    /**
     * @var stdClass the studentquiz settings
     */
    protected $studentquiz;


    /**
     * Constructor assuming we already have the necessary data loaded.
     * @param int $cmid course_module id
     * @throws mod_studentquiz_view_exception if course module or course can't be retrieved
     */
    public function __construct($cmid) {
        global $DB;
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
     * Get the question quantifier of this studentquiz
     */
    public function get_quantifier_question() {
        return $this->studentquiz->questionquantifier;
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
        return array(
            'question' => $this->studentquiz->questionquantifier,
            'vote' => $this->studentquiz->votequantifier,
            'correctanswer' => $this->studentquiz->correctanswerquantifier,
            'incorrectanswer' => $this->studentquiz->incorrectanswerquantifier,
        );
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
     * Get all quiz course_modules from the active StudentQuiz
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

        /** @var mod_studentquiz_renderer $reportrenderer */
        $reportrenderer = $PAGE->get_renderer('mod_studentquiz');

        $total = $this->get_user_quiz_summary($USER->id, $total);
        $outputstats = $this->get_user_quiz_stats($USER->id);
        $usergrades = $this->get_user_quiz_grade($USER->id);
        $output = $reportrenderer->view_quizreport_stats(null, $total, $outputstats, $usergrades);
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
        return mod_studentquiz_check_created_permission($this->cm->id);
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

        $admintotal = new stdClass();
        $admintotal->numattempts = 0;
        $admintotal->obtainedmarks = 0;
        $admintotal->questionsright = 0;
        $admintotal->questionsanswered = 0;

        foreach ($users as $user) {
            $total = new stdClass();
            $total->numattempts = 0;
            $total->obtainedmarks = 0;
            $total->questionsright = 0;
            $total->questionsanswered = 0;
            $this->get_user_quiz_summary($user->userid, $total);
            $userstats = $this->get_user_quiz_grade($user->userid);
            $total->attemptedgrade = $userstats->usermark;
            $total->maxgrade = $userstats->stuquizmaxmark;

            $overalltotal->numattempts += $total->numattempts;
            $overalltotal->obtainedmarks += $total->obtainedmarks;
            $overalltotal->questionsright += $total->questionsright;
            $overalltotal->questionsanswered += $total->questionsanswered;

            $total->name = $user->firstname . ' ' . $user->lastname;
            $total->id = $user->userid;
            $usersdata[] = $total;
            if ($user->userid == $USER->id) {
                $admintotal = $total;
            }
        }
        $outputstats = $this->get_user_quiz_stats($USER->id);
        $usergrades = $this->get_user_quiz_grade($USER->id);

        $output = $reportrenderer->view_quizreport_stats($overalltotal, $admintotal, $outputstats, $usergrades, true);
        $output .= $reportrenderer->view_quizreport_table($this, $usersdata);

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
     * @param $userid
     * @return array usermaxmark usermark stuquizmaxmark
     */
    public function get_user_quiz_grade($userid) {
        global $DB;
        $sql = 'select COALESCE(round(sum(sub.maxmark), 1), 0.0) as usermaxmark, '
            .' COALESCE(round(sum(sub.mark), 1), 0.0) usermark, '
            .'  COALESCE((SELECT round(sum(q.defaultmark), 1) '
            .'     FROM {question} q '
            .'       LEFT JOIN {question_categories} qc ON q.category = qc.id '
            .'       LEFT JOIN {context} c ON qc.contextid = c.id '
            .'     WHERE q.parent = 0 AND c.instanceid = :cmid AND c.contextlevel = 70), 0.0) as stuquizmaxmark '
            .'from ( '
            .'    SELECT suatt.id, suatt.questionid, questionattemptid, max(fraction) as fraction, suatt.maxmark,  '
            .'max(fraction) * suatt.maxmark as mark '
            .'from {question_attempt_steps} suats '
            .'  left JOIN {question_attempts} suatt on suats.questionattemptid = suatt.id '
            .'WHERE state in (\'gradedright\', \'gradedpartial\', \'gradedwrong\') '
            .'        AND userid = :userid AND suatt.questionid IN (SELECT q.id '
            .'                                            FROM {question} q '
            .'                                              LEFT JOIN {question_categories} qc ON q.category = qc.id '
            .'                                              LEFT JOIN {context} c ON qc.contextid = c.id '
            .'                                            WHERE q.parent = 0 AND c.instanceid = :cmid2 AND c.contextlevel = 70) '
            .'AND suats.id in (select max(suatsmax.id)
                         FROM {question_attempt_steps} suatsmax
                           LEFT JOIN {question_attempts} suattmax ON suatsmax.questionattemptid = suattmax.id
                         where suatsmax.state in (\'gradedright\', \'gradedpartial\', \'gradedwrong\')
                         AND suatsmax.userid = suats.userid
                         GROUP BY suattmax.questionid)'
            .'GROUP BY suatt.questionid, suatt.id, suatt.questionid, suatt.maxmark, suats.questionattemptid) as sub ';

        $record = $DB->get_record_sql($sql, array(
            'cmid' => $this->cm->id, 'cmid2' => $this->cm->id,
            'userid' => $userid));
        return $record;
    }

    /**
     * gets the Stats of the user for the actual studenquiz
     * @param int $userid
     * @return array
     */
    public function get_user_quiz_stats($userid) {
        global $DB;
        $sql = 'select ( '
            . '  SELECT count(1) '
            . '  FROM {question} q '
            . '    LEFT JOIN {question_categories} qc ON q.category = qc.id '
            . '    LEFT JOIN {context} c ON qc.contextid = c.id '
            . '  WHERE c.instanceid = :cmid AND q.parent = 0 AND c.contextlevel = 70 '
            . ') AS TotalNrOfQuestions, '
            . '  (SELECT count(1) '
            . '   FROM {question} q '
            . '     LEFT JOIN {question_categories} qc ON q.category = qc.id '
            . '     LEFT JOIN {context} c ON qc.contextid = c.id '
            . '   WHERE c.instanceid = :cmid2 AND q.parent = 0 AND c.contextlevel = 70 AND q.createdby = :userid '
            . '  ) AS TotalUsersQuestions, '
            . '  (select count(DISTINCT att.questionid) '
            . '   from {question_attempt_steps} ats '
            . '     left JOIN {question_attempts} att on att.id = ats.questionattemptid '
            . '   WHERE ats.userid = :userid2 AND ats.state = \'gradedright\' '
            . '         AND att.questionid in (SELECT q.id '
            . '                            FROM {question} q '
            . '                              LEFT JOIN {question_categories} qc ON q.category = qc.id '
            . '                              LEFT JOIN {context} c ON qc.contextid = c.id '
            . '                            WHERE c.instanceid = :cmid3 AND c.contextlevel = 70)
            AND ats.id IN (SELECT max(suatsmax.id)
                        FROM {question_attempt_steps} suatsmax LEFT JOIN {question_attempts} suattmax
                            ON suatsmax.questionattemptid = suattmax.id
                        WHERE suatsmax.state IN (\'gradedright\', \'gradedpartial\', \'gradedwrong\') AND
                              suatsmax.userid = ats.userid
                        GROUP BY suattmax.questionid)) AS TotalRightAnswers ,
                (select  COALESCE(round(avg(v.vote), 1), 0.0)
from {studentquiz_vote} v
where v.questionid in (SELECT q.id
                       FROM {question} q LEFT JOIN
                         {question_categories} qc
                           ON q.category = qc.id
                         LEFT JOIN {context} c
                           ON qc.contextid = c.id
                       WHERE c.instanceid = :cmid4 AND
                             c.contextlevel = 70
                             and q.createdby = :userid3)) as avgvotes,
 (select COALESCE(sum(v.approved), 0)
 from {studentquiz_question} v
 WHERE v.questionid in (SELECT q.id
                       FROM {question} q LEFT JOIN
                         {question_categories} qc
                           ON q.category = qc.id
                         LEFT JOIN {context} c
                           ON qc.contextid = c.id
                       WHERE c.instanceid = :cmid5 AND
                             c.contextlevel = 70
                             and q.createdby = :userid4)) as numapproved ';
        $record = $DB->get_record_sql($sql, array(
            'cmid' => $this->cm->id, 'cmid2' => $this->cm->id, 'cmid3' => $this->cm->id,
            'cmid4' => $this->cm->id, 'cmid5' => $this->cm->id,
            'userid' => $userid, 'userid2' => $userid, 'userid3' => $userid, 'userid4' => $userid));
        return $record;
    }

    /**
     * Returns the id of the currently evaluated StudentQuiz.
     */
    public function get_studentquiz_id() {
        return $this->cm->instance;
    }

    public function get_studentquiz_attempts($studentquizid, $userid) {
        global $DB;
        return $DB->get_records('studentquiz_attempt',
            array('studentquizid' => $studentquizid, 'userid' => $userid));
    }

    /**
     * Pre render the single user summary table and get quiz stats
     * @param int $userid
     * @return stdClass $total aggregated result of attempt statistics
     * @throws coding_exception
     */
    public function get_user_quiz_summary($userid, &$total) {
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
     */
    private function get_attempt_statistic($attemptuniqueid, &$total) {
        $quba = question_engine::load_questions_usage_by_activity($attemptuniqueid);

        foreach ($quba->get_slots() as $slot) {
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
            . '    ROUND(COALESCE(MAX(countquestions),0),1) AS countquestions,'
            . '    ROUND(COALESCE(SUM(votes.meanvotes),0),1) AS summeanvotes,'
            . '    ROUND(COALESCE(MAX(correctanswers.countanswer),0),1) AS correctanswers,'
            . '    ROUND(COALESCE(MAX(incorrectanswers.countanswer),0),1) AS incorrectanswers,'
            . '    ROUND(COALESCE('
            . '        COALESCE(MAX(countquestions) * :questionquantifier, 0) +'
            . '        COALESCE(SUM(votes.meanvotes) * :votequantifier, 0) +'
            . '        COALESCE(MAX(correctanswers.countanswer) * :correctanswerquantifier, 0) +'
            . '        COALESCE(MAX(incorrectanswers.countanswer) * :incorrectanswerquantifier, 0)'
            . '    , 0), 1) AS points'
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
            . '   LEFT JOIN (SELECT  count(DISTINCT suatt.questionid) AS countanswer, userid
             FROM {question_attempt_steps} suats
               LEFT JOIN {question_attempts} suatt ON suats.questionattemptid = suatt.id
             WHERE
               state IN (\'gradedright\', \'gradedpartial\', \'gradedwrong\')
               AND suatt.rightanswer = suatt.responsesummary
               AND suatt.questionid IN (
                 SELECT q.id  FROM {question} q
                   LEFT JOIN {question_categories} qc ON q.category = qc.id
                   LEFT JOIN {context} c ON qc.contextid = c.id
                 WHERE q.parent = 0
                       AND c.instanceid = :cmid2 AND
                       c.contextlevel = 70) AND
               suats.id IN (SELECT max(suatsmax.id)
                            FROM {question_attempt_steps} suatsmax LEFT JOIN {question_attempts} suattmax
                                ON suatsmax.questionattemptid = suattmax.id
                            WHERE suatsmax.state IN (\'gradedright\', \'gradedpartial\', \'gradedwrong\') AND
                                  suatsmax.userid = suats.userid
                            GROUP BY suattmax.questionid)
             GROUP BY userid) correctanswers
    ON (correctanswers.userid = u.id) '
            // Incorrect answers.
            . '    LEFT JOIN'
            . '    ('
            . '         SELECT'
            . '            count(distinct q.id) AS countanswer,'
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
            . '         SELECT COUNT(*) AS countquestions, createdby, category FROM {question}'
            . '         WHERE parent = 0 GROUP BY category, createdby'
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
            . '     GROUP BY u.id, u.firstname, u.lastname'
            . '     ORDER BY points DESC';

        return $DB->get_records_sql($sql, array(
            'cmid' => $this->cm->id, 'cmid2' => $this->cm->id
            , 'questionquantifier' => $this->studentquiz->questionquantifier
            , 'votequantifier' => $this->studentquiz->votequantifier
            , 'correctanswerquantifier' => $this->studentquiz->correctanswerquantifier
            , 'incorrectanswerquantifier' => $this->studentquiz->incorrectanswerquantifier
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
        return mod_studentquiz_is_anonym($this->cm->id);
    }
}
