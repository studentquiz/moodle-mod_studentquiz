<?php

defined('MOODLE_INTERNAL') || die();

class mod_studentquiz_renderer extends plugin_renderer_base {

    public function summary_table($sessionid) {
        global $DB;

        $actualSession = $DB->get_record('studentquiz_p_session', array('studentquiz_p_session_id' => $sessionid));
        $allSession = $DB->get_records('studentquiz_p_session', array('studentquiz_p_overview_id' => $actualSession->studentquiz_p_overview_id));

        $table = new html_table();
        $table->attributes['class'] = 'generaltable qpracticesummaryofattempt boxaligncenter';
        $table->caption = get_string('practice_past_sessions', 'studentquiz');
        $table->head = array(get_string('practice_total_questions', 'studentquiz'), get_string('practice_total_marks', 'studentquiz'));
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $table->data = array();

        $rows = array();
        foreach($allSession as $session){
            $cellTotalQuestions = new html_table_cell();
            $cellTotalQuestions->text = $session->total_no_of_questions;

            $cellMarks = new html_table_cell();
            $cellMarks->text = $session->marks_obtained . '/' . $session->total_marks;


            $row = new html_table_row();
            if($session->studentquiz_p_session_id == $actualSession->studentquiz_p_session_id){
                $style = array('class' => 'mod-studentquiz-summary-highlight');

                $cellTotalQuestions->attributes = $style;
                $cellMarks->attributes = $style;
                $row->attributes = $style;
            }

            $row->cells = array($cellTotalQuestions, $cellMarks);
            $rows[] = $row;
        }
        $table->data = $rows;
        echo html_writer::table($table);
    }

    public function summary_form($sessionid) {

        $actionurl = new moodle_url('/mod/studentquiz/summary.php', array('id' => $sessionid));
        $output = '';
        $output .= html_writer::start_tag('form', array('method' => 'post', 'action' => $actionurl,
            'enctype' => 'multipart/form-data', 'id' => 'responseform'));
        $output .= html_writer::start_tag('div', array('align' => 'center'));
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'retry', 'value' => get_string('practice_retry', 'studentquiz')));
        $output .= html_writer::empty_tag('br');
        $output .= html_writer::empty_tag('br');
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'finish', 'value' => get_string('practice_finish', 'studentquiz')));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        echo $output;
    }

    public function report_table($cm, $context) {
        global $DB, $USER;

        $canviewallreports = true; //has_capability('mod/studentquiz:viewallreports', $context);
        $canviewmyreports = true; //has_capability('mod/studentquiz:viewmyreport', $context);

        if ($canviewmyreports) {
            $overview = $DB->get_records('studentquiz_p_overview', array('question_category_id' => $cm->instance, 'user_id' => $USER->id));
            $session = $DB->get_records('studentquiz_p_session', array('studentquiz_p_overview_id' => $overview->studentquiz_overview_id));
        } if ($canviewallreports) {
            $overview = $DB->get_records('studentquiz_p_overview', array('question_category_id' => $cm->instance));
            $session = $DB->get_records('studentquiz_p_session', array('studentquiz_p_overview_id' => $overview->studentquiz_overview_id));
        }

        if ($session != null) {
            $table = new html_table();
            $table->attributes['class'] = 'generaltable qpracticesummaryofpractices boxaligncenter';
            $table->caption = get_string('practice_past_sessions', 'studentquiz');
            $table->head = array(get_string('practice_date', 'studentquiz'), get_string('practice_category', 'studentquiz'),
                get_string('score', 'studentquiz'),
                get_string('pracitce_no_of_questions_viewed', 'studentquiz'),
                get_string('practice_no_of_questions_right', 'studentquiz'));
            $table->align = array('left', 'left', 'left', 'left', 'left', 'left', 'left');
            $table->size = array('', '', '', '', '', '', '', '');
            $table->data = array();
            foreach ($session as $practice) {
                $date = $practice->practice_date;
                $categoryid = $practice->category_category_id;

                $category = $DB->get_records_menu('question_categories', array('id' => $categoryid), 'name');
                /* If the category has been deleted, jump to the next session */
                if (empty($category)) {
                    continue;
                }
                $table->data[] = array(userdate($date), $category[$categoryid],
                    $practice->marks_obtained . '/' . $practice->total_marks,
                    $practice->total_no_of_questions, $practice->total_no_of_questions_right);
            }
            echo html_writer::table($table);
        } else {
            $viewurl = new moodle_url('/mod/studentquiz/view.php', array('id' => $cm->id));
            $viewtext = get_string('practice_no_records_viewurl', 'studentquiz');
            redirect($viewurl, $viewtext);
        }
    }

    public function attemptPage($attempt){
        $html = '';

        $html = html_writer::start_tag('form', array('method' => 'post', 'action' => $attempt->getViewUrl(),
            'enctype' => 'multipart/form-data', 'id' => 'responseform'));

        $html .= $attempt->renderQuestion();
        $html .= html_writer::start_tag('div');

        $html .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'next', 'value' => get_string('practice_nextquestion', 'studentquiz')));

        $html .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'finish', 'value' => get_string('practice_stoppractice', 'studentquiz')));

        $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'slots', 'value' => $attempt->getSlot()));

        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('form');

        echo $html;
    }

    public function displayQuestionBank($view) {
        echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';
        $pagevars = $view->getQbPageVar();
        $view->getQuestionBank()->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
            $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'],
            $pagevars['qbshowtext']);
        echo "</div>\n";
    }

}
