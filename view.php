<?php
define('CACHE_DISABLE_ALL', true);
define('CACHE_DISABLE_STORES', true);

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

class TextCustomView extends \core_question\bank\view {
    public function __construct($contexts, $pageurl, $course, $cm) {
        parent::__construct($contexts, $pageurl, $course, $cm);

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
        array_unshift($this->searchconditions, new \mod_studentquiz\condition\student_quiz_condition(
            $cat, $recurse, $editcontexts, $this->baseurl, $this->course));
        // Continues with list of questions.
        $this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
            $this->baseurl, $cat, $this->cm,
            null, $page, $perpage, $showhidden, $showquestiontext,
            $this->contexts->having_cap('moodle/question:add'));
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

        $canadd = has_capability('moodle/question:add', $catcontext);

        $this->create_new_question_form($category, $canadd);

        $this->build_query();
        $totalnumber = $this->get_question_count();
        if ($totalnumber == 0) {
            return;
        }
        $questions = $this->load_page_questions($page, $perpage);

        echo '<div class="categorypagingbarcontainer">';
        $pageingurl = new \moodle_url('edit.php');
        $r = $pageingurl->params($pageurl->params());
        $pagingbar = new \paging_bar($totalnumber, $page, $perpage, $pageingurl);
        $pagingbar->pagevar = 'qpage';
        echo $OUTPUT->render($pagingbar);
        echo '</div>';

        echo '<form method="post" action="edit.php">';
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
                $url = new \moodle_url('edit.php', array_merge($pageurl->params(),
                    array('qperpage' => MAXIMUM_QUESTIONS_PER_PAGE)));
                if ($totalnumber > MAXIMUM_QUESTIONS_PER_PAGE) {
                    $showall = '<a href="'.$url.'">'.get_string('showperpage', 'moodle', MAXIMUM_QUESTIONS_PER_PAGE).'</a>';
                } else {
                    $showall = '<a href="'.$url.'">'.get_string('showall', 'moodle', $totalnumber).'</a>';
                }
            } else {
                $url = new \moodle_url('edit.php', array_merge($pageurl->params(),
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
}

function mod_studentquiz_get_question_bank_search_conditions() {
    echo "get extendsion";
    return array();
}

$cmid = optional_param('id', 0, PARAM_INT);
if(!$cmid){
    $cmid = required_param('cmid', PARAM_INT);
}

$context = context_module::instance($cmid);
$category = question_get_default_category($context->id);
$_GET['cmid'] = $cmid;
$_POST['cat'] = $category->id . ',' . $context->id;
list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
    question_edit_setup('questions', '/mod/studentquiz/view.php', true, false);



$url = new moodle_url($thispageurl);
if (($lastchanged = optional_param('lastchanged', 0, PARAM_INT)) !== 0) {
    $url->param('lastchanged', $lastchanged);
}
$PAGE->set_url($url);

$questionbank = new TextCustomView($contexts, $thispageurl, $COURSE, $cm);

$questionbank->process_actions();

// TODO log this page view.

$context = $contexts->lowest();
$streditingquestions = get_string('editquestions', 'question');
$PAGE->set_title($streditingquestions);
$PAGE->set_heading($COURSE->fullname);

echo $OUTPUT->header();



echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';
$questionbank->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
    $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'],
    $pagevars['qbshowtext']);
echo "</div>\n";

echo $OUTPUT->footer();
