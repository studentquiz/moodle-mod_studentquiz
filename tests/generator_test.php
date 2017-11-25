<?php
/**
 * Data generator test
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Data generator test
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_studentquiz_generator_testcase extends advanced_testcase {

    /**
     * Test create comment
     * @throws coding_exception
     */
    public function test_create_comment() {
        global $DB;

        $this->resetAfterTest();
        $studentquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_studentquiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('description', null, array('category' => $cat->id));

        $count = $DB->count_records('studentquiz_comment');
        $user = $this->getDataGenerator()->create_user();

        $commentrecord = new stdClass();
        $commentrecord->questionid = $question->id;
        $commentrecord->userid = $user->id;

        $studentquizgenerator->create_comment($commentrecord);
        $this->assertEquals($count + 1, $DB->count_records('studentquiz_comment'));
    }

    /**
     * Test create vote
     * @throws coding_exception
     */
    public function test_create_vote() {
        global $DB;

        $this->resetAfterTest();
        $studentquizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_studentquiz');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('description', null, array('category' => $cat->id));

        $count = $DB->count_records('studentquiz_vote');

        $user = $this->getDataGenerator()->create_user();

        $voterecord = new stdClass();
        $voterecord->vote = 5;
        $voterecord->questionid = $question->id;
        $voterecord->userid = $user->id;

        $rec = $studentquizgenerator->create_comment($voterecord);
        $this->assertEquals($count + 1, $DB->count_records('studentquiz_comment'));
        $this->assertEquals(5, $rec->vote);
    }
}
