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
require_once(dirname(__FILE__).'/vote_column.php');
require_once(dirname(__FILE__).'/difficulty_level_column.php');
require_once(dirname(__FILE__).'/tag_column.php');
require_once(dirname(__FILE__).'/question_bank_filter.php');
require_once(dirname(__FILE__).'/question_text_row.php');

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
    public function     __construct($contexts, $pageurl, $course, $cm) {
        parent::__construct($contexts, $pageurl, $course, $cm);
        $this->set_fields();
        $this->init($pageurl);
    }

    /**
     * Set fields
     */
    public function set_fields() {
        $this->fields = array();

        $this->fields[] = new \user_filter_number('vote', get_string('filter_label_votes', 'studentquiz'), false, 'vote');
        $this->fields[] = new \user_filter_number('difficultylevel', get_string('filter_label_difficulty_level'
            , 'studentquiz'), false, 'difficultylevel');
        $this->fields[] = new \user_filter_text('tagname', get_string('filter_label_tags', 'studentquiz'), false, 'tagname');
        $this->fields[] = new \user_filter_text('name', get_string('filter_label_question', 'studentquiz'), true, 'name');
        $this->fields[] = new \user_filter_text('questiontext', 'Question content', true, 'questiontext');
        if (is_anonym($this->cm->id) && !check_created_permission()) {
            $this->fields[] = new \user_filter_checkbox('createdby'
                , get_string('filter_label_show_mine', 'studentquiz'), true, 'createdby');
        } else {
            $this->fields[] = new \user_filter_text('firstname'
                , get_string('filter_label_firstname', 'studentquiz'), true, 'firstname');
            $this->fields[] = new \user_filter_text('lastname'
                , get_string('filter_label_surname', 'studentquiz'), true, 'lastname');
        }
        $this->fields[] = new \user_filter_date('timecreated'
            , get_string('filter_label_createdate', 'studentquiz'), true, 'timecreated');
    }

    /**
     * Initialize filter
     * @param moodle_url $pageurl
     * @throws \coding_exception missing url param exception
     */
    public function init($pageurl) {
        $this->isfilteractive = false;
        $this->set_order_page_data();

        $reset = optional_param('resetbutton', false, PARAM_ALPHA);

        if ($reset) {
            $this->resetfilter();
        }

        $createdby = optional_param('createdby', false, PARAM_INT);

        if ($createdby) {
            $this->setshowmineuserid();
        }
        $this->modify_base_url();
        $this->filterform = new \question_bank_filter_form(
            $this->fields,
            $pageurl->out(),
            array('cmid' => $this->cm->id)
        );
        $this->filterform->set_defaults();
    }

    /**
     * Modify base url for ordering
     */
    public function modify_base_url() {
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
     * Set data for filter recognition
     */
    public function set_order_page_data() {
        foreach ($this->fields as $field) {
            if (isset($_GET[$field->_name])) {
                $_POST[$field->_name] = $_GET[$field->_name];
            }

            if (isset($_GET[$field->_name . '_op'])) {
                $_POST[$field->_name . '_op'] = $_GET[$field->_name . '_op'];
            }
        }
        if (isset($_GET['timecreated_sdt_day'])) {
            $_POST['timecreated_sdt']['day'] = $_GET['timecreated_sdt_day'];
        }
        if (isset($_GET['timecreated_sdt_month'])) {
            $_POST['timecreated_sdt']['month'] = $_GET['timecreated_sdt_month'];
        }
        if (isset($_GET['timecreated_sdt_year'])) {
            $_POST['timecreated_sdt']['year'] = $_GET['timecreated_sdt_year'];
        }
        if (isset($_GET['timecreated_edt_day'])) {
            $_POST['timecreated_edt']['day'] = $_GET['timecreated_edt_day'];
        }
        if (isset($_GET['timecreated_edt_month'])) {
            $_POST['timecreated_edt']['month'] = $_GET['timecreated_edt_month'];
        }
        if (isset($_GET['timecreated_edt_year'])) {
            $_POST['timecreated_edt']['year'] = $_GET['timecreated_edt_year'];
        }
        if (isset($_POST['timecreated_sdt'])) {
            $_POST['timecreated_sdt']['enabled'] = '1';
        }
        if (isset($_POST['timecreated_edt'])) {
            $_POST['timecreated_edt']['enabled'] = '1';
        }
        if (isset($_GET['createdby'])) {
            $_POST['createdby'] = '1';
        }
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
        global $PAGE, $OUTPUT;

        $editcontexts = $this->contexts->having_one_edit_tab_cap($tabname);
        array_unshift($this->searchconditions, new \mod_studentquiz\condition\student_quiz_condition(
            $cat, $recurse, $editcontexts, $this->baseurl, $this->course));

        // This function can be moderately slow with large question counts and may time out.
        // We probably do not want to raise it to unlimited, so randomly picking 5 minutes.
        // Note: We do not call this in the loop because quiz ob_ captures this function (see raise() PHP doc).
        \core_php_time_limit::raise(300);
        $this->build_query();

        $this->questions = $this->filter_questions($this->load_questions());
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

        if ($this->has_questions_in_category()) {
            $this->create_new_quiz_form();
        }

        // Continues with list of questions.
        $this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
            $this->baseurl, $cat, $this->cm,
            null, $page, $perpage, $showhidden, $showquestiontext,
            $this->contexts->having_cap('moodle/question:add'));

        if ($this->has_questions_in_category()) {
            $this->create_new_quiz_form();
        }

        echo '</form>';
    }

    /**
     * (Copy from parent class - modified several code snippets)
     * Create the SQL query to retrieve the indicated questions, based on
     * \core_question\bank\search\condition filters.
     */
    protected function build_query() {
        global $DB;

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

                if ($field->_name == 'firstname' && !mod_check_created_permission()) {
                    continue;
                }

                if ($field->_name == 'lastname' && !mod_check_created_permission()) {
                    continue;
                }

                if ($field->_name == 'tagname') {
                    $this->tagnamefield = $sqldata;
                    continue;
                }

                // The user_filter_checkbox class has a buggy get_sql_filter function.
                if ($field->_name == 'createdby') {
                    $sqldata = array($field->_name . ' = ' . intval($data['value']), array());
                }

                $sqldata[0] = str_replace ( $field->_name, $this->get_sql_table_prefix($field->_name).$field->_name, $sqldata[0] );
                $tests[] = '((' . $sqldata[0]  .'))';
                $this->sqlparams = array_merge($this->sqlparams, $sqldata[1]);
            }
        }
        // Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $tests);
        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
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
            case 'firstname':
            case 'lastname':
                return 'uc.';
            default;
                return 'q.';
        }
    }

    /**
     * Has questions in category
     * @return bool
     */
    private function has_questions_in_category() {
        return $this->totalnumber > 0;
    }

    /**
     * Create new quiz form
     */
    private function create_new_quiz_form() {
        echo '<div class="createnewquiz">';
        echo '<div class="form-buttons">';
        echo '<div>';
        echo "<input name='id' type='hidden' value='".$this->cm->id ."' />";
        echo "<input name='filtered_question_ids' type='hidden' value='". implode(',', $this->get_filtered_question_ids()) ."' />";
        echo '<input class="form-submit" name="startfilteredquiz" type="submit" value="'
            . get_string('createnewquizfromfilter', 'studentquiz') . '" />';
        echo '<input type="submit" name="startquiz" value="' . get_string('start_quiz_button', 'studentquiz') . "\" />\n";
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Extends the question form with custom add question button
     * @param string $cat question category
     */
    private function create_new_question_form_ext($cat) {
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
        global $CFG;
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
        $canuseall = has_capability('moodle/question:useall', $catcontext);
        $canmoveall = has_capability('moodle/question:moveall', $catcontext);

        if ($caneditall || $canmoveall) {
            echo '<div class="modulespecificbuttonscontainer">';
            echo '<strong>&nbsp;'.get_string('withselected', 'question').':</strong><br />';

            // Print delete and move selected question.
            if ($caneditall) {
                echo '<input type="submit" name="deleteselected" value="' . get_string('delete') . "\" />\n";
            }

            if ($canmoveall && count($addcontexts)) {
                echo '<input type="submit" name="move" value="' . get_string('moveto', 'question') . "\" />\n";
                question_category_select_menu($addcontexts, false, 0, "{$category->id},{$category->contextid}");
            }

            echo "</div>\n";
        }
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
        global $CFG, $DB, $OUTPUT;
        $category = $this->get_current_category($categoryandcontext);

        $strselectall = get_string('selectall');
        $strselectnone = get_string('deselectall');

        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);

        if ($this->totalnumber == 0) {
            return;
        }

        $questions = $this->load_page_questions_array($this->questions, $page, $perpage);

        echo '<div class="categorypagingbarcontainer">';
        $pageingurl = new \moodle_url('view.php');
        $r = $pageingurl->params($pageurl->params());
        $pageingurl->params($this->baseurl->params());

        $pagingbar = new \paging_bar($this->totalnumber, $page, $perpage, $pageingurl);
        $pagingbar->pagevar = 'qpage';
        echo $OUTPUT->render($pagingbar);
        echo '</div>';

        echo '<fieldset class="invisiblefieldset" style="display: block;">';
        echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        echo \html_writer::input_hidden_params($this->baseurl);

        echo '<div class="categoryquestionscontainer">';
        $this->start_table();
        $rowcount = 0;
        foreach ($questions as $question) {
            $this->print_table_row($question, $rowcount);
            $rowcount += 1;
        }
        $this->end_table();
        echo "</div>\n";

        echo '<div class="categorypagingbarcontainer pagingbottom">';
        echo $OUTPUT->render($pagingbar);
        if ($this->totalnumber > DEFAULT_QUESTIONS_PER_PAGE) {
            if ($perpage == DEFAULT_QUESTIONS_PER_PAGE) {
                $url = new \moodle_url('view.php', array_merge($pageurl->params(),
                    array('qperpage' => MAXIMUM_QUESTIONS_PER_PAGE)));
                if ($this->totalnumber > MAXIMUM_QUESTIONS_PER_PAGE) {
                    $showall = '<a href="'.$url.'">'
                        . get_string('showperpage', 'moodle', MAXIMUM_QUESTIONS_PER_PAGE).'</a>';
                } else {
                    $showall = '<a href="'.$url.'">'.get_string('showall', 'moodle', $this->totalnumber ).'</a>';
                }
            } else {
                $url = new \moodle_url('view.php', array_merge($pageurl->params(),
                    array('qperpage' => DEFAULT_QUESTIONS_PER_PAGE)));
                $showall = '<a href="'.$url.'">'.get_string('showperpage', 'moodle', DEFAULT_QUESTIONS_PER_PAGE).'</a>';
            }
            echo "<div class='paging'>{$showall}</div>";
        }
        echo '</div>';

        $this->display_bottom_controls($this->totalnumber , $recurse, $category, $catcontext, $addcontexts);

        echo '</fieldset>';
    }

    /**
     * Get all filtered question ids qith q prefix
     * @return array question ids with q prefix
     */
    protected function get_filtered_question_ids() {
        $questionids = array();
        foreach ($this->questions as $question) {
            $questionids[] = 'q' . $question->id;
        }
        return $questionids;
    }

    /**
     * Filter question with the filter option
     * @param stdClass $questions
     * @return array questions
     */
    protected function filter_questions($questions) {
        global $USER;

        $filteredquestions = array();
        foreach ($questions as $question) {
            $question->tagname = '';
            if (
                is_anonym($this->cm->id) &&
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
     * (Copy from parent class - modified several code snippets)
     * @param stdClass $question
     * @param int $page
     * @param int $perpage
     * @return array questions
     */
    protected function load_page_questions_array($question, $page, $perpage) {
        if ($page * $perpage > count($question)) {
            $questions = array_slice ($question , 0, $perpage, true);
        } else {
            $questions = array_slice ($question , $page * $perpage, $perpage, true);
        }

        return $questions;
    }

    /**
     * Load question from database
     * @return \moodle_recordset
     */
    protected function load_questions() {
        global $DB;
        return $DB->get_recordset_sql($this->loadsql, $this->sqlparams);
    }

    /**
     * Get all question tags
     * @param int $id
     * @return \moodle_recordset all tags connected with the question
     */
    protected function get_question_tag($id) {
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
    protected function show_question($id, $count) {
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
     * Get the count of the connected tags with the question
     * @param int $id
     * @param bool $withfilter
     * @return int
     * @throws \coding_exception
     */
    protected function get_question_tag_count($id, $withfilter = true) {
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
     * (Copy from parent class - modified several code snippets)
     * @return mixed
     */
    protected function wanted_columns() {
        global $CFG;

        $CFG->questionbankcolumns = 'checkbox_column,question_type_column'
            . ',question_name_column,mod_studentquiz\\bank\\question_text_row,edit_action_column,copy_action_column,'
            . 'preview_action_column,delete_action_column,creator_name_column,'
            . 'mod_studentquiz\\bank\\tag_column,mod_studentquiz\\bank\\vote_column,'
            . 'mod_studentquiz\\bank\\difficulty_level_column';

        return parent::wanted_columns();
    }

    /**
     * Set createby POST data
     */
    protected function setshowmineuserid() {
        global $USER;

        $_POST['createdby'] = $USER->id;
    }

    /**
     * Reset the filter
     */
    protected function resetfilter() {
        foreach ($this->fields as $field) {
            $_POST[$field->_name] = '';
            $_POST[$field->_name . '_op'] = '0';
        }

        unset($_POST['timecreated_sdt']);
        unset($_POST['timecreated_edt']);
        unset($_POST['createdby']);

    }

    /**
     * (Copy from parent class - modified several code snippets)
     * process action buttons
     */
    public function process_actions() {
        global $CFG, $DB;
        // Now, check for commands on this page and modify variables as necessary.
        if (optional_param('move', false, PARAM_BOOL) and confirm_sesskey()) {
            // Move selected questions to new category.
            $category = required_param('category', PARAM_SEQUENCE);
            list($tocategoryid, $contextid) = explode(',', $category);
            if (! $tocategory = $DB->get_record('question_categories', array('id' => $tocategoryid, 'contextid' => $contextid))) {
                print_error('cannotfindcate', 'question');
            }
            $tocontext = \context::instance_by_id($contextid);
            require_capability('moodle/question:add', $tocontext);
            $rawdata = (array) data_submitted();
            $questionids = array();
            foreach ($rawdata as $key => $value) {  // Parse input for question ids.
                if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
                    $key = $matches[1];
                    $questionids[] = $key;
                }
            }
            if ($questionids) {
                list($usql, $params) = $DB->get_in_or_equal($questionids);
                $sql = "";
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

        if (optional_param('deleteselected', false, PARAM_BOOL)) { // Delete selected questions from the category.
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
     * Confirmation on process action if needed
     * @return boolean
     */
    public function process_actions_needing_ui() {
        global $DB, $OUTPUT;
        if (optional_param('deleteselected', false, PARAM_BOOL)) {
            // Make a list of all the questions that are selected.
            $rawquestions = $_REQUEST; // This code is called by both POST forms and GET links, so cannot use data_submitted.
            $questionlist = '';  // comma separated list of ids of questions to be deleted
            $questionnames = ''; // string with names of questions separated by <br /> with
                                 // an asterix in front of those that are in use
            $inuse = false;      // set to true if at least one of the questions is in use
            foreach ($rawquestions as $key => $value) {    // Parse input for question ids.
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
            if (!$questionlist) { // No questions were selected.
                redirect($this->baseurl);
            }
            $questionlist = rtrim($questionlist, ',');

            // Add an explanation about questions in use.
            if ($inuse) {
                $questionnames .= '<br />'.get_string('questionsinuse', 'question');
            }
            $baseurl = new \moodle_url('view.php', $this->baseurl->params());
            $deleteurl = new \moodle_url($baseurl, array('deleteselected' => $questionlist, 'confirm' => md5($questionlist),
                                                 'sesskey' => sesskey()));

            $continue = new \single_button($deleteurl, get_string('delete'), 'post');
            echo $OUTPUT->confirm(get_string('deletequestionscheck', 'question', $questionnames), $continue, $baseurl);

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
}
