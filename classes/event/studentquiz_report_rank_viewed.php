<?php
/**
 * The mod_studentquiz report rank viewed event
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_studentquiz report rank viewed event
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_report_rank_viewed extends \core\event\base {

    /**
     * Init event
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'studentquiz';
    }

    /**
     * Get description
     * @return string get description
     */
    public function get_description() {
        return "On course: {$this->courseid} studentquizid: {$this->objectid} was viewed";
    }

    /**
     * Get url
     * @return \moodle_url view.php url
     */
    public function get_url() {
        return new \moodle_url('/mod/studentquiz/reportrank.php', array('id' => $this->objectid));
    }

}
