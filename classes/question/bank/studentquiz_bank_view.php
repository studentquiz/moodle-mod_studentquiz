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

use mod_studentquiz\local\studentquiz_helper;
use mod_studentquiz\utils;
use stdClass;
use \core_question\local\bank\question_version_status;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ .'/../../../locallib.php');
require_once(__DIR__ . '/studentquiz_column_base.php');
require_once(__DIR__ . '/question_bank_filter.php');
require_once(__DIR__ . '/question_text_row.php');
require_once(__DIR__ . '/rate_column.php');
require_once(__DIR__ . '/difficulty_level_column.php');
require_once(__DIR__ . '/tag_column.php');
require_once(__DIR__ . '/attempts_column.php');
require_once(__DIR__ . '/comments_column.php');
require_once(__DIR__ . '/state_column.php');
require_once(__DIR__ . '/anonym_creator_name_column.php');
require_once(__DIR__ . '/preview_column.php');
require_once(__DIR__ . '/question_name_column.php');
require_once(__DIR__ . '/sq_hidden_action_column.php');
require_once(__DIR__ . '/sq_edit_action_column.php');
require_once(__DIR__ . '/sq_pin_action_column.php');
require_once(__DIR__ . '/state_pin_column.php');
require_once(__DIR__ . '/sq_edit_menu_column.php');
require_once(__DIR__ . '/sq_delete_action_column.php');

