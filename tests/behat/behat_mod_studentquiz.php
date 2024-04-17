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
 * Steps definitions related to mod_studentquiz.
 *
 * @package    mod_studentquiz
 * @category   test
 * @copyright  2019 HSR (http://www.hsr.ch)
 * @author     2019 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use mod_studentquiz\utils;
use Behat\Mink\Exception\ExpectationException as ExpectationException;
use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;
use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Steps definitions related to mod_studentquiz.
 *
 * @package    mod_studentquiz
 * @category   test
 * @copyright  2019 HSR (http://www.hsr.ch)
 * @author     2019 Huong Nguyen <huongnv13@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_studentquiz extends behat_base {

    /**
     * Behat function to set availability field
     *
     * @Given I set the availability field ":field" to ":days" days from now
     * @param string $field Field name.
     * @param int $days Number of days from now.
     */
    public function i_set_availability_field_to($field, $days) {
        $date = strtotime($days . ' day');
        $day = date('j', $date);
        $month = date('F', $date);
        $year = date('Y', $date);
        $this->set_field_value('id_' . $field . '_day', $day);
        $this->set_field_value('id_' . $field . '_month', $month);
        $this->set_field_value('id_' . $field . '_year', $year);
    }

    /**
     * Generic field setter.
     *
     * Internal API method, a generic *I set "VALUE" to "FIELD" field*
     * could be created based on it.
     *
     * @param string $fieldlocator The pointer to the field, it will depend on the field type.
     * @param string $value
     * @return void
     */
    protected function set_field_value($fieldlocator, $value) {
        // We delegate to behat_form_field class, it will
        // guess the type properly as it is a select tag.
        $field = behat_field_manager::get_form_field_from_label($fieldlocator, $this);
        $field->set_value($value);
    }

    /**
     * Behat function to check moodle branch is greater or equal provided value
     *
     * @Given I make sure the current Moodle branch is greater or equal ":version"
     *
     * @param int $version
     */
    public function i_check_moodle_version($version) {
        global $CFG;

        if (utils::moodle_version_is("<", $version)) {
            throw new \Moodle\BehatExtension\Exception\SkippedException();
        }
    }

    /**
     * Enter the text into the editor, this step will trigger trusted event.
     *
     * @Given I enter the text ":value" into the ":fieldlocator" editor
     *
     * @param string $value
     * @param string $fieldlocator
     */
    public function enter_the_text_into_field($value, $fieldlocator) {
        $editorid = $this->find_field($fieldlocator)->getAttribute('id') . 'editable';
        $js = 'M.util.js_pending("behat-update-editor");
               var ele = document.getElementById("' . $editorid . '");
               ele.focus();
               document.execCommand("selectall", null, false);
               if("' . $value . '" == "") {
                 document.execCommand("delete", false);
               } else {
                 document.execCommand("insertText", false, "' . $value . '");
               }
               ele.blur();
               M.util.js_complete("behat-update-editor")';
        $this->getSession()->executeScript($js);
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[page name]" page'.
     *
     * Recognised page names are:
     * | None so far!      |                                                              |
     *
     * @param string $page name of the page, with the component name removed e.g. 'Admin notification'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch ($page) {
            default:
                throw new Exception('Unrecognised studentquiz page type "' . $page . '."');
        }
    }

    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype          | name meaning                                | description                                  |
     * | View              | Student Quiz name                           | The student quiz info page (view.php)        |
     * | Edit              | Student Quiz name                           | The edit quiz page (edit.php)                |
     * | Statistics        | Student Quiz name                           | The Statistics report page                   |
     * | Ranking           | Student Quiz name                           | The Ranking page                             |
     *
     * @param string $type identifies which type of page this is, e.g. 'View'.
     * @param string $identifier identifies the particular page, e.g. 'Test student quiz'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        switch ($type) {
            case 'View':
                return new moodle_url('/mod/studentquiz/view.php',
                                      ['id' => $this->get_cm_by_studentquiz_name($identifier)->id]);

            case 'Edit':
                return new moodle_url('/course/modedit.php',
                                      ['update' => $this->get_cm_by_studentquiz_name($identifier)->id]);

            case 'Statistics':
                return new moodle_url('/mod/studentquiz/reportstat.php',
                                      ['id' => $this->get_cm_by_studentquiz_name($identifier)->id]);

            case 'Ranking':
                return new moodle_url('/mod/studentquiz/reportrank.php',
                                      ['id' => $this->get_cm_by_studentquiz_name($identifier)->id]);

            default:
                throw new Exception('Unrecognised studentquiz page type "' . $type . '."');
        }
    }

    /**
     * Get a studentquiz by name.
     *
     * @param string $name studentquiz name.
     * @return stdClass the corresponding DB row.
     */
    protected function get_studentquiz_by_name(string $name): stdClass {
        global $DB;
        return $DB->get_record('studentquiz', array('name' => $name), '*', MUST_EXIST);
    }

    /**
     * Get cmid from the studentquiz name.
     *
     * @param string $name studentquiz name.
     * @return stdClass cm from get_coursemodule_from_instance.
     */
    protected function get_cm_by_studentquiz_name(string $name): stdClass {
        $studentquiz = $this->get_studentquiz_by_name($name);
        return get_coursemodule_from_instance('studentquiz', $studentquiz->id, $studentquiz->course);
    }

    /**
     * Checks that the specified question's action menu does not contain an action.
     *
     * @Then I should not see :action action for :questionname in the question bank
     * @throws ExpectationException
     * @param string $action Action in the menu action
     * @param string $questionname Question we look in.
     */
    public function assert_question_not_contains_action_item($action, $questionname) {
        try {
            $node = $this->get_node_in_container('link', $action, 'table_row', $questionname);
        } catch (ElementNotFoundException $e) {
            return;
        }

        $msg = '"' . $action . '" action was found in the "' . $questionname . '" question menu action';
        $exception = new ExpectationException($msg, $this->getSession());

        if (!$this->running_javascript()) {
            throw $exception;
        }

        $this->spin(
            function($context, $args) {
                if ($args->isVisible()) {
                    return true;
                }

                return false;
            }, $node, behat_base::get_reduced_timeout(), $exception, true);
    }

    /**
     * Checks that the specified question's action menu does not contain an action.
     *
     * @Then I should see :action action for :questionname in the question bank
     * @throws ExpectationException
     * @param string $action Action in the menu action.
     * @param string $questionname Question we look in.
     */
    public function assert_question_contains_action_item($action, $questionname) {
        $msg = '"' . $action . '" action was not found in the "' . $questionname . '" question menu action';
        $exception = new ExpectationException($msg, $this->getSession());

        try {
            $node = $this->get_node_in_container('link', $action, 'table_row', $questionname);
        } catch (ElementNotFoundException $e) {
            throw $exception;
        }

        if (!$this->running_javascript()) {
            return;
        }

        $this->spin(
            function($context, $args) {
                if ($args->isVisible()) {
                    return false;
                }

                return true;
            }, $node, behat_base::get_reduced_timeout(), $exception, true);
    }
}
