<?php
/**
 * Define all the restore steps that will be used by the restore_studentquiz_activity_structure_step
 *
 * @package   mod_studentquiz
 * @category  backup
 * @copyright 2017 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Structure step to restore one StudentQuiz activity
 *
 * @package   mod_studentquiz
 * @category  backup
 * @copyright 2017 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_studentquiz_activity_structure_step extends restore_questions_activity_structure_step {

    /**
     * @var object $currentquizattempt intermediate result for studentquiz attempts
     */
    protected $currentattempt;

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('studentquiz', '/activity/studentquiz');

        // Get Setting value if user data should be restored.
        $userinfo = $this->get_setting_value('userinfo');

        // Additional Path for attempts.
        $attempt = new restore_path_element('attempt',
            '/activity/studentquiz/attempts/attempt');
        $paths[] = $attempt;

        // Add attempt data.
        $this->add_question_usages($attempt, $paths);

        // Restore Votes.
        $vote = new restore_path_element('vote',
            '/activity/studentquiz/votes/vote');
        $paths[] = $vote;

        // Restore Comments.
        $comment = new restore_path_element('comment',
            '/activity/studentquiz/comments/comment');
        $paths[] = $comment;

        // Restore Question meta.
        $question = new restore_path_element('question_meta',
            '/activity/studentquiz/questions/question');
        $paths[] = $question;

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_studentquiz($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->coursemodule = $this->get_mappingid('course_module', $data->coursemodule);
        $oldid = $data->id;

        if (empty($data->timecreated)) $data->timecreated = time();
        if (empty($data->timemodified)) $data->timemodified = time();
        if ($data->grade < 0) $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        if (empty($data->quizpracticebehaviour) || $data->quizpracticebehaviour == STUDENTQUIZ_BEHAVIOUR) {
            $data->quizpracticebehaviour = STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR;
        }
        if (empty($data->anonymrank)) $data->anonymrank = true;
        if (empty($data->questionquantifier)) $data->questionquantifier = get_config('studentquiz', 'addquestion');
        if (empty($data->approvedquantifier)) $data->approvedquantifier = get_config('studentquiz', 'approved');
        if (empty($data->votequantifier)) $data->votequantifier = get_config('studentquiz', 'vote');
        if (empty($data->correctanswerquantifier)) $data->correctanswerquantifier = get_config('studentquiz', 'correctanswered');
        if (empty($data->incorrectanswerquantifier)) $data->incorrectanswerquantifier = get_config('studentquiz', 'incorrectanswered');
        if (empty($data->allowedqtypes)) $data->allowedqtypes = 'ALL';

        // Create the StudentQuiz instance.
        $newitemid = $DB->insert_record('studentquiz', $data);
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('studentquiz', $oldid, $newitemid, true); // Has related files.
    }

    protected function process_attempt($data) {
        $data = (object)$data;

        $data->studentquizid = $this->get_new_parentid('studentquiz');
        $data->userid = $this->get_mappingid('user', $data->userid);

        // The data is actually inserted into the database later in inform_new_usage_id.
        $this->currentattempt = clone($data);
    }

    protected function process_vote($data) {
        global $DB;
        $data = (object) $data;
        $data->questionid = $this->get_mappingid('question', $data->questionid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $newitemid = $DB->insert_record('studentquiz_vote', $data);
    }

    protected function process_comment($data) {
        global $DB;
        $data = (object) $data;
        $data->questionid = $this->get_mappingid('question', $data->questionid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('studentquiz_comment', $data);
    }

    protected function process_question_meta($data) {
        global $DB;
        $data = (object) $data;
        $data->questionid = $this->get_mappingid('question', $data->questionid);
        $DB->insert_record('studentquiz_question', $data);
    }

    /**
     * Post-execution actions per activity
     */
    protected function after_execute() {
        // Add StudentQuiz related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_studentquiz', 'intro', null);
    }


    protected function inform_new_usage_id($newusageid) {
        global $DB;

        $data = $this->currentattempt;

        $oldid = $data->id;
        $data->questionusageid = $newusageid;

        $data->categoryid = $this->get_mappingid('question_category', $data->categoryid);

        $newitemid = $DB->insert_record('studentquiz_attempt', $data);

        // Save studentuquiz_attempt->id mapping, because logs use it.
        $this->set_mapping('studentquiz_attempt', $oldid, $newitemid, false);
    }

    /**
     * Post-execution actions per whole restore
     */
    protected function after_restore() {
        global $DB;

        // Import old Core Quiz Data (question attempts) to studentquiz.
        // This is the case, when the orphaned section can be found.
        $orphanedsection = $DB->get_record('course_sections', array(
            'course' => $this->get_courseid(),
            'name' => STUDENTQUIZ_COURSE_SECTION_NAME
        ));

        if ($orphanedsection !== false) {

            mod_studentquiz_migrate_old_quiz_usage($this->get_courseid());

            // Then remove empty sections if it's empty, if the admin allows us.
            if (get_config('studentquiz', 'removeemptysections')) {
                // So lookup the last non-empty section first.
                $lastnonemptysection = $DB->get_record_sql(
                    'SELECT MAX(s.section) as max_section' .
                    '   FROM {course_sections} s' .
                    '   left join {course_modules} m on s.id = m.section ' .
                    '   where s.course = :course' .
                    '   and s.section <> :section' .
                    '   and (' .
                    '       m.id is not NULL' .
                    '       or s.name <> :sectionname' .
                    '       or s.summary <> :sectionsummary' .
                    '   )', array(
                    'section' => STUDENTQUIZ_OLD_ORPHANED_SECTION_NUMBER,
                    'course' => $this->get_courseid(),
                    'sectionname' => '',
                    'sectionsummary' => ''
                ));
                if ($lastnonemptysection !== false) {
                    // And remove all these useless sections.
                    $success = $DB->delete_records_select('course_sections',
                        'course = :course AND section > :nonemptysection',
                        array(
                            'course' => $this->get_courseid(),
                            'nonemptysection' => $lastnonemptysection->max_section
                        )
                    );
                }
            }
        }
    }
}
