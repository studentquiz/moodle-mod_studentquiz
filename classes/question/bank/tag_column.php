<?php
/**
 * Representing tag column
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Represent tag column in studentquiz_bank_view
 *
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tag_column extends \core_question\bank\column_base {

    /**
     * Get column name
     * @return string
     */
    public function get_name() {
        return 'tags';
    }

    /**
     * Get column title
     * @return string translated title
     */
    protected function get_title() {
        return get_string('tags', 'studentquiz');
    }

    /**
     * Default display column content
     * @param  stdClass $question Questionbank from database
     * @param  string $rowclasses
     */
    protected function display_content($question, $rowclasses) {
        if (!empty($question->tags)) {
            foreach(explode(',,', $question->tags) as $tagstr) {
                $tag = $this->render_tag(explode(',', $tagstr)[1]);
                echo $tag;
            }
        } else {
            echo get_string('no_tags', 'studentquiz');
        }
    }

    private function render_tag($rawname) {
        return '<span role="listitem" data-value="HELLO" aria-selected="true" class="tag tag-success " style="font-size: 60%">'
                . (strlen($rawname) > 10 ? (substr($rawname,0,8) ."...") : $rawname)
                . '</span> ';
    }

    /**
     * Get sql query join for this column
     * @return array sql query join additional
     */
    public function get_extra_joins() {
        return array( 'tags' => 'LEFT JOIN ('
            .'  SELECT'
            .'   ti.itemid questionid,'
            .'   GROUP_CONCAT(CONCAT_WS(\',\', t.id, t.rawname) ORDER BY t.name DESC SEPARATOR \',,\') tags'
            .'  FROM {tag} t'
            .'  JOIN {tag_instance} ti ON t.id = ti.tagid'
            .'  WHERE ti.itemtype = \'question\''
            .'  GROUP BY ti.itemid'
            .') tags ON tags.questionid = q.id ');
    }

    public function get_required_fields()
    {
        $fields = parent::get_required_fields();
        $fields[] = 'tags.tags';
        return $fields;
    }

}
