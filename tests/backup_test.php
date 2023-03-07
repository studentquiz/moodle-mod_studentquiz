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

namespace mod_studentquiz;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Unit tests for backup/restore process in StudentQuiz.
 *
 * @package mod_studentquiz
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_test extends \restore_date_testcase {

    /**
     * Load required libraries
     */
    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/backup/util/includes/restore_includes.php");
    }

    /**
     * Test backup/restore process in studentquiz.
     *
     * @covers \restore_studentquiz_activity_task
     */
    public function test_backup_restore_course_with_sq() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $course = $this->getDataGenerator()->create_course();

        $activity = $this->getDataGenerator()->create_module('studentquiz', [
            'course' => $course->id,
            'anonymrank' => true,
            'forcecommenting' => 1,
            'opensubmissionfrom' => 1676912400,
            'closesubmissionfrom' => 1677085200,
            'openansweringfrom' => 1677171600,
            'closeansweringfrom' => 1677344400,
            'publishnewquestion' => 1
        ]);
        $context = \context_module::instance($activity->cmid);
        $studentquiz = mod_studentquiz_load_studentquiz($activity->cmid, $context->id);
        $questionname = 'Test question to be copied';
        $questiongenerator->create_question('essay', null, ['name' => $questionname, 'category' => $studentquiz->categoryid]);

        $newcourseid = $this->backup_and_restore($course);
        $this->assertEquals(2, $DB->count_records('question', ['name' => $questionname]));
        // Delete the old course.
        delete_course($course, false);

        $newstudentquiz = $DB->get_record('studentquiz', ['course' => $newcourseid]);

        $this->assertEquals(1, $DB->count_records('question', ['name' => $questionname]));
        $this->assertEquals(1, $newstudentquiz->anonymrank);
        $this->assertEquals(1676912400, $newstudentquiz->opensubmissionfrom);
        $this->assertEquals(1677085200, $newstudentquiz->closesubmissionfrom);
        $this->assertEquals(1677171600, $newstudentquiz->openansweringfrom);
        $this->assertEquals(1677344400, $newstudentquiz->closeansweringfrom);
        $this->assertEquals(1, $newstudentquiz->publishnewquestion);
    }

    /**
     * Restore the studentquiz backup file in the fixture folder base on filemame.
     *
     * @param string $filename Backup file name.
     * @param string $coursefullname course full name.
     * @param string $courseshortname course short name.
     * @return mixed bool|stdClass return the studentquiz object restored.
     */
    protected function restore_sq_backup_file_to_course_shortname(string $filename, string $coursefullname,
        string $courseshortname) {
        global $DB, $USER;
        $testfixture = __DIR__ . '/fixtures/' . $filename;

        // Extract our test fixture, ready to be restored.
        $backuptempdir = 'studentquiz';
        $backuppath = make_backup_temp_directory($backuptempdir);
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname($testfixture, $backuppath);
        // Do the restore to new course with default settings.
        $categoryid = $DB->get_field('course_categories', 'MIN(id)', []);
        $courseid = \restore_dbops::create_new_course($coursefullname, $courseshortname, $categoryid);

        $controller = new \restore_controller($backuptempdir, $courseid, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id,
            \backup::TARGET_NEW_COURSE);

        $controller->execute_precheck();
        $controller->execute_plan();
        $controller->destroy();

        return $DB->get_record('studentquiz', []);
    }

    /**
     * Data provider for test_old_sq_backup_data().
     *
     * @coversNothing
     * @return array
     */
    public function old_sq_backup_data_provider(): array {

        return [
            'aggregated before' => [
                'filename' => 'backup-moodle2-aggregated-before.mbz',
                'coursefullname' => 'aggregated before',
                'courseshortname' => 'ab',
                'correct_answered_points' => [1, 2],
                'total_points' => [32, 23],
                'questionname' => 'first',
            ],
            'during 0' => [
               'filename' => 'backup-moodle2-aggregated-during-0.mbz',
                'coursefullname' => 'during 0',
                'courseshortname' => 'd0',
                'correct_answered_points' => [2, 1],
                'total_points' => [28, 20],
                'questionname' => 'q1',
            ],
            'during 1' => [
                'filename' => 'backup-moodle2-aggregated-during-1.mbz',
                'coursefullname' => 'during 1',
                'courseshortname' => 'd1',
                'correct_answered_points' => [2, 1],
                'total_points' => [28, 20],
                'questionname' => 'q2',
            ],
            'Missing state' => [
                'filename' => 'backup-moodle2-course-two-moodle_35_sq404_missingstate.mbz',
                'coursefullname' => 'Course Two',
                'courseshortname' => 'C2',
                'correct_answered_points' => [0],
                'total_points' => [10],
                'questionname' => 'False is correct',
            ],
            'Correct state' => [
                'filename' => 'backup-moodle2-course-two-moodle_35_sq404_correctstate.mbz',
                'coursefullname' => 'Course Two',
                'courseshortname' => 'C2',
                'correct_answered_points' => [0],
                'total_points' => [15],
                'questionname' => 'False is correct',
            ],
            'SQ in M311' => [
                'filename' => 'backup-moodle2-course-with-studentquiz-m311.mbz',
                'coursefullname' => 'Course Three',
                'courseshortname' => 'C3',
                'correct_answered_points' => [0, 0],
                'total_points' => [0, 0],
                'questionname' => 'Test T/F Question',
            ],
            'SQ in M311 with question data' => [
                'filename' => 'backup-moodle2-course-2-311-with-questiondata.mbz',
                'coursefullname' => 'Course Two',
                'courseshortname' => 'C2',
                'correct_answered_points' => [2, 0, 0],
                'total_points' => [31, 21, 0],
                'questionname' => 'T/F Student',
            ],
            'SQ 4.0' => [
                'filename' => 'backup-moodle2-course-with-studentquiz-m400.mbz',
                'coursefullname' => 'Course Four',
                'courseshortname' => 'C4',
                'correct_answered_points' => [0, 0],
                'total_points' => [0, 0],
                'questionname' => 'Question T/F for 4.0',
            ]
        ];
    }

    /**
     * Test old sq backup data from earlier version.
     *
     * @covers \restore_studentquiz_activity_task
     * @dataProvider old_sq_backup_data_provider
     * @param string $filename file name of the backup file.
     * @param string $coursefullname course full name.
     * @param string $courseshortname course short name.
     * @param array $correctanswerpoints correct answer point for each user in the ranking table.
     * @param array $totalpoints total point for each users in the ranking table.
     * @param string $questionname question name after we restore.
     */
    public function test_old_sq_backup_data(string $filename, string $coursefullname, string $courseshortname,
        array $correctanswerpoints, array $totalpoints, string $questionname): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        // Check question with question name is not exist before restore.
        $this->assertFalse($DB->record_exists('question', ['name' => $questionname]));
        $sq = $this->restore_sq_backup_file_to_course_shortname($filename, $coursefullname, $courseshortname);
        // Check question with question name exist after restore.
        $this->assertTrue($DB->record_exists('question', ['name' => $questionname]));

        $report = new \mod_studentquiz_report($sq->coursemodule);
        $count = 0;
        // Check ranking page.
        foreach ($report->get_user_ranking_table() as $ur) {
            $this->assertEquals($totalpoints[$count], $ur->points);
            $this->assertEquals($correctanswerpoints[$count], $ur->last_attempt_correct);
            $count++;
        }
    }
}
