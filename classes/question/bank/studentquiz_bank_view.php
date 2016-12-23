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
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_studentquiz\question\bank;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../../locallib.php');
require_once(dirname(__FILE__).'/question_bank_filter.php');
require_once(dirname(__FILE__).'/question_text_row.php');
require_once(dirname(__FILE__).'/vote_column.php');
require_once(dirname(__FILE__).'/difficulty_level_column.php');
require_once(dirname(__FILE__).'/tag_column.php');
require_once(dirname(__FILE__).'/performances_column.php');
require_once(dirname(__FILE__).'/comments_column.php');
require_once(dirname(__FILE__).'/approved_column.php');

/**
 * Module instance settings form
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class studentquiz_bank_view extends \core_question\bank\view {
    /**
     * @var stdClass filtered questions from database
     */
    private $questions;
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
     * Constructor assuming we already have the necessary data loaded.
     *
     * @param \core_question\bank\question_edit_contexts $contexts
     * @param \core_question\bank\moodle_url $pageurl
     * @param object $course
     * @param null|object $cm
     */
    public function __construct($contexts, $pageurl, $course, $cm) {
        parent::__construct($contexts, $pageurl, $course, $cm);
        $this->set_filter_form_fields();
        $this->initialize_filter_form($pageurl);
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
     */
    public function display($tabname, $page, $perpage, $cat,
                            $recurse, $showhidden, $showquestiontext) {
        global $OUTPUT;

        $editcontexts = $this->contexts->having_one_edit_tab_cap($tabname);
        array_unshift($this->searchconditions, new \mod_studentquiz\condition\studentquiz_condition(
            $cat, $recurse, $editcontexts, $this->baseurl, $this->course));

        // This function can be moderately slow with large question counts and may time out.
        // We probably do not want to raise it to unlimited, so randomly picking 5 minutes.
        // Note: We do not call this in the loop because quiz ob_ captures this function (see raise() PHP doc).
        \core_php_time_limit::raise(300);
        $this->build_query();

        $this->questions = $this->load_questions();
        $this->update_questions($this->load_questions());
        $this->questions = $this->filter_questions($this->questions);
        $this->totalnumber = count($this->questions);

        if ($this->process_actions_needing_ui()) {
            return;
        }

        echo $OUTPUT->heading($this->cm->name, 2);
        $this->create_new_question_form_ext($cat);

        if ($this->has_questions_in_category() || $this->isfilteractive) {
            echo $this->filterform->render();
        }

        echo '<form method="post" action="view.php">';

        // Continues with list of questions.
        $this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
            $this->baseurl, $cat, $this->cm,
            null, $page, $perpage, $showhidden, $showquestiontext,
            $this->contexts->having_cap('moodle/question:add'));

        echo '</form>';
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
                $approveselected = required_param('approveselected', PARAM_RAW);
                if ($confirm == md5($approveselected)) {
                    if ($questionlist = explode(',', $approveselected)) {
                        // For each question either hide it if it is in use or delete it.
                        foreach ($questionlist as $questionid) {
                            $questionid = (int)$questionid;

                            $approved = $DB->get_field('studentquiz_question', 'approved', array('questionid' => $questionid));
                            $DB->set_field('studentquiz_question', 'approved', !$approved, array('questionid' => $questionid));

                            mod_studentquiz_notify_approving($questionid, $this->course);
                        }
                    }
                    redirect($this->baseurl);
                } else {
                    print_error('invalidconfirm', 'question');
                }
            }
        }

        // Move selected questions to new category.
        if (optional_param('move', false, PARAM_BOOL) and confirm_sesskey()) {
            $category = required_param('category', PARAM_SEQUENCE);
            list($tocategoryid, $contextid) = explode(',', $category);
            if (! $tocategory = $DB->get_record('question_categories', array('id' => $tocategoryid, 'contextid' => $contextid))) {
                print_error('cannotfindcate', 'question');
            }
            $tocontext = \context::instance_by_id($contextid);
            require_capability('moodle/question:add', $tocontext);
            $rawdata = (array) data_submitted();
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
     * (Copy from parent class - modified several code snippets)
     *
     * Create the SQL query to retrieve the indicated questions, based on
     * \core_question\bank\search\condition filters.
     */
    protected function build_query() {
        // Get the required tables and fields.
        $joins = array();
        $fields = array('q.hidden', 'q.category');
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

        // Build the order by clause.
        $sorts = array();
        foreach ($this->sort as $sort => $order) {
            list($colname, $subsort) = $this->parse_subsort($sort);
            $sorts[] = $this->requiredcolumns[$colname]->sort_expression($order < 0, $subsort);
        }

        // Build the where clause.
        $tests = array('q.parent = 0');
        $this->sqlparams = array();
        foreach ($this->searchconditions as $searchcondition) {
            if ($searchcondition->where()) {
                $tests[] = '((' . $searchcondition->where() .'))';
            }
            if ($searchcondition->params()) {
                $this->sqlparams = array_merge($this->sqlparams, $searchcondition->params());
            }
        }
        $this->sqlparams['filter'] = '';
        if ($adddata = $this->filterform->get_data()) {
            foreach ($this->filterform->get_fields() as $field) {
                $data = $field->check_data($adddata);

                if ($data === false) {
                    continue;
                }

                $this->isfilteractive = true;
                $sqldata = $field->get_sql_filter($data);

                if ($field->_name == 'firstname' && !mod_studentquiz_check_created_permission($this->cm->id)) {
                    continue;
                }

                if ($field->_name == 'lastname' && !mod_studentquiz_check_created_permission($this->cm->id)) {
                    continue;
                }

                if ($field->_name == 'tagname') {
                    $this->tagnamefield = $sqldata;
                    continue;
                }

                // The user_filter_checkbox class has a buggy get_sql_filter function.
                if ($field->_name == 'createdby' || $field->_name == 'approved') {
                    $sqldata = array($field->_name . ' = ' . intval($data['value']), array());
                }

                if (is_array($sqldata)) {
                    $sqldata[0] = str_replace($field->_name,
                                              $this->get_sql_table_prefix($field->_name) . $field->_name, $sqldata[0]);
                    $tests[] = '((' . $sqldata[0] . '))';
                    $this->sqlparams = array_merge($this->sqlparams, $sqldata[1]);
                }
            }
        }

        // Build the complete SQL query.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $tests);
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
     * Extends the question form with custom add question button
     * @param string $cat question category
     */
    protected function create_new_question_form_ext($cat) {
        $category = $this->get_current_category($cat);
        list($categoryid, $contextid) = explode(',', $cat);

        $catcontext = \context::instance_by_id($contextid);

        $canadd = has_capability('moodle/question:add', $catcontext);
        $this->create_new_question_form($category, $canadd);
    }

    /**
     * Create new default question form
     * @param string $category question category
     * @param bool $canadd capability state
     */
    protected function create_new_question_form($category, $canadd) {
        echo '<div class="createnewquestion">';
        $caption = get_string('createnewquestion', 'studentquiz');
        if (!$this->has_questions_in_category()) {
            $caption = get_string('createnewquestionfirst', 'studentquiz');
        }
        if ($canadd) {
            create_new_question_button($category->id, $this->editquestionurl->params(),
                $caption);
        } else {
            print_string('nopermissionadd', 'question');
        }
        echo '</div>';
    }

    /**
     * (Copy from parent class - modified several code snippets)
     * Display the controls at the bottom of the list of questions.
     * @param int      $totalnumber Total number of questions that might be shown (if it was not for paging).
     * @param bool     $recurse     Whether to include subcategories.
     * @param stdClass $category    The question_category row from the database.
     * @param context  $catcontext  The context of the category being displayed.
     * @param array    $addcontexts contexts where the user is allowed to add new questions.
     */
    protected function display_bottom_controls($totalnumber, $recurse, $category, \context $catcontext, array $addcontexts) {
        $caneditall = has_capability('moodle/question:editall', $catcontext);
        $canmoveall = has_capability('moodle/question:moveall', $catcontext);

        echo '<div class="modulespecificbuttonscontainer">';
        echo '<strong>&nbsp;' . get_string('withselected', 'question') . ':</strong><br />';

        if ($this->has_questions_in_category()) {
            echo '<input class="btn btn-primary form-submit" type="submit" name="startquiz" value="'
                 . get_string('start_quiz_button', 'studentquiz') . "\" />\n";
        }

        if ($caneditall) {
            echo '<input type="submit" class="btn" name="approveselected" value="'
                    . get_string('approve', 'studentquiz') . "\" />\n";
            echo '<input type="submit" class="btn" name="deleteselected" value="' . get_string('delete') . "\" />\n";
        }

        if ($canmoveall && count($addcontexts)) {
            echo '<input type="submit" class="btn" name="move" value="' . get_string('moveto', 'question') . "\" />\n";
            question_category_select_menu($addcontexts, false, 0, "{$category->id},{$category->contextid}");
        }

        echo "</div>\n";
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
     */
    protected function display_question_list($contexts, $pageurl, $categoryandcontext,
                                             $cm = null, $recurse=1, $page=0, $perpage=100, $showhidden=false,
                                             $showquestiontext = false, $addcontexts = array()) {
        $category = $this->get_current_category($categoryandcontext);

        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);

        if ($this->totalnumber == 0) {
            return;
        }

        $questions = $this->load_page_questions_array($this->questions, $page, $perpage);
        $pagingbar = $this->create_paging_bar($pageurl, $page, $perpage);

        echo '<fieldset class="invisiblefieldset" style="display: block;">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo "<input name='id' type='hidden' value='".$this->cm->id ."' />";
        echo "<input name='filtered_question_ids' type='hidden' value='". implode(',', $this->get_filtered_question_ids()) ."' />";
        echo \html_writer::input_hidden_params($this->baseurl);

        $this->display_bottom_controls($this->totalnumber , $recurse, $category, $catcontext, $addcontexts);
        echo $pagingbar;

        echo '<div class="categoryquestionscontainer">';
        $this->start_table();
        $rowcount = 0;
        foreach ($questions as $question) {
            $this->print_table_row($question, $rowcount);
            $rowcount += 1;
        }
        $this->end_table();
        echo "</div>\n";

        echo $pagingbar;
        $this->display_bottom_controls($this->totalnumber , $recurse, $category, $catcontext, $addcontexts);

        echo '</fieldset>';

        echo '<script>';
        // Select all questions.
        echo 'var el = document.querySelectorAll(".checkbox > input[type=checkbox]");
                for (var i=0; i<el.length; i++) {
                  el[i].checked = true;
              }';
        // Change both move-to dropdown box at when selection changes.
        echo 'var elements = document.getElementsByName(\'category\');
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
        echo '</script>';
    }

    /**
     * (Copy from parent class - modified several code snippets)
     * @return mixed
     */
    protected function wanted_columns() {
        global $CFG;

        $CFG->questionbankcolumns = 'checkbox_column,question_type_column'
            . ',question_name_column,mod_studentquiz\\bank\\question_text_row,edit_action_column,copy_action_column,'
            . 'preview_action_column,delete_action_column,creator_name_column,'
            . 'mod_studentquiz\\bank\\approved_column,'
            . 'mod_studentquiz\\bank\\tag_column,mod_studentquiz\\bank\\vote_column,'
            . 'mod_studentquiz\\bank\\difficulty_level_column,'
            . 'mod_studentquiz\\bank\\practice_column,'
            . 'mod_studentquiz\\bank\\comment_column';

        return parent::wanted_columns();
    }

    /**
     * Set filter form fields
     */
    private function set_filter_form_fields() {
        $this->fields = array();

        // Standard filters.
        $this->fields[] = new \user_filter_number('vote', get_string('filter_label_votes', 'studentquiz'), false, 'vote');
        $this->fields[] = new \user_filter_number('difficultylevel', get_string('filter_label_difficulty_level',
            'studentquiz'), false, 'difficultylevel');
        $this->fields[] = new \user_filter_text('tagname', get_string('filter_label_tags', 'studentquiz'), false, 'tagname');

        // Advanced filters.
        $this->fields[] = new \user_filter_number('practice', get_string('filter_label_practice', 'studentquiz'), true, 'practice');
        $this->fields[] = new \user_filter_number('comment', get_string('filter_label_comment', 'studentquiz'), true, 'comment');
        $this->fields[] = new \user_filter_text('name', get_string('filter_label_question', 'studentquiz'), true, 'name');
        $this->fields[] = new \user_filter_text('questiontext', get_string('filter_label_questiontext', 'studentquiz'),
            true, 'questiontext');

        if (mod_studentquiz_is_anonym($this->cm->id) && !mod_studentquiz_check_created_permission($this->cm->id)) {
            $this->fields[] = new \user_filter_checkbox('createdby', get_string('filter_label_show_mine', 'studentquiz'),
                true, 'createdby');
        } else {
            $this->fields[] = new \user_filter_text('firstname', get_string('filter_label_firstname', 'studentquiz'),
                true, 'firstname');
            $this->fields[] = new \user_filter_text('lastname', get_string('filter_label_surname', 'studentquiz'),
                true, 'lastname');
        }
        $this->fields[] = new \user_filter_checkbox('approved', get_string('filter_label_approved', 'studentquiz'),
            true, 'approved');

        $this->fields[] = new \user_filter_date('timecreated', get_string('filter_label_createdate', 'studentquiz'),
                                                true, 'timecreated');
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
     * @return \moodle_recordset
     */
    private function load_questions() {
        global $DB;
        return $DB->get_recordset_sql($this->loadsql, $this->sqlparams);
    }

    /**
     * Filter question with the filter option
     * @param stdClass $questions
     * @return array questions
     */
    private function filter_questions($questions) {
        global $USER;

        $filteredquestions = array();
        foreach ($questions as $question) {
            $question->tagname = '';
            if (
                mod_studentquiz_is_anonym($this->cm->id) &&
                $question->createdby != $USER->id
            ) {
                $question->creatorfirstname = get_string('creator_anonym_firstname', 'studentquiz');
                $question->creatorlastname = get_string('creator_anonym_lastname', 'studentquiz');
            }

            $count = $this->get_question_tag_count($question->id);
            if ($count) {
                foreach ($this->get_question_tag($question->id) as $tag) {
                    $question->tagname .= ', '.$tag->name;
                }
                $question->tagname = substr($question->tagname, 2);
            }
            if (!$this->isfilteractive) {
                $filteredquestions[] = $question;
            } else {
                if (isset($this->tagnamefield)) {
                    if ($this->show_question($question->id, $count)) {
                        $filteredquestions[] = $question;
                    }
                } else {
                    $filteredquestions[] = $question;
                }
            }
        }
        return $filteredquestions;
    }

    /**
     * Update our studenquiz_question table with the question list.
     *
     * @param stdClass $questions
     */
    private function update_questions($questions) {
        global $DB;
        $sqlparams = array();
        $sql = 'SELECT questionid FROM {studentquiz_question} q';
        $studentquizquestions = $DB->get_recordset_sql($sql, $sqlparams);

        $questionids = array();
        foreach ($studentquizquestions as $studentquizquestion) {
            array_push($questionids, $studentquizquestion->questionid);
        }

        foreach ($questions as $question) {
            if (!in_array($question->id, $questionids)) {
                $DB->insert_record('studentquiz_question', array('questionid' => $question->id, 'approved' => false));
            }
        }
    }

    /**
     * Get the count of the connected tags with the question
     * @param int $id
     * @param bool $withfilter
     * @return int
     * @throws \coding_exception
     */
    private function get_question_tag_count($id, $withfilter = true) {
        global $DB;
        $sqlparams = array();

        $sqlext = '';
        if (isset($this->tagnamefield)) {
            $sqlext = str_replace ( 'tagname' , 't.name' , $this->tagnamefield[0]);
            $sqlparams = $this->tagnamefield[1];

            $sqlext = ' AND '. '((' . $sqlext  .'))';
        }

        $sql = 'SELECT count(1)'
            .' FROM {tag} t'
            .' JOIN {tag_instance} ti'
            .' ON t.id = ti.tagid'
            .' WHERE ti.itemtype = \'question\' AND ti.itemid = :qid';

        if ($withfilter) {
            $sql .= $sqlext;
        }

        $sqlparams['qid'] = $id;

        return $DB->count_records_sql($sql, $sqlparams);
    }

    /**
     * Get the sql table prefix
     * @param string $name
     * @return string return sql prefix
     */
    private function get_sql_table_prefix($name) {
        switch($name){
            case 'difficultylevel':
                return 'dl.';
            case 'vote':
                return 'vo.';
            case 'practice':
                return 'pr.';
            case 'comment':
                return 'co.';
            case 'approved':
                return 'ap.';
            case 'firstname':
            case 'lastname':
                return 'uc.';
            default;
                return 'q.';
        }
    }

    /**
     * Get all filtered question ids qith q prefix
     * @return array question ids with q prefix
     */
    private function get_filtered_question_ids() {
        $questionids = array();
        foreach ($this->questions as $question) {
            $questionids[] = 'q' . $question->id;
        }
        return $questionids;
    }

    /**
     * Slice question list into array per page of questions.
     *
     * @param stdClass $question
     * @param int $page
     * @param int $perpage
     * @return array questions
     */
    private function load_page_questions_array($question, $page, $perpage) {
        if ($page * $perpage > count($question)) {
            $questions = array_slice ($question , 0, $perpage, true);
        } else {
            $questions = array_slice ($question , $page * $perpage, $perpage, true);
        }

        return $questions;
    }

    /**
     * Get all question tags
     * @param int $id
     * @return \moodle_recordset all tags connected with the question
     */
    private function get_question_tag($id) {
        global $DB;
        $sqlparams = array();

        $sql = 'SELECT t.name, ti.itemid'
            .' FROM {tag} t'
            .' JOIN {tag_instance} ti'
            .' ON t.id = ti.tagid'
            .' WHERE ti.itemtype = \'question\' AND ti.itemid = :qid';

        $sqlparams['qid'] = $id;

        return $DB->get_recordset_sql($sql, $sqlparams);
    }

    /**
     * Check if show question or not
     * @param int $id
     * @param int $count
     * @return bool question show or not
     */
    private function show_question($id, $count) {
        $countfiltered = $count;
        $count = $this->get_question_tag_count($id, false);

        if (strpos($this->tagnamefield[0], 'NOT LIKE') !== false) {
            if ($count == $countfiltered) {
                return true;
            }
            return false;
        }

        if (strpos($this->tagnamefield[0], 'LIKE') !== false) {
            if ($countfiltered > 0) {
                return true;
            }
            return false;
        }

        if (strpos($this->tagnamefield[0], '=' && $this->tagnamefield[1]['ex_text0']) == '') {
            if ($count == 0) {
                return true;
            }
            return false;
        }

        return false;
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

}
