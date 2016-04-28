<?php

defined('MOODLE_INTERNAL') || die();

class mod_studentquiz_renderer extends plugin_renderer_base {

    public function report_quiz_table($report) {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable qpracticesummaryofattempt boxaligncenter';
        $table->caption = get_string('practice_past_sessions', 'studentquiz');
        $table->head = array(get_string('practice_total_questions', 'studentquiz'), get_string('practice_total_marks', 'studentquiz'));
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $table->data = array();

        $rows = array();
        foreach($report->get_studentquiz_sessions() as $session){
            $cellTotalQuestions = new html_table_cell();
            $cellTotalQuestions->text = $session->totalnoofquestions;

            $cellMarks = new html_table_cell();
            $cellMarks->text = $session->marksobtained . '/' . $session->totalmarks;


            $row = new html_table_row();
            if($session->id == "actual user....."){
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

    public function display_questionbank($view) {
        echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';
        $pagevars = $view->get_qb_pagevar();
        $view->get_questionbank()->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
            $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'],
            $pagevars['qbshowtext']);
        echo "</div>\n";
    }
}
