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

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        if ($data->grade < 0) {
            // Scale found, get mapping.
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        if (empty($data->quizpracticebehaviour)) {
            $data->quizpracticebehaviour = STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR;
        }

        if (empty($data->anonymrank)) {
            $data->anonymrank = true;
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

        // Cleanup imports (empty sections) when this respective option is set.
        if (get_config('studentquiz', 'removeemptysections')) {
            // And only if a section 999 initially created from this module is present.
            $orphanedsection = $DB->get_record('course_sections', array(
                'course' => $this->get_courseid(),
                'section' => STUDENTQUIZ_OLD_ORPHANED_SECTION_NUMBER,
                'name' => STUDENTQUIZ_COURSE_SECTION_NAME
            ));
            if ($orphanedsection !== false) {
                // Then lookup the last non-empty section.
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
                    'course' => $this->get_courseid(),
                    'section' => STUDENTQUIZ_OLD_ORPHANED_SECTION_NUMBER,
                    'sectionname' => '',
                    'sectionsummary' => ''
                ));
                if ($lastnonemptysection !== false) {
                    // And remove all these useless sections.
                    $success = $DB->delete_records_select('course_sections',
                        'course = :course AND section > :nonemptysection AND section <> :oldorphanedsection',
                        array(
                            'course' => $this->get_courseid(),
                            'nonemptysection' => $lastnonemptysection->max_section,
                            'oldorphanedsection' => STUDENTQUIZ_OLD_ORPHANED_SECTION_NUMBER
                        )
                    );
                    if ($success) {
                        // And move the orphaned section to the next free section number.
                        $quizsectionid = $DB->get_field('course_sections', 'id', array(
                            'section' => STUDENTQUIZ_OLD_ORPHANED_SECTION_NUMBER,
                            'course' => $this->get_courseid()
                        ));
                        // TODO: better use: move_section_to().
                        $DB->set_field('course_sections', 'section', $lastnonemptysection->max_section + 1, array(
                            'id' => $quizsectionid
                        ));
                        // TODO: Reassign question usages of imported quiz instances to studentquiz activity.
                        // TODO: And delete quiz instances and generated section.

                    }
                }
            }
        }
    }
}
