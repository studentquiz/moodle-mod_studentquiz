<?php

require_once($CFG->dirroot . '/question/editlib.php');
require_once(dirname(__FILE__) . '/locallib.php');

class studentquiz_view {
    /** @var string submit data for question attempt */
    const STUDENTQUIZ_STARTQUIZ = 'startquiz';

    /** @var stdClass the course_module settings from the database. */
    protected $cm;
    /** @var stdClass the course settings from the database. */
    protected $course;
    /** @var context the quiz context. */
    protected $context;
    /** @var category the default category */
    protected $category;
    /** @var  int the quiz practice session id */
    protected $psessionid;
    /** @var  bool has question ids found */
    protected $hasquestionids;
    /** @var string page url */
    protected $pageurl;
    /** @var questionbank class */
    protected $questionbank;
    /** @var array pagevars questionbank */
    protected $qbpagevar;


    public function __construct($cmid) {
        global $DB;
        if (!$this->cm = get_coursemodule_from_id('studentquiz', $cmid)) {
            throw new moodle_studentquiz_view_exception($this, 'invalidcoursemodule');
        }
        if (!$this->course = $DB->get_record('course', array('id' => $this->cm->course))) {
            throw new moodle_studentquiz_view_exception($this, 'coursemisconf');
        }

        $this->context = context_module::instance($this->cm->id);
        $this->category = question_get_default_category($this->context->id);
    }

    public function start_quiz($submitdata) {
        $ids = quiz_practice_get_question_ids($submitdata);

        if($ids) {
            $this->hasquestionids = true;
            $this->psessionid = quiz_practice_create_quiz_helper($this->get_quiz_practice_session(), $this->context, $ids);
        } else {
            $this->hasquestionids = false;
        }
    }

    public function start_filtered_quiz($ids) {
        $tmp = explode(',', $ids);
        $ids = array();
        foreach($tmp as $id) {
            $ids[$id] = 1;
        }
        $this->start_quiz($ids);
    }

    public function retry_quiz($sessionid) {
        global $DB;
        if (!$session = $DB->get_record('studentquiz_psession', array('id' => $sessionid), 'questionusageid, studentquizpoverviewid')) {
            throw new moodle_studentquiz_view_exception($this, 'sessionmissconf');
        }
        $this->psessionid = quiz_practice_retry_quiz($this->get_quiz_practice_session(), $this->context, $session);
        $this->hasquestionids = true;
    }

    private function get_quiz_practice_session() {
        $data = new stdClass();
        $data->behaviour = get_current_behaviour($this->get_coursemodule());
        $data->instanceid = $this->cm->instance;
        $data->categoryid = $this->category->id;
        return $data;
    }

    public function create_questionbank() {
        $_GET['cmid'] = $this->get_cm_id();
        $_POST['cat'] = $this->get_category_id() . ',' . $this->get_context_id();

        list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
            question_edit_setup('questions', '/mod/studentquiz/view.php', true, false);

        $this->pageurl = new moodle_url($thispageurl);
        if (($lastchanged = optional_param('lastchanged', 0, PARAM_INT)) !== 0) {
            $this->pageurl->param('lastchanged', $lastchanged);
        }
        $this->qbpagevar = $pagevars;

        $this->questionbank = new \mod_studentquiz\question\bank\studentquiz_bank_view($contexts, $thispageurl, $this->course, $this->cm);
        $this->questionbank->process_actions();
    }

    public function has_questiond_ids(){
        return $this->hasquestionids;
    }

    public function get_qb_pagevar() {
        return $this->qbpagevar;
    }

    public function get_pageurl() {
        return new moodle_url($this->pageurl, $this->get_urlview_data());
    }

    public function get_viewurl() {
        return new moodle_url('/mod/studentquiz/view.php', $this->get_urlview_data());
    }
    public function get_attempturl() {
        return new moodle_url(new moodle_url('/mod/studentquiz/attempt.php', array('id' => $this->psessionid, studentquiz_view::STUDENTQUIZ_STARTQUIZ => 1)));
    }

    public function get_urlview_data() {
        return array('cmid' => $this->cm->id);
    }

    public function get_course() {
        return $this->course;
    }

    public function get_coursemodule() {
        return $this->cm;
    }

    public function get_cm_id() {
        return $this->cm->id;
    }
    public function get_psession_id() {
        return $this->psessionid;
    }

    public function get_category_id() {
        return $this->category->id;
    }

    public function get_context_id() {
        return $this->context->id;
    }

    public function get_title() {
        return get_string('editquestions', 'question');
    }

    public function get_questionbank() {
        return $this->questionbank;
    }
}

class moodle_studentquiz_view_exception extends moodle_exception {
    public function __construct($view, $errorCode, $a = null, $link = '', $debuginfo = null) {
        if (!$link) {
            $link = $view->getViewUrl();
        }
        parent::__construct($errorCode, 'studentquiz', $link, $a, $debuginfo);
    }
}