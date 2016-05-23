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
 * Defines the renderer for the studentquiz module.
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the renderer for the studentquiz module.
 *
 * @package    mod_studentquiz
 * @copyright  2016 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_renderer extends plugin_renderer_base {

    /**
     * builds the rank report table
     * @param $report studentquiz_report class with necessary information
     * @return string rank report table
     */
    public function view_rankreport_table($report) {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable qpracticesummaryofattempt boxaligncenter';
        $table->caption = $report->get_coursemodule()->name . ' '. get_string('reportrank_table_title', 'studentquiz');
        $table->head = array(get_string('reportrank_table_column_rank', 'studentquiz')
            ,get_string('reportrank_table_column_fullname', 'studentquiz')
            ,get_string('reportrank_table_column_points', 'studentquiz'));
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $table->data = array();
        $rows = array();
        $rank = 1;
        foreach($report->get_user_ranking() as $ur){
            $cellrank = new html_table_cell();
            $cellrank->text = $rank;
            $cellfullname = new html_table_cell();

            $tmp = $ur->firstname . ' ' . $ur->lastname;
            if($report->is_anonym()) {
                if(!$report->is_loggedin_user($ur->userid)){
                    $tmp = 'anonymous';
                }
            }
            $cellfullname->text = $tmp;

            $cellpoints = new html_table_cell();
            $cellpoints->text = $ur->points;

            $row = new html_table_row();

            if($report->is_loggedin_user($ur->userid)){
                $style = array('class' => 'mod-studentquiz-summary-highlight');
                $cellrank->attributes = $style;
                $cellfullname->attributes = $style;
                $cellpoints->attributes = $style;
                $row->attributes = $style;
            }
            $row->cells = array($cellrank, $cellfullname, $cellpoints);
            $rows[] = $row;
            ++$rank;
        }
        $table->data = $rows;
        return  html_writer::table($table);
    }

    /**
     * builds the quiz report table for the admin
     * @param $report studentquiz_report class with necessary information
     * @return string rank report table
     */
    public function view_quizreport_table($report, $usersdata) {
        $output = $this->heading(get_string('reportquiz_admin_title', 'studentquiz'), 2, 'reportquiz_total_heading');
        $table = new html_table();
        $table->attributes['class'] = 'generaltable qpracticesummaryofattempt boxaligncenter';
        $table->caption = $report->get_coursemodule()->name . ' '. get_string('reportquiz_table_title', 'studentquiz');
        $table->head = array(get_string('reportrank_table_column_fullname', 'studentquiz')
            ,get_string('reportquiz_total_attempt', 'studentquiz')
            ,get_string('reportquiz_total_questions_answered', 'studentquiz')
            ,get_string('reportquiz_total_questions_right', 'studentquiz')
            ,get_string('reportquiz_total_questions_wrong', 'studentquiz')
            ,get_string('reportquiz_total_obtained_marks', 'studentquiz'));
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $table->data = array();
        $rows = array();
        foreach($usersdata as $user){
            $cellfullname = new html_table_cell();
            $cellfullname->text = $user->name;

            $cellnumattempts = new html_table_cell();
            $cellnumattempts->text = $user->numattempts;

            $cellobtainedmarks = new html_table_cell();
            $cellobtainedmarks->text = $user->obtainedmarks;

            $cellquestionsanswered = new html_table_cell();
            $cellquestionsanswered->text = $user->questionsanswered;

            $cellquestionsright = new html_table_cell();
            $cellquestionsright->text = $user->questionsright;

            $cellquestionswrong= new html_table_cell();
            $cellquestionswrong->text = $user->questionsanswered - $user->questionsright;



            $row = new html_table_row();

            if($report->is_loggedin_user($user->id)){
                $style = array('class' => 'mod-studentquiz-summary-highlight');
                $cellfullname->attributes = $style;
                $cellnumattempts->attributes = $style;
                $cellobtainedmarks->attributes = $style;
                $cellquestionsanswered->attributes = $style;
                $cellquestionswrong->attributes = $style;
                $cellquestionsright->attributes = $style;
                $row->attributes = $style;
            }
            $row->cells = array($cellfullname, $cellnumattempts, $cellquestionsanswered, $cellquestionsright, $cellquestionswrong, $cellobtainedmarks);
            $rows[] = $row;
        }
        $table->data = $rows;
        $output .=  html_writer::table($table);
        return $output;
    }

    /**
     * build the quiz report summary section
     * @return string quiz report summary title
     */
    public function view_quizreport_summary() {
        return $this->heading(get_string('reportquiz_summary_title', 'studentquiz'), 2, 'reportquiz_total_heading');
    }

    /**
     * builds the quiz report total section
     * @param $total
     * @return string quiz report data
     */
    public function view_quizreport_total($total, $isadmin = false) {
        $output = '';

        if($isadmin)
            $output = $this->heading(get_string('reportquiz_admin_total_title', 'studentquiz'), 2, 'reportquiz_total_heading');
        else
            $output = $this->heading(get_string('reportquiz_total_title', 'studentquiz'), 2, 'reportquiz_total_heading');
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

        if($isadmin){
            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_total_users', 'studentquiz') . ': ', 'reportquiz_total_label')
                .html_writer::span($total->usercount)
            );
        }

        return $output;
    }

    /**
     * builds the studentquiz_bank_view
     * @param $view studentquiz_view class with the necessary information
     */
    public function display_questionbank($view) {
        echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';
        $pagevars = $view->get_qb_pagevar();
        $view->get_questionbank()->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
            $pagevars['cat'], $pagevars['recurse'], $pagevars['showhidden'],
            $pagevars['qbshowtext']);

        if($view->has_printableerror()){
            echo $this->show_error($view->get_errormessage());
        }

        echo "</div>\n";
    }

    /**
     * prints the error message
     * @param $errormessage string error message
     */
    public function show_error($errormessage) {
        return html_writer::div($errormessage, 'error');
    }
}
