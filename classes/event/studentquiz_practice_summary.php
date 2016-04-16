<?php
namespace mod_studentquiz\event;

defined('MOODLE_INTERNAL') || die();


class studentquiz_practice_summary extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'studentquiz';
    }

    public function get_description() {
        return "The user with id {$this->userid} viewed the summary for studentquiz_practice_summary id :  {$this->objectid}.";
    }

    public function get_url() {
        return new \moodle_url('/mod/studentquiz/summary.php', array('id' => $this->objectid));
    }
}