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
    protected $questionusagebyactivity;
    /** @var array of slot information. */
    protected $slots;
    /** @var array slot => page number for this slot. */
    protected $questionpage;
    /** @var int actual question slot */
    protected $slot;
    /** @var object actual question to display */
    protected $question;


    public function __construct($session, $overview, $cm, $course) {
        $this->session = new studentquiz_practice_session($session);
        $this->overview = new studentquiz_practice_overview($overview, $cm, $course);
        $this->questionusagebyactivity = question_engine::load_questions_usage_by_activity($this->session->get_question_usage_id());
    }

    public static function create($psessionid) {
        global $DB;
        $session = $DB->get_record('studentquiz_psession', array('id' => $psessionid));
        $overview = $DB->get_record('studentquiz_poverview', array('id' => $session->studentquizpoverviewid));
        $cm = get_coursemodule_from_instance('studentquiz', $overview->studentquizid);
        $course = $DB->get_record('course', array('id' => $cm->course));

        return new studentquiz_practice_attempt($session, $overview, $cm, $course);
    }

    public function get_attempturl() {
        return new moodle_url('/mod/studentquiz/attempt.php', $this->get_url_sessiondata());
    }
    public function get_viewurl() {
        return $this->get_attempturl();
    }

    public function get_abandonurl() {
        $urldata = $this->get_url_sessiondata();
        $urldata['hasAbandoned'] = 1;
        return new moodle_url('/mod/studentquiz/summary.php', $urldata);
    }

    public function get_summaryurl() {
        return new moodle_url('/mod/studentquiz/summary.php', $this->get_url_sessiondata());
    }


    private  function get_url_sessiondata() {
        return array('id' => $this->session->get_id());
    }

    /** @return the course*/
    public function get_course() {
        return $this->overview->get_course();
    }

    /** @return the course module*/
    public function get_coursemodule() {
        return $this->overview->get_coursemodule();
    }

    /** @return the context*/
    public function get_context() {
        return $this->overview->get_context();
    }

    /** @return the course module id*/
    public function get_cm_id() {
        return $this->overview->get_coursemodule()->id;
    }

    /** @return int the id of the user this attempt belongs to. */
    public function get_user_id() {
        return $this->overview->get_user_id();
    }

    /**
     * @return bool whether this attempt has been finished (true) or is still
     *     in progress (false). Be warned that this is not just state == self::FINISHED,
     *     it also includes self::ABANDONED.
     */
    public function is_finished() {
        return $this->session->get_state() == self::FINISHED || $this->session->get_state() == self::ABANDONED;
    }

    public function process_question($slot, $submitdata) {
        $this->slot = $slot;
        $this->questionusagebyactivity->process_all_actions(time(), $submitdata);
        $this->questionusagebyactivity->finish_question($slot);
        $this->next_slot();
        question_engine::save_questions_usage_by_activity($this->questionusagebyactivity);

        if($this->is_last_question()) {
            $this->update_attempt_points();
        } else {
            $this->question = $this->questionusagebyactivity->get_question($this->slot);
        }
    }

    private function next_slot() {
        $this->slot += 1;
    }

    public function is_last_question() {
        $slots = $this->questionusagebyactivity->get_slots();
        return $this->slot > end($slots);
    }

    public function update_attempt_points() {
        global $DB;
        $totalnumberofquestionsright = 0;
        $marksobtained = 0;
        foreach($this->questionusagebyactivity->get_slots() as $slot) {
            $fraction = $this->questionusagebyactivity->get_question_fraction($slot);
            $maxMarks = $this->questionusagebyactivity->get_question_max_mark($slot);
            $marksobtained += $fraction * $maxMarks;

            if ($fraction > 0) $totalnumberofquestionsright += 1;
        }

        $stdClass = new stdClass();
        $stdClass->id = $this->session->get_id();
        $stdClass->marksobtained = $marksobtained;
        $stdClass->totalnoofquestionsright = $totalnumberofquestionsright;

        $DB->update_record('studentquiz_psession', $stdClass);
    }

    public function process_finish() {
        question_engine::save_questions_usage_by_activity($this->questionusagebyactivity);
        $this->update_attempt_points();
    }

    public function review_question($slot, $submitdata) {
        $this->slot = $slot;
        $this->questionusagebyactivity->process_all_actions(time(), $submitdata);
        question_engine::save_questions_usage_by_activity($this->questionusagebyactivity);
        $this->question = $this->questionusagebyactivity->get_question($this->slot);
    }

    public function process_first_question() {
        $slots = $this->questionusagebyactivity->get_slots();
        $this->slot = reset($slots);
        $this->question = $this->questionusagebyactivity->get_question($this->slot);
    }

    public function get_slot() {
        return $this->slot;
    }

    public function get_html_head_tags() {
        $headtags = '';
        $headtags .= $this->questionusagebyactivity->render_question_head_html($this->slot);
        $headtags .= question_engine::initialise_js();
        return $headtags;
    }

    public function get_title() {
        return get_string('practice_session', 'studentquiz', format_string($this->question->name));
    }

    public function get_heading() {
        return $this->overview->get_course_fullname();
    }

    public function render_question() {
        return $this->questionusagebyactivity->render_question($this->slot, new question_display_options(), $this->slot);
    }
}

class studentquiz_practice_session {
    /** @var stdClass the practice session row from the database. */
    protected $session;

    public function __construct($session) {
        $this->session = $session;
    }

    /** @return the practice session id*/
    public function get_id() {
        return $this->session->id;
    }

    /** @return the practice session state*/
    public function get_state() {
        return $this->session->state;
    }

    public function get_question_usage_id() {
        return $this->session->questionusageid;
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
    public function get_course() {
        return $this->course;
    }

    /** @return the course module*/
    public function get_coursemodule() {
        return $this->cm;
    }

    /** @return the context*/
    public function get_context() {
        return $this->context;
    }

    /** @return int the id of the user this attempt belongs to. */
    public function get_user_id() {
        return $this->overview->userid;
    }

    public function get_course_fullname() {
        return $this->course->fullname;
    }
}

class moodle_studentquiz_practice_exception extends moodle_exception {
    public function __construct($attempt, $errorCode, $a = null, $link = '', $debuginfo = null) {
        if (!$link) {
            $link = $attempt->get_viewurl();
        }
        parent::__construct($errorCode, 'studentquiz', $link, $a, $debuginfo);
    }
}