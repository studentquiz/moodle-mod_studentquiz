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
 * The mod_studentquiz instance viewed event class
 *
 * If the view mode needs to be stored as well, you may need to
 * override methods get_url() and get_legacy_log_data(), too.
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\condition;
defined('MOODLE_INTERNAL') || die();


/**
 * This class controls from which category questions are listed.
 *
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_condition extends \core_question\bank\search\category_condition {

    /* Due to fix_sql_params not accepting repeated use of named params,
       we need to get unique names for params that will be used more than
       once...

       init() from parent class duplicated here as we can't call it directly
       (private) :-P

       where() overridden with call to init() followed by call to parent
       where()...

       params() always returns $this->params, which doesn't change between
       calls to get_in_or_equal, so don't need to fix anything there.
       Which is fortunate, as there'd be no way to keep where() and params()
       in sync.
    */

    protected function init() {
        global $DB;
        if (!$this->category = $this->get_current_category($this->cat)) {
            return;
        }
        if ($this->recurse) {
            $categoryids = question_categorylist($this->category->id);
        } else {
            $categoryids = array($this->category->id);
        }
        list($catidtest, $this->params) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'cat');
        $this->where = 'q.category ' . $catidtest;
    }

    public function where() {
        // Gross, but rebuilds this->where with fresh catidtest...
        $this->init();
        return parent::where();
    }

    /**
     * Called by question_bank_view to display the GUI for selecting a category
     *
     */
    public function display_options() {

    }

    /**
     * Displays the recursion checkbox GUI.
     * question_bank_view places this within the section that is hidden by default
     *
     */
    public function display_options_adv() {

    }

    /**
     * Display the drop down to select the category.
     *
     * @param array $contexts of contexts that can be accessed from here.
     * @param \moodle_url $pageurl the URL of this page.
     * @param string $current 'categoryID,contextID'.
     */
    protected function display_category_form($contexts, $pageurl, $current) {

    }
}
