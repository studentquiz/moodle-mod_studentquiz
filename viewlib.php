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
 * Back-end code for handling data about selected / created questions and call /mod/quiz to generate quizzes
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');
require_once(dirname(__FILE__) . '/locallib.php');

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');

/**
 * This class  holds data about the selected state and generate quizzes
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_view {
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
     * @var category the default category
     */
    protected $category;
    /**
     * @var  bool has question ids found
     */
    protected $hasquestionids = false;
    /**
     * @var object pagevars
     */
    protected $qbpagevar;
    /**
     * @var bool has errors
     */
    protected $hasprintableerror;
    /**
     * @var string error message
     */
    protected $errormessage;


    /**
     * Constructor assuming we already have the necessary data loaded.
     * @param int $cmid the course_module id for this StudentQuiz
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

        $this->context = context_module::instance($this->cm->id);
        $this->category = question_get_default_category($this->context->id);

        $this->check_question_category();
    }

    /**
     * Check whether the question category is set and set it if it isn't.
     */
    private function check_question_category() {
        global $DB;
        $questioncategory = $DB->get_record('question_categories', array('contextid' => $this->context->id));

        if ($questioncategory->parent != -1) {
            return;
        }

        $parentqcategory = $DB->get_records('question_categories',
                                                  array('contextid' => $this->context->get_parent_context()->id, 'parent' => 0));
        // If there are multiple parents category with parent == 0, use the one with the lowest id.
        if (!empty($parentqcategory)) {
            $questioncategory->parent = reset($parentqcategory)->id;

            foreach ($parentqcategory as $category) {
                if ($questioncategory->parent > $category->id) {
                    $questioncategory->parent = $category->id;
                }
            }
            $DB->update_record('question_categories', $questioncategory);
        }
    }

    /**
     * Generate a quiz if id's are submitted
     * @param array $ids question id's
     * @return bool|int generated quiz course_module id or false on error
     */
    private function generate_quiz($ids) {
        if ($ids) {
            $this->hasquestionids = true;
            // Check whether there already is a Quiz for this User and exactly those questions.
            if ($qcmid = $this->get_existing_quiz($ids)) {
                return $qcmid;
            } else if (!$qcmid = $this->generate_quiz_activity($ids)) {
                $this->hasprintableerror = true;
                $this->errormessage = get_string('viewlib_please_contact_the_admin', 'studentquiz');
                return false;
            }
            return $qcmid;
        } else {
            $this->hasquestionids = false;
            $this->hasprintableerror = true;
            $this->errormessage = get_string('viewlib_please_select_question', 'studentquiz');
            return false;
        }
    }

    /**
     * Checks whetere there allready is the same quiz and returns its id
     * @param array $ids question id's
     * @return bool|int quiz-ID if any has been found
     */
    private function get_existing_quiz($ids) {
        global $USER, $DB;
        $sql = 'SELECT  quizid, COUNT(quizid) FROM {quiz_slots} s1 '
        .' WHERE questionid IN ('.implode(',', $ids).') '
        .' AND (select count(s2.quizid) FROM {quiz_slots} s2 WHERE s1.quizid = s2.quizid) = '
            . count($ids)
                .' GROUP BY quizid '
                .' HAVING COUNT(questionid) = '. count($ids) .' ';
        $result = $DB->get_records_sql($sql, array(), 0, 1);
        if ($entry = reset($result)) {
            $moduleid = mod_studentquiz_get_quiz_module_id();

            $qcmid = $DB->get_field('course_modules', 'id', array('instance' => $entry->quizid, 'module' => $moduleid));
            if (!$DB->get_field('studentquiz_practice', 'id', array('userid' => $USER->id, 'quizcoursemodule' => $qcmid,
                'studentquizcoursemodule' => $this->get_cm_id()))) {
                $this->save_quiz_practice($qcmid);
            }
            return $qcmid;
        }

        return false;
    }

    /**
     * Setup all quiz information and generate it
     * @param array $ids question id's
     * @return bool|int generated quiz course_module id or false on error
     */
    private function generate_quiz_activity($ids) {
        $quiz = $this->get_standard_quiz_setup();
        $quiz->coursemodule = $this->create_quiz_course_module($quiz->course);

        $this->set_course_section_information($quiz->course, $quiz->coursemodule);

        $quiz->instance = $this->quiz_add_instance($quiz);

        foreach ($ids as $key) {
            quiz_add_quiz_question($key, $quiz, 0);
            quiz_update_sumgrades($quiz);
            quiz_set_grade($quiz->sumgrades, $quiz);
        }

        rebuild_course_cache($quiz->course, true);

        $this->save_quiz_practice($quiz->coursemodule);
        return $quiz->coursemodule;
    }

    /**
     * Create a new StudentQuiz practice entry in the database
     * @param int $quizcmid quiz course module id
     */
    private function save_quiz_practice($quizcmid) {
        global $USER, $DB;
        $quizpractice = new stdClass();
        $quizpractice->quizcoursemodule = $quizcmid;
        $quizpractice->studentquizcoursemodule = $this->get_cm_id();
        $quizpractice->userid = $USER->id;
        $DB->insert_record('studentquiz_practice', $quizpractice);
    }

    /**
     * Set the course_section information
     * @param int $courseid destination course id
     * @param int $coursemoudleid quiz course_module id
     */
    private function set_course_section_information($courseid, $coursemoudleid) {
        global $DB;
        $coursesection = $this->get_course_section();

        if (!$coursesection) {
            $coursesectionid = $this->create_course_section($courseid);
            $sequence = array();
        } else {
            $coursesectionid = $coursesection->id;
            $sequence = explode(',', $coursesection->sequence);
        }

        $sequence[] = $coursemoudleid;
        sort($sequence);

        $DB->set_field('course_modules', 'section', $coursesectionid, array('id' => $coursemoudleid));
        $DB->set_field('course_sections', 'sequence', implode(',', $sequence), array('id' => $coursesectionid));
    }

    /**
     * Create a new course section with default parameters
     * @param int $courseid destination course id
     * @return bool|int course_sectionds id or false on error
     */
    private function create_course_section($courseid) {
        global $DB;
        $coursesection = new stdClass();
        $coursesection->course = $courseid;
        $coursesection->section = STUDENTQUIZ_COURSE_SECTION_ID;
        $coursesection->name = STUDENTQUIZ_COURSE_SECTION_NAME;
        $coursesection->summary = STUDENTQUIZ_COURSE_SECTION_SUMMARY;
        $coursesection->summaryformat = STUDENTQUIZ_COURSE_SECTION_SUMMARYFORMAT;
        $coursesection->visible = STUDENTQUIZ_COURSE_SECTION_VISIBLE;

        return $DB->insert_record('course_sections', $coursesection);
    }

    /**
     * Get the course_section with the defined default parameter
     * @return mixed course_section rows
     */
    private function get_course_section() {
        global $DB;
        return $DB->get_record('course_sections', array('section' => STUDENTQUIZ_COURSE_SECTION_ID,
                                                        'course' => $this->get_course()->id));
    }

    /**
     * Create a quiz course_module entry with the destination courseid
     * @param int $courseid destination course id
     * @return bool|int course_modules id or false on error
     */
    private function create_quiz_course_module($courseid) {
        global $DB;
        $moduleid = mod_studentquiz_get_quiz_module_id();
        $qcm = new stdClass();
        $qcm->course = $courseid;
        $qcm->module = $moduleid;
        $qcm->instance = 0;

        return $DB->insert_record('course_modules', $qcm);
    }

    /**
     * Get the standard quiz setup - default database parameters quiz table
     * with question behaviour setup in activity module
     * @return stdClass quiz object
     */
    private function get_standard_quiz_setup() {
        $quiz = new stdClass();
        $quiz->course = $this->get_course()->id;
        $quiz->name = $this->cm->name;
        $quiz->intro = STUDENTQUIZ_GENERATE_QUIZ_INTRO;
        $quiz->introformat = 1;
        $quiz->timeopen = 0;
        $quiz->timeclose = 0;
        $quiz->timelimit = 0;
        $quiz->overduehandling = STUDENTQUIZ_GENERATE_QUIZ_OVERDUEHANDLING;
        $quiz->graceperiod = 0;
        $quiz->preferredbehaviour = mod_studentquiz_get_current_behaviour($this->cm);
        $quiz->canredoquestions = 0;
        $quiz->attempts = 0;
        $quiz->attemptonlast = 0;
        $quiz->grademethod = 1;
        $quiz->decimalpoints = 2;
        $quiz->questiondecimalpoints = -1;

        // Reviewattempt.
        $quiz->attemptduring = 1;
        $quiz->attemptimmediately = 1;
        $quiz->attemptopen = 1;
        $quiz->attemptclosed = 1;

        // Reviewcorrectness.
        $quiz->correctnessduring = 1;
        $quiz->correctnessimmediately = 1;
        $quiz->correctnessopen = 1;
        $quiz->correctnessclosed = 1;

        // Reviewmarks.
        $quiz->marksduring = 1;
        $quiz->marksimmediately = 1;
        $quiz->marksopen = 1;
        $quiz->marksclosed = 1;

        // Reviewspecificfeedback.
        $quiz->specificfeedbackduring = 1;
        $quiz->specificfeedbackimmediately = 1;
        $quiz->specificfeedbackopen = 1;
        $quiz->specificfeedbackclosed = 1;

        // Reviewgeneralfeedback.
        $quiz->generalfeedbackduring = 1;
        $quiz->generalfeedbackimmediately = 1;
        $quiz->generalfeedbackopen = 1;
        $quiz->generalfeedbackclosed = 1;

        // Reviewrightanswer.
        $quiz->rightanswerduring = 1;
        $quiz->rightanswerimmediately = 1;
        $quiz->rightansweropen = 1;
        $quiz->rightanswerclosed = 1;

        // Reviewoverallfeedback.
        $quiz->overallfeedbackimmediately = 1;
        $quiz->overallfeedbackopen = 1;
        $quiz->overallfeedbackclosed = 1;

        $quiz->questionsperpage = 1;
        $quiz->navmethod = 'free';
        $quiz->shuffleanswers = 1;
        $quiz->sumgrades = 0.0;
        $quiz->grade = 0.0;
        $quiz->timecreated = time();
        $quiz->quizpassword = '';
        $quiz->subnet = '';
        $quiz->browsersecurity = '-';
        $quiz->delay1 = 0;
        $quiz->delay2 = 0;
        $quiz->showuserpicture = 0;
        $quiz->showblocks = 0;
        $quiz->completionattemptsexhausted  = 0;
        $quiz->completionpass = 0;
        return $quiz;
    }

    /**
     * Override quiz_add_instance method from quiz lib to call custom quiz_after_add_or_update method,
     * because the user has no permission to call this method.
     * @param stdClass $quiz
     * @return bool|int|void
     */
    private function quiz_add_instance($quiz) {
        global $DB;

        // Process the options from the form.
        $quiz->created = time();
        $result = quiz_process_options($quiz);
        if ($result && is_string($result)) {
            return $result;
        }

        // Try to store it in the database.
        $quiz->id = $DB->insert_record('quiz', $quiz);

        // Create the first section for this quiz.
        $DB->insert_record('quiz_sections', array('quizid' => $quiz->id,
            'firstslot' => 1, 'heading' => '', 'shufflequestions' => 0));

        // Do the processing required after an add or an update.
        $this->quiz_after_add_or_update($quiz);

        return $quiz->id;
    }

    /**
     * Override quiz_after_add_or_update method from quiz lib to prevent quiz_update_events,
     * because the user has no permission to do this.
     * @param stdClass $quiz
     */
    private function quiz_after_add_or_update($quiz) {
        global $DB;
        $cmid = $quiz->coursemodule;

        // We need to use context now, so we need to make sure all needed info is already in db.
        $DB->set_field('course_modules', 'instance', $quiz->id, array('id' => $cmid));
        $context = context_module::instance($cmid);

        // Save the feedback.
        $DB->delete_records('quiz_feedback', array('quizid' => $quiz->id));

        for ($i = 0; $i <= $quiz->feedbackboundarycount; $i++) {
            $feedback = new stdClass();
            $feedback->quizid = $quiz->id;
            $feedback->feedbacktext = $quiz->feedbacktext[$i]['text'];
            $feedback->feedbacktextformat = $quiz->feedbacktext[$i]['format'];
            $feedback->mingrade = $quiz->feedbackboundaries[$i];
            $feedback->maxgrade = $quiz->feedbackboundaries[$i - 1];
            $feedback->id = $DB->insert_record('quiz_feedback', $feedback);
            $feedbacktext = file_save_draft_area_files((int)$quiz->feedbacktext[$i]['itemid'],
                $context->id, 'mod_quiz', 'feedback', $feedback->id,
                array('subdirs' => false, 'maxfiles' => -1, 'maxbytes' => 0),
                $quiz->feedbacktext[$i]['text']);
            $DB->set_field('quiz_feedback', 'feedbacktext', $feedbacktext,
                array('id' => $feedback->id));
        }

        // Store any settings belonging to the access rules.
        quiz_access_manager::save_settings($quiz);

        // Update the events relating to this quiz.
        // Function quiz_update_events($quiz); no permission.

        // Update related grade item.
        quiz_grade_item_update($quiz);
    }

    /**
     * Generate the quiz activity with the filtered quiz ids
     * @param array $ids filtered question ids
     * @return bool|int course_module id from generate quiz or false on error
     */
    public function generate_quiz_with_filtered_ids($ids) {
        $tmp = explode(',', $ids);
        $ids = array();
        foreach ($tmp as $id) {
            $ids[$id] = 1;
        }

        return $this->generate_quiz($this->get_question_ids($ids));
    }

    /**
     * Generate the quiz activity with the selected quiz ids
     * @param mixed $submitdata
     * @return bool|int course_module id from generate quiz or false on error
     */
    public function generate_quiz_with_selected_ids($submitdata) {
        return $this->generate_quiz($this->get_question_ids($submitdata));
    }

    /**
     * Shows the question custom bank view
     */
    public function show_questionbank() {
        // Workaround to get permission to use questionbank.
        $_GET['cmid'] = $this->get_cm_id();
        $_POST['cat'] = $this->get_category_id() . ',' . $this->get_context_id();

        // Hide question text.
        $_GET["qbshowtext"] = 0;

        list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars)
            = question_edit_setup('questions', '/mod/studentquiz/view.php', true);

        $this->pageurl = new moodle_url($thispageurl);
        if (($lastchanged = optional_param('lastchanged', 0, PARAM_INT)) !== 0) {
            $this->pageurl->param('lastchanged', $lastchanged);
            mod_studentquiz_notify_change($lastchanged, $this->course);
        }
        $this->qbpagevar = $pagevars;

        $this->questionbank = new \mod_studentquiz\question\bank\studentquiz_bank_view($contexts, $thispageurl,
                                                                                       $this->course, $this->cm);
        $this->questionbank->process_actions();
    }

    /**
     * Get the quiz ids from the submit data
     * @param mixed $rawdata array with prefix q and the id
     * @return array without the prefix q
     */
    private function get_prefixed_question_ids($rawdata) {
        $ids = array();
        foreach ($rawdata as $key => $value) { // Parse input for question ids.
            if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
                $ids[] = $matches[1];
            }
        }
        return $ids;
    }

    /**
     * Get the question ids
     * @param mixed $rawdata
     * @return array|bool ids or false on empty array
     */
    private function get_question_ids($rawdata) {
        if (!isset($rawdata)&& empty($rawdata)) {
            return false;
        }

        $ids = $this->get_prefixed_question_ids($rawdata);

        if (!count($ids)) {
            return false;
        }

        return $ids;
    }

    /**
     * Has question ids set
     * @return bool
     */
    public function has_question_ids() {
        return $this->hasquestionids;
    }

    /**
     * Get the question bank page url
     * @return moodle_url
     */
    public function get_pageurl() {
        return new moodle_url($this->pageurl, $this->get_urlview_data());
    }

    /**
     * Get actual view url
     * @return moodle_url
     */
    public function get_viewurl() {
        return new moodle_url('/mod/studentquiz/view.php', $this->get_urlview_data());
    }

    /**
     * Get the question pagevar
     * @return object
     */
    public function get_qb_pagevar() {
        return $this->qbpagevar;
    }

    /**
     * Get the urlview data (includes cmid)
     * @return array
     */
    public function get_urlview_data() {
        return array('cmid' => $this->cm->id);
    }

    /**
     * Get activity course
     * @return mixed|stdClass
     */
    public function get_course() {
        return $this->course;
    }

    /**
     * Has printable error
     * @return bool
     */
    public function has_printableerror() {
        return $this->hasprintableerror;
    }

    /**
     * Get error message
     * @return string error message
     */
    public function get_errormessage() {
        return $this->errormessage;
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
     * Get activity category id
     * @return mixed
     */
    public function get_category_id() {
        return $this->category->id;
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
     * Get the view title
     * @return string
     */
    public function get_title() {
        return get_string('editquestions', 'question');
    }

    /**
     * Get the question view
     * @return \mod_studentquiz\question\bank\studentquiz_bank_view mixed
     */
    public function get_questionbank() {
        return $this->questionbank;
    }
}

/**
 * Class for StudentQuiz view exceptions. Just saves a couple of arguments on the constructor for a moodle_exception.
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_view_exception extends moodle_exception {
    /**
     * moodle_studentquiz_view_exception constructor.
     * @param string $view
     * @param string $errorcode
     * @param null $a
     * @param string $link
     * @param null $debuginfo
     */
    public function __construct($view, $errorcode, $a = null, $link = '', $debuginfo = null) {
        if (!$link) {
            $link = $view->get_viewurl();
        }
        parent::__construct($errorcode, 'studentquiz', $link, $a, $debuginfo);
    }
}
