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
 * Defines the renderers for the StudentQuiz module.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Base renderer for Studentquiz with helpers
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_renderer extends plugin_renderer_base {

    /**
     * TODO: document blocks missing everywhere here
     * @param $celldata
     * @param $rowattributes
     * @return array
     */
    public function render_table_data(array $celldata, array $rowattributes=array()) {
        $rows = array();
        foreach($celldata as $num => $row){
            $cells = array();
            foreach($row as $cell){
                if (!empty($rowattributes[$num])) {
                    $cells[] = $this->render_table_cell($cell, $rowattributes[$num]);
                } else {
                    $cells[] = $this->render_table_cell($cell);
                }
            }
            $rows[] = $this->render_table_row($cells);
        }
        return $rows;
    }

    public function render_table_cell(string $text, array $attributes=array()) {
        $cell = new html_table_cell();
        $cell->text = $text;
        if(!empty($attributes)) {
            $cell->attributes = $attributes;
        }
        return $cell;
    }

    public function render_stat_block($report) {
        // TODO: Refactor: use mod_studentquiz_report_record_type!
        $userstats = $report->get_user_stats();
        $sqstats = $report->get_studentquiz_stats();
        if(!$userstats) {
            $bc = new block_contents();
            $bc->attributes['id'] = 'mod_studentquiz_statblock';
            $bc->attributes['role'] = 'navigation';
            $bc->attributes['aria-labelledby'] = 'mod_studentquiz_navblock_title';
            $bc->title = html_writer::span(get_string('statistic_block_title', 'studentquiz'));
            $bc->content = get_string('please_enrole_message', 'studentquiz');
            return $bc;
        }
        $bc = new block_contents();
        $bc->attributes['id'] = 'mod_studentquiz_statblock';
        $bc->attributes['role'] = 'navigation';
        $bc->attributes['aria-labelledby'] = 'mod_studentquiz_navblock_title';
        $bc->title = html_writer::span(get_string('statistic_block_title', 'studentquiz'));
        $info1 = new stdClass();
        $info1->total = $sqstats->questions_available;
        $info1->group = $userstats->last_attempt_exists;
        $info1->one = $userstats->last_attempt_correct;
        $info2 = new stdClass();
        $info2->total = $userstats->questions_created;
        $info2->group = 0;
        $info2->one = $userstats->questions_approved;
        $bc->content =
            html_writer::div($this->render_progress_bar($info1), '', array('style' => 'width:inherit'))
             . html_writer::div(
                get_string('statistic_block_progress_last_attempt_correct', 'studentquiz')
                .html_writer::span('<b>' .$userstats->last_attempt_correct .'</b>', '',
                    array('style' => 'float: right;color:#5cb85c;')))
            . html_writer::div(
                get_string('statistic_block_progress_last_attempt_incorrect', 'studentquiz')
                .html_writer::span('<b>' .$userstats->last_attempt_incorrect .'</b>', '',
                    array('style' => 'float: right;color:#d9534f;')))
            . html_writer::div(
                get_string('statistic_block_progress_never', 'studentquiz')
                .html_writer::span('<b>' . ($sqstats->questions_available - $userstats->last_attempt_exists) .'</b>', '',
                    array('style' => 'float: right;color:#f0ad4e;')))
            . html_writer::div(
                get_string('statistic_block_progress_available', 'studentquiz')
                .html_writer::span('<b>' .$sqstats->questions_available .'</b>', '',
                    array('style' => 'float: right;')))
            . html_writer::div($this->render_progress_bar($info2), '', array('style' => 'width:inherit'))
            . html_writer::div(get_string('statistic_block_approvals', 'studentquiz')
                .html_writer::span('<b>' .$userstats->questions_approved .'</b>','',
                    array('style' => 'float: right;color:#28A745;')))
            . html_writer::div(get_string('statistic_block_created', 'studentquiz')
                .html_writer::span('<b>' .$userstats->questions_created .'</b>','',
                    array('style' => 'float: right;')));
        return $bc;
    }

    public function render_ranking_block($report) {
        $ranking = $report->get_user_ranking_table(0, 10);
        $currentuserid = $report->get_user_id();
        $anonymname = get_string('creator_anonym_firstname', 'studentquiz') . ' '
                        . get_string('creator_anonym_lastname', 'studentquiz');
        $anonymise = $report->is_anonymized();
        $rows = array();
        $rank = 1;
        foreach($ranking as $row) {
            if($currentuserid == $row->userid || !$anonymise) {
                $name = $row->firstname .' ' . $row->lastname;
            } else  {
                $name = $anonymname;
            }
            $rows[] = \html_writer::div($rank . '. ' . $name .
                html_writer::span(html_writer::tag('b' , round($row->points)),
                    '', array('style' => 'float: right;')));
            $rank++;
            if($rank > 10) break;
        }
        $ranking->close();
        $bc = new block_contents();
        $bc->attributes['id'] = 'mod_studentquiz_rankingblock';
        $bc->attributes['role'] = 'navigation';
        $bc->attributes['aria-labelledby'] = 'mod_studentquiz_navblock_title';
        $bc->title = html_writer::span(get_string('ranking_block_title', 'studentquiz'));
        $bc->content = implode('', $rows);
        return $bc;
    }

    public function render_table_row($cells) {
        $row = new html_table_row();
        $row->cells = $cells;
        return $row;
    }

    public function render_table($data, $size, $align, $head, $caption) {
        $table = new html_table();
        if(!empty($caption)) {
            $table->caption = $caption;
        }
        $table->head = $head;
        $table->align = $align;
        $table->size = $size;
        $table->data = $data;
        return html_writer::table($table);
    }

    /**
     * Return a svg representing a progress bar filling 100% of is containing element
     * @param stdClass $info: total, group, one
     * @param string $texttotal: text to be displayed in the center of the bar.
     * @param bool bicolor: only bicolor color scheme.
     * @return string
     */
    public function render_progress_bar($info, $texttotal=null, $bicolor=false) {

        // Check input.
        $validInput = true;
        if (!isset($info->total)) {
            $validInput = false;
        }

        if (!isset($info->group)) {
            $validInput = false;
        }

        if (!isset($info->one)) {
            $validInput = false;
        }

        // Stylings.
        $rgb_stroke = 'rgb(200,200,200)';
        $rgb_yellow = 'rgb(255,193,7)';
        $rgb_green = 'rgb(40, 167, 69)';
        $rgb_blue = 'rgb(2, 117, 216)';
        $rgb_red = 'rgb(220, 53, 69)';
        $rgb_grey = 'rgb(200, 200, 200)';
        $bar_stroke = 'stroke-width:0.1;stroke:' . $rgb_stroke .';';
        $svg_dims = array('width' => '100%', 'height' => 20);
        $bar_dims = array('height' => '100%', 'rx' => 5, 'ry' => 5);
        $id_blue = 'blue';
        $id_green = 'green';
        $id_red = 'red';
        $gradient_dims = array('cx' => '50%', 'cy' => '50%', 'r' => '50%', 'fx' => '50%', 'fy' => '50%');
        $stopColorGreen = html_writer::tag('stop', null,
            array('offset' => '100%','style' => 'stop-color:' . $rgb_green . ';stop-opacity:1'));
        $stopColorRed = html_writer::tag('stop', null,
            array('offset' => '100%','style' => 'stop-color:' . $rgb_red . ';stop-opacity:1'));
        $stopColorBlue = html_writer::tag('stop', null,
            array('offset' => '100%','style' => 'stop-color:' . $rgb_blue . ';stop-opacity:1'));
        $gradientBlue = html_writer::tag('radialGradient', $stopColorBlue . $stopColorBlue,
            array_merge($gradient_dims, array('id' => $id_blue)));
        $gradientRed = html_writer::tag('radialGradient', $stopColorRed . $stopColorRed,
            array_merge($gradient_dims, array('id' => $id_red)));
        $gradientGreen = html_writer::tag('radialGradient', $stopColorGreen . $stopColorGreen,
            array_merge($gradient_dims, array('id' => $id_green)));
        $gradients = array($gradientRed, $gradientGreen, $gradientBlue);
        $defs = html_writer::tag('defs', implode($gradients));


        // Background bar.
        if($bicolor) {
            $barbackground = html_writer::tag('rect', null, array_merge($bar_dims,
                array('width' => '100%', 'style' => $bar_stroke . 'fill:' . $rgb_grey )));
        } else {
            $barbackground = html_writer::tag('rect', null, array_merge($bar_dims,
                array('width' => '100%', 'style' => $bar_stroke . 'fill:' . $rgb_yellow)));
        }

        // Return empty bar if no questions are in StudentQuiz.
        if( !$validInput || $info->total <= 0) {
            return html_writer::tag('svg', $barbackground, $svg_dims);
        }

        // Calculate Percentages to display.
        $percent_group = round(100 * ($info->group / $info->total));
        $percent_one = round(100 * ($info->one / $info->total));

        if(!empty($texttotal)) {
            $text = '
             <text xml:space="preserve" text-anchor="start" font-family="Helvetica, Arial, sans-serif" 
             font-size="12" font-weight="bold" id="svg_text" x="50%" y="50%" alignment-baseline="middle" text-anchor="middle" stroke-width="0" stroke="#000" fill="#000000">' . $texttotal . '</text>';
        }else {
            $text = '';
        }

        // Return stacked bars.
        $bars = array($barbackground);
        if($bicolor) {
            $bars[] = html_writer::tag('rect', null, array_merge($bar_dims,
                array('width' => $percent_one . '%', 'style' => $bar_stroke . 'fill:url(#' . $id_blue .')')));
        } else {
            $bars[] = html_writer::tag('rect', null, array_merge($bar_dims,
                array('width' => $percent_group . '%', 'style' => $bar_stroke . 'fill:url(#' . $id_red .')')));
            $bars[] = html_writer::tag('rect', null, array_merge($bar_dims,
                array('width' => $percent_one . '%', 'style' => $bar_stroke . 'fill:url(#' . $id_green .')')));
        }
        return html_writer::tag('svg', $defs . implode($bars) . $text, $svg_dims);
    }


    /**
     * @Override from core_question renderer
     * Render an icon, optionally with the word 'Preview' beside it, to preview
     * a given question.
     * @param stdClass $question object of the question to be previewed.
     * @param int $cmid the currend coursemodule id.
     * @param bool $showlabel if true, show the word 'Preview' after the icon.
     *      If false, just show the icon.
     */
    public function question_preview_link($question, $context, $showlabel) {
        if ($showlabel) {
            $alt = '';
            $label = ' ' . get_string('preview');
            $attributes = array();
        } else {
            $alt = get_string('preview');
            $label = '';
            $attributes = array('title' => $alt);
        }

        $image = $this->pix_icon('t/preview', $alt, '', array('class' => 'iconsmall'));
        //$link = question_preview_url($questionid, null, null, null, null, $context);
        $params = array('cmid' => $context->instanceid, 'questionid' => $question->id);
        $link = new moodle_url('/mod/studentquiz/preview.php', $params);
        $action = new popup_action('click', $link, 'questionpreview',
            question_preview_popup_params());
        return $this->action_link($link, $image . $label, $action, $attributes);
    }

    /**
     * Prints the error message
     * @param string $errormessage string error message
     * @return string error as HTML
     */
    public function show_error($errormessage) {
        return html_writer::div($errormessage, 'error');
    }

}