/**
 * Module instance settings form
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_bank_view extends \core_question\local\bank\view {
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
     * @var \core\dml\sql_join Current group join sql.
     */
    private $currentgroupjoinsql;

    /**
     * @var int Currently viewing user id.
     */
    protected $userid;


    /**
     * @var mixed
     */
    private $pagevars;

    /**
     * @var stdClass StudentQuiz renderer.
     */
    protected $renderer;

    /** @var mod_studentquiz_report  */
    protected $report;

    /**
     * Constructor assuming we already have the necessary data loaded.
     *
     * @param \core_question\local\bank\question_edit_contexts $contexts
     * @param \moodle_url $pageurl
     * @param object $course
     * @param object|null $cm
     * @param object $studentquiz
     * @param mixed $pagevars
     * @param mod_studentquiz_report $report
     */
    public function __construct($contexts, $pageurl, $course, $cm, $studentquiz, $pagevars, $report) {
        $this->set_filter_post_data();
        global $USER, $PAGE;
        $this->pagevars = $pagevars;
        $this->studentquiz = $studentquiz;
        $this->userid = $USER->id;
        $this->report = $report;
        parent::__construct($contexts, $pageurl, $course, $cm);
        $this->set_filter_form_fields($this->is_anonymized());
        $this->initialize_filter_form($pageurl);
        $currentgroup = groups_get_activity_group($cm, true);
        $this->currentgroupjoinsql = utils::groups_get_questions_joins($currentgroup, 'sqq.groupid');
        // Init search conditions with filterform state.
        $categorycondition = new \core_question\bank\search\category_condition(
                $pagevars['cat'], $pagevars['recurse'], $contexts, $pageurl, $course);
        $studentquizcondition = new \mod_studentquiz\condition\studentquiz_condition($cm, $this->filterform,
            $this->report, $studentquiz);
        $this->isfilteractive = $studentquizcondition->is_filter_active();
        $this->searchconditions = array ($categorycondition, $studentquizcondition);
        $this->renderer = $PAGE->get_renderer('mod_studentquiz', 'overview');
    }

    /**
     * Shows the question bank interface.
     *
     * The function also processes a number of actions:
     *
     * Actions affecting the question pool:
     * move           Moves a question to a different category
     * deleteselected Deletes the selected questions from the category
     * Other actions:
     * category      Chooses the category
     * params: $tabname question bank edit tab name, for permission checking
     * $pagevars current list of page variables
     *
     * @param array $pagevars
     * @param string $tabname
     */
    public function display($pagevars, $tabname): void {
        $page = $pagevars['qpage'];
        $perpage = $pagevars['qperpage'];
        $cat = $pagevars['cat'];
        $recurse = $pagevars['recurse'];
        $showhidden = $pagevars['showhidden'];
        $showquestiontext = $pagevars['qbshowtext'];
        $tagids = [];
        if (!empty($pagevars['qtagids'])) {
            $tagids = $pagevars['qtagids'];
        }
        $output = '';

        $this->build_query();

        // Get result set.
        $questions = $this->load_questions($page, $perpage);
        $this->questions = $questions;
        $this->countsql = count($this->questions);
        if ($this->countsql || $this->isfilteractive) {
            // We're unable to force the filter form to submit with get method. We have 2 forms on the page
            // which need to interact with each other, so forcing method as get here.
            $output .= str_replace('method="post"', 'method="get"', $this->renderer->render_filter_form($this->filterform));
        }
        echo $output;
        if ($this->countsql > 0) {
            $this->display_question_list($this->baseurl, $cat, null, $page, $perpage,
                    $this->contexts->having_cap('moodle/question:add')
            );
        } else {
            list($message, $questionsubmissionallow) = mod_studentquiz_check_availability($this->studentquiz->opensubmissionfrom,
                    $this->studentquiz->closesubmissionfrom, 'submission');
            if ($questionsubmissionallow) {
                echo $this->renderer->render_no_questions_notification($this->isfilteractive);
            }
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
    protected function default_sort(): array {
        return array();
    }

    /**
     * Create the SQL query to retrieve the indicated questions, based on
     * \core_question\local\bank\search\condition filters.
     */
    protected function build_query(): void {
        global $CFG;

        // Hard coded setup.
        $params = array();
        $joins = [
                'qv' => 'JOIN {question_versions} qv ON qv.questionid = q.id',
                'qbe' => 'JOIN {question_bank_entries} qbe on qbe.id = qv.questionbankentryid',
                'qc' => 'JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid',
                'qr' => "JOIN {question_references} qr ON qr.questionbankentryid = qbe.id AND qv.version = (SELECT MAX(v.version)
                                          FROM {question_versions} v
                                          JOIN {question_bank_entries} be
                                            ON be.id = v.questionbankentryid
                                         WHERE be.id = qbe.id)
                              AND qr.component = 'mod_studentquiz'
                              AND qr.questionarea = 'studentquiz_question'
                              AND qc.contextid = qr.usingcontextid",
                'sqq' => 'JOIN {studentquiz_question} sqq ON sqq.id = qr.itemid'
        ];
        $fields = [
                'sqq.id studentquizquestionid',
                'qc.id categoryid',
                'qv.version',
                'qv.id versionid',
                'qbe.id questionbankentryid',
                'qv.status',
                'q.timecreated',
                'q.createdby',
        ];
        $stateready = question_version_status::QUESTION_STATUS_READY;
        $tests = [
                'q.parent = 0',
                "qv.status = '$stateready'",
        ];
        foreach ($this->requiredcolumns as $column) {
            $extrajoins = $column->get_extra_joins();
            foreach ($extrajoins as $prefix => $join) {
                if (isset($joins[$prefix]) && $joins[$prefix] != $join) {
                    throw new \coding_exception('Join ' . $join . ' conflicts with previous join ' . $joins[$prefix]);
                }
                $joins[$prefix] = $join;
            }
            $fields = array_merge($fields, $column->get_required_fields());
        }
        $fields = array_unique($fields);
        if ($this->currentgroupjoinsql->wheres) {
            $params += $this->currentgroupjoinsql->params;
            $tests[] = $this->currentgroupjoinsql->wheres;
        }

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

        if (isset($CFG->questionbankcolumns)) {
            array_unshift($sorts, 'sqq.pinned DESC');
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
     * Create new default question form
     * @param int $categoryid question category
     * @param bool $canadd capability state
     */
    public function create_new_question_form($categoryid, $canadd): void {
        global $OUTPUT;

        $output = '';

        $caption = get_string('createnewquestion', 'studentquiz');

        if ($canadd) {
            $returnurl = $this->baseurl;
            $params = array(
                // TODO: MAGIC CONSTANT!
                'returnurl' => $returnurl->out_as_local_url(false),
                'category' => $categoryid,
                'cmid' => $this->studentquiz->coursemodule,
            );

            $url = new \moodle_url('/question/bank/editquestion/addquestion.php', $params);

            $allowedtypes = (empty($this->studentquiz->allowedqtypes)) ? 'ALL' : $this->studentquiz->allowedqtypes;
            $allowedtypes = ($allowedtypes == 'ALL') ? mod_studentquiz_get_question_types_keys() : explode(',', $allowedtypes);
            $qtypecontainer = \html_writer::div(
                \qbank_editquestion\editquestion_helper::print_choose_qtype_to_add_form(array(), $allowedtypes, true
            ), '', array('id' => 'qtypechoicecontainer'));
            $questionsubmissionbutton = new \single_button($url, $caption, 'get', true);

            list($message, $questionsubmissionallow) = mod_studentquiz_check_availability($this->studentquiz->opensubmissionfrom,
                    $this->studentquiz->closesubmissionfrom, 'submission');

            $questionsubmissionbutton->disabled = !$questionsubmissionallow;
            $output .= \html_writer::div($OUTPUT->render($questionsubmissionbutton) . $qtypecontainer, 'createnewquestion py-3');

            if (!empty($message)) {
                $output .= $this->renderer->render_availability_message($message, 'mod_studentquiz_submission_info');
            }
        } else {
            $output .= get_string('nopermissionadd', 'question');
        }
        echo $output;
    }

    /**
     * Prints the table of questions in a category with interactions
     *
     * @param \moodle_url $pageurl     The URL to reload this page.
     * @param string     $categoryandcontext 'categoryID,contextID'.
     * @param int        $recurse     Whether to include subcategories.
     * @param int        $page        The number of the page to be displayed
     * @param int        $perpage     Number of questions to show per page
     * @param array      $addcontexts contexts where the user is allowed to add new questions.
     */
    protected function display_question_list($pageurl, $categoryandcontext, $recurse = 1, $page = 0,
                                                $perpage = 100, $addcontexts = []): void {
        $output = '';
        $category = $this->get_current_category($categoryandcontext);
        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);
        $output .= \html_writer::start_tag('form', ['action' => '', 'method' => 'get', 'id' => 'questionsubmit']);
        $output .= \html_writer::empty_tag('input', ['type' => 'submit', 'style' => 'display:none;']);

        $output .= \html_writer::start_tag('fieldset', array('class' => 'invisiblefieldset', 'style' => 'display:block;'));

        $output .= $this->renderer->render_hidden_field($this->cm->id, $this->baseurl, $perpage);

        $output .= $this->renderer->render_control_buttons($catcontext, $this->has_questions_in_category(),
            $addcontexts, $category);

        $output .= $this->renderer->render_pagination_bar($this->pagevars, $this->baseurl, $this->totalnumber, $page,
            $perpage, true);

        $output .= $this->display_question_list_rows();

        $output .= $this->renderer->render_pagination_bar($this->pagevars, $this->baseurl, $this->totalnumber, $page,
            $perpage, false);

        $output .= $this->renderer->render_control_buttons($catcontext, $this->has_questions_in_category(),
            $addcontexts, $category);

        $output .= \html_writer::end_tag('fieldset');
        $output .= \html_writer::end_tag('form');

        $output .= $this->renderer->display_javascript_snippet();

        echo $output;
    }

    /**
     * Prints the effective question table
     *
     * @return string
     */
    protected function display_question_list_rows() {
        $output = '';
        $output .= \html_writer::start_div('categoryquestionscontainer');
        ob_start();
        $this->print_table($this->questions);
        $output .= ob_get_contents();
        ob_end_clean();
        $output .= \html_writer::end_div();
        return $output;
    }

    /**
     * Return the row classes for question table
     *
     * @param object $question the row from the $question table, augmented with extra information.
     * @param int $rowcount Row index
     * @return array Classes of row
     */
    protected function get_row_classes($question, $rowcount): array {
        $classes = parent::get_row_classes($question, $rowcount);
        if (($key = array_search('dimmed_text', $classes)) !== false) {
            unset($classes[$key]);
        }
        return $classes;
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

        $this->fields[] = new \toggle_filter_checkbox('only_new_state',
                get_string('state_newplural', 'studentquiz'), false, 'sqq.state',
                ['approved'], 2, studentquiz_helper::STATE_NEW);
        $this->fields[] = new \toggle_filter_checkbox('only_approved_state',
                get_string('state_approvedplural', 'studentquiz'), false, 'sqq.state',
                ['approved'], 2, studentquiz_helper::STATE_APPROVED);
        $this->fields[] = new \toggle_filter_checkbox('only_disapproved_state',
                get_string('state_disapprovedplural', 'studentquiz'), false, 'sqq.state',
                ['approved'], 2, studentquiz_helper::STATE_DISAPPROVED);
        $this->fields[] = new \toggle_filter_checkbox('only_changed_state',
                get_string('state_changedplural', 'studentquiz'), false, 'sqq.state',
                ['approved'], 2, studentquiz_helper::STATE_CHANGED);
        $this->fields[] = new \toggle_filter_checkbox('only_reviewable_state',
                get_string('state_reviewableplural', 'studentquiz'), false, 'sqq.state',
                ['approved'], 2, studentquiz_helper::STATE_REVIEWABLE);

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

        // Advanced filters.
        $this->fields[] = new \studentquiz_user_filter_text('tagarray', get_string('filter_label_tags', 'studentquiz'),
            true, 'tagarray');

        $states = array();
        foreach (studentquiz_helper::$statename as $num => $name) {
            if ($num == studentquiz_helper::STATE_DELETE || $num == studentquiz_helper::STATE_HIDE) {
                continue;
            }
            $states[$num] = get_string('state_'.$name, 'studentquiz');
        }
        $this->fields[] = new \user_filter_simpleselect('state', get_string('state_column_name', 'studentquiz'),
            true, 'state', $states);

        $this->fields[] = new \user_filter_number('rate', get_string('filter_label_rates', 'studentquiz'),
            true, 'rate');
        $this->fields[] = new \user_filter_percent('difficultylevel', get_string('filter_label_difficulty_level', 'studentquiz'),
            true, 'difficultylevel');

        $this->fields[] = new \user_filter_number('publiccomment', get_string('filter_label_comment', 'studentquiz'),
            true, 'publiccomment');
        $this->fields[] = new \studentquiz_user_filter_text('name', get_string('filter_label_question', 'studentquiz'),
            true, 'name');
        $this->fields[] = new \studentquiz_user_filter_text('questiontext', get_string('filter_label_questiontext', 'studentquiz'),
            true, 'questiontext');

        if ($anonymize) {
            $this->fields[] = new \user_filter_checkbox('createdby', get_string('filter_label_show_mine', 'studentquiz'),
                true, 'createdby');
        } else {
            $this->fields[] = new \studentquiz_user_filter_text('firstname', get_string('firstname'), true, 'firstname');
            $this->fields[] = new \studentquiz_user_filter_text('lastname', get_string('lastname'), true, 'lastname');
        }

        $this->fields[] = new \studentquiz_user_filter_date('timecreated', get_string('filter_label_createdate', 'studentquiz'),
            true, 'timecreated');

        $this->fields[] = new \user_filter_simpleselect('lastanswercorrect',
            get_string('filter_label_mylastattempt', 'studentquiz'),
            true, 'lastanswercorrect', array(
                '1' => get_string('lastattempt_right', 'studentquiz'),
                '0' => get_string('lastattempt_wrong', 'studentquiz')
            ));

        $this->fields[] = new \user_filter_number('myattempts', get_string('filter_label_myattempts', 'studentquiz'),
            true, 'myattempts');

        $this->fields[] = new \user_filter_number('mydifficulty', get_string('filter_label_mydifficulty', 'studentquiz'),
            true, 'mydifficulty');

        $this->fields[] = new \user_filter_number('myrate', get_string('filter_label_myrate', 'studentquiz'),
            true, 'myrate');
    }

     /**
      * Set data for filter recognition
      * We have two forms in the view.php page which need to interact with each other. All params are sent through GET,
      * but the moodle filter form can only process POST, so we need to copy them there.
      */
    private function set_filter_post_data() {
        $_POST = $_GET;
    }

    /**
     * Initialize filter form
     * @param moodle_url $pageurl
     */
    private function initialize_filter_form($pageurl) {
        $this->isfilteractive = false;

        // If reset button was pressed, redirect the user again to the page.
        // This means all submitted data is intentionally lost and thus the form clean again.
        if (optional_param('resetbutton', false, PARAM_ALPHA)) {
            // Reset to clean state.
            $pageurl->remove_all_params();
            $pageurl->params(['id' => $this->cm->id]);
            redirect($pageurl->out());
        }
        $this->filterform = new \mod_studentquiz_question_bank_filter_form(
            $this->fields,
            $pageurl->out(false),
            array_merge(['cmid' => $this->cm->id], $this->pagevars)
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

    /**
     * Get Studentquiz object of question bank.
     * @return \stdClass studentquiz object.
     */
    public function get_studentquiz() {
        return $this->studentquiz;
    }

    /**
     * Deal with a sort name of the form columnname, or colname_subsort by
     * breaking it up, validating the bits that are present, and returning them.
     * If there is no subsort, then $subsort is returned as ''.
     *
     * @param string $sort the sort parameter to process.
     * @return array array($colname, $subsort).
     */
    protected function parse_subsort($sort): array {
        // When we sort by public/private comments and turn off the setting studentquiz | privatecomment,
        // the parse_subsort function will throw exception. We should redirect to the base_url after cleaning all sort params.
        $showprivatecomment = $this->studentquiz->privatecommenting;
        if ($showprivatecomment && $sort == 'mod_studentquiz\bank\comment_column' ||
                !$showprivatecomment && ($sort == 'mod_studentquiz\bank\comment_column-privatecomment' ||
                $sort == 'mod_studentquiz\bank\comment_column-publiccomment')) {
            for ($i = 1; $i <= self::MAX_SORTS; $i++) {
                $this->baseurl->remove_params('qbs' . $i);
            }
            redirect($this->base_url());
        }

        return parent::parse_subsort($sort);
    }

    /**
     *  Return the all the required column for the view.
     *
     * @return array \question_bank_column_base[]
     */
    protected function wanted_columns(): array {
        global $PAGE;
        $renderer = $PAGE->get_renderer('mod_studentquiz');
        $this->requiredcolumns = $renderer->get_columns_for_question_bank_view($this);
        return $this->requiredcolumns;
    }
}
