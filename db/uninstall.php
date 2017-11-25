<?php
/**
 * Provides code to be executed during the module uninstallation
 *
 * @see uninstall_plugin()
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/lib.php');
require_once(__DIR__ . '/../lib.php');

/**
 * Custom uninstallation procedure
 */
function xmldb_studentquiz_uninstall() {
    global $DB;

    $studentquizzes = $DB->get_records('studentquiz');
    foreach ($studentquizzes as $studentquiz) {
        course_delete_module($studentquiz->coursemodule);
        studentquiz_delete_instance($studentquiz->id);
    }
}
