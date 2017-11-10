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
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');
require_once(__DIR__ . '/locallib.php');

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');

/**
 * This class  holds data about the selected state and generate quizzes
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
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
            return $this->generate_attempt($ids);
        } else {
            $this->hasquestionids = false;
            $this->hasprintableerror = true;
            $this->errormessage = get_string('viewlib_please_select_question', 'studentquiz');
            return false;
        }
    }

    /**
     * Generate an attempt with question usage
     * @param ids array of question ids to be used in this attempt
     */
    private function generate_attempt($ids){

        global $DB, $USER;

        // Load context of studentquiz activity.
        // TODO: use: this->get_context()?
        $context = context_module::instance($this->get_cm_id());
        // ??? --> Should be instance id of studentquiz cm.

        $questionusage = question_engine::make_questions_usage_by_activity('mod_studentquiz', $context);

        $attempt = new stdClass();

        // Add further attempt default values here.
        // TODO: Check if get category id always points to lowest context level category of our studentquiz activity.
        $attempt->categoryid = $this->get_category_id();
        $attempt->userid = $USER->id;

        // TODO: Configurable on Activity Level.
        $questionusage->set_preferred_behaviour(STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR);
        // TODO: Check if this is instance id from studentquiz table
        $attempt->studentquizid = $this->cm->instance;

        // Add questions to usage
        $usageorder = array();
        foreach ($ids as $i => $questionid) {
            $questiondata = question_bank::load_question($questionid);
            $usageorder[$i] = $questionusage->add_question($questiondata);
        }

        // TODO: is it necessary to start all questions here, or just the current one.
        $questionusage->start_all_questions();

        // Commit Usage to persistence:
        question_engine::save_questions_usage_by_activity($questionusage);

        $attempt->questionusageid = $questionusage->get_id();

        $attemptid = $DB->insert_record('studentquiz_attempt', $attempt);

        return $attemptid;
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
            mod_studentquiz_notify_change($lastchanged, $this->course, $module);
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
 * @copyright  2017 HSR (http://www.hsr.ch)
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
