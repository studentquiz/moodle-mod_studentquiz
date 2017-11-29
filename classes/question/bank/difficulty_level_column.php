<?php
/**
 * Representing difficulty level column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Representing difficulty level column in studentquiz_bank_view
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class difficulty_level_column extends \core_question\bank\column_base {

    /**
     * Initialise Parameters for join
     */
    protected function init() {
        global $DB, $USER;
        $this->currentuserid = $USER->id;
        $cmid = $this->qbank->get_most_specific_context()->instanceid;
        // TODO: Get StudentQuiz id from infrastructure instead of DB!
        // TODO: Exception handling lookup fails somehow.
        $sq = $DB->get_record('studentquiz', array('coursemodule' => $cmid));
        $this->studentquizid = $sq->id;
        // TODO: Sanitize!
    }

    protected $sqlparams =  array();

    /**
     * Return name of column
     * @return string columnname
     */
    public function get_name() {
        return 'difficultylevel';
    }

    /**
     * Set conditions to apply to join.
     * @param  array $joinconditions Conditions to apply to join (via WHERE clause)
     */
    public function set_joinconditions($joinconditions) {
        $this->joinconditions = $joinconditions;
    }

    /**
     * Get params that this join requires be added to the query.
     * @return array sqlparams required to be added to query
     */
    public function get_sqlparams() {
        return $this->sqlparams;
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_extra_joins() {

        $tests = array(
            'quiza.studentquizid = ' . $this->studentquizid,
            'quiza.userid = ' . $this->currentuserid,
            'name=\'-submit\'',
            '(state = \'gradedright\' OR state = \'gradedwrong\' OR state=\'gradedpartial\')'
        );

        return array('dl' => 'LEFT JOIN ('
            . 'SELECT ROUND(1 - (COALESCE(correct.num, 0) / total.num), 2) AS difficultylevel,'
            . 'qa.questionid'
            . ' FROM {question_attempts} qa JOIN {question} q ON q.id = qa.questionid'
            . ' LEFT JOIN  ('
            . ' SELECT COUNT(*) AS num, questionid'
            . '  FROM {question_attempts} qa'
            . '  JOIN {question} q ON q.id = qa.questionid'
            . '  WHERE rightanswer = responsesummary'
            . '  GROUP BY questionid'
            . ') correct ON(correct.questionid = qa.questionid)'
            . ' LEFT JOIN ('
            . ' SELECT COUNT(*) AS num, questionid'
            . '  FROM {question_attempts} qa JOIN {question} q ON q.id = qa.questionid'
            . '  WHERE responsesummary IS NOT NULL'
            . '  GROUP BY questionid'
            . ') total ON(total.questionid = qa.questionid)'
            . ' WHERE q.parent = 0'
            . ' GROUP BY qa.questionid, correct.num, total.num'
            . ') dl ON dl.questionid = q.id',
            'mydiffs' => 'LEFT JOIN ('
                . 'SELECT '
                . ' ROUND(1-(sum(case state when \'gradedright\' then 1 else 0 end)/count(*)),2) as mydifficulty,'
                . ' sum(case state when \'gradedright\' then 1 else 0 end) as mycorrectattempts,'
                . ' questionid'
                . ' FROM {studentquiz_attempt} quiza '
                . ' JOIN {question_usages} qu ON qu.id = quiza.questionusageid '
                . ' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
                . ' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
                . ' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
                . ' WHERE ' . implode(' AND ', $tests)
                . ' GROUP BY qa.questionid) mydiffs ON mydiffs.questionid = q.id');
    }

    /**
     * Get sql field name
     * @return array fieldname in array
     */
    public function get_required_fields() {
        return array('dl.difficultylevel', 'mydiffs.mydifficulty', 'mydiffs.mycorrectattempts');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return array(
            'difficulty' => array('field' => 'dl.difficultylevel', 'title' => get_string('average_column_name', 'studentquiz')),
            'mydifficulty' => array('field' => 'mydiffs.mydifficulty', 'title' => get_string('mine_column_name', 'studentquiz'))
        );
    }

    /**
     * Get column real title
     * @return string translated title
     */
    protected function get_title() {
        return get_string('difficulty_level_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if (!empty($question->difficultylevel) || !empty($question->mydifficulty)) {
            echo \html_writer::span($this->render_difficultybar($question->difficultylevel, $question->mydifficulty),null,
                array('title' =>
                    get_string('difficulty_level_column_name', 'studentquiz') . ": " . $question->difficultylevel . " "
                    .get_string('mydifficulty_column_name', 'studentquiz') . ": " . $question->mydifficulty));
        } else {
            echo get_string('no_difficulty_level', 'studentquiz');
        }
    }

    /**
     * @param $average
     * @param $mine
     * @return string
     */
    private function render_difficultybar($average, $mine) {
        $mine = floatval($mine);
        $average = floatval($average);
        $fillboltson = "#ffff00";
        $fillboltsoff = "#fff";
        $fillbaron = "#fff";
        $fillbaroff = "#ffaaaa";

        if($average > 0 && $average <=1) {
            $width = round($average * 100, 0);
        }else {
            $width = 0;
        }

        if($mine>0 && $mine <= 1){
            $bolts = ceil($mine * 5);
        }else{
            $bolts = 0;
        }
        $output = '';
        $output .= '<svg width="101" height="21" xmlns="http://www.w3.org/2000/svg">
                            <!-- Created with Method Draw - http://github.com/duopixel/Method-Draw/ -->
                            <g>
                              <title>Difficulty bar</title>
                              <rect id="canvas_background" height="23" width="103" y="-1" x="-1"/>
                              <g display="none" overflow="visible" y="0" x="0" height="100%" width="100%" id="canvasGrid">
                               <rect fill="url(#gridpattern)" stroke-width="0" y="0" x="0" height="100%" width="100%"/>
                              </g>
                             </g>
                             <g>
                              <rect id="svg_6" height="20" width="100" y="0.397703" x="0.396847" fill-opacity="null" stroke-opacity="null" stroke-width="0.5" stroke="#000" fill="'.$fillbaron .'"/>
                              <rect id="svg_7" height="20" width="' . $width . '" y="0.397703" x="0.396847" stroke-opacity="null" stroke-width="0.5" stroke="#000" fill="'. $fillbaroff .'"/>';
        for($i = 1; $i<=$bolts; $i++){
            $output .= '<path stroke="#000" id="svg_'.$i.'" d="m'.(($i * 20)-10).',1.838819l3.59776,4.98423l-1.4835,0.58821l4.53027,4.2704l-1.48284,0.71317l5.60036,7.15099l-9.49921,-5.48006l1.81184,-0.76102l-5.90211,-3.51003l2.11492,-1.08472l-6.23178,-3.68217l6.94429,-3.189z" stroke-width="1.5" fill="'.$fillboltson.'"/>';
        }
        for($i = $bolts+1; $i<=5; $i++){
            $output .= '<path stroke="#000" id="svg_'.$i.'" d="m'.(($i * 20)-10).',1.838819l3.59776,4.98423l-1.4835,0.58821l4.53027,4.2704l-1.48284,0.71317l5.60036,7.15099l-9.49921,-5.48006l1.81184,-0.76102l-5.90211,-3.51003l2.11492,-1.08472l-6.23178,-3.68217l6.94429,-3.189z" stroke-width="1.5" fill="'.$fillboltsoff.'"/>';
        }
        $output .= '</g></svg>';
        return $output;

    }
}
