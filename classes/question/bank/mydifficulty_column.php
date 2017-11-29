<?php
/**
 * Representing my attempts column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent my difficulty column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mydifficulty_column extends \core_question\bank\column_base {
        /**
     * Get column name
     * @return string column name
     */
    public function get_name() {
        return 'mydifficulty';
    }

    /**
     * Get title
     * @return string column title
     */
    protected function get_title() {
        return get_string('mydifficulty_column_name', 'studentquiz');
    }

    protected function display_content($question, $rowclasses) {
        echo '';
    }
}
