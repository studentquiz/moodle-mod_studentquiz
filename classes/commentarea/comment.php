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

namespace mod_studentquiz\commentarea;

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
    const SHORTEN_LENGTH = 75;

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
        if ($this->data->status == utils::COMMENT_HISTORY_DELETE) {
            if (is_null($this->container->get_user_from_user_list($this->data->usermodified))) {
                $this->container->add_user_to_user_list($this->data->usermodified);
            }
            return $this->container->get_user_from_user_list($this->data->usermodified);
        }

        return utils::COMMENT_HISTORY_CREATE;
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
        global $DB;
        $isdeleted = $DB->record_exists('studentquiz_comment_history',
                ['commentid' => $this->data->id, 'action' => utils::COMMENT_HISTORY_DELETE]);
        return $isdeleted ? $this->data->timemodified : utils::COMMENT_HISTORY_CREATE;
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
     * Check that the comment is edited or not.
     *
     * @return bool
     */
    private function is_edited() {
        global $DB;

        return $DB->record_exists('studentquiz_comment_history',
                ['commentid' => $this->data->id, 'action' => utils::COMMENT_HISTORY_EDIT]);
    }

    /**
     * Get the latest edited time.
     *
     * @return int
     */
    private function get_latest_edited_time() {
        global $DB;

        $commenthistory = $DB->get_records('studentquiz_comment_history',
                ['commentid' => $this->data->id, 'action' => utils::COMMENT_HISTORY_EDIT], 'id DESC', 'timemodified', 0, 1);

        if (count($commenthistory) > 0) {
            return reset($commenthistory)->timemodified;
        }

        return 0;
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
     * Convert comment->data property to object and add extra properties (use for api response).
     *
     * @return \stdClass
     */
    public function convert_to_object() {

        $comment = $this->data;
        $container = $this->get_container();
        $canviewdeleted = $container->can_view_deleted();
        $object = new \stdClass();
        $object->id = $comment->id;
        $object->studentquizquestionid = $comment->studentquizquestionid;
        $object->parentid = $comment->parentid;
        $object->content = format_text($comment->comment, FORMAT_HTML);
        // Because html_to_text will convert escaped html entity to html.
        // We want to escape the "<" and ">" characters so html entity will display in browser as text for short content.
        // So we use s() to convert it back.
        $object->shortcontent = s(content_to_text($comment->comment, FORMAT_HTML));
        $object->shortcontent = shorten_text($object->shortcontent, self::SHORTEN_LENGTH);
        $object->numberofreply = $this->get_total_replies();
        $object->plural = $this->get_reply_plural_text($object);
        $object->candelete = $this->can_delete();
        $object->canreply = $this->can_reply();
        $object->iscreator = $this->is_creator();
        // Row number is use as username 'Anonymous Student #' see line 412.
        $object->rownumber = isset($comment->rownumber) ? $comment->rownumber : $comment->id;
        $object->root = $this->is_root_comment();
        $object->status = $this->data->status;
        $object->type = $comment->type;
        // Check is this comment is deleted and user permission to view deleted comment.
        $object->deleteuser = new \stdClass();
        if ($this->is_deleted() && !$canviewdeleted) {
            // If this comment is deleted and user don't have permission to view then we hide following information.
            $object->title = '';
            $object->authorname = '';
            $object->authorprofileurl = '';
            $object->posttime = '';
            $object->deleteuser->fullname = '';
            $object->deleteuser->profileurl = '';
        } else {
            if ($container->can_view_username() || $this->is_creator()) {
                $user = $this->get_user();
                $object->authorname = $user->fullname;
                $object->authorprofileurl = $user->profileurl->out();
            } else {
                $object->authorname = get_string('anonymous_user_name', 'mod_studentquiz', $object->rownumber);
                $object->authorprofileurl = '';
            }
            $object->posttime = userdate($this->get_created(), $this->strings['timeformat']);
            if ($this->is_deleted()) {
                $deleteuser = $this->get_delete_user();
                $object->deleteuser->fullname = $deleteuser->fullname;
                $object->deleteuser->profileurl = $deleteuser->profileurl->out();
            } else {
                $object->deleteuser->fullname = '';
                $object->deleteuser->profileurl = '';
            }
        }
        $object->deleted = $this->is_deleted();
        $object->deletedtime = $this->get_deleted_time();
        $object->hascomment = $container->check_has_comment();
        $object->canreport = $this->can_report();
        // Add report link if report enabled.
        $object->reportlink = $object->canreport ? $this->get_abuse_link($object->id) : null;
        $object->canedit = $this->can_edit();
        $object->isedithistory = $this->is_edited();
        $object->commenthistorymetadata = '';
        $object->commenthistorylink = '';
        if ($object->isedithistory) {
            // Comment history.
            if ($this->data->userid == $comment->usermodified) {
                $editedcommenthistoryuser = get_string('comment_author', 'mod_studentquiz');
            } else if ($container->can_view_username()) {
                $user = \core_user::get_user($comment->usermodified);
                $editedcommenthistoryuser = fullname($user);
                $editedcommenthistoryuserurl = utils::get_user_profile_url($user->id,
                    $this->get_container()->get_course()->id)->out();
            } else {
                $editedcommenthistoryuser = get_string('anonymous_user_name', 'mod_studentquiz', $object->rownumber);
            }

            if ($object->deleted) {
                $object->commenthistorymetadata = userdate($this->get_latest_edited_time(), $this->strings['timeformat']);
            } else if (!empty($editedcommenthistoryuserurl)) {
                $object->commenthistorymetadata = get_string('editedcommenthistorywithuserlink', 'mod_studentquiz', [
                    'lastesteditedcommentauthorname' => $editedcommenthistoryuser,
                    'lastededitedcommenttime' => userdate($this->get_latest_edited_time(), $this->strings['timeformat']),
                    'lastesteditedcommentauthorprofileurl' => $editedcommenthistoryuserurl
                ]);
            } else {
                $object->commenthistorymetadata = get_string('editedcommenthistory', 'mod_studentquiz', [
                        'lastesteditedcommentauthorname' => $editedcommenthistoryuser,
                        'lastededitedcommenttime' => userdate($this->get_latest_edited_time(), $this->strings['timeformat'])
                ]);
            }

            $object->commenthistorylink = (new moodle_url('/mod/studentquiz/commenthistory.php', [
                    'cmid' => $this->get_container()->get_cmid(),
                    'studentquizquestionid' => $this->get_container()->get_studentquiz_question()->get_id(),
                    'commentid' => $comment->id
            ]))->out();
        }
        $object->allowselfcommentrating = utils::allow_self_comment_and_rating_in_preview_mode(
            $this->get_container()->get_studentquiz_question(),
            $this->get_container()->get_cmid(),
            $comment->type,
            $this->get_container()->get_studentquiz()->privatecommenting
        );
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
        $data->studentquizquestionid = $this->get_container()->get_studentquiz_question()->get_id();
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
                'commentid' => $commentid,
                'type' => $this->get_container()->get_type(),
                'studentquizquestionid' => $this->get_container()->get_studentquiz_question()->get_id()
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
        $data->type = $datacomment->type;
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
