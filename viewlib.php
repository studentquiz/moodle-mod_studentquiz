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
 * StudentQuiz
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');
require_once(__DIR__ . '/locallib.php');
/**
 * This class loads and represents the state for the main view.
 *
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
     * @var string pageurl
     */
    protected $pageurl;
    /**
     * @var bool has errors
     */
    protected $hasprintableerror;
    /**
     * @var string error message
     */
    protected $errormessage;
    /**
     * @var stdClass studentquiz representing the loaded studentquiz activity
     */
    protected $studentquiz;
    /**
     * @var int userid the currently loaded userid
     */
    protected $userid;
    /**
     * question bank
     */
    protected $questionbank;

    /** @var  mod_studentquiz_report */
    protected $report;


    /**
     * Constructor assuming we already have the necessary data loaded.
     * @param course course
     * @param context course module context
     * @param cm course module
     * @param stdClass studentquiz loaded studentquiz
     * @param int loaded userid
     * @param mod_studentquiz_report
     * @throws mod_studentquiz_view_exception if course module or course can't be retrieved
     */
    public function __construct($course, $context, $cm, $studentquiz, $userid, $report) {

        $this->cm = $cm;

        $this->course = $course;

        $this->context = $context;

        $this->category = question_get_default_category($this->context->id);

        $this->studentquiz = $studentquiz;

        $this->report = $report;

        $this->userid = $userid;

        // TODO: Refactor!
        $this->load_questionbank();
    }

    /**
     * Loads the question custom bank view
     */
    private function load_questionbank() {
        // Ensure capabilities are set to load question bank.
        // When there are changes to the required capabilities for different moodles, the capabilities have to be corrected
        // TODO: We should fix these with the updates and restore functions (analog fix question category etc.)
        mod_studentquiz_ensure_question_capabilities($this->context);

        $_POST['cat'] = $this->get_category_id() . ',' . $this->get_context_id();

        // Get edit question link setup
        list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars)
            = question_edit_setup('questions', '/mod/studentquiz/view.php', true);
        $pagevars['qperpage'] = optional_param('qperpage', DEFAULT_QUESTIONS_PER_PAGE, PARAM_INT);
        $pagevars['showall'] = optional_param('showall', false, PARAM_BOOL);
        $pagevars['cat'] = $this->get_category_id() . ',' . $this->get_context_id();

        $this->pageurl = new moodle_url($thispageurl);

        // Trigger notification if user got returned from the question edit form.
        // TODO: Shouldn't this be somewhere outside of load_questionbank(), as this is clearly not relevant for showing the
        // question bank?
        if (($lastchanged = optional_param('lastchanged', 0, PARAM_INT)) !== 0) {
            $this->pageurl->param('lastchanged', $lastchanged);
            // Ensure we have a studentquiz_question record.
            mod_studentquiz_ensure_studentquiz_question_record($lastchanged);
            mod_studentquiz_notify_changed($lastchanged, $this->course, $this->cm);
            redirect(new moodle_url('/mod/studentquiz/view.php', array('id' => $this->get_cm_id())));
        }

        $this->qbpagevar = $pagevars;

        $this->questionbank = new \mod_studentquiz\question\bank\studentquiz_bank_view(
            $contexts, $thispageurl, $this->course, $this->cm, $this->studentquiz, $pagevars, $this->report);
    }

    /**
     * Use this method to process actions on this view.
     */
    public function process_actions() {
        // TODO: Process actions of questionbank could redirect!
        $this->questionbank->process_actions();
    }

    /**
     * Return the users' progress information in this StudentQuiz
     * TODO: Refactor this method to actually return personal progress values!
     */
    public function get_progress_info() {
        $info = new stdClass();
        $info->total = 20;
        $info->attempted = 10;
        $info->lastattemptcorrect = 5;
        return $info;
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
     * Get StudentQuiz Activity Name
     */
    public function get_studentquiz_name() {
        return $this->cm->name;
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
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the view title
     * @return string
     */
    public function get_title() {
        return get_string('modulename', 'studentquiz') .
                ': '.  $this->get_coursemodule()->name;
    }

    /**
     * Get the question view
     * @return \mod_studentquiz\question\bank\studentquiz_bank_view mixed
     * @deprecated
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
     * @param mod_studentquiz_view $view
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
