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
 * Defines the renderer for the StudentQuiz module.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the renderer for the StudentQuiz module.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_renderer extends plugin_renderer_base {

    /**
     * builds the rank report table
     * @param mod_studentquiz_report $report studentquiz_report class with necessary information
     * @return string rank report table
     * @throws coding_exception
     */
    public function view_rankreport_table($report) {
        $table = new html_table();
        $table->attributes['class'] = 'generaltable boxaligncenter';
        $table->caption = $report->get_coursemodule()->name . ' '. get_string('reportrank_table_title', 'studentquiz');
        $table->head = array(get_string('reportrank_table_column_rank', 'studentquiz')
            , get_string('reportrank_table_column_fullname', 'studentquiz')
            , get_string('reportrank_table_column_points', 'studentquiz')
            , get_string( 'reportrank_table_column_countquestions', 'studentquiz')
            , get_string( 'reportrank_table_column_summeanvotes', 'studentquiz')
            , get_string( 'reportrank_table_column_correctanswers', 'studentquiz')
            , get_string( 'reportrank_table_column_incorrectanswers', 'studentquiz')
            );
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $table->data = array();
        $rows = array();
        $rank = 1;
        foreach ($report->get_user_ranking() as $ur) {
            $cellrank = new html_table_cell();
            $cellrank->text = $rank;
            $cellfullname = new html_table_cell();

            $tmp = $ur->firstname . ' ' . $ur->lastname;
            if ($report->is_anonym()) {
                if (!$report->is_loggedin_user($ur->userid)) {
                    // TODO: code smell: magic constant
                    $tmp = 'anonymous';
                }
            }
            $cellfullname->text = $tmp;

            $cellpoints = new html_table_cell();
            $cellpoints->text = $ur->points;

            $cellcountquestions = new html_table_cell();
            $cellcountquestions->text = $ur->countquestions;

            $cellsummeanvotes = new html_table_cell();
            $cellsummeanvotes->text = $ur->summeanvotes;

            $cellcorrectanswers = new html_table_cell();
            $cellcorrectanswers->text = $ur->correctanswers;

            $cellincorrectanswers = new html_table_cell();
            $cellincorrectanswers->text = $ur->incorrectanswers;

            $row = new html_table_row();

            if ($report->is_loggedin_user($ur->userid)) {
                $style = array('class' => 'mod-studentquiz-summary-highlight');
                $cellrank->attributes = $style;
                $cellfullname->attributes = $style;
                $cellpoints->attributes = $style;
                $cellcountquestions->attributes = $style;
                $cellsummeanvotes->attributes = $style;
                $cellcorrectanswers->attributes = $style;
                $cellincorrectanswers->attributes = $style;
                $row->attributes = $style;
            }
            $row->cells = array($cellrank, $cellfullname, $cellpoints,
                $cellcountquestions, $cellsummeanvotes, $cellcorrectanswers, $cellincorrectanswers);
            $rows[] = $row;
            ++$rank;
        }
        $table->data = $rows;
        return  html_writer::table($table);
    }

    /**
     * Builds the quiz report table for the admin
     * @param mod_studentquiz_report $report studentquiz_report class with necessary information
     * @param stdClass $usersdata
     * @return string rank report table
     */
    public function view_quizreport_table($report, $usersdata) {
        $output = $this->heading(get_string('reportquiz_admin_title', 'studentquiz'), 2, 'reportquiz_total_heading');
        $table = new html_table();
        $table->attributes['class'] = 'generaltable boxaligncenter';
        $table->head = array(get_string('reportrank_table_column_fullname', 'studentquiz')
            , get_string('reportquiz_total_attempt', 'studentquiz')
            , get_string('reportquiz_total_questions_answered', 'studentquiz')
            , get_string('reportquiz_total_questions_right', 'studentquiz')
            , get_string('reportquiz_total_obtained_marks', 'studentquiz'));
        $table->align = array('left', 'left');
        $table->size = array('', '');
        $table->data = array();
        $rows = array();
        foreach ($usersdata as $user) {
            $cellfullname = new html_table_cell();
            $cellfullname->text = $user->name;

            $cellnumattempts = new html_table_cell();
            $cellnumattempts->text = $user->numattempts;

            $cellobtainedmarks = new html_table_cell();
            $cellobtainedmarks->text = $user->attemptedgrade . ' / ' . $user->maxgrade;

            $cellquestionsanswered = new html_table_cell();
            $cellquestionsanswered->text = $user->questionsanswered;

            $cellquestionsright = new html_table_cell();
            $cellquestionsright->text = $user->questionsright;

            $row = new html_table_row();

            if ($report->is_loggedin_user($user->id)) {
                $style = array('class' => 'mod-studentquiz-summary-highlight');
                $cellfullname->attributes = $style;
                $cellnumattempts->attributes = $style;
                $cellobtainedmarks->attributes = $style;
                $cellquestionsanswered->attributes = $style;
                $cellquestionsright->attributes = $style;
                $row->attributes = $style;
            }
            $row->cells = array($cellfullname, $cellnumattempts, $cellquestionsanswered
            , $cellquestionsright, $cellobtainedmarks);
            $rows[] = $row;
        }
        $table->data = $rows;
        $output .= html_writer::table($table);
        return $output;
    }

    /**
     * Builds the quiz admin report view with the created quizzes
     * @param mod_studentquiz_report $report studentquiz_report class with necessary information
     * @param stdClass $quizzes
     * @return string rank report table
     */

    /**
     * Builds the quiz report total section
     * @param stdClass $total
     * @param stdClass $usergrades
     * @param bool $isadmin
     * @return string quiz report data
     */
    public function view_quizreport_stats($total, $owntotal, $stats, $usergrades, $isadmin = false) {
        $output = '';
        $output .= $this->heading(get_string('reportquiz_stats_title', 'studentquiz'), 2, 'reportquiz_stats_heading');

        // No stats for admin yet.
        if ($stats != null) {
            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_stats_nr_of_questions', 'studentquiz') . ': ', 'reportquiz_total_label')
                .html_writer::span($stats->totalnrofquestions)
            );

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_stats_nr_of_own_questions', 'studentquiz')
                    . ': ', 'reportquiz_total_label')
                .html_writer::span($stats->totalusersquestions)
            );

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_stats_nr_of_approved_questions', 'studentquiz')
                    . ': ', 'reportquiz_total_label')
                .html_writer::span($stats->numapproved)
            );

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_stats_avg_rating', 'studentquiz')
                    . ': ', 'reportquiz_total_label')
                .html_writer::span($stats->avgvotes)
            );

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_stats_right_answered_questions', 'studentquiz')
                    . ': ', 'reportquiz_total_label')
                .html_writer::span($stats->totalrightanswers)
            );

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_stats_own_grade_of_max', 'studentquiz') . ': ', 'reportquiz_total_label')
                .html_writer::span($usergrades->usermark . ' / ' . $usergrades->stuquizmaxmark)
            );
        }

        if ($owntotal) {
            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_stats_attempt', 'studentquiz') . ': ', 'reportquiz_total_label')
                . html_writer::span($owntotal->numattempts)
            );

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_stats_questions_answered', 'studentquiz') . ': ', 'reportquiz_total_label')
                . html_writer::span($owntotal->questionsanswered)
            );

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_stats_questions_right', 'studentquiz') . ': ', 'reportquiz_total_label')
                . html_writer::span($owntotal->questionsright)
            );
            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_stats_learning_quotient', 'studentquiz') . ': ', 'reportquiz_total_label')
                . html_writer::span(1 - (($owntotal->questionsright)/($owntotal->questionsanswered + $owntotal->questionsright)))
            );
        }
        if ($total != null && false) {
            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_total_attempt', 'studentquiz') . ': ', 'reportquiz_total_label')
                . html_writer::span($total->numattempts)
            );

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_total_questions_answered', 'studentquiz') . ': ', 'reportquiz_total_label')
                . html_writer::span($total->questionsanswered)
            );

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_total_questions_right', 'studentquiz') . ': ', 'reportquiz_total_label')
                . html_writer::span($total->questionsright)
            );

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_total_questions_wrong', 'studentquiz') . ': ', 'reportquiz_total_label')
                . html_writer::span(($total->questionsanswered - $total->questionsright))
            );
            // Ex Label with: reportquiz_total_label and $total->obtainedmarks,.

            $output .= html_writer::tag('p',
                html_writer::span(get_string('reportquiz_total_users', 'studentquiz') . ': ', 'reportquiz_total_label')
                . html_writer::span($total->usercount)
            );
        }

        return $output;
    }

    /**
     * Builds the studentquiz_bank_view
     * @param studentquiz_view $view studentquiz_view class with the necessary information
     */
    public function display_questionbank($view) {
        echo '<div class="questionbankwindow boxwidthwide boxaligncenter">';
        $pagevars = $view->get_qb_pagevar();
        $view->get_questionbank()->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
            $pagevars['cat'], false, $pagevars['showhidden'],
            $pagevars['qbshowtext']);

        if ($view->has_printableerror()) {
            echo $this->show_error($view->get_errormessage());
        }

        echo "</div>\n";
    }

    /**
     * Prints the error message
     * @param string $errormessage string error message
     * @return string error as HTML
     */
    public function show_error($errormessage) {
        return html_writer::div($errormessage, 'error');
    }

    /**
     * Generate some HTML to display comment list
     *
     * @param  int $questionid Question id
     * @return string HTML fragment
     */
    public function comment_list($questionid) {
        return studentquiz_comment_renderer($questionid);
    }

    /**
     * Add submit button to controls
     *
     * @param question_attempt $qa a question attempt.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
/*    public function controls(question_attempt $qa, question_display_options $options) {
        return $this->submit_button($qa, $options);
    }
*/

    /**
     * Generate some HTML (which may be blank) that appears in the outcome area,
     * after the question-type generated output.
     *
     * For example, the CBM models use this to display an explanation of the score
     * adjustment that was made based on the certainty selected.
     *
     * @param question_definition $question the current question.
     * @param question_display_options $options controls what should and should not be displayed.
     * @return string HTML fragment.
     */
    public function feedback(question_definition $question, question_display_options $options) {
        // TODO: ->feedback enabled?
        //if ($options->feedback) {
            global $CFG;
            return html_writer::div(
                    $this->render_vote($question->id)
                    . $this->render_comment($question->id), 'studentquiz_behaviour')
                . html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'baseurlmoodle'
                , 'id' => 'baseurlmoodle', 'value' => $CFG->wwwroot))
                . html_writer::start_div('none')
                . html_writer::start_div('none');
        //}
        //return '';
    }

    /**
     * Generate some HTML to display rating options
     *
     * @param  int $questionid Question id
     * @param  boolean $selected shows the selected vote
     * @param  boolean $readonly describes if rating is readonly
     * @return string HTML fragment
     */
    protected function vote_choices($questionid, $selected, $readonly) {
        $attributes = array(
            'type' => 'radio',
            'name' => 'q' . $questionid,
        );

        if ($readonly) {
            $attributes['disabled'] = 'disabled';
        }

        $selected = (int)$selected;

        $rateable = '';
        if (!$readonly) {
            $rateable = 'rateable ';
        }

        $choices = '';
        $votes = [5, 4, 3, 2, 1];
        foreach ($votes as $vote) {
            $class = 'star-empty';
            if ($vote <= $selected) {
                $class = 'star';
            }
            $choices .= html_writer::span('', $rateable . $class, array('data-rate' => $vote, 'data-questionid' => $questionid));
        }
        return get_string('vote_title', 'mod_studentquiz')
            . $this->output->help_icon('vote_help', 'mod_studentquiz') . ': '
            . html_writer::div($choices, 'rating')
            . html_writer::div(get_string('vote_error', 'mod_studentquiz'), 'hide error');
    }

    /**
     * Generate some HTML to display comment form for add comment
     *
     * @param  int $questionid Question id
     * @return string HTML fragment
     */
    protected function comment_form($questionid) {
        return html_writer::tag('p', get_string('add_comment', 'mod_studentquiz')
                . $this->output->help_icon('comment_help', 'mod_studentquiz') . ':')
            . html_writer::tag('p', html_writer::tag(
                'textarea', '',
                array('class' => 'add_comment_field', 'name' => 'q' . $questionid)))
            . html_writer::tag('p', html_writer::tag(
                'button',
                get_string('add_comment', 'mod_studentquiz'),
                array('type' => 'button', 'class' => 'add_comment'))
            );
    }

    /**
     * Generate some HTML to display rating
     *
     * @param  int $questionid Question id
     * @return string HTML fragment
     */
    protected function render_vote($questionid) {
        global $DB, $USER;

        $value = -1; $readonly = false;
        $vote = $DB->get_record('studentquiz_vote', array('questionid' => $questionid, 'userid' => $USER->id));
        if ($vote !== false) {
            $value = $vote->vote;
            $readonly = true;
        }

        return html_writer::div($this->vote_choices($questionid, $value , $readonly), 'vote');
    }

    /**
     * Generate some HTML to display the complete comment fragment
     *
     * @param  int $questionid Question id
     * @return string HTML fragment
     */
    protected function render_comment($questionid) {
        return html_writer::div(
            $this->comment_form($questionid)
            . html_writer::div($this->comment_list($questionid), 'comment_list'), 'comments');
    }
}
