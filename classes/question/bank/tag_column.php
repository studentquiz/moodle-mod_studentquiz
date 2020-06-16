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
 * Representing tag column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/studentquiz/classes/local/db.php');
use mod_studentquiz\local\db;

/**
 * Represent tag column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tag_column extends \core_question\bank\column_base {

    /**
     * @var bool
     */
    protected $tagfilteractive;

    /**
     * @var stdClass
     */
    protected $renderer;

    /**
     * Initialise Parameters for join
     */
    protected function init() {

        global $DB, $PAGE;

        // Build context and categoryid here for use later.
        $context = $this->qbank->get_most_specific_context();
        $this->categoryid = question_get_default_category($context->id)->id;
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
    }

    /**
     * Get column name
     * @return string
     */
    public function get_name() {
        return 'tags';
    }

    /**
     * Get column title
     * @return string translated title
     */
    protected function get_title() {
        return get_string('tags', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        $output = $this->renderer->render_tag_column($question, $rowclasses);
        echo $output;
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_extra_joins() {
        global $DB;

        // Concatenated string always containing a leading and ending ',' so a potential search for an item is always in
        // between elements.
        $concatenated = $DB->sql_concat_join("','", array("''", db::group_concat('t.rawname'), "''"));

        return array('tags' => "LEFT JOIN (
                                            SELECT " . $concatenated . " AS tagarray, ti.itemid as questionid
                                              FROM {tag_instance} ti
                                              JOIN {tag} t ON ti.tagid = t.id
                                             WHERE ti.itemtype = 'question'
                                          GROUP BY ti.itemid
                                          ) tags ON tags.questionid = q.id");
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('tags.tagarray');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return 'tags.tagarray';
    }
}