class mod_studentquiz_summary_renderer extends mod_studentquiz_renderer {
    /**
     * Renders the summary object given to html
     * @param mod_studentquiz_summary_view $summary summary view obj.
     * @return summary html
     */
    public function render_summary($summary) {
        $output = '';
        $output .= html_writer::start_tag('form', array('method' => 'post', 'action' => '',
            'enctype' => 'multipart/form-data', 'id' => 'responseform'));
        $output .= html_writer::start_tag('div', array('align' => 'center'));
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'back', 'value' => get_string('review_button', 'studentquiz')));
        $output .= html_writer::empty_tag('br');
        $output .= html_writer::empty_tag('br');
        $output .= html_writer::empty_tag('input', array('type' => 'submit',
            'name' => 'finish', 'value' => get_string('finish_button', 'studentquiz')));
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');
        return $output;
    }
}

class mod_studentquiz_overview_renderer extends mod_studentquiz_renderer {

    /**
     * Builds the studentquiz_bank_view
     * @param studentquiz_view $view studentquiz_view class with the necessary information
     * @return string formatted html
     */
    public function render_overview($view)
    {
        $contents = '';

        if (!optional_param('deleteselected', false, PARAM_BOOL) && !optional_param('approveselected', false, PARAM_BOOL)) {
            $contents .= $this->heading(format_string($view->get_studentquiz_name()));
            $contents .= $this->render_select_qtype_form($view);
        }

        $contents .= $this->render_questionbank($view);

        if ($view->has_printableerror()) {
            $contents .= $this->show_error($view->get_errormessage());
        }

        return html_writer::tag('div', $contents, array('class' => implode(' ',
                array('questionbankwindow', 'boxwidthwide', 'boxaligncenter'))));
    }

