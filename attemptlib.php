<?php

class studentquiz_practice_attempt {
    /** @var string to identify the in progress state. */
    const IN_PROGRESS = 'inprogress';
    /** @var string to identify the overdue state. */
    const FINISHED    = 'finished';
    /** @var string to identify the abandoned state. */
    const ABANDONED   = 'abandoned';
    /** @var  session object containing the practice session. */
    protected $session;
    /** @var  overview object containing the overview session. */
    protected $overview;
    /** @var question_usage_by_activity the question usage for this quiz attempt. */
    protected $questionUsageByActivity;
    /** @var array of slot information. */
    protected $slots;
    /** @var array slot => page number for this slot. */
    protected $questionPage;
    /** @var int actual question slot */
    protected $slot;
    /** @var object actual question to display */
    protected $question;


    public function __construct($session, $overview, $cm, $course) {
        $this->session = new studentquiz_practice_session($session);
        $this->overview = new studentquiz_practice_overview($overview, $cm, $course);
        $this->questionUsageByActivity = question_engine::load_questions_usage_by_activity($this->session->getQuestionUsageId());
    }

    public static function create($sessionId) {
        global $DB;
        $session = $DB->get_record('studentquiz_p_session', array('studentquiz_p_session_id' => $sessionId));
        $overview = $DB->get_record('studentquiz_p_overview', array('studentquiz_p_overview_id' => $session->studentquiz_p_overview_id));
        $cm = get_coursemodule_from_instance('studentquiz', $overview->studentquiz_id);
        $course = $DB->get_record('course', array('id' => $cm->course));

        return new studentquiz_practice_attempt($session, $overview, $cm, $course);
    }

    public function getAttemptUrl() {
        return new moodle_url('/mod/studentquiz/attempt.php', $this->getUrlSessionData());
    }
    public function getViewUrl() {
        return $this->getAttemptUrl();
    }

    public function getAbandonUrl() {
        $urlData = $this->getUrlSessionData();
        $urlData['hasAbandoned'] = 1;
        return new moodle_url('/mod/studentquiz/summary.php', $urlData);
    }

    public function getSummaryUrl() {
        return new moodle_url('/mod/studentquiz/summary.php', $this->getUrlSessionData());
    }


    private  function getUrlSessionData() {
        return array('id' => $this->session->getId());
    }

    /** @return the course*/
    public function getCourse() {
        return $this->overview->getCourse();
    }

    /** @return the course module*/
    public function getCourseModule() {
        return $this->overview->getCourseModule();
    }

    /** @return the context*/
    public function getContext() {
        return $this->overview->getContext();
    }

    /** @return the course module id*/
    public function getCMId() {
        return $this->overview->getCourseModule()->id;
    }

    /** @return int the id of the user this attempt belongs to. */
    public function getUserId() {
        return $this->overview->getUserId();
    }

    /**
     * @return bool whether this attempt has been finished (true) or is still
     *     in progress (false). Be warned that this is not just state == self::FINISHED,
     *     it also includes self::ABANDONED.
     */
    public function isFinished() {
        return $this->session->getState() == self::FINISHED || $this->session->getState() == self::ABANDONED;
    }

    public function processQuestion($slot, $submitData) {
        $this->slot = $slot;
        $this->questionUsageByActivity->process_all_actions(time(), $submitData);
        $this->questionUsageByActivity->finish_question($slot);
        $this->nextSlot();
        question_engine::save_questions_usage_by_activity($this->questionUsageByActivity);

        if($this->isLastQuestion()) {
            $this->updateAttemptPoints();
        } else {
            $this->question = $this->questionUsageByActivity->get_question($this->slot);
        }
    }

    private function nextSlot() {
        $this->slot += 1;
    }

    public function isLastQuestion() {
        $slots = $this->questionUsageByActivity->get_slots();
        return $this->slot > end($slots);
    }

    public function updateAttemptPoints() {
        global $DB;
        $totalNumberOfQuestionsRight = 0;
        $marksObtained = 0;
        foreach($this->questionUsageByActivity->get_slots() as $slot) {
            $fraction = $this->questionUsageByActivity->get_question_fraction($slot);
            $maxMarks = $this->questionUsageByActivity->get_question_max_mark($slot);
            $marksObtained += $fraction * $maxMarks;

            if ($fraction > 0) $totalNumberOfQuestionsRight += 1;
        }

        $updateSql = "UPDATE {studentquiz_p_session}
                      SET marks_obtained = ?, total_no_of_questions_right = ?
                    WHERE studentquiz_p_session_id = ?";
        $DB->execute($updateSql, array($marksObtained, $totalNumberOfQuestionsRight, $this->session->getId()));
    }

    public function processFinish() {
        question_engine::save_questions_usage_by_activity($this->questionUsageByActivity);
        $this->updateAttemptPoints();
    }

    public function reviewQuestion($slot, $submitData) {
        $this->slot = $slot;
        $this->questionUsageByActivity->process_all_actions(time(), $submitData);
        question_engine::save_questions_usage_by_activity($this->questionUsageByActivity);
        $this->question = $this->questionUsageByActivity->get_question($this->slot);
    }

    public function processFirstQuestion() {
        $slots = $this->questionUsageByActivity->get_slots();
        $this->slot = reset($slots);
        $this->question = $this->questionUsageByActivity->get_question($this->slot);
    }

    public function getSlot() {
        return $this->slot;
    }

    public function getHtmlHeadTags() {
        $headtags = '';
        $headtags .= $this->questionUsageByActivity->render_question_head_html($this->slot);
        $headtags .= question_engine::initialise_js();
        return $headtags;
    }

    public function getTitle() {
        return get_string('practice_session', 'studentquiz', format_string($this->question->name));
    }

    public function getHeading() {
        return $this->overview->getCourseFullName();
    }

    public function renderQuestion() {
        return $this->questionUsageByActivity->render_question($this->slot, new question_display_options(), $this->slot);
    }
}

class studentquiz_practice_session {
    /** @var stdClass the practice session row from the database. */
    protected $session;

    public function __construct($session) {
        $this->session = $session;
    }

    /** @return the practice session id*/
    public function getId() {
        return $this->session->studentquiz_p_session_id;
    }

    /** @return the practice session state*/
    public function getState() {
        return $this->session->state;
    }

    public function getQuestionUsageId() {
        return $this->session->question_usage_id;
    }
}


class studentquiz_practice_overview {
    /** @var stdClass the practice overview row from the database. */
    protected $overview;
    /** @var stdClass the course_module settings from the database. */
    protected $cm;
    /** @var stdClass the course settings from the database. */
    protected $course;
    /** @var context the quiz context. */
    protected $context;


    public function __construct($overview, $cm, $course) {
        $this->overview = $overview;
        $this->cm = $cm;
        $this->course = $course;
        if(!empty($cm->id)) {
            $this->context = context_module::instance($cm->id);
        }
    }

    /** @return the course*/
    public function getCourse() {
        return $this->course;
    }

    /** @return the course module*/
    public function getCourseModule() {
        return $this->cm;
    }

    /** @return the context*/
    public function getContext() {
        return $this->context;
    }

    /** @return int the id of the user this attempt belongs to. */
    public function getUserId() {
        return $this->overview->user_id;
    }

    public function getCourseFullName() {
        return $this->course->fullname;
    }
}

class moodle_studentquiz_practice_exception extends moodle_exception {
    public function __construct($attempt, $errorCode, $a = null, $link = '', $debuginfo = null) {
        if (!$link) {
            $link = $attempt->getViewUrl();
        }
        parent::__construct($errorCode, 'studentquiz', $link, $a, $debuginfo);
    }
}