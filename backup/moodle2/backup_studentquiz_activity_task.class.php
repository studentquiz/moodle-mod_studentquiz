<?php
/**
 * Defines backup_studentquiz_activity_task class
 *
 * @package   mod_studentquiz
 * @category  backup
 * @copyright 2015 Your Name <your@email.adress>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/studentquiz/backup/moodle2/backup_studentquiz_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the StudentQuiz instance
 *
 * @package   mod_studentquiz
 * @category  backup
 * @copyright 2015 Your Name <your@email.adress>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_studentquiz_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the studentquiz.xml file
     */
    protected function define_my_steps() {
        // Backup studentquiz tables.
        $this->add_step(new backup_studentquiz_activity_structure_step('studentquiz_structure', 'studentquiz.xml'));

        // Backup question categories.
        $this->add_step(new backup_calculate_question_categories('activity_question_categories'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of studentquizs.
        $search = '/('.$base.'\/mod\/studentquiz\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@STUDENTQUIZINDEX*$2@$', $content);

        // Link to StudentQuiz view by moduleid.
        $search = '/('.$base.'\/mod\/studentquiz\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@STUDENTQUIZVIEWBYID*$2@$', $content);

        return $content;
    }
}