    /**
     * @param $view
     * @return mixed
     * TODO: REFACTOR!
     */
    public function render_questionbank($view) {
        $pagevars = $view->get_qb_pagevar();
        return $view->get_questionbank()->display('questions', $pagevars['qpage'], $pagevars['qperpage'],
            $pagevars['cat'], false, $pagevars['showhidden'], $pagevars['qbshowtext']);
    }

    /**
     * @param $view
     * @return string
     */
    public function render_select_qtype_form($view) {
        return $view->get_questionbank()->create_new_question_form($view->get_category_id(), true);
    }
}

class mod_studentquiz_attempt_renderer extends mod_studentquiz_renderer {
    /**
     * Generate some HTML to display comment list
     * @param array $comments comments joined by user.firstname and user.lastname, ordered by createdby ASC
     * @param int $userid viewing user id
     * @param bool $anonymize users can't see other comment authors user names except ismoderator
     * @param bool $ismoderator can delete all comments, can see all usernames
     * @return string HTML fragment
     * TODO: move mod_studentquiz_comment_renderer in here!
     */
    public function comment_list($comments, $userid, $anonymize = true, $ismoderator = false) {
        return mod_studentquiz_comment_renderer($comments, $userid, $anonymize, $ismoderator);
    }

    /**
     * Generate some HTML (which may be blank) that appears in the outcome area,
     * after the question-type generated output.
     *
     * For example, the CBM models use this to display an explanation of the score
     * adjustment that was made based on the certainty selected.
     *
     * @param question_definition $question the current question.
     * @param question_display_options $options controls what should and should not be displayed.
     * @param array $comments comments joined by user.firstname and user.lastname, ordered by createdby ASC
     * @param int $userid viewing user id
     * @param bool $anonymize users can't see other comment authors user names except ismoderator
     * @param bool $ismoderator can delete all comments, can see all usernames
     * @return string HTML fragment
     * @return string HTML fragment.
     */
    public function feedback(question_definition $question,
                             question_display_options $options, $cmid,
                             $comments, $userid, $anonymize = true, $ismoderator = false) {
        global $CFG;
        return html_writer::div($this->render_rate($question->id)
                . $this->render_comment($cmid, $question->id, $comments, $userid, $anonymize, $ismoderator), 'studentquiz_behaviour')
            . html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'baseurlmoodle'
            , 'id' => 'baseurlmoodle', 'value' => $CFG->wwwroot))
            . html_writer::start_div('none')
            . html_writer::start_div('none');
    }

    /**
     * Generate some HTML to display rating options
     *
     * @param  int $questionid Question id
     * @param  boolean $selected shows the selected rate
     * @param  boolean $readonly describes if rating is readonly
     * @return string HTML fragment
     */
    protected function rate_choices($questionid, $selected, $readonly) {
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
        $rates = [5, 4, 3, 2, 1];
        foreach ($rates as $rate) {
            $class = 'star-empty';
            if ($rate <= $selected) {
                $class = 'star';
            }
            $choices .= html_writer::span('', $rateable . $class, array('data-rate' => $rate, 'data-questionid' => $questionid));
        }
        return get_string('rate_title', 'mod_studentquiz')
            . $this->output->help_icon('rate_help', 'mod_studentquiz') . ': '
            . html_writer::div($choices, 'rating')
            . html_writer::div(get_string('rate_error', 'mod_studentquiz'), 'hide error');
    }

    /**
     * Generate some HTML to display comment form for add comment
     *
     * @param  int $questionid Question id
     * @return string HTML fragment
     */
    protected function comment_form($questionid, $cmid) {
        return html_writer::tag('p', get_string('add_comment', 'mod_studentquiz')
                . $this->output->help_icon('comment_help', 'mod_studentquiz') . ':')
            . html_writer::tag('p', html_writer::tag(
                'textarea', '',
                array('class' => 'add_comment_field', 'name' => 'q' . $questionid)))
            . html_writer::tag('p', html_writer::tag(
                'button',
                get_string('add_comment', 'mod_studentquiz'),
                array('type' => 'button', 'class' => 'add_comment'))
            )
            . html_writer::tag('input', '', array('type' => 'hidden', 'name' => 'cmid', 'value' => $cmid));
    }

    /**
     * Generate some HTML to display rating
     *
     * @param  int $questionid Question id
     * @param array $comments comments joined by user.firstname and user.lastname, ordered by createdby ASC
     * @param int $userid viewing user id
     * @param bool $anonymize users can't see other comment authors user names except ismoderator
     * @param bool $ismoderator can delete all comments, can see all usernames
     * @return string HTML fragment
     * @return string HTML fragment
     */
    protected function render_rate($questionid) {
        global $DB, $USER;

        $value = -1; $readonly = false;
        $rate = $DB->get_record('studentquiz_rate', array('questionid' => $questionid, 'userid' => $USER->id));
        if ($rate !== false) {
            $value = $rate->rate;
            $readonly = true;
        }

        return html_writer::div($this->rate_choices($questionid, $value , $readonly), 'rate');
    }

    /**
     * Generate some HTML to display the complete comment fragment
     *
     * @param  int $questionid Question id
     * @return string HTML fragment
     */
    protected function render_comment($cmid, $questionid, $comments, $userid, $anonymize = true, $ismoderator = false) {
        return html_writer::div(
            $this->comment_form($questionid, $cmid)
            . html_writer::div($this->comment_list($comments, $userid, $anonymize, $ismoderator),
                'comment_list'), 'comments');
    }
}

