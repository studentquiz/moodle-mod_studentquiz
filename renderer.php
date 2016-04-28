<?php

defined('MOODLE_INTERNAL') || die();


class mod_studentquiz_renderer extends plugin_renderer_base {

    public function view_quizreport_total($total) {
        $output = '';
        $output = $this->heading(get_string('reportquiz_total_title', 'studentquiz'), 2);
        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_total_attempt', 'studentquiz') . ': ', 'reportquiz_total_label')
            .html_writer::span($total->numattempts)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_total_questions_answered', 'studentquiz') . ': ', 'reportquiz_total_label')
            .html_writer::span($total->questionsanswered)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_total_questions_right', 'studentquiz') . ': ', 'reportquiz_total_label')
            .html_writer::span($total->questionsright)
        );

        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_total_questions_wrong', 'studentquiz') . ': ', 'reportquiz_total_label')
            .html_writer::span(($total->questionsanswered - $total->questionsright))
        );
        $output .= html_writer::tag('p',
            html_writer::span(get_string('reportquiz_total_obtained_marks', 'studentquiz') . ': ', 'reportquiz_total_label')
            .html_writer::span($total->obtainedmarks)
        );

        return $output;
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
