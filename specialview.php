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
        global $OUTPUT;

        if ($this->process_actions_needing_ui()) {
            return;
        }
        $editcontexts = $this->contexts->having_one_edit_tab_cap($tabname);
        // Category selection form.
        echo $OUTPUT->heading('Community-Quiz - Made by DM, MS', 2);
        array_unshift($this->searchconditions, new \core_question\bank\search\hidden_condition(!$showhidden));
        array_unshift($this->searchconditions, new \core_question\bank\search\category_condition(
            $cat, $recurse, $editcontexts, $this->baseurl, $this->course));
        $this->display_options_form($showquestiontext);

        // Continues with list of questions.
        $this->display_question_list($this->contexts->having_one_edit_tab_cap($tabname),
            $this->baseurl, $cat, $this->cm,
            null, $page, $perpage, $showhidden, $showquestiontext,
            $this->contexts->having_cap('moodle/question:add'));
    }

    protected function init_search_conditions() {
        $searchplugins = get_plugin_list_with_function('mod', 'get_question_bank_search_conditions');
        foreach ($searchplugins as $component => $function) {
            foreach ($function($this) as $searchobject) {
                $this->add_searchcondition($searchobject);
            }
        }
    }
}

function mod_socialquiz_get_question_bank_search_conditions() {
    echo "get extendsion";
    return array();
}
$context = context_module::instance(required_param('cmid', PARAM_INT));
$category = question_get_default_category($context->id);
$_POST['cat'] = $category->id . ',' . $context->id;
list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
    question_edit_setup('questions', '/question/edit.php', true, false);



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

var_dump($pagevars['cat']);
echo $OUTPUT->header();



echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';
$questionbank->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
    $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'],
    $pagevars['qbshowtext']);
echo "</div>\n";

echo $OUTPUT->footer();