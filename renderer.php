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

use mod_studentquiz\commentarea\container;
use mod_studentquiz\local\studentquiz_helper;
use mod_studentquiz\utils;
use mod_studentquiz\local\studentquiz_question;
use \mod_studentquiz\question\bank\studentquiz_bank_view;

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
     * Cached question preview link image.
     * @var mixed
     */
    protected $cachedquestionpreviewlinkimage;

    /**
     * Render data into table while enriching them with attributes.
     *
     * @param array $celldata
     * @param array $rowattributes
     * @return html_table_row[]
     */
    public function render_table_data(array $celldata, array $rowattributes=array()) {
        $rows = array();
        foreach ($celldata as $num => $row) {
            $cells = array();
            foreach ($row as $cell) {
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

    /**
     * Render custom error message, so we can write the behat test for it.
     *
     * @param string $errormessage Error message.
     * @param string $title Page's title.
     * @return void
     */
    public function render_error_message(string $errormessage, string $title) : void {
        if ($title) {
            $this->page->set_title($title);
        }
        // We need to remove settings menu for view page because we are using custom error message.
        if ($settingmenu = $this->page->settingsnav->find('modulesettings', \navigation_node::TYPE_SETTING)) {
            $settingmenu->remove();
        }
        echo $this->output->header();
        echo $this->output->notification($errormessage, 'error', false);
        $courseurl = new moodle_url('/course/view.php', ['id' => $this->page->course->id]);

        $backtocourse = new single_button($courseurl, get_string('back_to_course_button', 'studentquiz'),
            'get', true);
        echo html_writer::div($this->render($backtocourse), 'studentquizerrormessage');
        echo $this->output->footer();
    }

    /**
     * Render one table cell.
     *
     * @param string $text
     * @param array $attributes
     * @return html_table_cell
     */
    public function render_table_cell($text, array $attributes=array()) {
        $cell = new html_table_cell();
        $cell->text = $text;
        if (!empty($attributes)) {
            $cell->attributes = $attributes;
        }
        return $cell;
    }

    /**
     * Render stat block.
     *
     * @param mixed $report
     * @return block_content
     */
    public function render_stat_block($report) {
        // TODO: Refactor: use mod_studentquiz_report_record_type!
        $userstats = $report->get_user_stats();
        $sqstats = $report->get_studentquiz_stats();
        $cmid = $report->get_cm_id();
        if (!$userstats) {
            $bc = new block_contents();
            $bc->attributes['id'] = 'mod_studentquiz_statblock';
            $bc->attributes['role'] = 'navigation';
            $bc->attributes['aria-labelledby'] = 'mod_studentquiz_navblock_title';
            $bc->title = get_string('statistic_block_title', 'studentquiz');
            $bc->content = get_string('please_enrole_message', 'studentquiz');
            return $bc;
        }
        $bc = new block_contents();
        $bc->attributes['id'] = 'mod_studentquiz_statblock';
        $bc->attributes['role'] = 'navigation';
        $bc->attributes['aria-labelledby'] = 'mod_studentquiz_navblock_title';
        $bc->title = get_string('statistic_block_title', 'studentquiz');
        $info1 = new stdClass();
        $info1->total = $sqstats->questions_available;
        $info1->group = $userstats->last_attempt_exists;
        $info1->one = $userstats->last_attempt_correct;
        $info2 = new stdClass();
        $info2->total = $userstats->questions_created;
        $info2->group = $userstats->questions_approved + $userstats->questions_disapproved;
        $info2->one = $userstats->questions_approved;
        $unansweredquestions = $sqstats->questions_available - $userstats->last_attempt_exists;
        $bc->content = html_writer::div($this->render_progress_bar($info1), '', array('style' => 'width:inherit'))
             . html_writer::div(
                get_string('statistic_block_progress_last_attempt_correct', 'studentquiz')
                .html_writer::span($userstats->last_attempt_correct, '', ['class' => "stat badge last-attempt-correct"]))
            . html_writer::div(
                get_string('statistic_block_progress_last_attempt_incorrect', 'studentquiz')
                .html_writer::span($userstats->last_attempt_incorrect, '', ['class' => 'stat badge last-attempt-incorrect']))
            . html_writer::div(
                get_string('statistic_block_progress_never', 'studentquiz')
                .html_writer::span($unansweredquestions, '', ['class' => 'stat badge never-answered']))
            . html_writer::div($this->render_progress_bar($info2), '', array('style' => 'width:inherit'))
            . html_writer::div(get_string('statistic_block_approvals', 'studentquiz')
                . html_writer::span($userstats->questions_approved, '', ['class' => 'stat badge approvals']))
            . html_writer::div(get_string('statistic_block_disapprovals', 'studentquiz')
                . html_writer::span($userstats->questions_disapproved, '', ['class' => 'stat badge disapprovals']))
                . html_writer::div(get_string('statistic_block_new_changed', 'studentquiz')
                . html_writer::span(
                    ($userstats->questions_created - $userstats->questions_approved - $userstats->questions_disapproved),
                    '', ['class' => 'stat badge changed']));

        // Add More link to Stat block.
        $reporturl = new moodle_url('/mod/studentquiz/reportstat.php', ['id' => $cmid]);
        $readmorelink = $this->render_report_more_link($reporturl);
        $bc->content .= $readmorelink;

        return $bc;
    }

    /**
     * Render ranking block.
     *
     * @param mixed $report
     * @return block_content
     */
    public function render_ranking_block($report) {
        $ranking = $report->get_user_ranking_table(0, 10);
        $currentuserid = $report->get_user_id();
        $anonymname = get_string('creator_anonym_fullname', 'studentquiz');
        $anonymise = $report->is_anonymized();
        $studentquiz = mod_studentquiz_load_studentquiz($report->get_cm_id(), $this->page->context->id);
        // We need to check this instead of using $report->is_anonymized()
        // because we want to apply this text regardless of role.
        $blocktitle = $studentquiz->anonymrank ? get_string('ranking_block_title_anonymised', 'studentquiz') :
                get_string('ranking_block_title', 'studentquiz');
        $cmid = $report->get_cm_id();
        $rows = array();
        $rank = 1;
        foreach ($ranking as $row) {
            if ($currentuserid == $row->userid || !$anonymise) {
                $author = user_get_users_by_id(array($row->userid))[$row->userid];
                $name = html_writer::link(utils::get_user_profile_url($author->id, $this->page->course->id), fullname($author));
            } else {
                $name = $anonymname;
            }
            $rankname = \html_writer::div($rank . '. ' . $name);
            $rows[] = \html_writer::div($rankname .
                html_writer::span(html_writer::tag('b' , round($row->points)),
                    '', array('style' => 'float: right;')));
            $rank++;
            if ($rank > 10) {
                break;
            }
        }
        $ranking->close();
        $bc = new block_contents();
        $bc->attributes['id'] = 'mod_studentquiz_rankingblock';
        $bc->attributes['role'] = 'navigation';
        $bc->attributes['aria-labelledby'] = 'mod_studentquiz_navblock_title';
        $bc->title = $blocktitle;
        $bc->content = implode('', $rows);

        // Add More link to Ranking block.
        $reporturl = new moodle_url('/mod/studentquiz/reportrank.php', ['id' => $cmid]);
        $readmorelink = $this->render_report_more_link($reporturl);
        $bc->content .= $readmorelink;

        return $bc;
    }

    /**
     * Render table row.
     *
     * @param array $cells
     * @return html_table_row
     */
    public function render_table_row($cells) {
        $row = new html_table_row();
        $row->cells = $cells;
        return $row;
    }

    /**
     * Render table with options.
     *
     * @param array $data 2-level array of content data
     * @param array $size
     * @param array $align
     * @param array $head
     * @param string $caption
     * @param string $class
     * @return string
     */
    public function render_table($data, $size, $align, $head, $caption, $class='') {
        $table = new html_table();
        if (!empty($caption)) {
            $table->caption = $caption;
        }
        $table->head = $head;
        $table->align = $align;
        $table->size = $size;
        $table->data = $data;
        $table->attributes['class'] = $class;
        return html_writer::table($table);
    }

    /**
     * Return a svg representing a progress bar filling 100% of is containing element.
     *
     * @param stdClass $info total, group, one
     * @param string $texttotal text to be displayed in the center of the bar
     * @param bool $bicolor only bicolor color scheme
     * @return string
     */
    public function render_progress_bar($info, $texttotal=null, $bicolor=false) {

        // Check input.
        $validinput = true;
        if (!isset($info->total)) {
            $validinput = false;
        }

        if (!isset($info->group)) {
            $validinput = false;
        }

        if (!isset($info->one)) {
            $validinput = false;
        }

        // Stylings.
        $rgbstroke = 'rgb(200, 200, 200)';
        $rgbyellow = 'rgb(255, 193, 7)';
        $rgbgreen = 'rgb(40, 167, 69)';
        $rgbblue = 'rgb(2, 117, 216)';
        $rgbred = 'rgb(220, 53, 69)';
        $rgbgrey = 'rgb(200, 200, 200)';
        $barstroke = 'stroke-width:0.1;stroke:' . $rgbstroke .';';
        $svgdims = array('width' => '100%', 'height' => 20);
        $bardims = array('height' => '100%', 'rx' => 5, 'ry' => 5);
        $idblue = 'blue';
        $idgreen = 'green';
        $idred = 'red';
        $gradientdims = array('cx' => '50%', 'cy' => '50%', 'r' => '50%', 'fx' => '50%', 'fy' => '50%');
        $stopcolorgreen = html_writer::tag('stop', null,
            array('offset' => '100%', 'style' => 'stop-color:' . $rgbgreen . ';stop-opacity:1'));
        $stopcolorred = html_writer::tag('stop', null,
            array('offset' => '100%', 'style' => 'stop-color:' . $rgbred . ';stop-opacity:1'));
        $stopcolorblue = html_writer::tag('stop', null,
            array('offset' => '100%', 'style' => 'stop-color:' . $rgbblue . ';stop-opacity:1'));
        $gradientblue = html_writer::tag('radialGradient', $stopcolorblue . $stopcolorblue,
            array_merge($gradientdims, array('id' => $idblue)));
        $gradientred = html_writer::tag('radialGradient', $stopcolorred . $stopcolorred,
            array_merge($gradientdims, array('id' => $idred)));
        $gradientgreen = html_writer::tag('radialGradient', $stopcolorgreen . $stopcolorgreen,
            array_merge($gradientdims, array('id' => $idgreen)));
        $gradients = array($gradientred, $gradientgreen, $gradientblue);
        $defs = html_writer::tag('defs', implode($gradients));

        // Background bar.
        if ($bicolor) {
            $barbackground = html_writer::tag('rect', null, array_merge($bardims,
                array('width' => '100%', 'style' => $barstroke . 'fill:' . $rgbgrey )));
        } else {
            $barbackground = html_writer::tag('rect', null, array_merge($bardims,
                array('width' => '100%', 'style' => $barstroke . 'fill:' . $rgbyellow)));
        }

        // Return empty bar if no questions are in StudentQuiz.
        if (!$validinput || $info->total <= 0) {
            return html_writer::tag('svg', $barbackground, $svgdims);
        }

        // Calculate Percentages to display.
        $percentgroup = round(100 * ($info->group / $info->total));
        $percentone = round(100 * ($info->one / $info->total));

        if (!empty($texttotal)) {
            $text = html_writer::tag('text', $texttotal, array('xml:space' => 'preserve', 'text-anchor' => 'start',
                'font-family' => 'Helvetica, Arial, sans-serif', 'font-size' => '12', 'font-weight' => 'bold',
                'id' => 'svg_text', 'x' => '50%', 'y' => '50%', 'alignment-baseline' => 'middle',
                'text-anchor' => 'middle', 'stroke-width' => '0', 'stroke' => '#000', 'fill' => '#000'));
        } else {
            $text = '';
        }

        // Return stacked bars.
        $bars = array($barbackground);
        if ($bicolor) {
            $bars[] = html_writer::tag('rect', null, array_merge($bardims,
                array('width' => $percentone . '%', 'style' => $barstroke . 'fill:url(#' . $idblue .')')));
        } else {
            $bars[] = html_writer::tag('rect', null, array_merge($bardims,
                array('width' => $percentgroup . '%', 'style' => $barstroke . 'fill:url(#' . $idred .')')));
            $bars[] = html_writer::tag('rect', null, array_merge($bardims,
                array('width' => $percentone . '%', 'style' => $barstroke . 'fill:url(#' . $idgreen .')')));
        }
        return html_writer::tag('svg', $defs . implode($bars) . $text, $svgdims);
    }

    /**
     * Prints the error message.
     *
     * @param string $errormessage string error message
     * @return string error as HTML
     */
    public function show_error($errormessage) {
        return html_writer::div($errormessage, 'error');
    }

    /**
     * Render the content of creator column.
     *
     * @param bool $anonymize
     * @param stdClass $question
     * @param int $currentuserid
     * @param string $anonymousname
     * @param array $rowclasses
     * @return string
     */
    public function render_anonym_creator_name_column($anonymize, $question, $currentuserid, $anonymousname, $rowclasses) {
        $output = '';

        $date = userdate($question->timecreated, get_string('strftimedatetime', 'langconfig'));
        if ($anonymize && $question->createdby != $currentuserid) {
            $output .= html_writer::tag('span', $anonymousname);
            $output .= html_writer::empty_tag('br');
            $output .= html_writer::tag('span', $date, ['class' => 'date']);
        } else {
            $author = core_user::get_user($question->createdby);
            if ($author) {
                $userprofilelink = html_writer::link(utils::get_user_profile_url($author->id,
                    $this->page->course->id), fullname($author));
                $output .= html_writer::tag('span', $userprofilelink);
            } else {
                // Cannot find the user. Leave it blank.
                $output .= html_writer::tag('span', '');
            }
            $output .= html_writer::empty_tag('br');
            $output .= html_writer::tag('span', $date, ['class' => 'date']);
        }

        return $output;
    }

    /**
     * Render the content of approve column.
     *
     * @param stdClass $question
     * @param array $rowclasses
     * @return string
     */
    public function render_state_column($question, $rowclasses) {
        global $COURSE;
        // Moodle doesn't process "empty" objects in restore. So questions from older backups can have no question state
        // assigned. Need to figure out for the calculation, if it's fine to handle them just as new or if the question
        // table has to have an entry. Ref: https://github.com/frankkoch/moodle-mod_studentquiz/issues/172.
        if (is_null($question->state) || $question->state === "") {
            $question->state = studentquiz_helper::STATE_NEW;
        }

        if (!in_array(intval($question->state), array(
            studentquiz_helper::STATE_DISAPPROVED,
            studentquiz_helper::STATE_APPROVED,
            studentquiz_helper::STATE_NEW,
            studentquiz_helper::STATE_CHANGED,
            studentquiz_helper::STATE_REVIEWABLE,
        ))) {
            throw new coding_exception('Invalid question state '.$question->state.' for question id '.$question->id.'');
        }

        $statename = studentquiz_helper::$statename[intval($question->state)];
        $title = get_string('state_change_tooltip_'.$statename, 'studentquiz');
        $content = $this->output->pix_icon('state_'.$statename, '', 'studentquiz');
        if (has_capability('mod/studentquiz:changestate', $this->page->context)) {

            $changestateurl = new \moodle_url('/mod/studentquiz/changestate.php', ['courseid' => $COURSE->id,
                    'approveselected' => $question->id,
                    'q' . $question->id => 1,
                    'sesskey' => sesskey(),
                    'returnurl' => $this->page->url,
                    'cmid' => $this->page->cm->id]);
            $content = html_writer::link($changestateurl, $content, ['title' => $title]);
        }

        return $content;
    }

    /**
     * Render the content of state pin column.
     *
     * @param stdClass $question The row from the $question table, augmented with extra information.
     * @return string The html string.
     */
    public function render_state_pin($question): string {
        $content = '';
        if ($question->pinned) {
            $content = $this->output->pix_icon('i/pinned', get_string('state_pinned', 'studentquiz'), 'mod_forum');
        }

        return $content;
    }

    /**
     * Render the content of comment column.
     *
     * @param stdClass $question
     * @param array $rowclasses
     * @param bool $privatecommenting Does this studentquiz enable private commenting?
     * @return string
     */
    public function render_comment_column($question, $rowclasses, $privatecommenting = false) {
        $publiccontext = [
            'tooltiptext' => get_string('commentcolumnexplainpublic', 'studentquiz'),
            'sronlytext' => get_string('public', 'studentquiz') . ' ' .
                utils::get_comment_plural_text($question->publiccomment),
            'class' => 'public-comment'
        ];

        if (!empty($question->publiccomment)) {
            $publiccontext['numberofcomments'] = $question->publiccomment;
            if ($question->lasteditpubliccomment > $question->lastreadpubliccomment) {
                $publiccontext['sronlytext'] .= get_string('includingunread', 'studentquiz');
                $publiccontext['unread'] = true;
            }
        } else {
            $publiccontext['numberofcomments'] = get_string('no_comment', 'studentquiz');
        }

        $publiccomment = $this->render_from_template('mod_studentquiz/questionbank_comment_badge', $publiccontext);

        $privatecomment = '';
        if (utils::can_view_private_comment($this->page->cm->id, $question, $privatecommenting)) {
            $privatecontext = [
                'tooltiptext' => get_string('commentcolumnexplainprivate', 'studentquiz'),
                'sronlytext' => get_string('private', 'studentquiz') . ' ' .
                    utils::get_comment_plural_text($question->privatecomment),
                'class' => 'private-comment'
            ];

            if (!empty($question->privatecomment)) {
                $privatecontext['numberofcomments'] = $question->privatecomment;
                if ($question->lasteditprivatecomment > $question->lastreadprivatecomment) {
                    $privatecontext['sronlytext'] .= get_string('includingunread', 'studentquiz');
                    $privatecontext['unread'] = true;
                }
            } else {
                $privatecontext['numberofcomments'] = get_string('no_comment', 'studentquiz');
            }

            $privatecomment = '&nbsp;|&nbsp;' . $this->render_from_template('mod_studentquiz/questionbank_comment_badge',
                $privatecontext);
        }

        return $publiccomment . $privatecomment;
    }

    /**
     * Render the content of difficulty level column.
     * The svg image is renderer later using javascript.
     * See render_bar_javascript_snippet()
     *
     * @param stdClass $question
     * @param array $rowclasses
     * @return string
     */
    public function render_difficulty_level_column($question, $rowclasses) {
        $nodifficultylevel = get_string('no_difficulty_level', 'studentquiz');
        $difficultytitle = get_string('difficulty_all_column_name', 'studentquiz');
        $mydifficultytitle = get_string('mydifficulty_column_name', 'studentquiz');
        $title = "";
        if (!empty($question->difficultylevel) || !empty($question->mydifficulty)) {
            $title = $difficultytitle . ': ' . (100 * round($question->difficultylevel, 2)) . '% ';
            if (!empty($question->mydifficulty)) {
                $title .= ', ' . $mydifficultytitle . ': ' . (100 * round($question->mydifficulty, 2)) . '%';
            } else {
                $title .= ', ' . $mydifficultytitle . ': ' . $nodifficultylevel;
            }
        }

        $output = html_writer::tag("span", "",
            array(
                "class" => "mod_studentquiz_difficulty",
                "data-difficultylevel" => $question->difficultylevel,
                "data-mydifficulty" => $question->mydifficulty,
                "title" => $title
            ));

        return $output;
    }

    /**
     * Render the difficulty bar.
     *
     * @param float $average
     * @param float $mine
     * @param string $fillboltson
     * @param string $fillboltsoff
     * @param string $fillbaron
     * @param string $fillbaroff
     * @return string
     */
    public function render_difficultybar($average, $mine, $fillboltson = '#ffc107', $fillboltsoff = '#fff', $fillbaron = '#fff',
            $fillbaroff = '#007bff') {
        $output = '';

        $mine = floatval($mine);
        $average = floatval($average);

        if ($average > 0 && $average <= 1) {
            $width = round($average * 100, 0);
        } else {
            $width = 0;
        }

        if ($mine > 0 && $mine <= 1) {
            $bolts = ceil($mine * 5);
        } else {
            $bolts = 0;
        }

        $output .= html_writer::start_tag('svg', [
                'width' => 101,
                'height' => 21,
                'xmlns' => 'http://www.w3.org/2000/svg'
        ]);
        $output .= html_writer::tag('svg', html_writer::tag('title', get_string('difficulty_title', 'studentquiz')));
        $output .= html_writer::start_tag('g');
        $output .= $this->render_fill_bar('svg_6', $fillbaron);
        $output .= $this->render_fill_bar('svg_7', $fillbaroff, $width);

        $boltpath = ',1.838819l3.59776,4.98423l-1.4835,0.58821l4.53027,4.2704l-1.48284,0.71317l5.60036,7.15099l-9.49921,'
                . '-5.48006l1.81184,-0.76102l-5.90211,-3.51003l2.11492,-1.08472l-6.23178,-3.68217l6.94429,-3.189z';

        for ($i = 1; $i <= $bolts; $i++) {
            $output .= $this->render_fill_bolt($fillboltson, $i, $boltpath, $fillboltson);
        }

        for ($i = $bolts + 1; $i <= 5; $i++) {
            $output .= $this->render_fill_bolt('#868e96', $i, $boltpath, $fillboltsoff);
        }
        $output .= html_writer::end_tag('g');
        $output .= html_writer::end_tag('svg');

        return $output;
    }

    /**
     * Render the content of attempts column.
     *
     * @param stdClass $question
     * @param array $rowclasses
     * @return string
     */
    public function render_attempts_column($question, $rowclasses) {
        $output = '';
        $attrs = ['tabindex' => 0];

        if (!empty($question->myattempts)) {
            $output .= $question->myattempts;
        } else {
            $output .= get_string('no_myattempts', 'studentquiz');
        }

        $output .= '&nbsp;|&nbsp;';

        if (!empty($question->myattempts) && $question->mylastanswercorrect !== null) {
            // TODO: Refactor magic constant.
            if ($question->mylastanswercorrect == '1') {
                $output .= get_string('lastattempt_right', 'studentquiz');
                $attrs['aria-label'] = get_string('lastattempt_right_label', 'studentquiz');
            } else {
                $output .= get_string('lastattempt_wrong', 'studentquiz');
                $attrs['aria-label'] = get_string('lastattempt_wrong_label', 'studentquiz');
            }
        } else {
            $output .= get_string('no_mylastattempt', 'studentquiz');
            $attrs['aria-label'] = get_string('no_mylastattempt_label', 'studentquiz');
        }

        return html_writer::span($output, 'pratice_info', $attrs);
    }

    /**
     * Render the content of rate column.
     * The svg image is renderer later using javascript.
     * See render_bar_javascript_snippet()
     *
     * @param stdClass $question
     * @param array $rowclasses
     * @return string
     */
    public function render_rate_column($question, $rowclasses) {
        $myratingtitle = get_string('myrate_column_name', 'studentquiz');
        $ratingtitle = get_string('rate_all_column_name', 'studentquiz');
        $notavailable = get_string('no_rates', 'studentquiz');
        $title = "";
        if (!empty($question->rate) || !empty($question->myrate)) {
            $title = $ratingtitle . ': ' . round($question->rate, 2) . ' ';
            if (!empty($question->myrate)) {
                $title .= ', ' . $myratingtitle . ': ' . round($question->myrate, 2);
            } else {
                $title .= ', ' . $myratingtitle . ': ' . $notavailable;
            }
        }

        $output = html_writer::tag("span", "",
            array(
                "class" => "mod_studentquiz_ratingbar",
                "data-rate" => $question->rate,
                "data-myrate" => $question->myrate,
                "title" => $title
            ));

        return $output;
    }

    /**
     * Renders a svg bar.
     *
     * @param float $average float between 1 to 5 for backgroud bar.
     * @param float $mine between 1 to 5 for number of stars to be yellow
     * @param string $fillstarson
     * @param string $fillstarsoff
     * @param string $fillbaron
     * @param string $fillbaroff
     */
    public function render_ratingbar($average, $mine, $fillstarson = '#ffc107', $fillstarsoff = '#fff', $fillbaron = '#fff',
            $fillbaroff = '#007bff') {
        $output = '';

        $mine = intval($mine);
        $average = floatval($average);

        if ($average > 0 && $average <= 5) {
            $width = round($average * 20, 0);
        } else {
            $width = 1;
        }

        if ($mine > 0 && $mine <= 5) {
            $stars = $mine;
        } else {
            $stars = 0;
        }

        $output .= html_writer::start_tag('svg', [
                'width' => 101,
                'height' => 21,
                'xmlns' => 'http://www.w3.org/2000/svg'
        ]);
        $output .= html_writer::tag('svg', html_writer::tag('title', get_string('ratingbar_title', 'studentquiz')));
        $output .= html_writer::start_tag('g');
        $output .= $this->render_fill_bar('svg_6', $fillbaron);
        $output .= $this->render_fill_bar('svg_7', $fillbaroff, $width);

        $starpath = ',8.514401l5.348972,0l1.652874,-5.081501l1.652875,5.081501l5.348971,0l-4.327402,3.140505l1.652959,'
                .'5.081501l-4.327403,-3.14059l-4.327402,3.14059l1.65296,-5.081501l-4.327403,-3.140505z';
        for ($i = 1; $i <= $stars; $i++) {
            $output .= $this->render_fill_star('#000', $i, $starpath, $fillstarson);
        }
        for ($i = $stars + 1; $i <= 5; $i++) {
            $output .= $this->render_fill_star('#868e96', $i, $starpath, $fillstarsoff);
        }
        $output .= html_writer::end_tag('g');
        $output .= html_writer::end_tag('svg');

        return $output;
    }

    /**
     * Render the content of tag column.
     *
     * @param stdClass $question
     * @param array $rowclasses
     * @return string
     */
    public function render_tag_column($question, $rowclasses) {
        $output = '';

        if (!empty($question->tagarray)) {
            foreach (explode(',', $question->tagarray) as $tag) {
                $tag = $this->render_tag($tag);
                $output .= $tag;
            }
        } else {
            $output .= get_string('no_tags', 'studentquiz');
        }

        return $output;
    }

    /**
     * Render tag element.
     *
     * @param string $tag
     * @return string
     */
    public function render_tag($tag) {
        $output = html_writer::tag('span', $tag, [
                'role' => 'listitem',
                'data-value' => 'HELLO',
                'aria-selected' => 'true',
                'class' => 'tag tag-success text-truncate'
        ]);
        $output .= ' ';
        return $output;
    }

    /**
     * Render fill bar.
     *
     * @param string $id
     * @param string $fill
     * @param int $width
     * @return string
     */
    public function render_fill_bar($id, $fill, $width = 100) {
        $output = '';

        $output .= html_writer::empty_tag('rect', [
                'id' => $id,
                'height' => 20,
                'width' => $width,
                'x' => 0.396847,
                'y' => 0.397703,
                'rx' => 5,
                'ry' => 5,
                'fill-opacity' => null,
                'stroke-opacity' => null,
                'stroke-width' => 0.5,
                'stroke' => '#868e96',
                'fill' => $fill
        ]);

        return $output;
    }

    /**
     * Render bolt icon.
     *
     * @param string $stroke
     * @param string $id
     * @param string $boltpath
     * @param string $fill
     * @return string
     */
    public function render_fill_bolt($stroke, $id, $boltpath, $fill) {
        $output = '';

        $output .= html_writer::empty_tag('path', [
                'stroke' => $stroke,
                'id' => 'svg_' . $id,
                'd' => 'm' . (($id * 20) - 12) . $boltpath,
                'stroke-width' => 0.5,
                'fill' => $fill
        ]);

        return $output;
    }

    /**
     * Render start icon.
     *
     * @param string $stroke
     * @param string $id
     * @param string $starpath
     * @param string $fill
     * @return string
     */
    public function render_fill_star($stroke, $id, $starpath, $fill) {
        $output = '';

        $output .= html_writer::empty_tag('path', [
                'stroke' => $stroke,
                'id' => 'svg_' . $id,
                'd' => 'm' . (($id * 20) - 15) . $starpath,
                'stroke-width' => 0.5,
                'fill' => $fill
        ]);

        return $output;
    }

    /**
     * Render the content of question name column.
     *
     * @param stdClass $question
     * @param array $rowclasses
     * @param string $labelfor
     * @return string
     */
    public function render_question_name_column($question, $rowclasses, $labelfor) {
        $output = '';

        if ($labelfor) {
            $output .= html_writer::start_tag('label', ['for' => $labelfor]);
        }
        $output .= format_string($question->name);
        if ($labelfor) {
            $output .= html_writer::end_tag('label');
        }

        return $output;
    }

    /**
     * Get sortable fields for difficulty level column.
     *
     * @return array
     */
    public function get_is_sortable_difficulty_level_column() {
        return [
                'difficulty' => [
                        'field' => 'dl.difficultylevel',
                        'title' => get_string('average_column_name', 'studentquiz'),
                        'tip' => get_string('average_column_name', 'studentquiz')
                ],
                'mydifficulty' => [
                        'field' => 'mydifficulty',
                        'title' => get_string('mine_column_name', 'studentquiz'),
                        'tip' => get_string('mine_column_name', 'studentquiz')
                ]
        ];
    }

    /**
     * Get sortable fields for rate column.
     *
     * @return array
     */
    public function get_is_sortable_rate_column() {
        return [
                'rate' => [
                        'field' => 'vo.rate',
                        'title' => get_string('average_column_name', 'studentquiz'),
                        'tip' => get_string('average_column_name', 'studentquiz')
                ],
                'myrate' => [
                        'field' => 'myrate.myrate',
                        'title' => get_string('mine_column_name', 'studentquiz'),
                        'tip' => get_string('mine_column_name', 'studentquiz')
                ]
        ];
    }

    /**
     * Get report read more link.
     *
     * @param moodle_url $url Url to the report.
     * @return string Html string of read more link.
     */
    public function render_report_more_link($url) {
        $output = html_writer::start_div('report_more_url');
        $output .= html_writer::link($url, get_string('more', 'studentquiz'));
        $output .= html_writer::end_div();

        return $output;
    }

    /**
     * Get all the required columns for StudentQuiz view.
     *
     * @param mod_studentquiz\question\bank\studentquiz_bank_view $view
     * @return array
     */
    public function get_columns_for_question_bank_view(mod_studentquiz\question\bank\studentquiz_bank_view $view) {
        return [
            new core_question\local\bank\checkbox_column($view),
            new qbank_viewquestiontype\question_type_column($view),
            new \mod_studentquiz\bank\state_column($view),
            new \mod_studentquiz\bank\state_pin_column($view),
            new \mod_studentquiz\bank\question_name_column($view),
            new \mod_studentquiz\bank\sq_edit_action_column($view),
            new \mod_studentquiz\bank\preview_column($view),
            new \mod_studentquiz\bank\sq_delete_action_column($view),
            new \mod_studentquiz\bank\sq_hidden_action_column($view),
            new \mod_studentquiz\bank\sq_pin_action_column($view),
            new \mod_studentquiz\bank\sq_edit_menu_column($view),
            new \mod_studentquiz\bank\anonym_creator_name_column($view),
            new \mod_studentquiz\bank\tag_column($view),
            new \mod_studentquiz\bank\attempts_column($view),
            new \mod_studentquiz\bank\difficulty_level_column($view),
            new \mod_studentquiz\bank\rate_column($view),
            new \mod_studentquiz\bank\comment_column($view),
        ];
    }

}

/**
 * Question bank overview renderer.
 */
class mod_studentquiz_overview_renderer extends mod_studentquiz_renderer {

    /**
     * Builds the studentquiz_bank_view.
     *
     * @param studentquiz_view $view studentquiz_view class with the necessary information
     * @return string formatted html
     */
    public function render_overview($view) {
        $contents = '';

        if (!optional_param('deleteselected', false, PARAM_BOOL) && !optional_param('approveselected', false, PARAM_BOOL)) {
            $contents .= $this->heading(format_string($view->get_studentquiz_name()));

            if (!empty($view->get_studentquiz()->intro)) {
                $contents .= $this->box(format_module_intro('studentquiz', $view->get_studentquiz(),
                    $view->get_cm_id()), 'generalbox', 'intro');
            }

            $contents .= groups_print_activity_menu($view->get_coursemodule(), $view->get_pageurl(), true, true);

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
     * Render the question bank.
     *
     * @param mod_studentquiz_view $view
     * @return string
     * TODO: REFACTOR!
     */
    public function render_questionbank($view) {
        $pagevars = $view->get_qb_pagevar();
        ob_start();
        $view->get_questionbank()->display($pagevars, 'questions');
        return ob_get_clean();
    }

    /**
     * Render the question type form.
     *
     * @param mod_studentquiz_view $view
     * @return string
     */
    public function render_select_qtype_form($view) {
        return $view->get_questionbank()->create_new_question_form($view->get_category_id(),
                has_capability('mod/studentquiz:submit', $view->get_context()));
    }

    /**
     * Add Stat block and Ranking block to page.
     *
     * @param mod_studentquiz_report $report
     * @param string $region
     */
    public function add_fake_block(mod_studentquiz_report $report, $region = null) {
        if (empty($region)) {
            $regions = $this->page->blocks->get_regions();
            $region = reset($regions);
        }
        $this->page->blocks->add_fake_block($this->render_stat_block($report), $region);
        $this->page->blocks->add_fake_block($this->render_ranking_block($report), $region);
    }

    /**
     * Render filter form for questions table.
     *
     * @param mod_studentquiz_question_bank_filter_form $filterform
     * @return string
     */
    public function render_filter_form(mod_studentquiz_question_bank_filter_form $filterform) {
        return $filterform->render();
    }

    /**
     * Render no questions notification.
     *
     * @param bool $isfilteractive
     * @return string
     */
    public function render_no_questions_notification($isfilteractive) {
        if ($isfilteractive) {
            return $this->output->notification(get_string('no_questions_filter', 'studentquiz'), 'notifysuccess');
        }
        return $this->output->notification(get_string('no_questions_add', 'studentquiz'), 'notifysuccess');
    }

    /**
     * Render question bank inner javascript snippet
     *
     * @return string
     */
    public function display_javascript_snippet() {
        $output = '';
        $output .= html_writer::start_tag('script');
        // Change both move-to dropdown box at when selection changes.
        $output .= 'var elements = document.getElementsByName(\'category\');
              for(e in elements) {
                elements[e].onchange = function() {
                  var elms = document.getElementsByName(\'category\');
                  for(el in elms) {
                    if(typeof elms[el] !== \'undefined\' && elms[el] !== this) {
                      elms[el].value = this.value;
                    }
                  }
                }
              }';
        $output .= html_writer::end_tag('script');
        return $output;
    }

    /**
     * Returns javascript for rendering difficulty and rating svg
     *
     * @return string (javascript)
     */
    public function render_bar_javascript_snippet() {
        $output = <<<EOT
    boltbase = ",1.838819l3.59776,4.98423l-1.4835,0.58821l4.53027,4.2704l-1.48284,0.71317l5.60036,7.15099l-9.49921,\
        -5.48006l1.81184,-0.76102l-5.90211,-3.51003l2.11492,-1.08472l-6.23178,-3.68217l6.94429,-3.189z";
    starbase = ",8.514401l5.348972,0l1.652874,-5.081501l1.652875,5.081501l5.348971,0l-4.327402,3.140505l1.652959,\
        5.081501l-4.327403,-3.14059l-4.327402,3.14059l1.65296,-5.081501l-4.327403,-3.140505z";

    function getNode(n, v) {
        n = document.createElementNS("http://www.w3.org/2000/svg", n);
        for (var p in v)
            n.setAttributeNS(null, p.replace(/[A-Z]/g, function(m, p, o, s) { return "-" + m.toLowerCase(); }), v[p]);
        return n
    }

    function getBoltOrStar(svg, m, filled, base) {
        var fillcolor = "#ffc107";
        if(!filled) {
            fillcolor = "#fff";
        }
        var r = getNode("path", {stroke:"#868e96", fill: fillcolor, d: "m" + m + base});
        svg.appendChild(r);
    }

    function addBackground(svg, level) {
        var r = getNode('rect', { x: 0.396847, y: 0.397703, rx: 5, ry: 5, width: 100, height: 20, "stroke-width": 0.5,
            fill:'#fff', stroke:"#868e96"});
        svg.appendChild(r);

        var r = getNode('rect', { x: 0.396847, y: 0.397703, rx: 5, ry: 5, width: level, height: 20, "stroke-width": 0.5,
            fill: '#007bff', stroke:"#868e96"});
        svg.appendChild(r);
    }

    function createStarBar(mine, average) {
        var svg = getNode("svg", {width: 101, height: 21 });
        var g = getNode("g", {});
        svg.appendChild(g);
        addBackground(g, average * 20);
        var stars = mine * 5;
        for(var i = 5; i <= 85; i = i + 20) {
            var makestar = false;
            if(stars > 0) {
                makestar = true;
            }
            getBoltOrStar(g, i, makestar, starbase);
            stars = stars - 5;
        }
        return svg;
    }

    function createBoltBar(mine, average) {
        var svg = getNode("svg", {width: 101, height: 21});
        var g = getNode("g", {});
        svg.appendChild(g);
        addBackground(g, average * 100);
        var bolts = mine * 5;
        for(var i = 8; i <= 88; i = i + 20) {
            var makebolt = false;
            if(bolts > 0) {
                makebolt = true;
            }
            getBoltOrStar(g, i, makebolt, boltbase);
            bolts = bolts - 1;
        }
        return svg;
    }


    require(['jquery'], function($) {
        $(".mod_studentquiz_difficulty").each(function(){
        var difficultylevel = $(this).data("difficultylevel");
        var mydifficulty = $(this).data("mydifficulty");
            if(difficultylevel === undefined && mydifficulty === undefined) {
                $(this).append("n.a.");
            }else{
                if(difficultylevel === undefined) {
                    difficultylevel = 0;
                }
                if(mydifficulty === undefined) {
                    mydifficulty = 0;
                }
                $(this).append(createBoltBar(mydifficulty,difficultylevel));
            }
        });
        $(".mod_studentquiz_ratingbar").each(function(){
        var rate = $(this).data("rate");
        var myrate = $(this).data("myrate");
            if(rate === undefined && myrate === undefined) {
                $(this).append("n.a.");
            }else{
                if(rate === undefined) {
                    rate = 0;
                }
                if(myrate === undefined) {
                    myrate = 0;
                }
                $(this).append(createStarBar(myrate,rate));
            }
        });
    });
EOT;
        return $output;
    }

    /**
     * Display the controls at the bottom of the list of questions.
     *
     * @param context $catcontext
     * @param bool $hasquestionincategory
     * @param mixed $addcontexts
     * @param stdClass $category
     * @return string
     */
    public function render_control_buttons($catcontext, $hasquestionincategory, $addcontexts, $category) {
        global $COURSE;

        $output = '';
        $caneditall = has_capability('mod/studentquiz:manage', $catcontext);
        $canmoveall = has_capability('mod/studentquiz:organize', $catcontext);

        $output .= html_writer::start_div('modulespecificbuttonscontainer my-3');
        $output .= html_writer::tag('strong', get_string('withselected', 'question') . ':');
        $output .= html_writer::empty_tag('br');

        $studentquiz = mod_studentquiz_load_studentquiz($this->page->url->get_param('cmid'), $this->page->context->id);
        list($message, $answeringallow) = mod_studentquiz_check_availability(
                $studentquiz->openansweringfrom, $studentquiz->closeansweringfrom, 'answering');
        $deleteurl = new \moodle_url('/question/bank/deletequestion/delete.php', ['courseid' => $COURSE->id,
            'returnurl' => $this->page->url]);
        $movetourl = new \moodle_url('/question/bank/bulkmove/move.php', ['courseid' => $COURSE->id,
            'returnurl' => $this->page->url]);
        $changestateurl = new \moodle_url('/mod/studentquiz/changestate.php', ['courseid' => $COURSE->id,
            'returnurl' => $this->page->url]);
        if ($hasquestionincategory) {
            $params = [
                'class' => 'btn btn-primary form-submit',
                'type' => 'submit',
                'name' => 'startquiz',
                'formmethod' => 'get',
                'value' => get_string('start_quiz_button', 'studentquiz'),
                'disabled' => true
            ];

            if ($answeringallow) {
                $params['data-action'] = 'toggle';
                $params['data-togglegroup'] = 'qbank';
                $params['data-toggle'] = 'action';
            }

            $output .= html_writer::empty_tag('input', $params);
        }
        if ($caneditall) {
            if (has_capability('mod/studentquiz:changestate', $this->page->context)) {
                $output .= html_writer::empty_tag('input', [
                    'class' => 'btn btn-secondary',
                    'type' => 'submit',
                    'name' => 'approveselected',
                    'formaction' => $changestateurl,
                    'value' => get_string('state_toggle', 'studentquiz'),
                    'form' => 'questionsubmit',
                    'data-action' => 'toggle',
                    'data-togglegroup' => 'qbank',
                    'data-toggle' => 'action',
                    'disabled' => true,
                    'formmethod' => 'post',
                ]);
            }
            $output .= html_writer::empty_tag('input', [
                'class' => 'btn btn-secondary',
                'type' => 'submit',
                'name' => 'deleteselected',
                'formaction' => $deleteurl,
                'form' => 'questionsubmit',
                'value' => get_string('delete'),
                'data-action' => 'toggle',
                'data-togglegroup' => 'qbank',
                'data-toggle' => 'action',
                'disabled' => true,
                'formmethod' => 'post',
            ]);
        }

        if ($canmoveall) {
            $output .= html_writer::empty_tag('input', [
                'class' => 'btn btn-secondary',
                'type' => 'submit',
                'name' => 'move',
                'formaction' => $movetourl,
                'form' => 'questionsubmit',
                'value' => get_string('moveto', 'question'),
                'data-action' => 'toggle',
                'data-togglegroup' => 'qbank',
                'data-toggle' => 'action',
                'disabled' => true,
                'formmethod' => 'post',
            ]);
            ob_start();
            \qbank_managecategories\helper::question_category_select_menu($addcontexts, false, 0,
                    "{$category->id},{$category->contextid}");
            $output .= ob_get_contents();
            ob_end_clean();
        }

        if (!empty($message)) {
            $output .= $this->render_availability_message($message, 'mod_studentquiz_answering_info');
        }
        $output .= html_writer::end_div();

        return $output;
    }

    /**
     * Display the pagination bar for Questions table.
     *
     * @param array $pagevars
     * @param moodle_url $baseurl
     * @param int $totalnumber
     * @param int $page
     * @param int $perpage
     * @param bool $showperpageselection
     * @return string
     */
    public function render_pagination_bar($pagevars, $baseurl, $totalnumber, $page, $perpage, $showperpageselection) {
        $pagingbar = new \paging_bar($totalnumber, $page, $perpage, $baseurl);
        $pagingbar->pagevar = 'qpage';

        $shouldshownavigation = false;
        $shouldshowall = false;
        $shouldshowpaging = false;
        $defaultperpage = utils::DEFAULT_QUESTIONS_PER_PAGE;
        if (!$pagevars['showall']) {
            if ($totalnumber > $perpage) {
                $shouldshownavigation = true;
                $shouldshowall = true;
                $shouldshowpaging = true;
            } else {
                if ($perpage > $defaultperpage) {
                    $shouldshownavigation = true;
                    $perpage = $defaultperpage;
                }
            }
        } else {
            $shouldshownavigation = true;
        }

        $pagingbaroutput = '';
        if ($shouldshownavigation) {
            if ($shouldshowpaging) {
                $pagingbaroutput .= html_writer::start_div('categorypagingbarcontainer');
                if ($showperpageselection) {
                    $selectionperpage = \html_writer::empty_tag('input', [
                        'type' => 'submit',
                        'value' => get_string('pagesize', 'studentquiz'),
                        'class' => 'btn'
                    ]);
                    $selectionperpage .= \html_writer::empty_tag('input', [
                        'type' => 'number',
                        'name' => 'qperpage',
                        'value' => $perpage,
                        'class' => 'form-control',
                        'min' => 1
                    ]);
                    $selectionperpage .= \html_writer::empty_tag('input', [
                        'type' => 'hidden',
                        'name' => 'changepagesize',
                        'value' => 1,
                    ]);
                    $pagingbaroutput .= \html_writer::div($selectionperpage, 'pull-right form-inline pagination m-t-1');
                }
                $pagingbaroutput .= $this->output->render($pagingbar);
                $pagingbaroutput .= html_writer::end_div();
            }
            if ($showperpageselection) {
                $showalllink = '';
                if ($shouldshowall) {
                    $linktext = get_string('showall', 'moodle', $totalnumber);
                    $url = new \moodle_url('view.php', array_merge($baseurl->params(), ['showall' => 1]));
                    $showalllink = html_writer::link($url, $linktext);
                } else {
                    $linktext = get_string('showperpage', 'moodle', $perpage);
                    $url = new \moodle_url('view.php', array_merge($baseurl->params(), ['showall' => 0, 'qperpage' => $perpage]));
                    $showalllink = html_writer::link($url, $linktext);
                }
                $pagingbaroutput .= html_writer::div($showalllink, 'paging');
            }
        }

        return $pagingbaroutput;
    }

    /**
     * Generate hidden fields for Questions table form.
     *
     * @param int $cmid
     * @param moodle_url $baseurl
     * @param int $perpage
     * @return string
     */
    public function render_hidden_field(int $cmid, moodle_url $baseurl, int $perpage): string {
        $output = '';

        $output .= $this->generate_hidden_input('sesskey', sesskey());
        $output .= $this->generate_hidden_input('id', $cmid);
        $output .= $this->generate_hidden_input('qperpage', $perpage);

        $output .= \html_writer::input_hidden_params($baseurl, ['qperpage']);

        return $output;
    }

    /**
     * Generate hidden field by given name and value.
     *
     * @param string $name
     * @param string $value
     * @return string
     */
    private function generate_hidden_input($name, $value) {
        $output = '';

        $output .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $name,
                'value' => $value
        ]);

        return $output;
    }

    /**
     * Render the availability message
     *
     * @param string $message Message to show
     * @param string $class Class of the message
     * @return string HTML string
     */
    public function render_availability_message($message, $class) {
        $output = '';

        if (!empty($message)) {
            $icon = new \pix_icon('info', get_string('info'), 'studentquiz');
            $output = \html_writer::div($this->output->render($icon) . $message, $class);
        }

        return $output;
    }

    /**
     * Render current state names of questions.
     *
     * @param array $questions List of questions.
     * @param bool $inuse True if at least one question is being used by the quiz.
     * @return string HTML current states.
     */
    public function render_current_state_questions(array $questions, bool $inuse): string {
        $output = '';
        $questionnametitle = \html_writer::div(get_string('question'), 'font-weight-bold col-12 col-md-8');
        $statenametitle = \html_writer::div(get_string('current_state', 'studentquiz'), 'font-weight-bold col-6 col-md-4');
        $output .= \html_writer::div($questionnametitle . $statenametitle, 'row');

        foreach ($questions as $question) {
            $questionname = \html_writer::span($question->name, 'col-12 col-md-8');
            $questionstate = \html_writer::span($question->state, 'col-6 col-md-4');
            $output .= \html_writer::div($questionname . $questionstate, 'row');
        }

        if ($inuse) {
            $output .= $this->render_explaintion_question_in_use();
        }
        $output .= \html_writer::div(get_string('changestateto', 'studentquiz'), 'mt-3');

        return $output;
    }

    /**
     * Render explaintion about questions in use.
     *
     * @return string HTML explaintion about questions in use.
     */
    public function render_explaintion_question_in_use(): string {
        return \html_writer::div(get_string('questionsinuse', 'studentquiz'), 'mt-3');
    }

    /**
     * Render Names of questions.
     * @param array $questions List of questions.
     * @return string HTML Names of questions.
     */
    public function render_question_names(array $questions): string {
        $output = '';
        foreach ($questions as $question) {
            $output .= \html_writer::div($question->name, '');
        }

        return $output;
    }

    /**
     * Render state change dialog
     *
     * @param string $message Message to display
     * @param string $continue Continue button label
     * @param string $cancel Cancel button label
     * @return string HTML for state change dialog
     */
    public function render_change_state_dialog($message, $continue, $cancel) {
        if ($continue instanceof single_button) {
            // Ok.
            $continue->primary = true;
        } else if (is_string($continue)) {
            $continue = new single_button(new moodle_url($continue), get_string('continue'), 'get', true);
        } else if ($continue instanceof moodle_url) {
            $continue = new single_button($continue, get_string('continue'), 'get', true);
        } else {
            throw new coding_exception('The continue param to $OUTPUT->confirm() must be either a URL (string/moodle_url)' .
                'or a single_button instance.');
        }
        if (is_string($cancel)) {
            $cancel = new single_button(new moodle_url($cancel), get_string('cancel'), 'get');
        } else if ($cancel instanceof moodle_url) {
            $cancel = new single_button($cancel, get_string('cancel'), 'get');
        } else {
            throw new coding_exception('The cancel param to $OUTPUT->confirm() must be either a URL (string/moodle_url)' .
                'or a single_button instance.');
        }
        $attributes = [
                'role' => 'alertdialog',
                'aria-labelledby' => 'modal-header',
                'aria-describedby' => 'modal-body',
                'aria-modal' => 'true'
        ];
        $states = [
                studentquiz_helper::STATE_DISAPPROVED => get_string('state_disapproved', 'studentquiz'),
                studentquiz_helper::STATE_APPROVED => get_string('state_approved', 'studentquiz'),
                studentquiz_helper::STATE_CHANGED => get_string('state_changed', 'studentquiz'),
                studentquiz_helper::STATE_REVIEWABLE => get_string('state_reviewable', 'studentquiz'),
                studentquiz_helper::STATE_HIDE => get_string('state_hidden', 'studentquiz'),
                studentquiz_helper::STATE_DELETE => get_string('state_deleted', 'studentquiz'),
        ];
        $output = $this->box_start('generalbox modal modal-dialog modal-in-page show', 'notice', $attributes);
        $output .= $this->box_start('modal-content', 'modal-content');
        $output .= $this->box_start('modal-header p-x-1', 'modal-header');
        $output .= html_writer::tag('h4', get_string('confirm'));
        $output .= $this->box_end();
        $attributes = [
                'role' => 'alert',
                'data-aria-autofocus' => 'true'
        ];
        $output .= $this->box_start('modal-body', 'modal-body', $attributes);
        $output .= html_writer::div($message, 'mb-2');
        $output .= html_writer::select($states, 'statetype');
        $output .= $this->box_end();
        $output .= $this->box_start('modal-footer', 'modal-footer');
        $output .= html_writer::tag('div', $this->render($continue) . $this->render($cancel), ['class' => 'buttons']);
        $output .= $this->box_end();
        $output .= $this->box_end();
        $output .= $this->box_end();

        $this->page->requires->js_call_amd('mod_studentquiz/state_change', 'init');

        return $output;
    }

}

/**
 * Attempt renderer.
 */
class mod_studentquiz_attempt_renderer extends mod_studentquiz_renderer {

    /**
     * Generate some HTML to display rating options
     *
     * @param studentquiz_question $studentquizquestion studentquiz_question object.
     * @param boolean $selected shows the selected rate
     * @param boolean $readonly describes if rating is readonly
     * @param boolean $forcerating True if enforce rating is turned on
     * @return string HTML fragment
     */
    protected function rate_choices(studentquiz_question $studentquizquestion, $selected, $readonly, $forcerating = true) {
        $output = '';
        $attributes = array(
            'type' => 'radio',
            'name' => 'q' . $studentquizquestion->get_id(),
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
        $rates = [1, 2, 3, 4, 5];
        foreach ($rates as $rate) {
            $class = 'star-empty';
            if ($rate <= $selected) {
                $class = 'star';
            }
            if ($rate == 1) {
                $ratedescription = get_string('rate_one_star_desc', 'mod_studentquiz');
            } else {
                $ratedescription = get_string('rate_multi_stars_desc', 'mod_studentquiz', $rate);
            }
            $rateableattr = [
                    'data-rate' => $rate,
                    'data-studentquizquestionid' => $studentquizquestion->get_id(),
                    'tabindex' => 0,
                    'aria-label' => $ratedescription
            ];
            $choices .= html_writer::span('', $rateable . $class, $rateableattr);
        }

        $output .= html_writer::tag('label', get_string('rate_title', 'mod_studentquiz'), ['for' => 'rate_field']);
        if ($forcerating) {
            $output .= html_writer::span($this->output->pix_icon('req', get_string('requiredelement', 'form')), 'req');
        }
        $output .= $this->output->help_icon('rate_help', 'mod_studentquiz') . ': ';
        $output .= html_writer::div($choices, 'rating');
        $output .= html_writer::div(get_string('rate_error', 'mod_studentquiz'), 'hide error rate_error');

        return $output;
    }

    /**
     * Generate some HTML to display rating
     *
     * @param studentquiz_question $studentquizquestion studentquiz_question object.
     * @param boolean $forcerating True if enforce rating is turned on.
     * @return string HTML fragment
     */
    public function render_rate(studentquiz_question $studentquizquestion, $forcerating = true) {
        global $DB, $USER;

        $question = $studentquizquestion->get_question();
        if (!utils::allow_self_comment_and_rating_in_preview_mode($studentquizquestion, $this->page->cm->id)) {
            return '';
        }

        $value = -1;
        $rate = $DB->get_record('studentquiz_rate', [
            'studentquizquestionid' => $studentquizquestion->get_id(), 'userid' => $USER->id]);
        if ($rate !== false) {
            $value = $rate->rate;
        }

        return html_writer::div(
            html_writer::div($this->rate_choices($studentquizquestion, $value , false, $forcerating), 'rate'),
            'studentquiz_behaviour'
        );
    }

    /**
     * Generate some HTML to display the complete comment fragment.
     *
     * @param studentquiz_question $studentquizquestion Studentquiz Question object.
     * @param int $userid - User id.
     * @param int $highlight - Highlight comment ID.
     * @return string HTML fragment.
     */
    public function render_comment(studentquiz_question $studentquizquestion, $userid, $highlight = 0) {
        $renderer = $this->page->get_renderer('mod_studentquiz', 'comment');
        return html_writer::div(
            html_writer::div(
                html_writer::div(
                    $renderer->render_comment_area($studentquizquestion, $userid, $highlight),
                    'comment_list'),
                'comments'
            ), 'studentquiz_behaviour'
        );
    }

    /**
     * Generate html for Private comments, public comments and state history tabs.
     *
     * @param studentquiz_question $studentquizquestion instance
     * @param int $userid User id.
     * @param int $highlight Highlight comment ID.
     * @param bool $privatecommenting Does the studentquiz enable private commenting?
     * @return string HTML fragment.
     */
    public function render_comment_nav_tabs($studentquizquestion, $userid, $highlight = 0, $privatecommenting = false) {
        $renderer = $this->page->get_renderer('mod_studentquiz', 'comment');
        $tabs = [];
        $question = $studentquizquestion->get_question();
        $cm = $studentquizquestion->get_cm();
        if (utils::can_view_private_comment($cm->id, $question, $privatecommenting)) {
            $privatecommentstab = $renderer->render_comment_area($studentquizquestion, $userid, $highlight,
                utils::COMMENT_TYPE_PRIVATE);
            $tabs[] = [
                'id' => 'private-comments-tab',
                'name' => get_string('privatecomments', 'mod_studentquiz'),
                'content' => $privatecommentstab,
            ];

        }

        $publiccommentstab = $renderer->render_comment_area($studentquizquestion,
                $userid, $highlight, utils::COMMENT_TYPE_PUBLIC);
        $tabs[] = [
            'id' => 'public-comments-tab',
            'name' => get_string('publiccomments', 'mod_studentquiz'),
            'content' => $publiccommentstab
        ];

        if (utils::can_view_state_history($cm->id, $question)) {
            $statehistoryrenderer = $this->page->get_renderer('mod_studentquiz', 'state_history');
            $statehistorytab = $statehistoryrenderer->state_history_table($studentquizquestion->get_id());
            $tabs[] = [
                'id' => 'state_history-tab',
                'name' => get_string('history', 'mod_studentquiz'),
                'content' => $statehistorytab
            ];
        }

        utils::mark_question_comment_current_active_tab($tabs, $privatecommenting);
        $context = [
            'tabs' => $tabs
        ];

        return $this->render_from_template('mod_studentquiz/question_tabs', $context);
    }

    /**
     * Render state choice for specific question
     *
     * @param studentquiz_question $studentquizquestion The studentquiz question object.
     * @return string HTML state choice select box
     */
    public function render_state_choice(studentquiz_question $studentquizquestion) {
        global $USER, $COURSE;

        $question = $studentquizquestion->get_question();
        $output = '';
        if ($USER->id != $question->createdby && !has_capability('mod/studentquiz:changestate', $this->page->context)) {
            return;
        }
        $states = [
            studentquiz_helper::STATE_CHANGED => get_string('state_changed', 'studentquiz'),
            studentquiz_helper::STATE_REVIEWABLE => get_string('state_reviewable', 'studentquiz'),
            studentquiz_helper::STATE_DELETE => get_string('state_deleted', 'studentquiz'),
        ];

        if (has_capability('mod/studentquiz:changestate', $this->page->context)) {
            $states = [
                studentquiz_helper::STATE_DISAPPROVED => get_string('state_disapproved', 'studentquiz'),
                studentquiz_helper::STATE_APPROVED => get_string('state_approved', 'studentquiz'),
                studentquiz_helper::STATE_CHANGED => get_string('state_changed', 'studentquiz'),
                studentquiz_helper::STATE_REVIEWABLE => get_string('state_reviewable', 'studentquiz'),
                studentquiz_helper::STATE_HIDE => get_string('state_hidden', 'studentquiz'),
                studentquiz_helper::STATE_DELETE => get_string('state_deleted', 'studentquiz'),
            ];
        }

        $currentstate = $studentquizquestion->get_state();
        $statenames = studentquiz_helper::get_state_descriptions();
        $output .= html_writer::start_span('change-question-state');
        $output .= html_writer::div(get_string('changecurrentstate', 'studentquiz',
            $statenames[$currentstate]), 'current-state mb-2');
        $output .= html_writer::tag('label', get_string('state_column_name', 'studentquiz'), ['for' => 'statetype']);
        $output .= html_writer::select($states, 'statetype');
        $output .= html_writer::tag('button', get_string('state_toggle', 'studentquiz'),
                ['type' => 'button', 'class' => 'btn btn-secondary', 'id' => 'change_state', 'data-questionid' => $question->id,
                        'data-studentquizquestionid' => $studentquizquestion->get_id(),
                        'data-courseid' => $COURSE->id, 'data-cmid' => $studentquizquestion->get_cm()->id, 'disabled' => 'disabled',
                        'data-currentstate' => $currentstate]);
        $output .= html_writer::end_span();
        $this->page->requires->js_call_amd('mod_studentquiz/state_change', 'init');

        return \html_writer::div($output, 'studentquiz_behaviour');
    }

    /**
     * Render navigation bar of attempt page.
     *
     * @param bool $hasprevious
     * @param bool $hasnext
     * @param bool $hasanswered
     * @return string
     */
    public function render_navigation_bar($hasprevious, $hasnext, $hasanswered) {
        $col1content = '&nbsp;';
        if ($hasprevious) {
            $col1content = html_writer::empty_tag('input', [
                    'type' => 'submit',
                    'name' => 'previous',
                    'value' => get_string('previous_button', 'studentquiz'),
                    'class' => 'btn btn-primary'
            ]);
        }

        $content1 = html_writer::div(html_writer::div($col1content, 'pull-left'), 'col-md-4');

        // The abort button can always be shown, except when the question has been answered and it is the last question
        // in the list.
        $col2content = '';
        if ($hasnext || !$hasanswered) {
            $col2content .= html_writer::empty_tag('input', [
                    'type' => 'submit', 'name' => 'finish',
                    'value' => get_string('abort_button', 'studentquiz'),
                    'class' => 'btn btn-secondary'
            ]);
        }

        $content2 = html_writer::div(html_writer::div($col2content, 'mdl-align'), 'col-md-4');

        $col3content = '';
        if ($hasanswered) {
            $strbutton = $hasnext ? get_string('next_button', 'studentquiz') : get_string('finish_button', 'studentquiz');
            $col3content .= html_writer::empty_tag('input', [
                    'type' => 'submit',
                    'name' => $hasnext ? 'next' : 'finish',
                    'value' => $strbutton,
                    'class' => 'btn btn-primary'
            ]);
        }
        $content3 = html_writer::div(html_writer::div($col3content, 'pull-right'), 'col-md-4');

        return html_writer::div($content1 . $content2 . $content3, 'mod-studentquiz-attempt-nav row');
    }
}
/**
 * State history renderer.
 */
class mod_studentquiz_state_history_renderer extends mod_studentquiz_renderer {

    /** @var string - Define name of Student Quiz mod. */
    const MODNAME = 'mod_studentquiz';

    /**
     * Render state history table.
     *
     * @param int $studentquizquestionid studentquizquestion id.
     * @return string The content render.
     */
    public function state_history_table($studentquizquestionid): string {

        $table = new html_table();
        $table->head  = [
            get_string('time'),
            get_string('action', 'question'),
        ];

        list($statehistories, $users) = utils::get_state_history_data($studentquizquestionid);

        if (get_string_manager()->string_exists('strftimedatetimeshortaccurate', 'core_langconfig')) {
            $formatdate = get_string('strftimedatetimeshortaccurate', 'core_langconfig');
        } else {
            $formatdate = get_string('strftimedatetimeshort', 'core_langconfig');
        }

        foreach ($statehistories as $statehistory) {
            $author = !empty($users[$statehistory->userid]) ? $this->action_author($users[$statehistory->userid]) : '-';
            $table->data[] = [
                userdate($statehistory->timecreated, $formatdate),
                $this->get_desc_action($statehistory->state) . ' ' . $author
            ];
        }

        return html_writer::tag('h5', get_string('statehistory', 'studentquiz'), ['class' => 'statehistoryheader mt-2']) .
            html_writer::tag('div', html_writer::table($table, true), ['class' => 'statehistorybody']);
    }

    /**
     * Action author's profile link.
     *
     * @param stdClass $user The user object.
     * @return string The link to user's profile.
     */
    public function action_author(\stdClass $user): string {
        if ($user->deleted) {
            return html_writer::div(get_string('deleteduser', 'mod_forum'));
        }

        return html_writer::link(new moodle_url('/user/view.php', ['id' => $user->id, 'course' => $this->page->course->id]),
            fullname($user), ['class' => 'd-table-cell']);
    }

    /**
     * Description of state.
     *
     * @param int $state State of question.
     * @return string Description of state.
     */
    public function get_desc_action(int $state): string {
        $states = studentquiz_helper::get_state_descriptions();
        if ($state == studentquiz_helper::STATE_NEW) {
            return get_string('descriptionofstatenew', 'studentquiz');
        }

        if ($state == studentquiz_helper::STATE_SHOW || $state == studentquiz_helper::STATE_HIDE) {
            return get_string('descriptionofvisibility', 'studentquiz', ['visibility' => $states[$state]]);
        }

        return get_string('descriptioncofstate', 'studentquiz', ['state' => $states[$state]]);
    }
}

/**
 * Report renderer.
 */
class mod_studentquiz_report_renderer extends mod_studentquiz_renderer {

    /**
     * Get quiz admin statistic view
     * $userid of viewing user
     * @param mod_studentquiz_report $report
     * @return string pre rendered /mod/stundentquiz view_quizreport_table
     */
    public function view_stat(mod_studentquiz_report $report) {
        $output = '';
        $output .= $this->heading(get_string('reportquiz_stats_title', 'studentquiz'));
        $output .= html_writer::div($report->get_group_title(), 'groupselector');
        $userstats = $report->get_user_stats();
        if (!$userstats) {
            $output .= $this->output->notification(get_string('please_enrole_message', 'studentquiz'), 'notify');
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
     * @param stdClass $commstats
     * @param stdClass $userstats
     * @return string quiz report data
     */
    public function view_stat_cards($commstats, $userstats) {
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

        // Protect from zero division.
        if (empty($commstats->participated)) {
            $participated = 1;
        } else {
            $participated = $commstats->participated;
        }

        if (empty($commstats->questions_available)) {
            $questionsavailable = 1;
        } else {
            $questionsavailable = $commstats->questions_available;
        }

        if ($userstats->question_attempts > 0) {
            $usercorrectattempts = 100 * round($userstats->question_attempts_correct / $userstats->question_attempts, 2);
        } else {
            $usercorrectattempts = 0;
        }

        if ($commstats->question_attempts > 0) {
            $commcorrectattempts = 100 * round($commstats->question_attempts_correct / $commstats->question_attempts, 2);
        } else {
            $commcorrectattempts = 0;
        }

        $celldata = array(
            array(
                html_writer::span(
                    get_string('reportquiz_stats_own_questions_created', 'studentquiz'),
                    '', array(
                        'title' => get_string('reportquiz_stats_own_questions_created_help', 'studentquiz'))),
                html_writer::span( intval($userstats->questions_created),
                    '', array(
                        'title' => get_string('reportquiz_stats_own_questions_created_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_questions_created', 'studentquiz'),
                    '', array(
                        'title' => get_string('reportquiz_stats_all_questions_created_help', 'studentquiz'))),
                html_writer::span( intval($commstats->questions_available),
                    '', array('title' => get_string('reportquiz_stats_all_questions_created_help', 'studentquiz'))), ''
            ),
            array(
                html_writer::span(
                    get_string('reportquiz_stats_own_questions_approved', 'studentquiz'),
                    '', array(
                        'title' => get_string('reportquiz_stats_own_questions_approved_help', 'studentquiz'))),
                html_writer::span( intval($userstats->questions_approved),
                    '', array(
                        'title' => get_string('reportquiz_stats_own_questions_approved_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_questions_approved', 'studentquiz'),
                    '', array(
                        'title' => get_string('reportquiz_stats_all_questions_approved_help', 'studentquiz'))),
                html_writer::span( intval($commstats->questions_questions_approved),
                    '', array(
                        'title' => get_string('reportquiz_stats_all_questions_approved_help', 'studentquiz'))), ''
            ),
            array(
                html_writer::span(
                get_string('reportquiz_stats_own_rates_average', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_own_rates_average_help', 'studentquiz'))),
                html_writer::span( round($userstats->rates_average, 2),
                    '', array('title' => get_string('reportquiz_stats_own_rates_average_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_rates_average', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_all_rates_average_help', 'studentquiz'))),
                html_writer::span(
                    round($commstats->questions_average_rating, 2),
                    '', array('title' => get_string('reportquiz_stats_all_rates_average_help', 'studentquiz'))), ''
            ),
            array(
                html_writer::span(
                    get_string('reportquiz_stats_own_questions_answered', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_own_questions_answered_help', 'studentquiz'))),
                html_writer::span( intval($userstats->question_attempts),
                    '', array('title' => get_string('reportquiz_stats_own_questions_answered_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_questions_answered', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_all_questions_answered_help', 'studentquiz'))),
                html_writer::span( round($commstats->question_attempts / $participated, 2),
                    '', array('title' => get_string('reportquiz_stats_all_questions_answered_help', 'studentquiz'))), ''
            ),
            array(
                html_writer::span(
                    get_string('reportquiz_stats_own_percentage_correct_answers', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_own_percentage_correct_answers_help', 'studentquiz'))),
                html_writer::span($usercorrectattempts . ' %',
                    '', array('title' => get_string('reportquiz_stats_own_percentage_correct_answers_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_percentage_correct_answers', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_all_percentage_correct_answers_help', 'studentquiz'))),
                html_writer::span($commcorrectattempts . ' %',
                    '', array('title' => get_string('reportquiz_stats_all_percentage_correct_answers_help', 'studentquiz'))), ''
            ),
            array(
                html_writer::span(
                    get_string('reportquiz_stats_own_progress', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_own_progress_help', 'studentquiz'))),
                html_writer::span(intval(100 * round($userstats->last_attempt_correct / ($questionsavailable), 2)) . ' %',
                    '', array('title' => get_string('reportquiz_stats_own_progress_help', 'studentquiz'))), '',
                html_writer::span(
                    get_string('reportquiz_stats_all_progress', 'studentquiz'),
                    '', array('title' => get_string('reportquiz_stats_all_progress_help', 'studentquiz'))),
                html_writer::span(
                    intval(100 * round(($commstats->last_attempt_correct / ($questionsavailable * $participated)), 2)) . ' %',
                    '', array('title' => get_string('reportquiz_stats_all_progress_help', 'studentquiz'))), ''
            )
        );
        $data = $this->render_table_data($celldata);
        return $this->render_table($data, $size, $align, $head, null);
    }
}

/**
 * Ranking renderer.
 */
class mod_studentquiz_ranking_renderer extends mod_studentquiz_renderer {

    /**
     * Render the ranking page contents.
     *
     * @param mod_studentquiz_report $report
     * @return string
     */
    public function view_rank($report) {
        return $this->heading(get_string('reportrank_title', 'studentquiz'))
            . html_writer::div($report->get_group_title(), 'groupselector')
            . $this->view_quantifier_information($report)
            . $this->view_rank_table($report);
    }

    /**
     * Display quantifier information.
     *
     * @param mod_studentquiz_report $report
     * @return string
     */
    public function view_quantifier_information($report) {
        $align = array('left', 'right', 'left');
        $size = array('250px', '50px', '');
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
            array('text' => get_string('settings_lastcorrectanswerquantifier', 'studentquiz'),
                $report->get_quantifier_correctanswer(),
                'value' => get_string('settings_lastcorrectanswerquantifier_help', 'studentquiz')),
            array('text' => get_string('settings_lastincorrectanswerquantifier', 'studentquiz'),
                $report->get_quantifier_incorrectanswer(),
                'value' => get_string('settings_lastincorrectanswerquantifier_help', 'studentquiz'))
        );
        $data = $this->render_table_data($celldata);
        return $this->render_table($data, $size, $align, $head, $caption);
    }

    /**
     * Build the rank report table.
     *
     * @param mod_studentquiz_report $report studentquiz_report class with necessary information
     * @return string $rank report table
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
        , get_string( 'reportrank_table_column_lastcorrectanswers', 'studentquiz')
        , get_string( 'reportrank_table_column_lastincorrectanswers', 'studentquiz')
        , get_string( 'reportrank_table_column_progress', 'studentquiz')
        );

        if (has_capability('mod/studentquiz:manage', $report->get_context())) {
            $caption = get_string('reportrank_table_title_for_manager', 'studentquiz');
        } else {
            $caption = get_string('reportrank_table_title', 'studentquiz');
        }

        $celldata = array();
        $rowstyle = array();

        // Todo: Get Pagination from request parameters!
        $limitfrom = 0;
        $limitnum = 0;
        $maxdisplayonpage = 10; // TODO: Make configurable.

        // Update rank offset to pagination.
        $rank = $limitfrom;
        $rankingresultset = $report->get_user_ranking_table($limitfrom, $limitnum);
        $numofquestions = $report->get_studentquiz_stats()->questions_available;
        $counter = 0;
        $userwasshown = false;
        $separatorwasshown = false;
        $userid = $report->get_user_id();
        $seeall = has_capability('mod/studentquiz:manage', $report->get_context());
        foreach ($rankingresultset as $ur) {
            $rank++;
            $counter++;
            if (!$seeall) {
                if ($counter > $maxdisplayonpage) {
                    if (!$userwasshown) {
                        if ($ur->userid == $userid) {
                            // Display current user ranking.
                            $userwasshown = true;
                        } else {
                            if (!$separatorwasshown) {
                                // Display an empty row to visually distance from top maxdisplayonpage.
                                $celldata[] = array('&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;',
                                    '&nbsp;', '&nbsp;', '&nbsp;', '&nbsp;');
                                $rowstyle[] = array('class' => 'mod-studentquiz-summary-separator');
                                $separatorwasshown = true;
                                continue;
                            } else {
                                // We continue to scroll through the set to find our current user.
                                continue;
                            }
                        }
                    } else {
                        // Our job is done.
                        break;
                    }
                }
            }
            $author = user_get_users_by_id(array($ur->userid))[$ur->userid];
            $username = html_writer::link(utils::get_user_profile_url($author->id, $this->page->course->id), fullname($author));
            if ($report->is_anonymized() && $ur->userid != $userid) {
                $username = get_string('creator_anonym_fullname', 'studentquiz');
            }
            $celldata[] = array(
                // Row: Rank.
                $rank,
                // Row: Fullname.
                $username,
                // Row: Total Points.
                round($ur->points, 2),
                // Points for questions created.
                round($ur->questions_created * $report->get_quantifier_question(), 2),
                // Points for approved questions.
                round($ur->questions_approved * $report->get_quantifier_approved(), 2),
                // Points for stars received.
                round($ur->rates_average * $ur->questions_created_and_rated * $report->get_quantifier_rate(), 2),
                // Points for latest correct attemps.
                round($ur->last_attempt_correct * $report->get_quantifier_correctanswer(), 2),
                // Points for latest wrong attemps.
                round($ur->last_attempt_incorrect * $report->get_quantifier_incorrectanswer(), 2),
                // Personal Progress.
                (100 * round($ur->last_attempt_correct / max($numofquestions, 1), 2)) . ' %'
            );
            $rowstyle[] = ($userid == $ur->userid) ? array('class' => 'mod-studentquiz-summary-highlight') : array();
        }
        $rankingresultset->close();
        $data = $this->render_table_data($celldata, $rowstyle);
        return $this->render_table($data, $size, $align, $head, $caption, 'generaltable rankingtable');
    }
}

/**
 * Comment renderer.
 *
 * @package    mod_studentquiz
 * @copyright  2019 The Open University.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_comment_renderer extends mod_studentquiz_renderer {

    /** @var string - Define name of Student Quiz mod. */
    const MODNAME = 'mod_studentquiz';

    /**
     * Generate HTML to render comments.
     *
     * @param studentquiz_question $studentquizquestion - Studentquiz question instance
     * @param int $userid - User id.
     * @param int $highlight - Highlight comment ID.
     * @param int $commenttype Comment Type.
     * @return string HTML fragment
     */
    public function render_comment_area($studentquizquestion, $userid, $highlight = 0, $commenttype = utils::COMMENT_TYPE_PUBLIC) {
        $id = 'question_comment_area_' . $studentquizquestion->get_question()->id . '_' . $commenttype;
        $cmid = $studentquizquestion->get_cm()->id;
        $commentarea = new container($studentquizquestion, null, '', $commenttype);
        $numbertoshow = $commentarea::NUMBER_COMMENT_TO_SHOW_BY_DEFAULT;
        $canviewdeleted = $commentarea->can_view_deleted();
        $context = $studentquizquestion->get_context();
        $allowselfcommentrating = utils::allow_self_comment_and_rating_in_preview_mode($studentquizquestion,
                $cmid, $commenttype, $studentquizquestion->get_studentquiz()->privatecommenting);
        if ($highlight != 0) {
            $numbertoshow = 0;
        }

        $isexpand = $numbertoshow === 0;

        $currentreferer = $this->page->url;
        // Making sure we don't pass highlight or referer param. It will be set later.
        $currentreferer->remove_params('highlight');
        $currentreferer->remove_params('referer');
        // Encode before pass it to comment area js.
        $referer = urlencode($currentreferer->out(false));

        $comments = $commentarea->fetch_all($numbertoshow);
        $res = [];
        if (count($comments) > 0) {
            foreach ($comments as $comment) {
                $item = $comment->convert_to_object();
                $item->canviewdeleted = $canviewdeleted;
                $item->replies = [];
                if ($numbertoshow == 0) {
                    $item->expanded = true;
                    $repliesstring = [];
                    foreach ($comment->get_replies() as $reply) {
                        $replyobject = $reply->convert_to_object();
                        // Check if reply is highlight.
                        $replyobject->highlight = false;
                        if ($highlight != 0) {
                            if ($replyobject->id == $highlight) {
                                $replyobject->highlight = true;
                            }
                        }
                        $replyurl = clone $currentreferer;
                        $replyurl->param('highlight', $replyobject->id);
                        $replyobject->reportlink .= '&referer=' . urlencode($replyurl->out());
                        $repliesstring[] = [
                                'id' => $replyobject->id,
                                'deleted' => $replyobject->deleted,
                                'reportlink' => $replyobject->reportlink
                        ];
                        $replyobject->allowselfcommentrating = $allowselfcommentrating;
                        $item->replies[] = $replyobject;
                    }
                    $item->repliesstring = json_encode($repliesstring);
                } else {
                    $item->expanded = false;
                }
                // Check if comment is highlighted.
                $item->highlight = false;
                if ($highlight != 0) {
                    if ($item->id == $highlight) {
                        $item->highlight = true;
                    }
                }
                $url = clone $currentreferer;
                $url->param('highlight', $item->id);
                $item->reportlink .= '&referer=' . urlencode($url->out());
                $item->allowselfcommentrating = $allowselfcommentrating;
                $res[] = $item;
            }
        }

        $forcecommenting = $commentarea->get_studentquiz()->forcecommenting;
        // Get current sort.
        $sortfeature = $commentarea->get_sort_feature();
        // Get a list of sortable features.
        $sortable = $commentarea->get_sortable();

        $jsdata = [
                'id' => $id,
                'forcecommenting' => $forcecommenting,
                'canviewdeleted' => $canviewdeleted,
                'highlight' => $highlight,
                'expand' => $isexpand,
                'sortfeature' => $sortfeature,
                'isnocomment' => empty($res),
                'type' => $commenttype
        ];

        $count = utils::count_comments_and_replies($res);
        $current = $count['total'];
        $total = $commentarea->get_num_comments();

        // Need to pass this to js to calculate current of total comments.
        $jsdata = array_merge($jsdata, [
                'count' => $count,
                'total' => $total,
                'allowselfcommentrating' => $allowselfcommentrating
        ]);

        $this->page->requires->js_call_amd(self::MODNAME . '/comment_area', 'generate', [$jsdata]);

        // Get strings.
        $strings = [
                'required' => get_string('required', 'core'),
                'deletecomment' => get_string('deletecomment', 'mod_studentquiz'),
                'confirmdeletecomment' => get_string('confirmdeletecomment', 'mod_studentquiz'),
                'deletetext' => get_string('delete', 'mod_studentquiz'),
                'cancel' => get_string('cancel', 'core'),
                'error' => get_string('error', 'core'),
                'replies' => get_string('replies', 'mod_studentquiz'),
                'reply' => get_string('reply', 'mod_studentquiz'),
                'sort' => [
                        'asc' => get_string('asc'),
                        'desc' => get_string('desc')
                ],
                'editedcommenthistorylinktext' => get_string('editedcommenthistorylinktext', 'mod_studentquiz')
        ];

        if ($allowselfcommentrating) {
            // Create form add comment.
            $mform = new \mod_studentquiz\commentarea\form\comment_form([
                    'index' => $id,
                    'replyto' => VALUE_DEFAULT,
                    'studentquizquestionid' => $studentquizquestion->get_id(),
                    'cmid' => $cmid,
                    'cancelbutton' => false,
                    'forcecommenting' => $forcecommenting,
                    'type' => $commenttype
            ]);
        }

        // Get current of total string. Example: 5 of 7.
        $commentcountstring = get_string('current_of_total', 'mod_studentquiz', [
                'current' => $current,
                'total' => $total
        ]);

        if ($commenttype == utils::COMMENT_TYPE_PRIVATE) {
            $notallowcommenttext = get_string('nofurtherprivatecomments', 'mod_studentquiz');
        } else {
            $notallowcommenttext = get_string('notshowratingcomment', 'mod_studentquiz');
        }

        return $this->output->render_from_template(self::MODNAME . '/comment_area', [
                'id' => $id,
                'notallowcommenttext' => $notallowcommenttext,
                'postform' => $allowselfcommentrating ? $mform->get_html() : null,
                'comments' => $res,
                'commentcountstring' => $commentcountstring,
                'hascomment' => $commentarea->check_has_comment(),
                'sortfeature' => $sortfeature,
                'sortable' => $sortable,
                'sortselect' => $commentarea->get_sort_select(),
                'strings' => json_encode($strings),
                'sortablestrings' => json_encode($sortable),
                'referer' => $referer,
                'studentquizquestionid' => $studentquizquestion->get_id(),
                'contextid' => $context->id,
                'userid' => $userid,
                'numbertoshow' => container::NUMBER_COMMENT_TO_SHOW_BY_DEFAULT,
                'cmid' => $cmid,
        ]);
    }
}

/**
 * Comment history renderer.
 *
 * @package    mod_studentquiz
 * @copyright  2020 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_comment_history_renderer extends mod_studentquiz_renderer {

    /**
     * Generate HTML to render comments.
     *
     * @param container $container instance.
     * @param int $commentid - Comment id.
     * @return string HTML fragment
     */
    public function render_comment_history($container, $commentid) {
        $commentarea = $container;

        $dbresults = $commentarea->get_history($commentid);
        $renderdata = $commentarea->extract_comment_history_to_render($dbresults);

        if (!empty($renderdata)) {
            return $this->output->render_from_template('mod_studentquiz/comment_history', [
                    'commenthistory' => $renderdata
            ]);
        } else {
            return get_string('nocommenthistoryexist', 'mod_studentquiz');
        }
    }
}
