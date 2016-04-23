<?php

require_once($CFG->dirroot . '/question/editlib.php');
require_once(dirname(__FILE__) . '/locallib.php');

class studentquiz_view {
    /** @var string default quiz behaviour */
    const STUDENTQUIZ_BEHAVIOUR = 'studentquiz';
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
    /** @var  int the quiz session id */
    protected $quizSessionId;
    /** @var  bool has question ids found */
    protected $hasQuestionIds;
    /** @var string page url */
    protected $pageUrl;
    /** @var questionbank class */
    protected $questionBank;
    /** @var array pagevars questionbank */
    protected $qBpageVar;


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

    public function startQuiz($submitData) {
        $ids = quiz_practice_get_question_ids($submitData);

        if($ids) {
            $this->hasQuestionIds = true;
            $this->quizSessionId = quiz_practice_create_quiz_helper($this->getQuizPracticeSessionObject(), $this->context, $ids);
        } else {
            $this->hasQuestionIds = false;
        }
    }

    public function startFilteredQuiz($ids) {
        $tmp = explode(',', $ids);
        $ids = array();
        foreach($tmp as $id) {
            $ids[$id] = 1;
        }
        $this->startQuiz($ids);
    }

    public function retryQuiz($sessionId) {
        global $DB;
        if (!$session = $DB->get_record('studentquiz_p_session', array('studentquiz_p_session_id' => $sessionId), 'question_usage_id, studentquiz_p_overview_id')) {
            throw new moodle_studentquiz_view_exception($this, 'sessionmissconf');
        }
        $this->quizSessionId = quiz_practice_retry_quiz($this->getQuizPracticeSessionObject(), $this->context, $session);
        $this->hasQuestionIds = true;
    }

    private function getQuizPracticeSessionObject() {
        $data = new stdClass();
        $data->behaviour = studentquiz_view::STUDENTQUIZ_BEHAVIOUR;
        $data->instanceid = $this->cm->instance;
        $data->categoryid = $this->category->id;
        return $data;
    }

    public function createQuestionBank() {
        $_GET['cmid'] = $this->getCMId();
        $_POST['cat'] = $this->getCategoryId() . ',' . $this->getContextId();

        list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
            question_edit_setup('questions', '/mod/studentquiz/view.php', true, false);

        $this->pageUrl = new moodle_url($thispageurl);
        if (($lastchanged = optional_param('lastchanged', 0, PARAM_INT)) !== 0) {
            $this->pageUrl->param('lastchanged', $lastchanged);
        }
        $this->qBpageVar = $pagevars;

        $this->questionBank = new \mod_studentquiz\question\bank\studentquiz_bank_view($contexts, $thispageurl, $this->course, $this->cm);
        $this->questionBank->process_actions();
    }

    public function hasQuestionIds(){
        return $this->hasQuestionIds;
    }

    public function getQbPageVar() {
        return $this->qBpageVar;
    }

    public function getPageUrl() {
        return new moodle_url($this->pageUrl, $this->getUrlViewData());
    }

    public function getViewUrl() {
        return new moodle_url('/mod/studentquiz/view.php', $this->getUrlViewData());
    }
    public function getAttemptUrl() {
        return new moodle_url(new moodle_url('/mod/studentquiz/attempt.php', array('id' => $this->quizSessionId, studentquiz_view::STUDENTQUIZ_STARTQUIZ => 1)));
    }

    public function getUrlViewData() {
        return array('cmid' => $this->cm->id);
    }

    public function getCourse() {
        return $this->course;
    }

    public function getCourseModule() {
        return $this->cm;
    }

    public function getCMId() {
        return $this->cm->id;
    }
    public function getQuizSessionId() {
        return $this->quizSessionId;
    }

    public function getCategoryId() {
        return $this->category->id;
    }

    public function getContextId() {
        return $this->context->id;
    }

    public function getTitle() {
        return get_string('editquestions', 'question');
    }

    public function getQuestionBank() {
        return $this->questionBank;
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