<?php

namespace mod_studentquiz\question\bank;

require_once(dirname(__FILE__).'/vote_column.php');
require_once(dirname(__FILE__).'/tag_column.php');
require_once(dirname(__FILE__).'/question_view_form.php');

class custom_view extends \core_question\bank\view {
    private $filterform;
    private $search;
    public function __construct($contexts, $pageurl, $course, $cm, $search) {
        parent::__construct($contexts, $pageurl, $course, $cm);
        $this->search = $search;

        $this->initFilterForm();

    }

    public function initFilterForm() {
        $this->filterform = new question_view_form('view.php', array('cmid' => $this->cm->id),
            'get', '', array('id' => 'filterform'));
        $this->filterform->set_data(array('search' => $this->search));
    }

    public function display($tabname, $page, $perpage, $cat,
                            $recurse, $showhidden, $showquestiontext){
        global $PAGE, $OUTPUT;

        if ($this->process_actions_needing_ui()) {
            return;
        }
        $editcontexts = $this->contexts->having_one_edit_tab_cap($tabname);
        // Category selection form.
        echo $OUTPUT->heading(get_string('modulename', 'studentquiz'), 2);


        $this->create_new_quiz_form();
        $this->create_new_question_form_ext($cat);

        array_unshift($this->searchconditions, new \mod_studentquiz\condition\student_quiz_condition(
            $cat, $recurse, $editcontexts, $this->baseurl, $this->course));
        // Continues with list of questions.
        echo $this->filterform->render();
        $this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
            $this->baseurl, $cat, $this->cm,
            null, $page, $perpage, $showhidden, $showquestiontext,
            $this->contexts->having_cap('moodle/question:add'));
    }

    function create_new_quiz_form() {
        echo '<div class="createnewquiz">';
        echo '<div class="singlebutton">';


        echo '<form method="get" action="atempt">';
        echo '<div>';
        echo '<input type="submit" value="Start new quiz ..." />';
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

        // This function can be moderately slow with large question counts and may time out.
        // We probably do not want to raise it to unlimited, so randomly picking 5 minutes.
        // Note: We do not call this in the loop because quiz ob_ captures this function (see raise() PHP doc).
        \core_php_time_limit::raise(300);

        $category = $this->get_current_category($categoryandcontext);

        $strselectall = get_string('selectall');
        $strselectnone = get_string('deselectall');

        list($categoryid, $contextid) = explode(',', $categoryandcontext);
        $catcontext = \context::instance_by_id($contextid);

        $this->build_query();

        $totalnumber = $this->get_question_count();
        if ($totalnumber == 0) {
            return;
        }

        $questions = $this->load_questions();

        $newQuestion = array();
        foreach($questions as $question) {
            $question->tag_name = '';
            foreach($this->get_question_tag($question->id) as $tag) {
                $question->tag_name .= ', '.$tag->name;
            }
            $question->tag_name = substr($question->tag_name, 2);


            if(empty($this->search)) {
                $newQuestion[] = $question;
            } else {
                if ($this->check_created_permission()) {
                    $text = strtolower($question->name
                        . $question->creatorfirstnamephonetic
                        . $question->creatorlastnamephonetic
                        . $question->creatormiddlename
                        . $question->creatoralternatename
                        . $question->creatorfirstname
                        . $question->creatorlastname
                        . str_replace(',', '', $question->tag_name));
                    if($this->property_contains_filter($text, strtolower($this->search))) {
                        $newQuestion[] = $question;
                    }
                }
            }
        }

        $totalnumber = count($newQuestion);

        $questions = $this->load_page_questions_array($newQuestion, $page, $perpage);


        echo '<div class="categorypagingbarcontainer">';
        $pageingurl = new \moodle_url('view.php');
        $r = $pageingurl->params($pageurl->params());
        $pagingbar = new \paging_bar($totalnumber, $page, $perpage, $pageingurl);
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
        if ($totalnumber > DEFAULT_QUESTIONS_PER_PAGE) {
            if ($perpage == DEFAULT_QUESTIONS_PER_PAGE) {
                $url = new \moodle_url('view.php', array_merge($pageurl->params(),
                    array('qperpage' => MAXIMUM_QUESTIONS_PER_PAGE)));
                if ($totalnumber > MAXIMUM_QUESTIONS_PER_PAGE) {
                    $showall = '<a href="'.$url.'">'.get_string('showperpage', 'moodle', MAXIMUM_QUESTIONS_PER_PAGE).'</a>';
                } else {
                    $showall = '<a href="'.$url.'">'.get_string('showall', 'moodle', $totalnumber).'</a>';
                }
            } else {
                $url = new \moodle_url('view.php', array_merge($pageurl->params(),
                    array('qperpage' => DEFAULT_QUESTIONS_PER_PAGE)));
                $showall = '<a href="'.$url.'">'.get_string('showperpage', 'moodle', DEFAULT_QUESTIONS_PER_PAGE).'</a>';
            }
            echo "<div class='paging'>{$showall}</div>";
        }
        echo '</div>';

        $this->display_bottom_controls($totalnumber, $recurse, $category, $catcontext, $addcontexts);

        echo '</fieldset>';
        echo "</form>\n";
    }

    protected function property_contains_filter($property, $filter) {
        return strrpos($property, $filter) !== false;
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
        $sql = 'SELECT t.name, ti.itemid'
            .' FROM mdl_tag t'
            .' JOIN mdl_tag_instance ti'
            .' ON t.id = ti.tagid'
            .' WHERE ti.itemtype = "question" AND ti.itemid = :qid';

        $sqlparams['qid'] = $id;
        return $DB->get_recordset_sql($sql, $sqlparams);
    }

    protected function wanted_columns() {
        global $CFG;

        $showcreator = '';
        if ($this->check_created_permission()) {
            $showcreator = 'creator_name_column,';
        }

        $CFG->questionbankcolumns = 'checkbox_column,question_type_column'
            . ',question_name_column,edit_action_column,copy_action_column,'
            . 'preview_action_column,delete_action_column,' . $showcreator . 'mod_studentquiz\\bank\\tag_column,mod_studentquiz\\bank\\vote_column';

        return parent::wanted_columns();
    }

    protected function check_created_permission() {
        global $USER;

        $admins = get_admins();
        foreach ($admins as $admin) {
            if ($USER->id == $admin->id) {
                return true;
            }
        }

        if (!user_has_role_assignment($USER->id,5)) {
            return true;
        }

        return false;
    }
}