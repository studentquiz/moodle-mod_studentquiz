<?php
/**
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\condition;
defined('MOODLE_INTERNAL') || die();


/**
 * This class controls from which category questions are listed.
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
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
