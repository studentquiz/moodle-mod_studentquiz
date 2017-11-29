<?php
/**
 * Representing vote column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent vote column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vote_column extends \core_question\bank\column_base {

    /**
     * Get column name
     * @return string column name
     */
    public function get_name() {
        return 'votes';
    }

    /**
     * Get title
     * @return string column title
     */
    protected function get_title() {
        return get_string('vote_column_name', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {

        if (!empty($question->vote)) {
               // echo $question->vote;
            echo \html_writer::span($this->render_ratingbar($question->vote, $question->myvote), null,
            array('title' =>
                get_string('vote_column_name', 'studentquiz') . ": " . $question->vote . " "
                .get_string('myvote_column_name', 'studentquiz') . ": " . $question->myvote));
        } else {
            echo get_string('no_votes', 'studentquiz');
        }
    }

    /**
     * Get the left join for voteing
     * @return array modified select left join
     */
    public function get_extra_joins() {
        return array('vo' => 'LEFT JOIN ('
        .'SELECT ROUND(SUM(vote)/COUNT(vote), 2) as vote'
        .', questionid FROM {studentquiz_vote} GROUP BY questionid) vo ON vo.questionid = q.id');
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_required_fields() {
        return array('vo.vote');
    }

    /**
     * Get sql sortable name
     * @return string field name
     */
    public function is_sortable() {
        return array(
            'vote' => array('field' => 'vo.vote', 'title' => get_string('vote_column_name', 'studentquiz')),
            'myvote' => array('field' => 'myvote.myvote', 'title' => get_string('myvote_column_name', 'studentquiz'))
        );

    }

    /**
     * Renders a svg bar
     * @param number $average float between 1 to 5 for backgroud bar.
     * @param int $mine between 1 to 5 for number of stars to be yellow
     */
    private function render_ratingbar($average, $mine) {
        $mine = intval($mine);
        $average = floatval($average);
        $fillstarson = "#ffff00";
        $fillstarsoff = "#fff";
        $fillbaron = "#fff";
        $fillbaroff = "#7fff00";

        if($average > 0 && $average <=5) {
            $width = round($average * 20, 0);
        }else {
            $width = 1;
        }

        if($mine>0 && $mine <= 5){
            $stars = $mine;
        }else{
            $stars = 0;
        }

        $output = '';
        $output .= '<svg width="101" height="21" xmlns="http://www.w3.org/2000/svg">
                    <!-- Created with Method Draw - http://github.com/duopixel/Method-Draw/ -->
                    <g>
                      <title>Rating bar</title>
                      <rect id="canvas_background" height="23" width="103" y="-1" x="-1"/>
                      <g display="none" overflow="visible" y="0" x="0" height="100%" width="100%" id="canvasGrid">
                       <rect fill="url(#gridpattern)" stroke-width="0" y="0" x="0" height="100%" width="100%"/>
                      </g>
                     </g>
                     <g>
                      <rect id="svg_6" height="20" width="100" y="0.397703" x="0.396847" fill-opacity="null" stroke-opacity="null" stroke-width="0.5" stroke="#000" fill="'.$fillbaron .'"/>
                      <rect id="svg_7" height="20" width="' . $width . '" y="0.397703" x="0.396847" stroke-opacity="null" stroke-width="0.5" stroke="#000" fill="'. $fillbaroff .'"/>';
                    for($i = 1; $i<=$stars; $i++){
                        $output .= '<path stroke="#000" id="svg_'.$i.'" d="m'.(($i * 20)-15).',8.514401l5.348972,0l1.652874,-5.081501l1.652875,5.081501l5.348971,0l-4.327402,3.140505l1.652959,5.081501l-4.327403,-3.14059l-4.327402,3.14059l1.65296,-5.081501l-4.327403,-3.140505z" stroke-width="1.5" fill="'.$fillstarson.'"/>';
                    }
                    for($i = $stars+1; $i<=5; $i++){
                        $output .= '<path stroke="#000" id="svg_'.$i.'" d="m'.(($i * 20)-15).',8.514401l5.348972,0l1.652874,-5.081501l1.652875,5.081501l5.348971,0l-4.327402,3.140505l1.652959,5.081501l-4.327403,-3.14059l-4.327402,3.14059l1.65296,-5.081501l-4.327403,-3.140505z" stroke-width="1.5" fill="'.$fillstarsoff.'"/>';
                    }
            $output .= '</g></svg>';
        return $output;
    }
}
