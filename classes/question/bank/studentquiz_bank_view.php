<?php
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
require_once(__DIR__ . '/vote_column.php');
require_once(__DIR__ . '/difficulty_level_column.php');
require_once(__DIR__ . '/tag_column.php');
require_once(__DIR__ . '/performances_column.php');
require_once(__DIR__ . '/comments_column.php');
require_once(__DIR__ . '/approved_column.php');
require_once(__DIR__ . '/anonym_creator_name_column.php');
require_once(__DIR__ . '/preview_column.php');

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


    private $pagevars;

    /**
     * Constructor assuming we already have the necessary data loaded.
     *
     * @param \core_question\bank\question_edit_contexts $contexts
     * @param \core_question\bank\moodle_url $pageurl
     * @param object $course
     * @param null|object $cm
     * @param object $studentquiz
     */
    public function __construct($contexts, $pageurl, $course, $cm, $studentquiz, $pagevars) {
        parent::__construct($contexts, $pageurl, $course, $cm);
        $this->pagevars = $pagevars;
        $this->studentquiz = $studentquiz;
        $this->set_filter_form_fields($this->is_anonymized());
        $this->initialize_filter_form($pageurl);
        // Init search conditions with filterform state.
        $cateorycondition = new \core_question\bank\search\category_condition(
                $pagevars['cat'], $pagevars['recurse'], $contexts, $pageurl, $course);
        $studentquizcondition = new \mod_studentquiz\condition\studentquiz_condition(
            $this->filterform, $pagevars, $cm, $studentquiz);
        $this->isfilteractive = $studentquizcondition->is_filter_active();
        $this->searchconditions = array ($cateorycondition, $studentquizcondition);
    }

    /**
     * (Copy from parent class - modified several code snippets)
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
     * @return html output
     */
    public function display($tabname, $page, $perpage, $cat,
                            $recurse, $showhidden, $showquestiontext) {
        $output = '';

        $this->build_query();

        // Get result set.
        $questions = $this->load_questions($page, $perpage  );

        $tags = mod_studentquiz_get_tags_by_question_ids($this->displayedquestionsids);

        // Annotate questions with
        foreach ($questions as $question) {
            if(array_key_exists($question->id, $tags)) {
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
            $output .= $this->filterform->render();
        }

        if (count($this->questions) > 0) {
            $output .= '<form method="post" action="view.php">';

            // Continues with list of questions.
            $output .= $this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
                $this->baseurl, $cat, $this->cm,
                null, $page, $perpage, $showhidden, $showquestiontext,
                $this->contexts->having_cap('moodle/question:add'));

            $output .= '</form>';
        } else {
            global $OUTPUT;
            if ($this->isfilteractive) {
                $output .= $OUTPUT->notification(get_string('no_questions_filter', 'studentquiz'), 'notifysuccess');
            } else {
                $output .= $OUTPUT->notification(get_string('no_questions_add', 'studentquiz'), 'notifysuccess');
            }
        }
        return $output;
    }

    /**
     * (Copy from parent class - modified several code snippets)
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
                // TODO: What? Security by obscurity? Needs a look closer, probably best by using the capability :manage
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
            // TODO: Seen that somewhere else, extract in common function
            $questionids = array();
            foreach (array_keys($rawdata) as $key) {  // Parse input for question ids.
                if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
                    $key = $matches[1];
                    $questionids[] = $key;
                }
            }
            if ($questionids) {
                list($usql, $params) = $DB->get_in_or_equal($questionids);
                $questions = $DB->get_records_sql("
                        SELECT q.*, c.contextid
                        FROM {question} q
                        JOIN {question_categories} c ON c.id = q.category
                        WHERE q.id {$usql}", $params);
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
     * (Copy from parent class - modified several code snippets)
     *
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
                    $questionnames .= '<br />'.get_string('questionsinuse', 'question');
                }

                $deleteurl = new \moodle_url($baseurl, array('deleteselected' => $questionlist, 'confirm' => md5($questionlist),
                    'sesskey' => sesskey()));

                $continue = new \single_button($deleteurl, get_string('delete'), 'post');

                $output = $OUTPUT->confirm(get_string('deletequestionscheck', 'question', $questionnames), $continue, $baseurl);
            } else if (optional_param('approveselected', false, PARAM_BOOL)) {
                // Add an explanation about questions in use.
                if ($inuse) {
                    $questionnames .= '<br />'.get_string('questionsinuse', 'studentquiz');
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
     * (Copy from parent class - modified several code snippets)
     *
     * Create the SQL query to retrieve the indicated questions, based on
     * \core_question\bank\search\condition filters.
     */
    protected function build_query() {
        // Hard coded setup
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
            $sorts[] = 'q.timecreated DESC';
        }

        // Build the where clause and load params from search conditions.
        foreach ($this->searchconditions as $searchcondition) {
            if(!empty($searchcondition->where())) {
                $tests[] = $searchcondition->where();
            }
            if(!empty($searchcondition->params())) {
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
        $output .= '<div class="createnewquestion">';

        $caption = get_string('createnewquestion', 'studentquiz');

        if ($canadd) {
            $returnurl = new \moodle_url('/mod/studentquiz/view.php', array(
                'id' => $this->studentquiz->coursemodule
            ));
            $params = array(
                // TODO: MAGIC CONSTANT
                'returnurl' => $returnurl->out_as_local_url(false),
                'category' => $categoryid,
                'cmid' => $this->studentquiz->coursemodule,
            );

            $url = new \moodle_url('/question/addquestion.php', $params);

            $allowedtypes = (empty($this->studentquiz->allowedqtypes))? 'ALL': $this->studentquiz->allowedqtypes;
            $allowedtypes = ($allowedtypes == 'ALL')? null: explode(',', $allowedtypes);
            $qtypecontainer = \html_writer::div(
                print_choose_qtype_to_add_form(array(), $allowedtypes, '',
                array('id' => 'qtypechoicecontainer')
            ));
            $output .= \html_writer::div(
                $OUTPUT->render(new \single_button($url, $caption, 'get', true)) .
                $qtypecontainer, 'createnewquestion'
            );
        } else {
            $output .= get_string('nopermissionadd', 'question');
        }
        $output .= '</div>';
        return $output;
    }

    /**
     * (Copy from parent class - modified several code snippets)
     * Display the controls at the bottom of the list of questions.
     * @param int      $totalnumber Total number of questions that might be shown (if it was not for paging).
     * @param bool     $recurse     Whether to include subcategories.
     * @param stdClass $category    The question_category row from the database.
     * @param context  $catcontext  The context of the category being displayed.
     * @param array    $addcontexts contexts where the user is allowed to add new questions.
     * @return html output
     */
    protected function display_bottom_controls($totalnumber, $recurse, $category, \context $catcontext, array $addcontexts) {
        $output = '';
        $caneditall = has_capability('moodle/question:editall', $catcontext);
        $canmoveall = has_capability('moodle/question:moveall', $catcontext);

        $output .= '<div class="modulespecificbuttonscontainer">';
        $output .= '<strong>&nbsp;' . get_string('withselected', 'question') . ':</strong><br />';

        if ($this->has_questions_in_category()) {
            $output .= '<input class="btn btn-primary form-submit" type="submit" name="startquiz" value="'
                 . get_string('start_quiz_button', 'studentquiz') . "\" />\n";
        }

        if ($caneditall) {
            $output .= '<input type="submit" class="btn" name="approveselected" value="'
                    . get_string('approve_toggle', 'studentquiz') . "\" />\n";
            $output .= '<input type="submit" class="btn" name="deleteselected" value="' . get_string('delete') . "\" />\n";
        }

        $output .= "</div>\n";
        return $output;
    }

    /**
     * (Copy from parent class - modified several code snippets)
     * Prints the table of questions in a category with interactions
     *
     * @param array      $contexts    Not used!
     * @param moodle_url $pageurl     The URL to reload this page.
     * @param string     $categoryandcontext 'categoryID,contextID'.
     * @param stdClass   $cm          Not used!
     * @param bool       $recurse     Whether to include subcategories.
     * @param int        $page        The number of the page to be displayed
     * @param int        $perpage     Number of questions to show per page
     * @param bool       $showhidden  whether deleted questions should be displayed.
     * @param bool       $showquestiontext whether the text of each question should be shown in the list. Deprecated.
     * @param array      $addcontexts contexts where the user is allowed to add new questions.
     * @return html output
     */
    protected function display_question_list($contexts, $pageurl, $categoryandcontext,
                                             $cm = null, $recurse=1, $page=0, $perpage=100, $showhidden=false,
                                             $showquestiontext = false, $addcontexts = array()) {
        $output = '';

        $category = $this->get_current_category($categoryandcontext);

        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);

        $pagingbar = $this->create_paging_bar($pageurl, $page, $perpage);

        $output .= '<fieldset class="invisiblefieldset" style="display: block;">';
        $output .=  '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        $output .=  "<input name='id' type='hidden' value='".$this->cm->id ."' />";
        $output .=  "<input name='filtered_question_ids' type='hidden' value='". implode(',', $this->get_filtered_question_ids()) ."' />";
        $output .=  \html_writer::input_hidden_params($this->baseurl);

        $output .= $this->display_bottom_controls($this->totalnumber , $recurse, $category, $catcontext, $addcontexts);

        $output .= $pagingbar;

        $output .= $this->display_question_list_rows($page);

        $output .= $pagingbar;

        $output .= $this->display_bottom_controls($this->totalnumber , $recurse, $category, $catcontext, $addcontexts);

        $output .=  '</fieldset>';

        $output .= $this->display_javascript_snippet();

        return $output;
    }

    protected function display_javascript_snippet() {
        $output = '';
        $output .=  '<script>';
        // Select all questions.
        $output .=  'var el = document.querySelectorAll(".checkbox > input[type=checkbox]");
                for (var i=0; i<el.length; i++) {
                  el[i].checked = true;
              }';
        // Change both move-to dropdown box at when selection changes.
        $output .=  'var elements = document.getElementsByName(\'category\');
              for(e in elements) {
                elements[e].onchange = function() {
                  var elms = document.getElementsByName(\'category\');
                  for(el in elms) {
                    if(typeof elms[el] !== \'undefined\' && elms[el] !== this) {
                      elms[el].value = this.value;
                    }
                  }
                }
              }';
        $output .=  '</script>';
        return $output;
    }

    protected function display_question_list_rows() {
        $output = '';
        $output .=  '<div class="categoryquestionscontainer">';
        ob_start();
        $this->start_table();
        $rowcount = 0;
        foreach($this->questions as $question) {
            $this->print_table_row($question, $rowcount);
            $rowcount++;
        }
        $this->numberofdisplayedquestions = $rowcount;
        $this->end_table();
        $output .= ob_get_contents();
        ob_end_clean();
        $output .= "</div>\n";
        return $output;
    }

    /**
     * (Copy from parent class - modified several code snippets)
     * @return mixed
     */
    protected function wanted_columns() {
        global $CFG;
        $CFG->questionbankcolumns = 'checkbox_column,question_type_column,'
            . 'mod_studentquiz\\bank\\approved_column,'
            . 'question_name_column,'
            . 'mod_studentquiz\\bank\\question_text_row,'
            . 'edit_action_column,copy_action_column,'
            . 'mod_studentquiz\\bank\\preview_column,'
            . 'delete_action_column,'
            . 'mod_studentquiz\\bank\\anonym_creator_name_column,'
            . 'mod_studentquiz\\bank\\tag_column,'
            . 'mod_studentquiz\\bank\\practice_column,'
            . 'mod_studentquiz\\bank\\difficulty_level_column,'
            . 'mod_studentquiz\\bank\\vote_column,'
            . 'mod_studentquiz\\bank\\comment_column';
        return parent::wanted_columns();
    }

    /**
     * Set filter form fields
     * @param bool $anonymize if false, questions can get filtered by author last name and first name instead by own userid only.
     */
    private function set_filter_form_fields($anonymize = true) {
        $this->fields = array();

        // Fast filters.
        $this->fields[] = new \toggle_filter_checkbox('onlygood',
            \html_writer::span(get_string('filter_label_onlygood', 'studentquiz')),
                false, 'vo.vote', array('vote', 'vote_op'), 1, 3);
        $this->fields[] = new \toggle_filter_checkbox('onlydifficult',
            \html_writer::span(get_string('filter_label_onlydifficult', 'studentquiz')),
            false, 'dl.difficultylevel', array('difficultylevel', 'difficultylevel_op'), 1, 0.5);
        $this->fields[] = new \toggle_filter_checkbox('onlynew',
            \html_writer::span(get_string('filter_label_onlynew', 'studentquiz')),
            false, 'myatts.myattempts', array('myattempts', 'myattempts_op'), 0, 0);

        $this->fields[] = new \toggle_filter_checkbox('onlyapproved',
            \html_writer::span(get_string('filter_label_onlyapproved', 'studentquiz')),
            false, 'ap.approved', array('approved', 'approved_op'), 1, 1);

        // Standard filters.
        $this->fields[] = new \user_filter_tag('tagname', get_string('filter_label_tags', 'studentquiz'),
            true, 'tagname');

        $this->fields[] = new \user_filter_simpleselect('approved', get_string('filter_label_approved', 'studentquiz'),
            true, 'approved', array(
                true => get_string('approved', 'studentquiz'),
                false => get_string('not_approved', 'studentquiz')
            ));

        // Advanced filters.
        $this->fields[] = new \user_filter_number('vote', get_string('filter_label_votes', 'studentquiz'),
            true, 'vote');
        $this->fields[] = new \user_filter_number('difficultylevel', get_string('filter_label_difficulty_level', 'studentquiz'),
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

        $this->fields[] = new \user_filter_number('myvote', get_string('filter_label_myvote', 'studentquiz'),
            true, 'myvote');
    }

    /**
     * Initialize filter form
     * @param moodle_url $pageurl
     * @throws \coding_exception missing url param exception
     */
    private function initialize_filter_form($pageurl) {
        $this->isfilteractive = false;
        $this->set_filter_post_data();

        $reset = optional_param('resetbutton', false, PARAM_ALPHA);
        if ($reset) {
            $this->reset_filter();
        }

        $createdby = optional_param('createdby', false, PARAM_INT);
        if ($createdby) {
            $this->set_createdby_user_id();
        }

        $this->modify_base_url();
        $this->filterform = new \mod_studentquiz_question_bank_filter_form(
            $this->fields,
            $pageurl->out(),
            array('cmid' => $this->cm->id)
        );
        $this->filterform->set_defaults();
    }

    /**
     * Set data for filter recognition
     */
    private function set_filter_post_data() {
        foreach ($this->fields as $field) {
            if (isset($_GET[$field->_name])) {
                $_POST[$field->_name] = $_GET[$field->_name];
            }

            if (isset($_GET[$field->_name . '_op'])) {
                $_POST[$field->_name . '_op'] = $_GET[$field->_name . '_op'];
            }
        }

        if (isset($_POST['timecreated_sdt'])) {
            $_POST['timecreated_sdt']['enabled'] = '1';
            $_POST['timecreated_sdt']['day'] = $_GET['timecreated_sdt_day'];
            $_POST['timecreated_sdt']['month'] = $_GET['timecreated_sdt_month'];
            $_POST['timecreated_sdt']['year'] = $_GET['timecreated_sdt_year'];
        }

        if (isset($_POST['timecreated_edt'])) {
            $_POST['timecreated_edt']['enabled'] = '1';
            $_POST['timecreated_edt']['day'] = $_GET['timecreated_edt_day'];
            $_POST['timecreated_edt']['month'] = $_GET['timecreated_edt_month'];
            $_POST['timecreated_edt']['year'] = $_GET['timecreated_edt_year'];
        }

        if (isset($_GET['createdby'])) {
            $_POST['createdby'] = '1';
        }
    }

    /**
     * Reset the filter
     */
    private function reset_filter() {
        foreach ($this->fields as $field) {
            $_POST[$field->_name] = '';
            $_POST[$field->_name . '_op'] = '0';
        }

        unset($_POST['timecreated_sdt']);
        unset($_POST['timecreated_edt']);
        unset($_POST['createdby']);
    }

    /**
     * Set createby POST data
     */
    private function set_createdby_user_id() {
        global $USER;
        $_POST['createdby'] = $USER->id;
    }

    /**
     * Modify base url for ordering
     */
    private function modify_base_url() {
        foreach ($this->fields as $field) {
            if (isset($_POST[$field->_name])) {
                $this->baseurl->param($field->_name, $_POST[$field->_name]);
            }

            if (isset($_POST[$field->_name . '_op'])) {
                $this->baseurl->param($field->_name . '_op', $_POST[$field->_name . '_op']);
            }
        }

        if (isset($_POST['timecreated_sdt'])) {
            $this->baseurl->param('timecreated_sdt_day', $_POST['timecreated_sdt']['day']);
            $this->baseurl->param('timecreated_sdt_month', $_POST['timecreated_sdt']['month']);
            $this->baseurl->param('timecreated_sdt_year', $_POST['timecreated_sdt']['year']);
        }

        if (isset($_POST['timecreated_edt'])) {
            $this->baseurl->param('timecreated_edt_day', $_POST['timecreated_edt']['day']);
            $this->baseurl->param('timecreated_edt_month', $_POST['timecreated_edt']['month']);
            $this->baseurl->param('timecreated_edt_year', $_POST['timecreated_edt']['year']);
        }

        if (isset($_POST['createdby'])) {
            $this->baseurl->param('createdby', $_POST['createdby']);
        }
    }

    /**
     * Load question from database
     * @return pqginated array of questions
     */
    private function load_questions($page, $perpage) {
        global $DB;
        $rs =  $DB->get_recordset_sql($this->loadsql, $this->sqlparams);

        $counterquestions = 0;
        $numberofdisplayedquestions = 0;
        $rs->rewind();

        // Skip Questions on previous pages.
        while($rs->valid() && $counterquestions < $page * $perpage) {
            $rs->next();
            $counterquestions++;
        }

        // Reset and start from 0 if page was empty
        if($counterquestions < $page * $perpage) {
            $rs->rewind();
        }

        //
        // Unfortunately we cant just render the questions directly, because we need
        // To annotate tags first.
        //
        $questions = array();
        // Load questions
        while($rs->valid() && $numberofdisplayedquestions < $perpage) {
            $question = $rs->current();
            $numberofdisplayedquestions++;
            $counterquestions++;
            $this->displayedquestionsids[] = $question->id;
            $rs->next();
            $questions[] = $question;
        }

        // Iterate to end.
        while($rs->valid()) {
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
     * @param $pageurl
     * @param $page
     * @param $perpage
     * @return array
     */
    private function create_paging_bar($pageurl, $page, $perpage) {
        global $OUTPUT;

        $pageingurl = new \moodle_url('view.php');
        $pageingurl->params($this->baseurl->params());

        $pagingbar = new \paging_bar($this->totalnumber, $page, $perpage, $pageingurl);
        $pagingbar->pagevar = 'qpage';
        $pagingshowall = '';
        if ($this->totalnumber > DEFAULT_QUESTIONS_PER_PAGE) {
            if ($perpage == DEFAULT_QUESTIONS_PER_PAGE) {
                $url = new \moodle_url('view.php', array_merge($pageurl->params(),
                    array('qperpage' => MAXIMUM_QUESTIONS_PER_PAGE)));
                if ($this->totalnumber > MAXIMUM_QUESTIONS_PER_PAGE) {
                    $showall = '<a href="' . $url . '">'
                        . get_string('showperpage', 'moodle', MAXIMUM_QUESTIONS_PER_PAGE) . '</a>';
                } else {
                    $showall = '<a href="' . $url . '">' . get_string('showall', 'moodle', $this->totalnumber) . '</a>';
                }
            } else {
                $url = new \moodle_url('view.php', array_merge($pageurl->params(),
                    array('qperpage' => DEFAULT_QUESTIONS_PER_PAGE)));
                $showall = '<a href="' . $url . '">' . get_string('showperpage', 'moodle', DEFAULT_QUESTIONS_PER_PAGE) . '</a>';
            }
            $pagingshowall = "<div class='paging'>{$showall}</div>";
        }

        $pagingbaroutput  = '<div class="categorypagingbarcontainer">';
        $pagingbaroutput .= $OUTPUT->render($pagingbar);
        $pagingbaroutput .= $pagingshowall;
        $pagingbaroutput .= '</div>';

        return $pagingbaroutput;
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
        if(has_capability('mod/studentquiz:unhideanonymous', $context)) {
            return false;
        }
        // Instance is anonymized and isn't allowed to unhide that.
        return true;
    }
}
