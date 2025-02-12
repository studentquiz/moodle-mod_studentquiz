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
use core_question\local\bank\question_version_status;
use qbank_managecategories\category_condition;
use core_question\local\bank\column_manager_base;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/question_bank_filter.php');

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
    protected $pagevars;

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
        $this->studentquiz = $studentquiz;
        $this->userid = $USER->id;
        $this->report = $report;
        parent::__construct($contexts, $pageurl, $course, $cm, $pagevars);

        $this->set_filter_form_fields($this->is_anonymized());
        $this->initialize_filter_form($pageurl);
        $currentgroup = groups_get_activity_group($cm, true);
        $this->currentgroupjoinsql = utils::groups_get_questions_joins($currentgroup, 'sqq.groupid');
        // Init search conditions with filterform state.
        $categorycondition = new category_condition($this);
        $studentquizcondition = new \mod_studentquiz\condition\studentquiz_condition($cm, $this->filterform,
            $this->report, $studentquiz);
        $this->isfilteractive = $studentquizcondition->is_filter_active();
        $this->searchconditions = array ($categorycondition, $studentquizcondition);
        $this->renderer = $PAGE->get_renderer('mod_studentquiz', 'overview');
    }

    /**
     * Shows the question bank interface.
     */
    public function display(): void {
        $output = '';

        $this->build_query();

        // Get result set.
        $questions = $this->load_questions();
        $this->questions = $questions;
        if ($this->totalnumber || $this->isfilteractive) {
            // We're unable to force the filter form to submit with get method. We have 2 forms on the page
            // which need to interact with each other, so forcing method as get here.
            $output .= str_replace('method="post"', 'method="get"', $this->renderer->render_filter_form($this->filterform));
        }
        echo $output;
        if ($this->totalnumber > 0) {
            $this->display_question_list();
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
        return [
            'mod_studentquiz__question__bank__anonym_creator_name_column-timecreated' => SORT_DESC,
            'mod_studentquiz__question__bank__question_name_column' => SORT_ASC,
        ];
    }

    public function new_sort_url($sortname, $newsortreverse): string {
        // Due to the way sorting param name change in Moodle 4.3.
        // We need to override this so we can remove all sort params in the url.
        // So that when we run the new_sort_url function, our sort name always be the first param in the url.
        // Example: 4.2, we have a default sorting ['qb1' => 'columnA', 'qb2' => 'columnB'].
        // After we run the new_sort_url function, it will return ['qb1' => 'columnC', 'qb2' => 'columnA', 'qb3' => 'columnB'].
        // But in 4.3, each column is unique key, so we can't override the param like that.
        // Example: ['columnA' => 3, 'columnB' => 4].
        // We want our columnC to be move the become the first element of the sorting array.
        // The simple way is just remove all existing sorting param in the baseurl, so when we running the new_sort_url function.
        // It will return like this ['columnC' => 4, 'columnA' => 3, 'columnB' => 4].
        foreach ($this->baseurl->params() as $paramname => $value) {
            if (strpos($paramname, 'sortdata') !== false) {
                $this->baseurl->remove_params($paramname);
            }
        }
        return parent::new_sort_url($sortname, $newsortreverse);
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
                'qc.contextid',
        ];
        // Only show ready and draft question.
        $tests = [
                'q.parent = 0',
                "qv.status <> :status",
        ];
        $params['status'] = question_version_status::QUESTION_STATUS_HIDDEN;
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
            $sorts[] = $this->requiredcolumns[$colname]->sort_expression($order === SORT_DESC, $subsort);
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
        array_unshift($sorts, 'sqq.pinned DESC');

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
     * Create new default question form
     * @param int $categoryid question category
     * @param bool $canadd capability state
     */
    public function create_new_question_form($categoryid, $canadd): void {
        global $OUTPUT;

        $output = '';

        $caption = get_string('createnewquestion', 'studentquiz');
        if (is_object($categoryid)) {
            $categoryid = $categoryid->id;
        }
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
            $questionsubmissionbutton = new \single_button($url, $caption, 'get', 'primary');

            list($message, $questionsubmissionallow) = mod_studentquiz_check_availability($this->studentquiz->opensubmissionfrom,
                    $this->studentquiz->closesubmissionfrom, 'submission');

            $questionsubmissionbutton->disabled = !$questionsubmissionallow;
            $output .= \html_writer::div($OUTPUT->render($questionsubmissionbutton) . $qtypecontainer, 'createnewquestion py-3');

            if (!empty($message)) {
                $output .= $this->renderer->render_availability_message($message, 'mod_studentquiz_submission_info');
            }
        } else {
            $output .= $this->renderer->render_warning_message(get_string('nopermissionadd', 'question'));
        }
        echo $output;
    }

    /**
     * Prints the table of questions in a category with interactions
     */
    public function display_question_list(): void {
        $output = '';
        [$categoryid, $contextid] = category_condition::validate_category_param($this->pagevars['cat']);
        $category = category_condition::get_category_record($categoryid, $contextid);
        $catcontext = \context::instance_by_id($contextid);
        $page = $this->get_pagevars('qpage');
        $perpage = $this->get_pagevars('qperpage');

        $addcontexts = $this->contexts->having_cap('moodle/question:add');
        $output .= \html_writer::start_tag('fieldset', array('class' => 'invisiblefieldset', 'style' => 'display:block;'));

        $output .= $this->renderer->render_hidden_field($this->cm->id, $this->baseurl, $perpage);

        $output .= $this->renderer->render_control_buttons($catcontext, $this->has_questions_in_category(),
            $addcontexts, $category, $this->get_pagevars('filter'));

        $output .= $this->renderer->render_pagination_bar($this->pagevars, $this->baseurl, $this->totalnumber, $page,
            $perpage, true);

        $output .= $this->display_question_list_rows();

        $output .= $this->renderer->render_pagination_bar($this->pagevars, $this->baseurl, $this->totalnumber, $page,
            $perpage, false);

        $output .= $this->renderer->render_control_buttons($catcontext, $this->has_questions_in_category(),
            $addcontexts, $category, $this->get_pagevars('filter'));

        $output .= \html_writer::end_tag('fieldset');
        $output = $this->renderer->render_question_form($output);
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
        $stategroup = [];
        $stategroup[] = new \toggle_filter_checkbox('only_new_state',
            get_string('state_newplural', 'studentquiz'), false, 'sqq.state',
            ['approved'], 2, studentquiz_helper::STATE_NEW);
        $stategroup[] = new \toggle_filter_checkbox('only_changed_state',
            get_string('state_changedplural', 'studentquiz'), false, 'sqq.state',        // Fast filters.

            ['approved'], 2, studentquiz_helper::STATE_CHANGED);
        $stategroup[] = new \toggle_filter_checkbox('only_reviewable_state',
            get_string('state_reviewableplural', 'studentquiz'), false, 'sqq.state',
            ['approved'], 2, studentquiz_helper::STATE_REVIEWABLE);
        $stategroup[] = new \toggle_filter_checkbox('only_approved_state',
            get_string('state_approvedplural', 'studentquiz'), false, 'sqq.state',
            ['approved'], 2, studentquiz_helper::STATE_APPROVED);
        $stategroup[] = new \toggle_filter_checkbox('only_disapproved_state',
            get_string('state_disapprovedplural', 'studentquiz'), false, 'sqq.state',
            ['approved'], 2, studentquiz_helper::STATE_DISAPPROVED);
        $stategroup[] = new \toggle_filter_checkbox('onlynew',
            get_string('filter_label_onlynew', 'studentquiz'),
            false, 'myattempts', ['myattempts', 'myattempts_op'], 0, 0,
            get_string('filter_label_onlynew_help', 'studentquiz'));
        $stategroup[] = new \toggle_filter_checkbox('onlyanswered',
            get_string('filter_label_answered', 'studentquiz'),
            false, 'myattempts', ['myattempts', 'myattempts_op'], 1, 1,
            get_string('filter_label_onlynew_help', 'studentquiz'));
        $this->fields[] = $stategroup;

        $ownergroup = [];
        $ownergroup[] = new \toggle_filter_checkbox('onlymine',
            get_string('filter_label_onlymine', 'studentquiz'),
            false, 'q.createdby', ['createdby'], 2, $this->userid,
            get_string('filter_label_onlymine_help', 'studentquiz'));

        $ownergroup[] = new \toggle_filter_checkbox('notmine',
            get_string('filter_label_notmine', 'studentquiz'),
            false, 'q.createdby', ['createdby'], 3, $this->userid,
            get_string('filter_label_notmine_help', 'studentquiz'));
        $this->fields[]= $ownergroup;

        $difficultygroup = [];
        $difficultygroup[] = new \toggle_filter_checkbox('onlydifficultforme',
            get_string('filter_label_onlydifficultforme', 'studentquiz'),
            false, 'mydifficulty', ['mydifficulty', 'mydifficulty_op'], 1, 0.60,
            get_string('filter_label_onlydifficultforme_help', 'studentquiz', '60'));

        $difficultygroup[] = new \toggle_filter_checkbox('onlydifficult',
            get_string('filter_label_onlydifficult', 'studentquiz'),
            false, 'dl.difficultylevel', ['difficultylevel', 'difficultylevel_op'], 1, 0.60,
            get_string('filter_label_onlydifficult_help', 'studentquiz', '60'));

        $this->fields[] = $difficultygroup;
        $ratinggroup = [];
        $ratinggroup[] = new \toggle_filter_checkbox('onlygood',
            get_string('filter_label_onlygood', 'studentquiz'),
            false, 'vo.rate', ['rate', 'rate_op'], 1, 4,
            get_string('filter_label_onlygood_help', 'studentquiz', '4'));
        $this->fields[] = $ratinggroup;

        // Advanced filters.
        $advancedgroups = [];
        $advancedgroups[] = new \studentquiz_user_filter_text('tagarray', get_string('filter_label_tags', 'studentquiz'),
            true, 'tagarray');

        $states = [];
        foreach (studentquiz_helper::$statename as $num => $name) {
            if ($num == studentquiz_helper::STATE_DELETE || $num == studentquiz_helper::STATE_HIDE) {
                continue;
            }
            $states[$num] = get_string('state_'.$name, 'studentquiz');
        }
        $advancedgroups[] = new \user_filter_simpleselect('state', get_string('state_column_name', 'studentquiz'),
            true, 'state', $states);

        $advancedgroups[] = new \user_filter_number('rate', get_string('filter_label_rates', 'studentquiz'),
            true, 'rate');
        $advancedgroups[] = new \user_filter_percent('difficultylevel', get_string('filter_label_difficulty_level', 'studentquiz'),
            true, 'difficultylevel');

        $advancedgroups[] = new \user_filter_number('publiccomment', get_string('filter_label_comment', 'studentquiz'),
            true, 'publiccomment');
        $advancedgroups[] = new \studentquiz_user_filter_text('name', get_string('filter_label_question', 'studentquiz'),
            true, 'name');
        $advancedgroups[] = new \studentquiz_user_filter_text('questiontext', get_string('filter_label_questiontext', 'studentquiz'),
            true, 'questiontext');

        if ($anonymize) {
            $advancedgroups[] = new \user_filter_checkbox('createdby', get_string('filter_label_show_mine', 'studentquiz'),
                true, 'createdby');
        } else {
            $advancedgroups[] = new \studentquiz_user_filter_text('firstname', get_string('firstname'), true, 'firstname');
            $advancedgroups[] = new \studentquiz_user_filter_text('lastname', get_string('lastname'), true, 'lastname');
        }

        $advancedgroups[] = new \studentquiz_user_filter_date('timecreated', get_string('filter_label_createdate', 'studentquiz'),
            true, 'timecreated');

        $advancedgroups[] = new \user_filter_simpleselect('lastanswercorrect',
            get_string('filter_label_mylastattempt', 'studentquiz'),
            true, 'lastanswercorrect', [
                '1' => get_string('lastattempt_right', 'studentquiz'),
                '0' => get_string('lastattempt_wrong', 'studentquiz')
            ]);

        $advancedgroups[] = new \user_filter_number('myattempts', get_string('filter_label_myattempts', 'studentquiz'),
            true, 'myattempts');

        $advancedgroups[] = new \user_filter_number('mydifficulty', get_string('filter_label_mydifficulty', 'studentquiz'),
            true, 'mydifficulty');

        $advancedgroups[] = new \user_filter_number('myrate', get_string('filter_label_myrate', 'studentquiz'),
            true, 'myrate');
        $this->fields[] = $advancedgroups;
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
     *
     * @return array array of questions
     */
    public function load_questions() {
        $questionsrs = $this->load_page_questions();
        $questions = [];
        foreach ($questionsrs as $question) {
            $questions[] = $question;
        }
        $questionsrs->close();
        $this->totalnumber = $this->get_question_count();
        return $questions;
    }

    #[\Override]
    protected function load_page_questions(): \moodle_recordset {
        global $DB;
        if (!$this->pagevars['showall']) {
            return parent::load_page_questions();
        } else {
            return $DB->get_recordset_sql($this->loadsql, $this->sqlparams);
        }
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
        if ($showprivatecomment && $sort == 'mod_studentquiz\question\bank\comment_column' ||
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
     * @return \question_bank_column_base[]
     */
    protected function wanted_columns(): array {
        global $PAGE;
        $renderer = $PAGE->get_renderer('mod_studentquiz');
        $this->requiredcolumns = $renderer->get_columns_for_question_bank_view($this);
        return $this->requiredcolumns;
    }

    /**
     * Allow qbank plugins to override the column manager.
     *
     * If multiple qbank plugins define a column manager, this will pick the first one sorted alphabetically.
     *
     * @return void
     */
    protected function init_column_manager(): void {
        $this->columnmanager = new column_manager_base();
    }

    /**
     * Initialise list of menu actions specific for SQ.
     *
     * @return void
     */
    protected function init_question_actions(): void {
        $this->questionactions = [
            new sq_edit_action($this),
            new sq_preview_action($this),
            new sq_delete_action($this),
            new sq_hidden_action($this),
            new sq_pin_action($this),
        ];

    }
}
