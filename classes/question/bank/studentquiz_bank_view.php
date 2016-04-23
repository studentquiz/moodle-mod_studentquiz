<?php

namespace mod_studentquiz\question\bank;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/vote_column.php');
require_once(dirname(__FILE__).'/difficulty_level_column.php');
require_once(dirname(__FILE__).'/tag_column.php');
require_once(dirname(__FILE__).'/question_view_form.php');
require_once(dirname(__FILE__).'/question_bank_filter.php');


class studentquiz_bank_view extends \core_question\bank\view {
    private $questions;
    private $totalnumber;
    private $tagnamefield;
    private $isfilteractive;
    private $_filterform;


    public function __construct($contexts, $pageurl, $course, $cm) {
        parent::__construct($contexts, $pageurl, $course, $cm);
        $this->init($pageurl);
    }

    public function init($pageurl) {
        $this->isfilteractive = false;

        $this->_filterform = new \question_bank_filter_form(
            $pageurl->out()
            , array(
                'cmid' => $this->cm->id
                ,'isadmin' => $this->check_created_permission()
            ));
    }

    public function display($tabname, $page, $perpage, $cat,
                            $recurse, $showhidden, $showquestiontext){
        global $PAGE, $OUTPUT;

        $editcontexts = $this->contexts->having_one_edit_tab_cap($tabname);
        array_unshift($this->searchconditions, new \mod_studentquiz\condition\student_quiz_condition(
            $cat, $recurse, $editcontexts, $this->baseurl, $this->course));

        // This function can be moderately slow with large question counts and may time out.
        // We probably do not want to raise it to unlimited, so randomly picking 5 minutes.
        // Note: We do not call this in the loop because quiz ob_ captures this function (see raise() PHP doc).
        \core_php_time_limit::raise(300);
        $this->build_query();

        $this->questions = $this->filterQuestions($this->load_questions());
        $this->totalnumber = count($this->questions);

        if ($this->process_actions_needing_ui()) {
            return;
        }

        echo $OUTPUT->heading($this->cm->name, 2);

        if($this->hasQuestionsInCategory()) {
            $this->create_new_quiz_form();
        }

        if($this->hasQuestionsInCategory() || $this->isfilteractive) {
            echo $this->_filterform->render();
        }

        $this->create_new_question_form_ext($cat);

        // Continues with list of questions.
        $this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
            $this->baseurl, $cat, $this->cm,
            null, $page, $perpage, $showhidden, $showquestiontext,
            $this->contexts->having_cap('moodle/question:add'));
    }

    /**
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

        if ($adddata = $this->_filterform->get_data()){
            foreach ($this->_filterform->getFields() as $field) {
                $data = $field->check_data($adddata);

                if ($data === false) continue;

                $this->isfilteractive = true;
                $sqldata = $field->get_sql_filter($data);

                if($field->_name == 'tagname') {
                    $this->tagnamefield = $sqldata;
                    continue;
                }

                $sqldata[0] = str_replace ( $field->_name , $this->get_sql_table_prefix($field->_name).$field->_name , $sqldata[0] );
                $tests[]= '((' . $sqldata[0]  .'))';
                $this->sqlparams = array_merge($this->sqlparams, $sqldata[1]);
            }
        }
        // Build the SQL.
        $sql = ' FROM {question} q ' . implode(' ', $joins);
        $sql .= ' WHERE ' . implode(' AND ', $tests);

        $this->countsql = 'SELECT count(1)' . $sql;
        $this->loadsql = 'SELECT ' . implode(', ', $fields) . $sql . ' ORDER BY ' . implode(', ', $sorts);
    }

    function get_sql_table_prefix($name) {
        switch($name){
            case 'difficultylevel':
                return 'dl.';
            case 'vote':
                return 'vo.';
            default;
                return 'q.';
        }
    }

    function hasQuestionsInCategory() {
        return $this->totalnumber > 0;
    }

    function create_new_quiz_form() {
        echo '<div class="createnewquiz">';
        echo '<div class="singlebutton">';


        echo '<form method="post" action="">';
        echo '<div>';
        echo "<input name='id' type='hidden' value='".$this->cm->id ."' />";
        echo "<input name='filtered_question_ids' type='hidden' value='". implode(',', $this->getFilteredQuestionIds()) ."' />";
        echo '<input name="startfilteredquiz" type="submit" value="Start new quiz ..." />';

        echo '</div>';
        echo '</form>';

        echo '</div>';
        echo '</div>';
    }

    function create_new_question_form_ext($cat){
        $category = $this->get_current_category($cat);
        list($categoryid, $contextid) = explode(',', $cat);

        $catcontext = \context::instance_by_id($contextid);

        $canadd = has_capability('moodle/question:add', $catcontext);
        $this->create_new_question_form($category, $canadd);
    }

    protected function create_new_question_form($category, $canadd) {
        global $CFG;
        $caption = get_string('createnewquestion', 'question');
        if($this->hasQuestionsInCategory()) {
            echo '<div class="createnewquestion">';
        } else {
            echo '<div class="createnewquestion_wo_question">';
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

        echo '<div class="modulespecificbuttonscontainer">';
        echo '<strong>&nbsp;'.get_string('withselected', 'question').':</strong><br />';

        echo '<input type="submit" name="startquiz" value="' . get_string('start_quiz_button', 'studentquiz') . "\" />\n";

        if ($caneditall || $canmoveall || $canuseall) {

            // Print delete and move selected question.
            if ($caneditall) {
                echo '<input type="submit" name="deleteselected" value="' . get_string('delete') . "\" />\n";
            }

            if ($canmoveall && count($addcontexts)) {
                echo '<input type="submit" name="move" value="' . get_string('moveto', 'question') . "\" />\n";
                question_category_select_menu($addcontexts, false, 0, "{$category->id},{$category->contextid}");
            }
        }
        echo "</div>\n";
    }

    /**
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
        $pagingbar = new \paging_bar($this->totalnumber, $page, $perpage, $pageingurl);
        $pagingbar->pagevar = 'qpage';
        echo $OUTPUT->render($pagingbar);
        echo '</div>';

        echo '<form method="post" action="view.php">';
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
        if ($this->totalnumber  > DEFAULT_QUESTIONS_PER_PAGE) {
            if ($perpage == DEFAULT_QUESTIONS_PER_PAGE) {
                $url = new \moodle_url('view.php', array_merge($pageurl->params(),
                    array('qperpage' => MAXIMUM_QUESTIONS_PER_PAGE)));
                if ($this->totalnumber  > MAXIMUM_QUESTIONS_PER_PAGE) {
                    $showall = '<a href="'.$url.'">'.get_string('showperpage', 'moodle', MAXIMUM_QUESTIONS_PER_PAGE).'</a>';
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
        echo "</form>\n";
    }

    protected function getFilteredQuestionIds(){
        $questionIds = array();
        foreach($this->questions as $question) {
            $questionIds[] = 'q' . $question->id;
        }
        return $questionIds;
    }

    protected function filterQuestions($questions) {
        $filteredQuestions = array();

        foreach($questions as $question) {
            $question->tagname = '';

            $count = $this->get_question_tag_count($question->id);
            if($count){
                foreach($this->get_question_tag($question->id) as $tag) {
                    $question->tagname .= ', '.$tag->name;
                }
                $question->tagname = substr($question->tagname, 2);
            }

            if(!$this->isfilteractive) {
                $filteredQuestions[] = $question;
            } else {
                if(isset($this->tagnamefield)) {
                    if(!empty($question->tagname) || $count == 0) {
                        $filteredQuestions[] = $question;
                    }
                } else {
                    $filteredQuestions[] = $question;
                }
            }
        }
        return $filteredQuestions;
    }

    protected function load_page_questions_array($question, $page, $perpage) {
        if($page * $perpage > count($question)) {
            $questions =  array_slice ($question , 0, $perpage, true);
        } else {
            $questions =  array_slice ($question , $page * $perpage, $perpage, true);
        }

        return $questions;
    }

    protected function load_questions() {
        global $DB;
        return $DB->get_recordset_sql($this->loadsql, $this->sqlparams);
    }
    protected function get_question_tag($id) {
        global $DB;
        $sqlparams = array();

        $sqlext = '';
        if(isset($this->tagnamefield)) {
            $sqlext = str_replace ( 'tagname' , 't.name' , $this->tagnamefield[0]);
            $sqlparams = $this->tagnamefield[1];

            $sqlext = ' AND '. '((' . $sqlext  .'))';
        }

        $sql = 'SELECT t.name, ti.itemid'
            .' FROM mdl_tag t'
            .' JOIN mdl_tag_instance ti'
            .' ON t.id = ti.tagid'
            .' WHERE ti.itemtype = "question" AND ti.itemid = :qid' . $sqlext;

        $sqlparams['qid'] = $id;

        return $DB->get_recordset_sql($sql, $sqlparams);
    }

    protected function get_question_tag_count($id) {
        global $DB;
        $sqlparams = array();

        $sqlext = '';

        $sql = 'SELECT count(1)'
            .' FROM mdl_tag t'
            .' JOIN mdl_tag_instance ti'
            .' ON t.id = ti.tagid'
            .' WHERE ti.itemtype = "question" AND ti.itemid = :qid';

        $sqlparams['qid'] = $id;

        return $DB->count_records_sql($sql, $sqlparams);
    }

    protected function wanted_columns() {
        global $CFG;

        $showcreator = '';
        if ($this->check_created_permission()) {
            $showcreator = 'creator_name_column,';
        }

        $CFG->questionbankcolumns = 'checkbox_column,question_type_column'
            . ',question_name_column,edit_action_column,copy_action_column,'
            . 'preview_action_column,delete_action_column,' . $showcreator 
            . 'mod_studentquiz\\bank\\tag_column,mod_studentquiz\\bank\\vote_column,mod_studentquiz\\bank\\difficulty_level_column';

        return parent::wanted_columns();
    }

    protected function check_created_permission() {
        global $USER;

        $admins = get_admins();
        foreach ($admins as $admin) {
            if($USER->id == $admin->id) {
                return true;
            }
        }

        return !user_has_role_assignment($USER->id,5);
    }
}