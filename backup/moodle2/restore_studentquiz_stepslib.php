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
use mod_studentquiz\local\studentquiz_helper;
use mod_studentquiz\utils;

/**
 * Structure step to restore one StudentQuiz activity
 *
 * @package   mod_studentquiz
 * @category  backup
 * @copyright 2017 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_studentquiz_activity_structure_step extends restore_questions_activity_structure_step {

    /**
     * @var object $currentquizattempt intermediate result for studentquiz attempts
     */
    protected $currentattempt;

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@see restore_path_element}
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('studentquiz', '/activity/studentquiz');

        // Get Setting value if user data should be restored.
        $userinfo = $this->get_setting_value('userinfo');

        // Additional Path for attempts.
        // TODO: attempts can be ignored if progress exists and works flawless.
        $attempt = new restore_path_element('attempt',
            '/activity/studentquiz/attempts/attempt');
        $paths[] = $attempt;

        // Add attempt data.
        $this->add_question_usages($attempt, $paths);

        // Restore Progress.
        $progress = new restore_path_element('progress',
            '/activity/studentquiz/progresses/progress');
        $paths[] = $progress;

        // Restore Rate.
        $rate = new restore_path_element('rate',
            '/activity/studentquiz/rates/rate');
        $paths[] = $rate;

        // Restore Comments.
        $comment = new restore_path_element('comment',
            '/activity/studentquiz/comments/comment');
        $paths[] = $comment;

        // Restore Comment Histories.
        $commenthistories = new restore_path_element('comment_history',
                '/activity/studentquiz/comments/comment/commenthistories/comment_history');
        $paths[] = $commenthistories;

        // Restore Question meta.
        $question = new restore_path_element('question_meta',
            '/activity/studentquiz/questions/question');
        $paths[] = $question;

        // Restore Notification meta.
        $notification = new restore_path_element('notification',
                '/activity/studentquiz/notifications/notification');
        $paths[] = $notification;

        // Restore State histories.
        $statehistories = new restore_path_element('state_history',
            '/activity/studentquiz/statehistories/state_history');
        $paths[] = $statehistories;

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data.
     *
     * @param array $data parsed element data
     */
    protected function process_studentquiz($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->coursemodule = $this->get_mappingid('course_module', $data->coursemodule);
        $oldid = $data->id;

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }
        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }
        if ($data->grade < 0) {
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }
        if (empty($data->anonymrank)) {
            $data->anonymrank = true;
        }
        if (empty($data->questionquantifier)) {
            $data->questionquantifier = get_config('studentquiz', 'addquestion');
        }
        if (empty($data->approvedquantifier)) {
            $data->approvedquantifier = get_config('studentquiz', 'approved');
        }
        if (empty($data->ratequantifier)) {
            $data->ratequantifier = get_config('studentquiz', 'rate');
        }
        if (empty($data->correctanswerquantifier)) {
            $data->correctanswerquantifier = get_config('studentquiz', 'correctanswered');
        }
        if (empty($data->incorrectanswerquantifier)) {
            $data->incorrectanswerquantifier = get_config('studentquiz', 'incorrectanswered');
        }
        if (empty($data->allowedqtypes)) {
            $data->allowedqtypes = get_config('studentquiz', 'defaultqtypes');
        }
        if (empty($data->aggregated)) {
            $data->aggregated = "0";
        }
        if (empty($data->excluderoles)) {
            $data->excluderoles = get_config('studentquiz', 'excluderoles');
        }
        if (empty($data->forcerating)) {
            $data->forcerating = get_config('studentquiz', 'forcerating');
        }
        if (empty($data->forcecommenting)) {
            $data->forcecommenting = get_config('studentquiz', 'forcecommenting');
        }
        if (empty($data->privatecommenting)) {
            $data->privatecommenting = get_config('studentquiz', 'showprivatecomment');
        }
        if (!isset($data->commentdeletionperiod)) {
            $data->commentdeletionperiod = get_config('studentquiz', 'commentediting_deletionperiod');
        }
        // Create the StudentQuiz instance.
        $newitemid = $DB->insert_record('studentquiz', $data);
        $this->apply_activity_instance($newitemid);
        $this->set_mapping('studentquiz', $oldid, $newitemid, true); // Has related files.
    }

    /**
     * Process attempt data.
     *
     * @param array $data parsed element data
     */
    protected function process_attempt($data) {
        // TODO: attempts can be ignored if progress exists and works flawless.
        $data = (object)$data;
        $data->studentquizid = $this->get_new_parentid('studentquiz');
        $data->userid = $this->get_mappingid('user', $data->userid);
        // The data is actually inserted into the database later in inform_new_usage_id.
        $this->currentattempt = clone($data);
    }

    /**
     * Process progress data.
     *
     * @param array $data parsed element data
     */
    protected function process_progress($data) {
        global $DB;
        $data = (object)$data;
        // Pre 4.0 version legacy backup.
        if (!empty($data->questionid)) {
            $newquestionid = $this->get_mappingid('question', $data->questionid);
            $question = question_bank::load_question($newquestionid);
            $sq = \mod_studentquiz\local\studentquiz_question::get_studentquiz_question_from_question($question);
            $data->studentquizquestionid = $sq->get_id();
        } else {
            $data->studentquizquestionid = $this->get_mappingid('question', $data->studentquizquestionid);
        }
        $data->studentquizid = $this->get_new_parentid('studentquiz');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('studentquiz_progress', $data);
    }

    /**
     * Process rate data.
     *
     * @param array $data parsed element data
     */
    protected function process_rate($data) {
        global $DB;
        $data = (object) $data;
        // Pre 4.0 version legacy backup.
        if (!empty($data->questionid)) {
            $newquestionid = $this->get_mappingid('question', $data->questionid);
            $question = question_bank::load_question($newquestionid);

            $sq = \mod_studentquiz\local\studentquiz_question::get_studentquiz_question_from_question($question);
            $data->studentquizquestionid = $sq->get_id();
        } else {
            $data->studentquizquestionid = $this->get_mappingid('question', $data->studentquizquestionid);
        }
        $data->userid = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('studentquiz_rate', $data);
    }

    /**
     * Process comment data.
     *
     * @param array $data parsed element data
     */
    protected function process_comment($data) {
        global $DB;
        $data = (object) $data;
        $oldid = $data->id;
        // Pre 4.0 version legacy backup.
        if (!empty($data->questionid)) {
            $newquestionid = $this->get_mappingid('question', $data->questionid);
            $question = question_bank::load_question($newquestionid);

            $sq = \mod_studentquiz\local\studentquiz_question::get_studentquiz_question_from_question($question);
            $data->studentquizquestionid = $sq->get_id();
        } else {
            $data->studentquizquestionid = $this->get_mappingid('question', $data->studentquizquestionid);
        }
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->type = $data->type ?? utils::COMMENT_TYPE_PUBLIC;

        // If is a reply (parentid != 0).
        if (!empty($data->parentid)) {
            if ($newparentid = $this->get_mappingid('comment', $data->parentid)) {
                $data->parentid = $newparentid;
            } else {
                $data->parentid = \mod_studentquiz\commentarea\container::PARENTID;
            }
        }

        if (isset($data->edited) && $data->edited > 0) {
            $data->edituserid = $this->get_mappingid_or_null('user', $data->edituserid);

            $commenthistory = new stdClass();
            $commenthistory->commentid = $data->id;
            $commenthistory->content = $data->comment;
            $commenthistory->userid = $data->edituserid;
            $commenthistory->action = utils::COMMENT_HISTORY_EDIT;
            $commenthistory->timemodified = $data->edited;
            $DB->insert_record('studentquiz_comment_history', $commenthistory);

            $data->status = utils::COMMENT_HISTORY_EDIT;
            $data->usermodified = $data->edituserid;
            $data->timemodified = $data->edited;
        }

        if (isset($data->deleted) && $data->deleted > 0) {
            $data->deleteuserid = $this->get_mappingid_or_null('user', $data->deleteuserid);

            $commenthistory = new stdClass();
            $commenthistory->commentid = $data->id;
            $commenthistory->content = '';
            $commenthistory->userid = $data->deleteuserid;
            $commenthistory->action = utils::COMMENT_HISTORY_DELETE;
            $commenthistory->timemodified = $data->deleted;
            $DB->insert_record('studentquiz_comment_history', $commenthistory);

            $data->status = utils::COMMENT_HISTORY_DELETE;
            $data->usermodified = $data->deleteuserid;
            $data->timemodified = $data->deleted;
        }

        $newid = $DB->insert_record('studentquiz_comment', $data);
        $this->set_mapping('comment', $oldid, $newid, true);
    }

    /**
     * Process question meta data.
     *
     * @param array $data parsed element data
     */
    protected function process_question_meta($data) {
        global $DB;

        $data = (object) $data;
        $data->studentquizid = $this->get_new_parentid('studentquiz');
        $data->groupid = $this->get_mappingid('group', $data->groupid ?? 0);

        if (!isset($data->state)) {
            if (isset($data->approved)) {
                $data->state = $data->approved;
                unset($data->approved);
            } else {
                $data->state = studentquiz_helper::STATE_NEW;
            }
        }

        if (!isset($data->hidden)) {
            $data->hidden = 0;
        }

        if (!isset($data->pinned)) {
            $data->pinned = 0;
        }

        $studentquizquestionid = $DB->insert_record('studentquiz_question', $data);
        // We need to create a question_references record.
        // Load question to create a question references.
        $newquestionid = $this->get_mappingid('question', $data->questionid);
        $question = question_bank::load_question($newquestionid);
        $studentquiz = $DB->get_record('studentquiz', ['id' => $data->studentquizid]);
        $contextid = context_module::instance($studentquiz->coursemodule)->id;
        $referenceparams = [
                'usingcontextid' => $contextid,
                'itemid' => $studentquizquestionid,
                'component' => STUDENTQUIZ_COMPONENT_QR,
                'questionarea' => STUDENTQUIZ_QUESTIONAREA_QR,
                'questionbankentryid' => $question->questionbankentryid,
                'version' => null
        ];
        $DB->insert_record('question_references', (object) $referenceparams);
    }

    /**
     * Process comment history data.
     *
     * @param array $data parsed element data
     */
    protected function process_comment_history($data) {
        global $DB;

        $data = (object) $data;
        $data->commentid = $this->get_new_parentid('comment');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('studentquiz_comment_history', $data);
    }

    /**
     * Process notification data.
     *
     * @param array $data parsed element data
     */
    protected function process_notification($data) {
        global $DB;

        $data = (object) $data;
        $data->studentquizid = $this->get_mappingid('notification', $data->studentquizid);

        $DB->insert_record('studentquiz_notification', $data);
    }

    /**
     * Process state history data.
     *
     * @param array $data parsed element data
     */
    protected function process_state_history($data) {
        global $DB;

        // If the path content is null, we will fix it after excuting.
        if (!isset($data)) {
            return;
        }

        $data = (object) $data;
        // Pre 4.0 version legacy backup.
        if (!empty($data->questionid)) {
            $newquestionid = $this->get_mappingid('question', $data->questionid);
            $question = question_bank::load_question($newquestionid);

            $sq = \mod_studentquiz\local\studentquiz_question::get_studentquiz_question_from_question($question);
            $data->studentquizquestionid = $sq->get_id();
        } else {
            $data->studentquizquestionid = $this->get_mappingid('question', $data->studentquizquestionid);
        }
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('studentquiz_state_history', $data);
    }

    /**
     * Post-execution actions per activity
     */
    protected function after_execute() {
        // Add StudentQuiz related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_studentquiz', 'intro', null);
    }


    /**
     * Inform the new question usage id.
     *
     * @param int $newusageid
     */
    protected function inform_new_usage_id($newusageid) {
        // TODO: attempts can be ignored if progress exists and works flawless.
        global $DB;

        $data = $this->currentattempt;

        $oldid = $data->id;
        $data->questionusageid = $newusageid;

        $data->categoryid = $this->get_mappingid('question_category', $data->categoryid);

        $newitemid = $DB->insert_record('studentquiz_attempt', $data);

        // Save studentuquiz_attempt->id mapping, because logs use it.
        $this->set_mapping('studentquiz_attempt', $oldid, $newitemid, false);
    }

    /**
     * Post-execution actions per activity after whole restore
     */
    protected function after_restore() {
        parent::after_execute();
        // Migrate progress from quiz usage to internal table.
        mod_studentquiz_migrate_all_studentquiz_instances_to_aggregated_state($this->get_courseid());
        // Workaround setting default question state if no state data is available.
        // ref: https://tracker.moodle.org/browse/MDL-67406.
        mod_studentquiz_fix_all_missing_question_state_after_restore($this->get_courseid());
        // Fix restore legacy data without path content.
        utils::fix_all_missing_question_state_history_after_restore($this->get_courseid());
    }

    /**
     * Get mapping id or null.
     *
     * @param string $type field identifier
     * @param int $oldid the old id of that field
     * @return mixed
     */
    private function get_mappingid_or_null($type, $oldid) {
        if ($oldid === null) {
            return null;
        }
        return $this->get_mappingid($type, $oldid);
    }
}
