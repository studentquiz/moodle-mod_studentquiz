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

        // Restore Rate.
        $rate = new restore_path_element('rate',
            '/activity/studentquiz/rates/rate');
        $paths[] = $rate;

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

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }
        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }
        if ($data->grade < 0) {
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }
        if (empty($data->quizpracticebehaviour) || $data->quizpracticebehaviour == STUDENTQUIZ_BEHAVIOUR) {
            $data->quizpracticebehaviour = STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR;
        }
        if (empty($data->anonymrank)) {
            $data->anonymrank = true;
        }
        if (empty($data->questionquantifier)) {
            $data->questionquantifier = get_config('studentquiz', 'addquestion');
        }
        if (empty($data->approvedquantifier)) {
            $data->approvedquantifier = get_config('studentquiz', 'approved');
        }
        if (empty($data->ratequantifier)) {
            $data->ratequantifier = get_config('studentquiz', 'rate');
        }
        if (empty($data->correctanswerquantifier)) {
            $data->correctanswerquantifier = get_config('studentquiz', 'correctanswered');
        }
        if (empty($data->incorrectanswerquantifier)) {
            $data->incorrectanswerquantifier = get_config('studentquiz', 'incorrectanswered');
        }
        if (empty($data->allowedqtypes)) {
            $data->allowedqtypes = 'ALL';
        }
        if (empty($data->aggregated)) {
            $data->aggregated = "1";
        }
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

    protected function process_rate($data) {
        global $DB;
        $data = (object) $data;
        $data->questionid = $this->get_mappingid('question', $data->questionid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $newitemid = $DB->insert_record('studentquiz_rate', $data);
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
     * Post-execution actions per activity after whole restore
     */
    protected function after_restore() {
        // Fix wrong parent in question categories if applicable
        mod_studentquiz_fix_wrong_parent_in_question_categories();
        // Migrate old quiz usage if needed (the function does the checking).
        mod_studentquiz_migrate_old_quiz_usage($this->get_courseid());
    }
}
