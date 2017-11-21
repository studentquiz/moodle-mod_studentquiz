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
        $data->coursemodule = 0; // Will be updated later.

        $oldid = $data->id;

        if (empty($data->timecreated)) $data->timecreated = time();
        if (empty($data->timemodified)) $data->timemodified = time();
        if ($data->grade < 0) $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        if (empty($data->quizpracticebehaviour)) $data->quizpracticebehaviour = STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR;
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

    /**
     * Post-execution actions per activity
     */
    protected function after_execute() {
        global $DB;

        // Add StudentQuiz related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_studentquiz', 'intro', null);

        // Update the coursemodule id on the studentquiz table.
        $courseid = $this->get_courseid();
        $moduleid = $DB->get_field('modules', 'id', array('name' => 'studentquiz'));

        $cms = $DB->get_records('course_modules', array('course' => $courseid, 'module' => $moduleid));

        foreach ($cms as $cm) {
            $studentquiz = $DB->get_record('studentquiz', array('id' => $cm->instance));
            $studentquiz->coursemodule = $cm->id;
            $DB->update_record('studentquiz', $studentquiz);
        }
    }


    protected function inform_new_usage_id($newusageid) {
        global $DB;

        $data = $this->currentattempt;

        $oldid = $data->id;
        $data->questionusageid = $newusageid;

        // TODO: Update Category id?

        //

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
            $studentquizzes = $DB->get_records_sql('
                select s.id, s.name, cm.id as cmid, c.id as contextid
                from {studentquiz} s
                inner join {course_modules} cm on s.id = cm.instance
                inner join {context} c on cm.id = c.instanceid
                inner join {modules} m on cm.module = m.id
                where m.name = :modulename
                and cm.course = :course
            ', array(
                'modulename' => 'studentquiz',
                'course' => $this->get_courseid()
            ));

            // TODO: probably we can use the $newitemid from above.
            foreach ($studentquizzes as $studentquiz) {
                $oldquizzes = $DB->get_records_sql('
                    select q.id, cm.id as cmid, c.id as contextid, qu.id as qusageid
                    from {quiz} q
                    inner join {course_modules} cm on q.id = cm.instance
                    inner join {context} c on cm.id = c.instanceid
                    inner join {modules} m on cm.module = m.id
                    inner join {question_usages} qu on c.id = qu.contextid
                    where m.name = :modulename
                    and cm.course = :course
                    and q.name = :name
                ', array(
                    'modulename' => 'quiz',
                    'course' => $this->get_courseid(),
                    'name' => $studentquiz->name
                ));

                // For each old quiz we need to move the question usage.
                foreach ($oldquizzes as $oldquiz) {
                    $DB->set_field("question_usages", "component", 'mod_studentquiz', array("id" => $oldquiz->qusageid));
                    $DB->set_field("question_usages", "contextid", $studentquiz->contextid, array("id" => $oldquiz->qusageid));
                    $DB->set_field("question_usages", "preferredbehaviour", STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR, array("id" => $oldquiz->qusageid));
                    $DB->set_field("question_attempts", "behaviour", STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR, array("questionusageid" => $oldquiz->qusageid));

                    // Now we need each user as own attempt.
                    $userids = $DB->get_fieldset_sql('
                        select distinct qas.userid
                        from {question_attempt_steps} qas
                        inner join {question_attempts} qa on qas.questionattemptid = qa.id
                        where qa.questionusageid = :qusageid
                    ', array(
                        'qusageid' => $oldquiz->qusageid
                    ));
                    foreach ($userids as $userid) {
                        $DB->insert_record("studentquiz_attempt", array(
                            "studentquizid" => $studentquiz->id,
                            "userid" => $userid,
                            "questionusageid" => $oldquiz->qusageid,
                            "categoryid" => 0, // TODO? how to find out?
                        ));
                    }
                    // So that quiz doesn't remove the question usages.
                    $DB->set_field("quiz_attempts", "uniqueid", 0, array('quiz' => $oldquiz->id));
                    quiz_delete_instance($oldquiz->id);
                }
            }

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
