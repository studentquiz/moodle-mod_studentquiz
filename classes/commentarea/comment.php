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
 * Comment for comment area.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\commentarea;

defined('MOODLE_INTERNAL') || die();

use mod_studentquiz\utils;
use moodle_url;
use popup_action;

/**
 * Comment for comment area.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment {

    /** @var int - Shorten text with maximum length. */
    const SHORTEN_LENGTH = 160;

    /** @var string - Allowable tags when shorten text. */
    const ALLOWABLE_TAGS = '<img>';

    /** @var string - Link to page when user press report button. */
    const ABUSE_PAGE = '/mod/studentquiz/reportcomment.php';

    /** @var \question_bank - Question. */
    private $question;

    /** @var container - Container of comment area. It stored studentquiz, question, context v.v... */
    private $container;

    /** @var object - Current comment. */
    private $data;

    /** @var comment|null - Parent of current comment. */
    private $parent;

    /** @var array - All replies of current comment. */
    private $children = [];

    /** @var string - Error string. */
    private $error;

    /** @var array - List of lang strings. */
    private $strings;

    /**
     * Constructor
     *
     * @param container $container - Container Comment Area.
     * @param \stdClass $data - Data of comment.
     * @param comment|null $parent - Parent data, null if dont have parent.
     */
    public function __construct(container $container, $data, $parent = null) {
        // Get user data from users list.
        $data->user = $container->get_user_from_user_list($data->userid);
        $data->deleteuser = $data->status == utils::COMMENT_HISTORY_DELETE ? null :
                $container->get_user_from_user_list($data->userid);

        $this->container = $container;
        $this->question = $this->get_container()->get_question();
        $this->data = $data;
        $this->parent = $parent;
        $this->error = null;
        $this->strings = [
                'timeformat' => get_string('strftimedatetime', 'langconfig'),
                'reply' => get_string('reply', 'mod_studentquiz'),
                'replies' => get_string('replies', 'mod_studentquiz')
        ];
    }

    /**
     * Get comment data.
     *
     * @return \stdClass
     */
    public function get_comment_data() {
        return $this->data;
    }

    /**
     * Container of comment area.
     *
     * @return container
     */
    public function get_container() {
        return $this->container;
    }

    /**
     * Get all replies of current comment.
     *
     * @return comment[]
     */
    public function get_replies() {
        return $this->children;
    }

    /**
     * Add child to current comment.
     *
     * @param comment $child
     */
    public function add_child($child) {
        $this->children[] = $child;
    }

    /**
     * Get comment ID.
     *
     * @return mixed
     */
    public function get_id() {
        return $this->data->id;
    }

    /**
     * Get user that created comment.
     *
     * @return mixed
     */
    public function get_user() {
        return $this->data->user;
    }

    /**
     * Get user id that created comment.
     *
     * @return int
     */
    public function get_user_id() {
        global $USER;
        return $USER->id;
    }

    /**
     * Get content of comment.
     *
     * @return string
     */
    public function get_comment_content() {
        return $this->data->comment;
    }

    /**
     * Get user that deleted comment.
     *
     * @return mixed
     */
    public function get_delete_user() {
        return $this->data->status == utils::COMMENT_HISTORY_DELETE ?
                $this->container->get_user_from_user_list($this->data->usermodified) : utils::COMMENT_HISTORY_CREATE;
    }

    /**
     * Get question of comment.
     *
     * @return \question_bank
     */
    public function get_question() {
        return $this->question;
    }

    /**
     * Get error.
     *
     * @return string
     */
    public function get_error() {
        return $this->error;
    }

    /**
     * Get limited time user can delete/edit comment.
     *
     * @return int
     */
    private function get_editable_time() {
        $studentquiz = $this->get_container()->get_studentquiz();
        // Convert minutes to seconds.
        $deletionperiod = $studentquiz->commentdeletionperiod * 60;
        return $this->get_created() + $deletionperiod;
    }

    /**
     * If not moderator, then only allow delete for 10 minutes.
     *
     * @return bool
     */
    public function can_delete() {
        $allow = true;
        // If comment is deleted, cannot delete again.
        if ($this->is_deleted()) {
            $this->error = get_string('describe_already_deleted', 'mod_studentquiz');
            $allow = false;
        } else if (!$this->is_moderator()) {
            if (!$this->is_creator()) {
                $this->error = get_string('describe_not_creator', 'mod_studentquiz');
                $allow = false;
            }
            $studentquiz = $this->get_container()->get_studentquiz();
            if ($studentquiz->commentdeletionperiod > 0) {
                if (time() > $this->get_editable_time()) {
                    $this->error = get_string('describe_out_of_time_delete', 'mod_studentquiz');
                    $allow = false;
                }
            } else if ($studentquiz->commentdeletionperiod == 0) {
                $this->error = get_string('describe_out_of_time_delete', 'mod_studentquiz');
                $allow = false;
            }
        }
        return $allow;
    }

    /**
     * Report permission.
     *
     * @return bool
     */
    public function can_report() {
        $flag = true;
        if ($this->is_deleted()) {
            $this->error = get_string('describe_already_deleted', 'mod_studentquiz');
            $flag = false;
        }
        // If set report emails and comment is not deleted yet.
        if (empty($this->get_container()->get_reporting_emails())) {
            $this->error = get_string('report_comment_not_available', 'mod_studentquiz');
            $flag = false;
        }
        return $flag;
    }

    /**
     * Can reply permission.
     *
     * @return bool
     */
    public function can_reply() {
        if (!$this->is_root_comment()) {
            $this->error = get_string('onlyrootcommentcanreply', 'mod_studentquiz');
            return false;
        }
        return true;
    }

    /**
     * Check if this comment is deleted.
     *
     * @return bool
     */
    private function is_deleted() {
        return $this->get_deleted() != 0;
    }

    /**
     * Get deleted field.
     *
     * @return mixed
     */
    private function get_deleted() {
        return $this->data->status == utils::COMMENT_HISTORY_DELETE ? $this->data->timemodified : utils::COMMENT_HISTORY_CREATE;
    }

    /**
     * Get created field.
     *
     * @return mixed
     */
    private function get_created() {
        return $this->data->created;
    }

    /**
     * Get deleted time.
     *
     * @return int|string
     */
    private function get_deleted_time() {
        return $this->is_deleted() ? userdate($this->get_deleted(), $this->strings['timeformat']) : 0;
    }

    /**
     * Check if current user is mod.
     *
     * @return bool
     */
    private function is_moderator() {
        return $this->get_container()->ismoderator;
    }

    /**
     * Check if current user is comment creator.
     *
     * @return bool
     */
    private function is_creator() {
        return $this->get_container()->get_user()->id == $this->data->userid;
    }

    /**
     * Get total replies.
     *
     * @return int - Number of replies.
     */
    public function get_total_replies() {
        $replies = $this->get_replies();
        // Count only un-deleted post.
        $count = 0;
        foreach ($replies as $reply) {
            if (!$reply->is_deleted()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Check if current comment is root comment or reply.
     *
     * @return bool
     */
    private function is_root_comment() {
        return $this->data->parentid == $this->get_container()::PARENTID;
    }

    /**
     * Convert data to object (use for api response).
     *
     * @return \stdClass
     */
    public function convert_to_object() {
        global $OUTPUT;

        $comment = $this->data;
        $container = $this->get_container();
        $canviewdeleted = $container->can_view_deleted();
        $object = new \stdClass();
        $object->id = $comment->id;
        $object->questionid = $comment->questionid;
        $object->parentid = $comment->parentid;
        $object->content = $comment->comment;
        $object->shortcontent = utils::nice_shorten_text(strip_tags($comment->comment, self::ALLOWABLE_TAGS), self::SHORTEN_LENGTH);
        $object->numberofreply = $this->get_total_replies();
        $object->plural = $this->get_reply_plural_text($object);
        $object->candelete = $this->can_delete();
        $object->canreply = $this->can_reply();
        $object->iscreator = $this->is_creator();
        // Row number is use as username 'Anonymous Student #' see line 412.
        $object->rownumber = isset($comment->rownumber) ? $comment->rownumber : $comment->id;
        $object->root = $this->is_root_comment();
        $object->status = $this->data->status;
        // Check is this comment is deleted and user permission to view deleted comment.
        $object->deleteuser = new \stdClass();
        if ($this->is_deleted() && !$canviewdeleted) {
            // If this comment is deleted and user don't have permission to view then we hide following information.
            $object->title = '';
            $object->authorname = '';
            $object->posttime = '';
            $object->deleteuser->firstname = '';
            $object->deleteuser->lastname = '';
        } else {
            if ($container->can_view_username() || $this->is_creator()) {
                $object->authorname = $this->get_user()->fullname;
            } else {
                $object->authorname = get_string('anonymous_user_name', 'mod_studentquiz', $object->rownumber);
            }
            $object->posttime = userdate($this->get_created(), $this->strings['timeformat']);
            if ($this->is_deleted()) {
                $deleteuser = $this->get_delete_user();
                $object->deleteuser->firstname = $deleteuser->firstname;
                $object->deleteuser->lastname = $deleteuser->lastname;
            } else {
                $object->deleteuser->firstname = '';
                $object->deleteuser->lastname = '';
            }
        }
        $object->deleted = $this->is_deleted();
        $object->deletedtime = $this->get_deleted_time();
        $object->hascomment = $container->check_has_comment();
        $object->canreport = $this->can_report();
        // Add report link if report enabled.
        $object->reportlink = $object->canreport ? $this->get_abuse_link($object->id) : null;
        $object->canedit = $this->can_edit();
        $object->isedithistory = $comment->status == utils::COMMENT_HISTORY_EDIT;
        // Comment history.
        if ($this->data->userid == $comment->usermodified) {
            $editedcommenthistoryuser = get_string('comment_author', 'mod_studentquiz');
        } else if ($container->can_view_username()) {
            $editedcommenthistoryuser = fullname(\core_user::get_user($comment->usermodified));
        } else {
            $editedcommenthistoryuser = get_string('anonymous_user_name', 'mod_studentquiz', $object->rownumber);
        }

        $object->commenthistorymetadata = get_string('editedcommenthistory', 'mod_studentquiz', [
                'lastesteditedcommentauthorname' => $editedcommenthistoryuser,
                'lastededitedcommenttime' => userdate($comment->timemodified, $this->strings['timeformat'])
        ]);
        $object->commenthistorylink = (new moodle_url('/mod/studentquiz/commenthistory.php', [
                'cmid' => $this->get_container()->get_cmid(),
                'questionid' => $this->get_container()->get_question()->id,
                'commentid' => $comment->id
        ]))->out();
        return $object;
    }

    /**
     * Delete method for this comment.
     *
     * @return int
     */
    public function delete() {
        global $DB;
        $container = $this->get_container();
        $transaction = $DB->start_delegated_transaction();
        $data = new \stdClass();
        $data->id = $this->data->id;
        $data->timemodified = time();
        $data->usermodified = $this->get_user_id();
        $data->status = utils::COMMENT_HISTORY_DELETE;
        $res = $DB->update_record('studentquiz_comment', $data);
        // Writing log.
        $record = $this->data;
        $container->log($container::COMMENT_DELETED, $record);
        $transaction->allow_commit();
        return $res;
    }

    /**
     * Get comment has reply/replies text.
     *
     * @param \stdClass $object - Object of comment.
     * @return string
     */
    private function get_reply_plural_text($object) {
        if ($object->numberofreply != 1) {
            return $this->strings['replies'];
        }
        return $this->strings['reply'];
    }

    /**
     * Generate report link.
     *
     * @param int $commentid
     * @return string
     */
    private function get_abuse_link($commentid) {
        $questiondata = $this->get_container()->get_question();
        $params = [
                'cmid' => $this->get_container()->get_cmid(),
                'questionid' => $questiondata->id,
                'commentid' => $commentid
        ];
        $url = new \moodle_url(self::ABUSE_PAGE, $params);
        return $url->out();
    }

    /**
     * If not moderator, then only allow edit for 10 minutes.
     *
     * @return bool
     */
    public function can_edit() {
        $allow = true;
        // Deleted comment can't be editable.
        if ($this->is_deleted()) {
            $this->error = get_string('describe_already_deleted', 'mod_studentquiz');
            $allow = false;
        } else if (!$this->is_moderator()) {
            // If not admin, and not comment's creator, can't be editable.
            if (!$this->is_creator()) {
                $this->error = get_string('describe_not_creator', 'mod_studentquiz');
                $allow = false;
            } else {
                $studentquiz = $this->get_container()->get_studentquiz();
                // If period is over or period = 0, can't be editable.
                if ($studentquiz->commentdeletionperiod > 0) {
                    if (time() > $this->get_editable_time()) {
                        $this->error = get_string('describe_out_of_time_edit', 'mod_studentquiz');
                        $allow = false;
                    }
                } else if ($studentquiz->commentdeletionperiod == 0) {
                    $this->error = get_string('describe_out_of_time_edit', 'mod_studentquiz');
                    $allow = false;
                }
            }
        }
        return $allow;
    }

    /**
     * Edit comment feature.
     *
     * @param \stdClass $datacomment - Comment edit data.
     * @return bool
     */
    public function update_comment($datacomment) {
        global $DB;
        $data = new \stdClass();
        $data->id = $this->data->id;
        // Update content from editor.
        $data->comment = $datacomment->message['text'];
        $data->timemodified = time();
        $data->usermodified = $this->get_user_id();
        $data->status = utils::COMMENT_HISTORY_EDIT;
        return $DB->update_record('studentquiz_comment', $data);
    }

    /**
     * Create new comment history
     *
     * @param int $commentid - comment id
     * @param int $userid - user that modify comment
     * @param int $action - action type Create 0 - Edit 1 - Delete 2
     * @param string $comment - store comment content
     */
    public function create_history($commentid, $userid, $action, $comment): int {
        global $DB;
        $instance = new \stdClass();
        $instance->commentid = $commentid;
        $instance->userid = $userid;
        $instance->content = $comment;
        $instance->action = $action;
        $instance->timemodified = time();
        $newid = $DB->insert_record('studentquiz_comment_history', $instance);

        return $newid;
    }
}
