<?php
/**
 * The mod_studentquiz comment created event.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\event;
defined('MOODLE_INTERNAL') || die();

class comment_created extends \core\event\comment_created {

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/studentquiz/attempt.php', null);
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' added a comment with id '$this->objectid' on the page with id " .
            "'{$this->other['itemid']}' for the studentquiz with course module id '$this->contextinstanceid'.";
    }
}