class mod_studentquiz_report_renderer extends mod_studentquiz_renderer{

    /**
     * Get quiz admin statistic view
     * $userid of viewing user
     * @param mod_studentquiz_report $report
     * @return string pre rendered /mod/stundentquiz view_quizreport_table
     */
    public function view_stat(mod_studentquiz_report $report) {
        $output = '';
        $userstats = $report->get_user_stats();
        if(!$userstats) {
            global $OUTPUT;
            $output .= $OUTPUT->notification(get_string('please_enrole_message', 'studentquiz'), 'notify');
            $userstats = $report->get_zero_user_stats();
        }
        $output .= $this->view_stat_cards(
            $report->get_studentquiz_stats(),
            $userstats
            );
        return $output;
    }

    /**
     * Builds the quiz report total section
     * @param stdClass $total
     * @param stdClass $usergrades
     * @return string quiz report data
     */
    public function view_stat_cards($studentquizstats, $userrankingstats) {
        $align = array('left', 'right', '', 'left', 'right', '');
        $size = array('300px', '40px', '77px', '300px', '40px', '*');
        $head = array(
            get_string('reportrank_table_column_yourstatus', 'studentquiz'),
            get_string('reportrank_table_column_value', 'studentquiz'),
            ''
            /* spacing */,
            get_string('reportrank_table_column_communitystatus', 'studentquiz'),
            get_string('reportrank_table_column_value', 'studentquiz'), ''
        );

        // Protect from zero division
        if (empty($studentquizstats->participated)) {
            $participated = 1;
        } else {
            $participated = $studentquizstats->participated;
        }

        if(empty($studentquizstats->questions_available)) {
            $questions_available = 1;
        } else {
            $questions_available = $studentquizstats->questions_available;
        }

        $celldata = array(
            array(
                get_string('reportquiz_stats_own_questions_created', 'studentquiz'),
                $userrankingstats->questions_created, '',
                get_string('reportquiz_stats_all_questions_created', 'studentquiz'),
                $studentquizstats->questions_available, ''
            ),
            array(
                html_writer::span(
                    get_string('reportquiz_stats_own_questions_approved', 'studentquiz'),
                    '', array('title' =>
                    get_string('reportquiz_stats_own_questions_approved_help', 'studentquiz'))),
                html_writer::span(
                    $userrankingstats->questions_approved,
                    '', array('title' =>
                    get_string('reportquiz_stats_own_questions_approved_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_questions_approved', 'studentquiz'),
                    '', array('title' =>
                    get_string('reportquiz_stats_all_questions_approved_help', 'studentquiz'))),
                html_writer::span($studentquizstats->questions_approved,
                    '', array('title' =>
                    get_string('reportquiz_stats_all_questions_approved_help', 'studentquiz'))), ''
            ),
            array(
                html_writer::span(
                get_string('reportquiz_stats_own_rates_average', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_own_rates_average_help', 'studentquiz'))),
                html_writer::span(
                round($userrankingstats->rates_average, 2),
                    '', array('title' => get_string('reportquiz_stats_own_rates_average_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_rates_average', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_all_rates_average_help', 'studentquiz'))),
                html_writer::span(
                    round($studentquizstats->questions_average_rating, 2),
                    '', array('title' => get_string('reportquiz_stats_all_rates_average_help', 'studentquiz'))), ''
            ),
            array(
                html_writer::span(
                    get_string('reportquiz_stats_own_questions_answered', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_own_questions_answered_help', 'studentquiz'))),
                html_writer::span(
                    $userrankingstats->question_attempts,
                    '', array('title' => get_string('reportquiz_stats_own_questions_answered_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_questions_answered', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_all_questions_answered_help', 'studentquiz'))),
                html_writer::span(round($studentquizstats->question_attempts / $participated, 2),
                    '', array('title' => get_string('reportquiz_stats_all_questions_answered_help', 'studentquiz'))), ''
            ),
            array(
                html_writer::span(
                    get_string('reportquiz_stats_own_percentage_correct_answers', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_own_percentage_correct_answers_help', 'studentquiz'))),
                html_writer::span(
                    (($userrankingstats->question_attempts > 0)?
                    100 * round($userrankingstats->question_attempts_correct / $userrankingstats->question_attempts, 2) : 0) . ' %',
                    '', array('title' => get_string('reportquiz_stats_own_percentage_correct_answers_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_percentage_correct_answers', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_all_percentage_correct_answers_help', 'studentquiz'))),
                html_writer::span(
                    (($studentquizstats->question_attempts > 0)?
                100 * round($studentquizstats->question_attempts_correct / $studentquizstats->question_attempts, 2) : 0) . ' %',
                    '', array('title' => get_string('reportquiz_stats_all_percentage_correct_answers_help', 'studentquiz'))), ''
            ),
            array(
                html_writer::span(
                    get_string('reportquiz_stats_own_progress', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_own_progress_help', 'studentquiz'))),
                html_writer::span((100 * round($userrankingstats->last_attempt_correct / ($questions_available), 2)) . ' %',
                    '', array('title' => get_string('reportquiz_stats_own_progress_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_progress', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_all_progress_help', 'studentquiz'))),
                html_writer::span(
                    (100 * round(($studentquizstats->last_attempt_correct / ($questions_available * $participated)), 2)) . ' %',
                    '', array('title' => get_string('reportquiz_stats_all_progress_help', 'studentquiz'))), ''
            )
        );
        $data = $this->render_table_data($celldata);
        return $this->render_table($data, $size, $align, $head, null);
    }
}

class mod_studentquiz_ranking_renderer extends mod_studentquiz_renderer {

    /**
     * @param $report
     * TODO: proper docs
     */
    public function view_rank($report) {
       return $this->heading(get_string('reportrank_title', 'studentquiz'))
                . $this->view_quantifier_information($report)
                . $this->view_rank_table($report);
    }

    /**
     * displays quantifier information
     * TODO: proper docs
     */
    public function view_quantifier_information($report) {
        $align = array('left', 'left');
        $size = array('', '', '');
        $head = array(get_string('reportrank_table_column_quantifier_name', 'studentquiz')
        , get_string('reportrank_table_column_factor', 'studentquiz')
        , get_string('reportrank_table_column_description', 'studentquiz'));
        $caption = get_string('reportrank_table_quantifier_caption', 'studentquiz');
        $celldata = array(
            array(get_string('settings_questionquantifier', 'studentquiz'),
                $report->get_quantifier_question(),
                'description' => get_string('settings_questionquantifier_help', 'studentquiz')),
            array(get_string('settings_approvedquantifier', 'studentquiz'),
                $report->get_quantifier_approved(),
                'description' => get_string('settings_approvedquantifier_help', 'studentquiz')),
            array('text' => get_string('settings_ratequantifier', 'studentquiz'),
                $report->get_quantifier_rate(),
                'value' => get_string('settings_ratequantifier_help', 'studentquiz')),
            array('text' => get_string('settings_correctanswerquantifier', 'studentquiz'),
                $report->get_quantifier_correctanswer(),
                'value' => get_string('settings_correctanswerquantifier_help', 'studentquiz')),
            array('text' => get_string('settings_incorrectanswerquantifier', 'studentquiz'),
                $report->get_quantifier_incorrectanswer(),
                'value' => get_string('settings_incorrectanswerquantifier_help', 'studentquiz'))
        );
        $data = $this->render_table_data($celldata);
        return $this->render_table($data, $size, $align, $head, $caption);
    }

    /**
     * builds the rank report table
     * @param mod_studentquiz_report $report studentquiz_report class with necessary information
     * @return string rank report table
     * @throws coding_exception
     * TODO: TODO: REFACTOR! Paginate ranking table or limit its length.
     */
    public function view_rank_table($report) {
        $align = array('left', 'left');
        $size = array('', '', '');
        $head = array(get_string('reportrank_table_column_rank', 'studentquiz')
        , get_string('reportrank_table_column_fullname', 'studentquiz')
        , get_string('reportrank_table_column_total_points', 'studentquiz')
        , get_string( 'reportrank_table_column_countquestions', 'studentquiz')
        , get_string( 'reportrank_table_column_approvedquestions', 'studentquiz')
        , get_string( 'reportrank_table_column_summeanrates', 'studentquiz')
        , get_string( 'reportrank_table_column_correctanswers', 'studentquiz')
        , get_string( 'reportrank_table_column_incorrectanswers', 'studentquiz')
        , get_string( 'reportrank_table_column_progress', 'studentquiz')
        );
        $caption = get_string('reportrank_table_title', 'studentquiz');
        $celldata = array();
        $rowstyle = array();

        // Todo: Get Pagination from request parameters!
        $limitfrom = 0;
        $limitnum = 0;
        $maxdisplayonpage = 10; // TODO: Make configurable.

        // Update rank offset to pagination.
        $rank = 1 + $limitfrom;
        $rankingresultset = $report->get_user_ranking_table($limitfrom, $limitnum);
        $numofquestions = $report->get_studentquiz_stats()->questions_created;
        $counter = 0;
        $userwasshown = false;
        $userid = $report->get_user_id();
        $seeall = has_capability('mod/studentquiz:manage', $report->get_context());
        foreach($rankingresultset as $ur) {
            $counter++;
            if (($counter > $maxdisplayonpage) && $userwasshown && !$seeall) {
                break;
            }
            if ($ur->userid == $userid) {
                $userwasshown = true;
            }else if($counter > $maxdisplayonpage) {
                $rank++;
                continue;
            }
            $username = $ur->firstname . ' ' . $ur->lastname;
            if ($report->is_anonymized()) {
                $username = get_string('creator_anonym_firstname', 'studentquiz') . ' ' . get_string('creator_anonym_lastname', 'studentquiz');
            }
            $celldata[] = array(
                $rank,
                $username,
                round($ur->points, 2),
                round($ur->questions_created * $report->get_quantifier_question(), 2),
                round($ur->questions_approved * $report->get_quantifier_approved(), 2),
                round($ur->rates_average * $ur->questions_created* $report->get_quantifier_rate(), 2),
                round($ur->last_attempt_correct * $report->get_quantifier_correctanswer(), 2),
                round($ur->last_attempt_incorrect * $report->get_quantifier_incorrectanswer(), 2),
                (100 * round($ur->last_attempt_correct / max($numofquestions, 1), 2)) . ' %'
            );
            $rowstyle[] = ($userid == $ur->userid)? array('class' => 'mod-studentquiz-summary-highlight'): array();
            $rank++;
        }
        $rankingresultset->close();
        $data = $this->render_table_data($celldata, $rowstyle);
        return $this->render_table($data, $size, $align, $head, $caption);
    }
}