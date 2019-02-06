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
 * The question bank view
 *
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\question\bank;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ .'/../../../locallib.php');
require_once(__DIR__ . '/question_bank_filter.php');
require_once(__DIR__ . '/question_text_row.php');
require_once(__DIR__ . '/rate_column.php');
require_once(__DIR__ . '/difficulty_level_column.php');
require_once(__DIR__ . '/tag_column.php');
require_once(__DIR__ . '/practice_column.php');
require_once(__DIR__ . '/comments_column.php');
require_once(__DIR__ . '/approved_column.php');
require_once(__DIR__ . '/anonym_creator_name_column.php');
require_once(__DIR__ . '/preview_column.php');
require_once(__DIR__ . '/question_name_column.php');

/**
 * Module instance settings form
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_bank_view extends \core_question\bank\view {
    /**
     * @var stdClass filtered questions from database
     */
    private $questions;

    /**
     * @var array of ids of the questions that are displayed on current page
     * (IF the filter result is paginated, ids on other pages are not collected!)
     */
    private $displayedquestionsids = array();

    /**
     * @var int totalnumber from filtered questions
     */
    private $totalnumber;

    /**
     * @var string sql tag name field
     */
    private $tagnamefield;

    /**
     * @var bool is filter active
     */
    private $isfilteractive;

    /**
     * @var question_bank_filter_form class
     */
    private $filterform;

    /**
     * @var array of user_filter_*
     */
    private $fields;

    /**
     * @var object $studentquiz current studentquiz record
     */
    private $studentquiz;

    /**
     * @var Currently viewing user id.
     */
    protected $userid;


    private $pagevars;

    /**
     * @var StudentQuiz renderer.
     */
    protected $renderer;

    /** @var mod_studentquiz_report  */
    protected $report;

    /**
     * Constructor assuming we already have the necessary data loaded.
     *
     * @param \core_question\bank\question_edit_contexts $contexts
     * @param \core_question\bank\moodle_url $pageurl
     * @param object $course
     * @param null|object $cm
     * @param object $studentquiz
     * @param $pagevars
     * @param mod_studentquiz_report $report
     */
    public function __construct($contexts, $pageurl, $course, $cm, $studentquiz, $pagevars, $report) {
        parent::__construct($contexts, $pageurl, $course, $cm);
        global $USER, $PAGE;
        $this->pagevars = $pagevars;
        $this->studentquiz = $studentquiz;
        $this->userid = $USER->id;
        $this->report = $report;
        $this->set_filter_form_fields($this->is_anonymized());
        $this->initialize_filter_form($pageurl);
        // Init search conditions with filterform state.
        $cateorycondition = new \core_question\bank\search\category_condition(
                $pagevars['cat'], $pagevars['recurse'], $contexts, $pageurl, $course);
        $studentquizcondition = new \mod_studentquiz\condition\studentquiz_condition($cm, $this->filterform, $this->report, $studentquiz);
        $this->isfilteractive = $studentquizcondition->is_filter_active();
        $this->searchconditions = array ($cateorycondition, $studentquizcondition);
        $this->renderer = $PAGE->get_renderer('mod_studentquiz', 'overview');
    }

    /**
     * Shows the question bank editing interface.
     *
     * The function also processes a number of actions:
     *
     * Actions affecting the question pool:
     * move           Moves a question to a different category
     * deleteselected Deletes the selected questions from the category
     * Other actions:
     * category      Chooses the category
     * displayoptions Sets display options
     *
     * @param string $tabname
     * @param int $page
     * @param int $perpage
     * @param bool $cat
     * @param bool $recurse
     * @param bool $showhidden
     * @param bool $showquestiontext
     * @param array $tagids
     * @return html output
     */
    public function display($tabname, $page, $perpage, $cat,
                            $recurse, $showhidden, $showquestiontext, $tagids = []) {
        $output = '';

        $this->build_query();

        // Get result set.
        $questions = $this->load_questions($page, $perpage);

        $tags = mod_studentquiz_get_tags_by_question_ids($this->displayedquestionsids);

        // Annotate questions with tags.
        foreach ($questions as $question) {
            if (array_key_exists($question->id, $tags)) {
                $question->tagarray = $tags[$question->id];
            } else {
                $question->tagarray = null;
            }
        }

        $this->questions = $questions;

        if ($this->process_actions_needing_ui()) {
            return;
        }

        if (count($this->questions) || $this->isfilteractive) {
            $output .= $this->renderer->render_filter_form($this->filterform);
        }

        if (count($this->questions) > 0) {
            $questionslist = $this->display_question_list(
                    $this->contexts->having_one_edit_tab_cap($tabname),
                    $this->baseurl, $cat, $this->cm,
                    null, $page, $perpage, $showhidden, $showquestiontext,
                    $this->contexts->having_cap('moodle/question:add')
            );
            $output .= $this->renderer->render_question_form($questionslist);
        } else {
            $output .= $this->renderer->render_no_questions_notification($this->isfilteractive);
        }
        return $output;
    }

    /**
     * process action buttons
     *
     * Check for commands on this page and modify variables as necessary.
     */
    public function process_actions() {
        global $DB;

        // Approve selected questions.
        if (optional_param('approveselected', false, PARAM_BOOL)) {
            // If teacher has already confirmed the action.
            if (($confirm = optional_param('confirm', '', PARAM_ALPHANUM)) and confirm_sesskey()) {
                // TODO: What? Security by obscurity? Needs a look closer, probably best by using the capability :manage!
                $approveselected = required_param('approveselected', PARAM_RAW);
                if ($confirm == md5($approveselected)) {
                    if ($questionlist = explode(',', $approveselected)) {
                        // For each question either hide it if it is in use or delete it.
                        foreach ($questionlist as $questionid) {
                            $questionid = (int)$questionid;
                            mod_studentquiz_flip_approved($questionid);
                            mod_studentquiz_notify_approved($questionid, $this->course, $this->cm);
                        }
                    }
                    redirect($this->baseurl);
                } else {
                    print_error('invalidconfirm', 'question');
                }
            }
        }

        // Move selected questions to new category.
        // TODO: Isn't there a questionlib function for that?
        if (optional_param('move', false, PARAM_BOOL) and confirm_sesskey()) {
            $category = required_param('category', PARAM_SEQUENCE);
            list($tocategoryid, $contextid) = explode(',', $category);
            if (! $tocategory = $DB->get_record('question_categories', array('id' => $tocategoryid, 'contextid' => $contextid))) {
                print_error('cannotfindcate', 'question');
            }
            $tocontext = \context::instance_by_id($contextid);
            require_capability('moodle/question:add', $tocontext);
            $rawdata = (array) data_submitted();
            // TODO: Seen that somewhere else, extract in common function!
            $questionids = array();
            foreach (array_keys($rawdata) as $key) {  // Parse input for question ids.
                if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
                    $key = $matches[1];
                    $questionids[] = $key;
                }
            }
            if ($questionids) {
                list($usql, $params) = $DB->get_in_or_equal($questionids);
                $sql = "SELECT q.*, c.contextid
                          FROM {question} q
                          JOIN {question_categories} c ON c.id = q.category
                         WHERE q.id {$usql}";
                $questions = $DB->get_records_sql($sql, $params);
                foreach ($questions as $question) {
                    question_require_capability_on($question, 'move');
                }
                question_move_questions_to_category($questionids, $tocategory->id);
                redirect($this->baseurl->out(false));
            }
        }

        // Delete selected questions from the category.
        if (optional_param('deleteselected', false, PARAM_BOOL)) {
            // If teacher has already confirmed the action.
            if (($confirm = optional_param('confirm', '', PARAM_ALPHANUM)) and confirm_sesskey()) {
                $deleteselected = required_param('deleteselected', PARAM_RAW);
                if ($confirm == md5($deleteselected)) {
                    if ($questionlist = explode(',', $deleteselected)) {
                        // For each question either hide it if it is in use or delete it.
                        foreach ($questionlist as $questionid) {
                            $questionid = (int)$questionid;
                            question_require_capability_on($questionid, 'edit');
                            mod_studentquiz_notify_deleted($questionid, $this->course, $this->cm);
                            if (questions_in_use(array($questionid))) {
                                $DB->set_field('question', 'hidden', 1, array('id' => $questionid));
                            } else {
                                question_delete_question($questionid);
                            }
                        }
                    }
                    redirect($this->baseurl);
                } else {
                    print_error('invalidconfirm', 'question');
                }
            }
        }

        // Unhide a question.
        if (($unhide = optional_param('unhide', '', PARAM_INT)) and confirm_sesskey()) {
            question_require_capability_on($unhide, 'edit');
            $DB->set_field('question', 'hidden', 0, array('id' => $unhide));

            // Purge these questions from the cache.
            \question_bank::notify_question_edited($unhide);

            redirect($this->baseurl);
        }
    }

    /**
     * Confirmation on process action if needed
     * @return boolean
     */
    public function process_actions_needing_ui() {
        global $DB, $OUTPUT;

        // Make a list of all the questions that are selected.
        if (optional_param('deleteselected', false, PARAM_BOOL) || optional_param('approveselected', false, PARAM_BOOL)) {
            // This code is called by both POST forms and GET links, so cannot use data_submitted.
            $rawquestions = $_REQUEST;
            // Comma separated list of ids of questions to be deleted.
            $questionlist = '';
            // String with names of questions separated by <br /> with.
            $questionnames = '';
            // An asterix in front of those that are in use Set to true if at least one of the questions is in use.
            $inuse = false;

            // Parse input for question ids.
            foreach (array_keys($rawquestions) as $key) {
                if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
                    $key = $matches[1];
                    $questionlist .= $key.',';
                    question_require_capability_on($key, 'edit');
                    if (questions_in_use(array($key))) {
                        $questionnames .= '* ';
                        $inuse = true;
                    }
                    $questionnames .= $DB->get_field('question', 'name', array('id' => $key)) . '<br />';
                }
            }

            // No questions were selected.
            if (!$questionlist) {
                redirect($this->baseurl);
            }
            $questionlist = rtrim($questionlist, ',');

            $baseurl = new \moodle_url('view.php', $this->baseurl->params());

            if (optional_param('deleteselected', false, PARAM_BOOL)) {
                // Add an explanation about questions in use.
                if ($inuse) {
                    $questionnames .= \html_writer::empty_tag('br').get_string('questionsinuse', 'question');
                }

                $deleteurl = new \moodle_url($baseurl, array('deleteselected' => $questionlist, 'confirm' => md5($questionlist),
                    'sesskey' => sesskey()));

                $continue = new \single_button($deleteurl, get_string('delete'), 'post');

                $output = $OUTPUT->confirm(get_string('deletequestionscheck', 'question', $questionnames), $continue, $baseurl);
            } else if (optional_param('approveselected', false, PARAM_BOOL)) {
                // Add an explanation about questions in use.
                if ($inuse) {
                    $questionnames .= \html_writer::empty_tag('br').get_string('questionsinuse', 'studentquiz');
                }

                $approveurl = new \moodle_url($baseurl, array('approveselected' => $questionlist, 'confirm' => md5($questionlist),
                    'sesskey' => sesskey()));

                $continue = new \single_button($approveurl, get_string('approve', 'studentquiz'), 'post');

                $output = $OUTPUT->confirm(get_string('approveselectedscheck', 'studentquiz', $questionnames), $continue, $baseurl);
            }

            echo $output;
            return true;
        }
    }

    /**
     * Get all questions
     * @return stdClass array of questions
     */
    public function get_questions() {
        return $this->questions;
    }

    /**
     * Override base default sort
     */
    protected function default_sort() {
        return array();
    }

    /**
     * Create the SQL query to retrieve the indicated questions, based on
     * \core_question\bank\search\condition filters.
     */
    protected function build_query() {
        // Hard coded setup.
        $params = array();
        $joins = array();
        $fields = array('q.hidden', 'q.category', 'q.timecreated', 'q.createdby');
        $tests = array('q.parent = 0', 'q.hidden = 0');
        foreach ($this->requiredcolumns as $column) {
            if (method_exists($column, 'set_searchconditions')) {
                $column->set_searchconditions($this->searchconditions);
            }
            $extrajoins = $column->get_extra_joins();
            foreach ($extrajoins as $prefix => $join) {
                if (isset($joins[$prefix]) && $joins[$prefix] != $join) {
                    throw new \coding_exception('Join ' . $join . ' conflicts with previous join ' . $joins[$prefix]);
                }
                $joins[$prefix] = $join;
            }
            if (method_exists($column, 'get_sqlparams')) {
                $params = array_merge($params, $column->get_sqlparams());
            }
            $fields = array_merge($fields, $column->get_required_fields());
        }
        $fields = array_unique($fields);

        // Build the order by clause.
        $sorts = array();
        foreach ($this->sort as $sort => $order) {
            list($colname, $subsort) = $this->parse_subsort($sort);
            $sorts[] = $this->requiredcolumns[$colname]->sort_expression($order < 0, $subsort);
        }

        // Default sorting.
        if (empty($sorts)) {
            $sorts[] = 'q.timecreated DESC,q.id ASC';
        }

        // Build the where clause and load params from search conditions.
        foreach ($this->searchconditions as $searchcondition) {
            if (!empty($searchcondition->where())) {
                $tests[] = $searchcondition->where();
            }
            if (!empty($searchcondition->params())) {
                $params = array_merge($params, $searchcondition->params());
            }
        }

        // Build the complete SQL query.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $tests);
        $this->sqlparams = $params;
        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
    }

    /**
     * Has questions in category
     * @return bool
     */
    protected function has_questions_in_category() {
        return $this->totalnumber > 0;
    }

    /**
     * TODO: document PHPDoc
     * Create new default question form
     * @param string $category question category
     * @param bool $canadd capability state
     */
    public function create_new_question_form($categoryid, $canadd) {
        global $OUTPUT;

        $output = '';

        $caption = get_string('createnewquestion', 'studentquiz');

        if ($canadd) {
            $returnurl = new \moodle_url('/mod/studentquiz/view.php', array(
                'id' => $this->studentquiz->coursemodule
            ));
            $params = array(
                // TODO: MAGIC CONSTANT!
                'returnurl' => $returnurl->out_as_local_url(false),
                'category' => $categoryid,
                'cmid' => $this->studentquiz->coursemodule,
            );

            $url = new \moodle_url('/question/addquestion.php', $params);

            $allowedtypes = (empty($this->studentquiz->allowedqtypes)) ? 'ALL' : $this->studentquiz->allowedqtypes;
            $allowedtypes = ($allowedtypes == 'ALL') ? mod_studentquiz_get_question_types_keys() : explode(',', $allowedtypes);
            $qtypecontainer = \html_writer::div(
                print_choose_qtype_to_add_form(array(), $allowedtypes, true
            ), '', array('id' => 'qtypechoicecontainer'));
            $questionsubmissionbutton = new \single_button($url, $caption, 'get', true);

            list($message, $questionsubmissionallow) = mod_studentquiz_check_availability($this->studentquiz->opensubmissionfrom,
                    $this->studentquiz->closesubmissionfrom, 'submission');

            $questionsubmissionbutton->disabled = !$questionsubmissionallow;
            $output .= \html_writer::div($OUTPUT->render($questionsubmissionbutton) . $qtypecontainer, 'createnewquestion');

            if (!empty($message)) {
                $output .= $this->renderer->render_availability_message($message, 'mod_studentquiz_submission_info');
            }
        } else {
            $output .= get_string('nopermissionadd', 'question');
        }
        return $output;
    }

    /**
     * Prints the table of questions in a category with interactions
     *
     * @param array $contexts Not used!
     * @param moodle_url $pageurl The URL to reload this page.
     * @param string $categoryandcontext 'categoryID,contextID'.
     * @param stdClass $cm Not used!
     * @param bool|int $recurse Whether to include subcategories.
     * @param int $page The number of the page to be displayed
     * @param int $perpage Number of questions to show per page
     * @param bool $showhidden whether deleted questions should be displayed.
     * @param bool $showquestiontext whether the text of each question should be shown in the list. Deprecated.
     * @param array $addcontexts contexts where the user is allowed to add new questions.
     * @return html output
     */
    protected function display_question_list($contexts, $pageurl, $categoryandcontext,
                                             $cm = null, $recurse=1, $page=0, $perpage=100, $showhidden=false,
                                             $showquestiontext = false, $addcontexts = array()) {
        $output = '';
        $category = $this->get_current_category($categoryandcontext);

        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);

        $output .= \html_writer::start_tag('fieldset', array('class' => 'invisiblefieldset', 'style' => 'display:block;'));

        $output .= $this->renderer->render_hidden_field($this->cm->id, $this->get_filtered_question_ids(), $this->baseurl);

        $output .= $this->renderer->render_control_buttons($catcontext, $this->has_questions_in_category(), $addcontexts, $category);

        $output .= $this->renderer->render_pagination_bar($this->pagevars, $this->baseurl, $this->totalnumber, $page, $perpage, $pageurl);

        $output .= $this->display_question_list_rows($page);

        $output .= $this->renderer->render_pagination_bar($this->pagevars, $this->baseurl, $this->totalnumber, $page, $perpage, $pageurl);

        $output .= $this->renderer->render_control_buttons($catcontext, $this->has_questions_in_category(), $addcontexts, $category);

        $output .= \html_writer::end_tag('fieldset');

        $output .= $this->renderer->display_javascript_snippet();

        return $output;
    }

    protected function display_question_list_rows() {
        $output = '';
        $output .= \html_writer::start_div('categoryquestionscontainer');
        ob_start();
        $this->start_table();
        $rowcount = 0;
        foreach ($this->questions as $question) {
            $this->print_table_row($question, $rowcount);
            $rowcount++;
        }
        $this->numberofdisplayedquestions = $rowcount;
        $this->end_table();
        $output .= ob_get_contents();
        ob_end_clean();
        $output .= \html_writer::end_div();
        return $output;
    }

    /**
     * Set filter form fields
     * @param bool $anonymize if false, questions can get filtered by author last name and first name instead by own userid only.
     */
    private function set_filter_form_fields($anonymize = true) {
        $this->fields = array();

        // Fast filters.
        $this->fields[] = new \toggle_filter_checkbox('onlynew',
            get_string('filter_label_onlynew', 'studentquiz'),
            false, 'myattempts', array('myattempts', 'myattempts_op'), 0, 0,
            get_string('filter_label_onlynew_help', 'studentquiz'));

        $this->fields[] = new \toggle_filter_checkbox('onlyapproved',
            get_string('filter_label_onlyapproved', 'studentquiz'),
            false, 'ap.approved', array('approved', 'approved_op'), 1, 1,
            get_string('filter_label_onlyapproved_help', 'studentquiz'));

        $this->fields[] = new \toggle_filter_checkbox('onlygood',
            get_string('filter_label_onlygood', 'studentquiz'),
                false, 'vo.rate', array('rate', 'rate_op'), 1, 4,
            get_string('filter_label_onlygood_help', 'studentquiz', '4'));

        $this->fields[] = new \toggle_filter_checkbox('onlymine',
            get_string('filter_label_onlymine', 'studentquiz'),
            false, 'q.createdby', array('createdby'), 2, $this->userid,
            get_string('filter_label_onlymine_help', 'studentquiz'));

        $this->fields[] = new \toggle_filter_checkbox('onlydifficultforme',
            get_string('filter_label_onlydifficultforme', 'studentquiz'),
            false, 'mydifficulty', array('mydifficulty', 'mydifficulty_op'), 1, 0.60,
            get_string('filter_label_onlydifficultforme_help', 'studentquiz', '60'));

        $this->fields[] = new \toggle_filter_checkbox('onlydifficult',
            get_string('filter_label_onlydifficult', 'studentquiz'),
            false, 'dl.difficultylevel', array('difficultylevel', 'difficultylevel_op'), 1, 0.60,
            get_string('filter_label_onlydifficult_help', 'studentquiz', '60'));

        // Standard filters.
        $this->fields[] = new \user_filter_tag('tagname', get_string('filter_label_tags', 'studentquiz'),
            true, 'tagname');

        $this->fields[] = new \user_filter_simpleselect('approved', get_string('filter_label_approved', 'studentquiz'),
            true, 'approved', array(
                true => get_string('approved', 'studentquiz'),
                false => get_string('not_approved', 'studentquiz')
            ));

        // Advanced filters.
        $this->fields[] = new \user_filter_number('rate', get_string('filter_label_rates', 'studentquiz'),
            true, 'rate');
        $this->fields[] = new \user_filter_percent('difficultylevel', get_string('filter_label_difficulty_level', 'studentquiz'),
            true, 'difficultylevel');

        $this->fields[] = new \user_filter_number('practice', get_string('filter_label_practice', 'studentquiz'),
            true, 'practice');
        $this->fields[] = new \user_filter_number('comment', get_string('filter_label_comment', 'studentquiz'),
            true, 'comment');
        $this->fields[] = new \user_filter_text('name', get_string('filter_label_question', 'studentquiz'),
            true, 'name');
        $this->fields[] = new \user_filter_text('questiontext', get_string('filter_label_questiontext', 'studentquiz'),
            true, 'questiontext');

        if ($anonymize) {
            $this->fields[] = new \user_filter_checkbox('createdby', get_string('filter_label_show_mine', 'studentquiz'),
                true, 'createdby');
        } else {
            $this->fields[] = new \user_filter_text('firstname', get_string('filter_label_firstname', 'studentquiz'),
                true, 'firstname');
            $this->fields[] = new \user_filter_text('lastname', get_string('filter_label_surname', 'studentquiz'),
                true, 'lastname');
        }

        $this->fields[] = new \user_filter_date('timecreated', get_string('filter_label_createdate', 'studentquiz'),
            true, 'timecreated');

        $this->fields[] = new \user_filter_simpleselect('mylastattempt', get_string('filter_label_mylastattempt', 'studentquiz'),
            true, 'mylastattempt', array(
                'gradedright' => get_string('lastattempt_right', 'studentquiz'),
                'gradedwrong' => get_string('lastattempt_wrong', 'studentquiz')
            ));

        $this->fields[] = new \user_filter_number('myattempts', get_string('filter_label_myattempts', 'studentquiz'),
            true, 'myattempts');

        $this->fields[] = new \user_filter_number('mydifficulty', get_string('filter_label_mydifficulty', 'studentquiz'),
            true, 'mydifficulty');

        $this->fields[] = new \user_filter_number('myrate', get_string('filter_label_myrate', 'studentquiz'),
            true, 'myrate');
    }

    /**
     * Initialize filter form
     * @param moodle_url $pageurl
     * @throws \coding_exception missing url param exception
     */
    private function initialize_filter_form($pageurl) {
        $this->isfilteractive = false;

        // If reset button was pressed, redirect the user again to the page.
        // This means all submitted data is intentionally lost and thus the form clean again.
        if (optional_param('resetbutton', false, PARAM_ALPHA)) {
            redirect($pageurl);
        }

        $this->filterform = new \mod_studentquiz_question_bank_filter_form(
            $this->fields,
            $pageurl->out(),
            array('cmid' => $this->cm->id)
        );
    }

    /**
     * Load question from database
     * @param int $page
     * @param int $perpage
     * @return paginated array of questions
     */
    private function load_questions($page, $perpage) {
        global $DB;
        $rs = $DB->get_recordset_sql($this->loadsql, $this->sqlparams);

        $counterquestions = 0;
        $numberofdisplayedquestions = 0;
        $showall = $this->pagevars['showall'];
        $rs->rewind();

        // Skip Questions on previous pages.
        while ($rs->valid() && !$showall && $counterquestions < $page * $perpage) {
            $rs->next();
            $counterquestions++;
        }

        // Reset and start from 0 if page was empty.
        if (!$showall && $counterquestions < $page * $perpage) {
            $rs->rewind();
        }

        // Unfortunately we cant just render the questions directly.
        // We need to annotate tags first.
        $questions = array();
        // Load questions.
        while ($rs->valid() && ($showall || $numberofdisplayedquestions < $perpage)) {
            $question = $rs->current();
            $numberofdisplayedquestions++;
            $counterquestions++;
            $this->displayedquestionsids[] = $question->id;
            $rs->next();
            $questions[] = $question;
        }

        // Iterate to end.
        while ($rs->valid()) {
            $rs->next();
            $counterquestions++;
        }
        $this->totalnumber = $counterquestions;
        $rs->close();
        return $questions;
    }

    /**
     * Get all filtered question ids qith q prefix
     * @return array question ids with q prefix
     * @deprecated TODO: This should nowhere be necessary!
     */
    private function get_filtered_question_ids() {
        return $this->displayedquestionsids;
    }

    /**
     * TODO: rename function and apply (there is duplicate method)
     * @return bool studentquiz is set to anoymize ranking.
     */
    public function is_anonymized() {
        if (!$this->studentquiz->anonymrank) {
            return false;
        }
        $context = \context_module::instance($this->studentquiz->coursemodule);
        if (has_capability('mod/studentquiz:unhideanonymous', $context)) {
            return false;
        }
        // Instance is anonymized and isn't allowed to unhide that.
        return true;
    }
}
