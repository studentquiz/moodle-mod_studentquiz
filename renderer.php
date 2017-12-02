<?php
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

    public function render_stat_block(mod_studentquiz_report $report) {
        if(!$userstats = $report->get_user_stats()) {
            $userstats->questions_created = 0;
            $userstats->last_attempt_exists = 0;
            $userstats->questions_approved = 0;
            $userstats->last_attempt_correct = 0;
            $userstats->last_attempt_incorrect = 0;
        };
        $sqstats = $report->get_studentquiz_stats();
        $bc = new block_contents();
        $bc->attributes['id'] = 'mod_studentquiz_statblock';
        $bc->attributes['role'] = 'navigation';
        $bc->attributes['aria-labelledby'] = 'mod_studentquiz_navblock_title';
        $bc->title = html_writer::span(get_string('statistic_block_title', 'studentquiz'));
        $info1 = new stdClass();
        $info1->total = $sqstats->questions_created;
        $info1->group = $userstats->last_attempt_exists;
        $info1->one = $userstats->last_attempt_correct;
        $info2 = new stdClass();
        $info2->total = $userstats->questions_created;
        $info2->group = 0;
        $info2->one = $userstats->questions_approved;
        $bc->content =
            html_writer::div($this->render_progress_bar($info1), '', array('style' => 'width:inherit'))
            . html_writer::div(get_string('statistic_block_progress', 'studentquiz', $info1))
            . html_writer::div($this->render_progress_bar($info2), '', array('style' => 'width:inherit'))
            . html_writer::div(get_string('statistic_block_approvals', 'studentquiz', $info2));

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
        $table->caption = $caption;
        $table->head = $head;
        $table->align = $align;
        $table->size = $size;
        $table->data = $data;
        return html_writer::table($table);
    }

    /**
     * Return a svg representing a progress bar filling 100% of is containing element
     * @param stdClass $info: total, group, one
     * @return string
     */
    public function render_progress_bar($info) {

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
        $rgb_stroke = 'rgb(100,100,100)';
        $rgb_background = 'rgb(200,200,200)';
        $rgb_green = 'rgb(0,255,0)';
        $rgb_blue = 'rgb(0,0,255)';
        $rgb_white = 'rgb(255,255,255)';
        $bar_stroke = 'stroke-width:3;stroke:' . $rgb_stroke .';';
        $svg_dims = array('width' => '100%', 'height' => 20);
        $bar_dims = array('height' => '100%', 'rx' => 5, 'ry' => 5);
        $id_blue = 'blue';
        $id_green = 'green';
        $gradient_dims = array('cx' => '50%', 'cy' => '50%', 'r' => '50%', 'fx' => '50%', 'fy' => '50%');
        $stopColorWhite = html_writer::tag('stop', null,
            array('offset' => '0%', 'style' => 'stop-color:' . $rgb_white .';stop-opacity:1'));
        $stopColorGreen = html_writer::tag('stop', null,
            array('offset' => '100%','style' => 'stop-color:' . $rgb_green . ';stop-opacity:1'));
        $stopColorBlue = html_writer::tag('stop', null,
            array('offset' => '100%','style' => 'stop-color:' . $rgb_blue . ';stop-opacity:1'));
        $gradientBlue = html_writer::tag('radialGradient', $stopColorWhite . $stopColorBlue,
            array_merge($gradient_dims, array('id' => $id_blue)));
        $gradientGreen = html_writer::tag('radialGradient', $stopColorWhite . $stopColorGreen,
            array_merge($gradient_dims, array('id' => $id_green)));
        $gradients = array($gradientBlue, $gradientGreen);
        $defs = html_writer::tag('defs', implode($gradients));

        // Background bar.
        $barbackground = html_writer::tag('rect', null, array_merge($bar_dims,
            array('width' => '100%', 'style' => $bar_stroke . 'fill:' . $rgb_background)));

        // Return empty bar if no questions are in StudentQuiz.
        if( !$validInput || $info->total <= 0) {
            return html_writer::tag('svg', $barbackground, $svg_dims);
        }

        // Calculate Percentages to display.
        $percent_group = round(100 * ($info->group / $info->total));
        $percent_one = round(100 * ($info->one / $info->total));

        // Return stacked bars.
        $bars = array($barbackground);
        $bars[] = html_writer::tag('rect', null, array_merge($bar_dims,
            array('width' => $percent_group . '%', 'style' => $bar_stroke . 'fill:url(#' . $id_blue .')')));
        $bars[] = html_writer::tag('rect', null, array_merge($bar_dims,
            array('width' => $percent_one . '%', 'style' => $bar_stroke . 'fill:url(#' . $id_green .')')));
        return html_writer::tag('svg', $defs . implode($bars), $svg_dims);
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

        $contents .= $this->heading(format_string($view->get_studentquiz_name()));

        //$contents .= html_writer::tag('div', $this->render_progress_bar($view->get_progress_info()));

        $contents .= $this->render_select_qtype_form($view);

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
            $pagevars['cat'], false, $pagevars['showhidden'],
            $pagevars['qbshowtext']);
    }

    /**
     * @param $view
     * @return string
     */
    public function render_select_qtype_form($view) {
        $output = '';
        $output .= $view->get_questionbank()->create_new_question_form($view->get_category_id(), true);
        return html_writer::tag('div', $output);
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
        return html_writer::div($this->render_vote($question->id)
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
    protected function render_comment($cmid, $questionid, $comments, $userid, $anonymize = true, $ismoderator = false) {
        return html_writer::div(
            $this->comment_form($questionid, $cmid)
            . html_writer::div($this->comment_list($comments, $userid, $anonymize, $ismoderator),
                'comment_list'), 'comments');
    }
}

class mod_studentquiz_report_renderer extends mod_studentquiz_renderer{

    /**
     * Builds the quiz report table for the admin
     * @param mod_studentquiz_report $report studentquiz_report class with necessary information
     * @param array $usersdata
     * @return string rank report table
     */
    public function view_stat_table($report, $usersdata) {
        $caption = get_string('reportquiz_admin_title', 'studentquiz');
        $align = array();
        $size = array();
        $head = array(
            get_string('reportrank_table_column_fullname', 'studentquiz'),
            get_string('reportquiz_total_questions_answered', 'studentquiz'),
            get_string('reportquiz_total_questions_right', 'studentquiz'),
            get_string('reportquiz_total_obtained_marks', 'studentquiz')
        );
        $celldata = array();
        $rowstyle = array();

        foreach ($usersdata as $user) {
            $celldata[] = array(
                $user->name,
                $user->questionsanswered,
                $user->questionsright,
                $user->attemptedgrade . ' / ' . $user->maxgrade
            );
            $rowstyle[] = $report->is_loggedin_user($user->id)? array('class' => 'mod-studentquiz-summary-highlight'): array();
        }

        $data = $this->render_table_data($celldata, $rowstyle);
        return $this->render_table($data, $size, $align, $head, $caption);
    }

    /**
     * Get quiz admin statistic view
     * $userid of viewing user
     * @param mod_studentquiz_report $report
     * @return string pre rendered /mod/stundentquiz view_quizreport_table
     */
    public function view_stat(mod_studentquiz_report $report) {
        $output = '';
        $output .= $this->heading(get_string('reportquiz_stats_title', 'studentquiz'), 2, 'reportquiz_stats_heading');
        $output .= $this->view_stat_cards(
            $report->get_studentquiz_stats(),
            $report->get_user_stats()
            );
        // TODO: Refactor: Suggestion to remove this redundant table entirely
        /*if($report->is_admin()) {
            $output .= $this->view_stat_table($report, $report->get_user_stats());
        }*/
        return $output;
    }

    /**
     * Builds the quiz report total section
     * @param stdClass $total
     * @param stdClass $usergrades
     * @return string quiz report data
     */
    public function view_stat_cards($studentquizstats, $userrankingstats) {
        $align = array();
        $size = array();
        $head = array(
            get_string('reportrank_table_column_yourstatus', 'studentquiz'),
            get_string('reportrank_table_column_value', 'studentquiz'),
            get_string('reportrank_table_column_communitystatus', 'studentquiz'),
            get_string('reportrank_table_column_value', 'studentquiz')
        );
        $caption = get_string('reportrank_table_progress_caption', 'studentquiz');
        $celldata = array(
            array(
                get_string('reportquiz_stats_own_questions_created', 'studentquiz'),
                $userrankingstats->questions_created,
                get_string('reportquiz_stats_all_questions_created', 'studentquiz'),
                $studentquizstats->questions_created,
            ),
            array(
                get_string('reportquiz_stats_own_questions_approved', 'studentquiz'),
                $userrankingstats->questions_approved,
                get_string('reportquiz_stats_all_questions_approved', 'studentquiz'),
                $studentquizstats->questions_approved,
            ),
            array(
                get_string('reportquiz_stats_own_votes_average', 'studentquiz'),
                $userrankingstats->votes_average,
                get_string('reportquiz_stats_all_votes_average', 'studentquiz'),
                $studentquizstats->votes_average,
            ),
            array(
                get_string('reportquiz_stats_own_question_attempts_correct', 'studentquiz'),
                $userrankingstats->question_attempts_correct,
                get_string('reportquiz_stats_all_question_attempts_correct', 'studentquiz'),
                $studentquizstats->question_attempts_correct
            ),
            array(
                get_string('reportquiz_stats_own_question_attempts_incorrect', 'studentquiz'),
                $userrankingstats->question_attempts_incorrect,
                get_string('reportquiz_stats_all_question_attempts_incorrect', 'studentquiz'),
                $studentquizstats->question_attempts_incorrect
            ),
            array(
                get_string('reportquiz_stats_own_last_attempt_correct', 'studentquiz'),
                $userrankingstats->last_attempt_correct,
                get_string('reportquiz_stats_all_last_attempt_correct', 'studentquiz'),
                $studentquizstats->last_attempt_correct
            ),
            array(
                get_string('reportquiz_stats_own_last_attempt_incorrect', 'studentquiz'),
                $userrankingstats->last_attempt_incorrect,
                get_string('reportquiz_stats_all_last_attempt_incorrect', 'studentquiz'),
                $studentquizstats->last_attempt_incorrect
            ),
            array(
                get_string('reportquiz_stats_own_questions_answered', 'studentquiz'),
                $userrankingstats->question_attempts,
                get_string('reportquiz_stats_all_questions_answered', 'studentquiz'),
                $studentquizstats->question_attempts,
            ),
            array(
                get_string('reportquiz_stats_own_progress', 'studentquiz'),
                (100 * round($userrankingstats->last_attempt_correct / ($studentquizstats->questions_created), 1)) . ' %',
                get_string('reportquiz_stats_all_progress', 'studentquiz'),
                (100 * round(($studentquizstats->last_attempt_correct / $studentquizstats->questions_created / $studentquizstats->participants), 1)) . ' %',
            )
        );
        $data = $this->render_table_data($celldata);
        return $this->render_table($data, $size, $align, $head, $caption);
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
                round($report->get_quantifier_question(), 2),
                'description' => get_string('settings_questionquantifier_help', 'studentquiz')),
            array(get_string('settings_approvedquantifier', 'studentquiz'),
                round($report->get_quantifier_approved(), 2),
                'description' => get_string('settings_approvedquantifier_help', 'studentquiz')),
            array('text' => get_string('settings_votequantifier', 'studentquiz'),
                round($report->get_quantifier_vote(), 2),
                'value' => get_string('settings_votequantifier_help', 'studentquiz')),
            array('text' => get_string('settings_correctanswerquantifier', 'studentquiz'),
                round($report->get_quantifier_correctanswer(), 2),
                'value' => get_string('settings_correctanswerquantifier_help', 'studentquiz')),
            array('text' => get_string('settings_incorrectanswerquantifier', 'studentquiz'),
                round($report->get_quantifier_incorrectanswer(), 2),
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
        , get_string( 'reportrank_table_column_summeanvotes', 'studentquiz')
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
        foreach($rankingresultset as $ur) {
            $counter++;
            if (($counter > $maxdisplayonpage) && $userwasshown) {
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
                round($ur->votes_average * $report->get_quantifier_vote(), 2),
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