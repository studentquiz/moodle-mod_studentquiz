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
 * Representing comments column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

use mod_studentquiz\utils;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent comments column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_column extends studentquiz_column_base {

    /**
     * Renderer
     * @var stdClass
     */
    protected $renderer;

    /**
     * Initialise
     */
    public function init() {
        global $PAGE;
        $this->renderer = $PAGE->get_renderer('mod_studentquiz');
    }

    /**
     * Get column name
     * @return string column name
     */
    public function get_name() {
        return 'comment';
    }

    /**
     * Get title to return the very short column name
     * @return string column title
     */
    protected function get_title() {
        return get_string('comment_column_name', 'studentquiz');
    }

    /**
     * Get title tip to return the full column name
     * @return string column title
     */
    protected function get_title_tip() {
        return get_string('comment_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        $output = $this->renderer->render_comment_column($question, $rowclasses);
        echo $output;
    }

    /**
     * Get the left join for comments
     * @return array modified select left join
     */
    public function get_extra_joins() {
        $deletedstatus = utils::COMMENT_HISTORY_DELETE;
        $typepublic = utils::COMMENT_TYPE_PUBLIC;
        $typeprivate = utils::COMMENT_TYPE_PRIVATE;
        $joins = [];
        $joins['copub'] = "LEFT JOIN (
                                      SELECT COUNT(comment) AS publiccomment,
                                             max(created) as lasteditpubliccomment,
                                             questionid
                                        FROM {studentquiz_comment}
                                       WHERE status <> {$deletedstatus}
                                             AND type = {$typepublic}
                                    GROUP BY questionid
                                    ) copub ON copub.questionid = q.id";
        $joins['copri'] = "LEFT JOIN (
                                      SELECT COUNT(comment) AS privatecomment,
                                             max(created) as lasteditprivatecomment,
                                             questionid
                                        FROM {studentquiz_comment}
                                       WHERE status <> {$deletedstatus}
                                             AND type = {$typeprivate}
                                    GROUP BY questionid
                                    ) copri ON copri.questionid = q.id";
        return $joins;

    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return [
            'copub.publiccomment',
            'copri.privatecomment',
            'copub.lasteditpubliccomment',
            'copri.lasteditprivatecomment',
            'sp.lastreadpubliccomment',
            'sp.lastreadprivatecomment'
            ];
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        if (get_config('studentquiz', 'showprivatecomment')) {
            return [
                'publiccomment' => [
                    'field' => 'copub.publiccomment',
                    'title' => get_string('public', 'studentquiz')
                ],
                'privatecomment' => [
                    'field' => 'copri.privatecomment',
                    'title' => get_string('private', 'studentquiz')
                ]
            ];
        } else {
            return 'copub.publiccomment';
        }
    }
}
