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
 * Container class for comment area.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_studentquiz\commentarea;

defined('MOODLE_INTERNAL') || die();

use mod_studentquiz\utils;

/**
 * Container class for comment area.
 *
 * @package mod_studentquiz
 * @copyright 2020 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class container {

    /** @var int - Number of comments to show by default. */
    const NUMBER_COMMENT_TO_SHOW_BY_DEFAULT = 5;

    /** @var int - Comment root parent id. */
    const PARENTID = 0;

    /** @var string - Created comment event name. */
    const COMMENT_CREATED = 'comment_created';

    /** @var string - Deleted comment event name. */
    const COMMENT_DELETED = 'comment_deleted';

    /** @var \question_definition $question - Question class. */
    private $question;

    /** @var \stdClass $cm - Module. */
    private $cm;

    /** @var \stdClass $context - Context. */
    private $context;

    /** @var array - Array of stored comments. */
    private $storedcomments;

    /** @var object|\stdClass - Studentquiz data. */
    private $studentquiz;

    /** @var string - Basic order to get comments. */
    private $basicorder = 'created ASC';

    /** @var object|\stdClass - Current user of Moodle. Only call it once when __construct */
    private $user;

    /** @var object|\stdClass - Current course of Moodle. Only call it once when __construct */
    private $course;

    /** @var int - Current set limit. */
    private $currentlimit = 0;

    /** @var int - Current offset. */
    private $currentoffset = 0;

    /** @var bool - Flag current user has commented. */
    private $checkhascomment = false;

    /**
     * @var array List of users has comments.
     */
    private $userlist = [];

    /** @var bool - Check if user is moderator in current context. */
    public $ismoderator = false;

    /** @var bool - Can view deleted. */
    public $canviewdeleted = false;

    /** @var int - Comment/reply deletion period default - 10 minutes. */
    const DELETION_PERIOD_DEFAULT = 10;

    /** @var int - Comment/reply deletion period min. */
    const DELETION_PERIOD_MIN = 0;

    /** @var int - Comment/reply deletion period max. */
    const DELETION_PERIOD_MAX = 60;

    /**
     * @var array - Reporting Emails.
     */
    private $reportemails = [];

    /**
     * mod_studentquiz_commentarea_list constructor.
     *
     * @param mixed $studentquiz - Student Quiz instance.
     * @param \question_definition $question - Question instance.
     * @param mixed $cm - Course Module instance.
     * @param mixed $context - Context instance.
     * @param \stdClass $user - User instance.
     */
    public function __construct($studentquiz, \question_definition $question, $cm, $context, $user = null) {
        global $USER, $COURSE;
        $this->studentquiz = $studentquiz;
        $this->question = $question;
        $this->cm = $cm;
        $this->context = $context;
        $this->storedcomments = null;
        $this->user = $user === null ? clone $USER : $user;
        $this->course = clone $COURSE;
        $this->ismoderator = has_capability('mod/studentquiz:previewothers', $context);
        $this->canviewdeleted = $this->ismoderator;

        // If not force commenting, always true;
        $this->refresh_has_comment();
        $this->reportemails = utils::extract_reporting_emails_from_string($studentquiz->reportingemail);
    }

    /**
     * Get current user
     *
     * @return mixed
     */
    public function get_user() {
        return $this->user;
    }

    /**
     * Get module.
     *
     * @return mixed
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * Get course.
     *
     * @return mixed
     */
    public function get_course() {
        return $this->course;
    }

    /**
     * Get question.
     *
     * @return \question_definition
     */
    public function get_question() {
        return $this->question;
    }

    /**
     * Get context.
     *
     * @return mixed
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get module id
     *
     * @return mixed
     */
    public function get_cmid() {
        return $this->get_cm()->id;
    }

    /**
     * Get studentquiz.
     *
     * @return object|\stdClass
     */
    public function get_studentquiz() {
        return $this->studentquiz;
    }

    /**
     * Fetch all comments.
     *
     * @param int $numbertoshow - Number of top-level comments to show.
     * @return comment[]
     */
    public function fetch_all($numbertoshow = 0) {
        return $this->fetch($numbertoshow, ['parentid' => self::PARENTID]);
    }

    /**
     * Fetch comments.
     *
     * @param int $numbertoshow - Number of top-level comments to show.
     * @param array $params - Query conditions.
     * @return array
     */
    public function fetch($numbertoshow, $params) {
        $this->storedcomments = $this->query_comments($numbertoshow, $params);
        $comments = $this->storedcomments;
        $list = [];
        // Check if we have any comments.
        if ($comments) {
            // We need to get users.
            $this->set_user_list($comments);
            // Obtain comments relationships.
            $tree = $this->build_tree($comments);
            foreach ($tree as $rootid => $children) {
                $comment = $this->build_comment($comments[$rootid]);
                if (!empty($children)) {
                    foreach ($children as $childid) {
                        $reply = $this->build_comment($comments[$childid], $comment);
                        $comment->add_child($reply);
                    }
                }
                $list[] = $comment;
            }
        }
        return $list;
    }

    /**
     * Build tree comment.
     *
     * @param array $comments - Array of comments.
     * @return array
     */
    public function build_tree($comments) {
        $tree = [];
        foreach ($comments as $id => $comment) {
            $parentid = $comment->parentid;
            // Add root comments.
            if ($parentid == self::PARENTID) {
                if (!isset($tree[$id])) {
                    $tree[$id] = [];
                }
                continue;
            }
            if (!isset($tree[$parentid])) {
                $tree[$parentid] = [];
            }
            $tree[$parentid][] = $id;
        }
        return $tree;
    }

    /**
     * Count all comments.
     *
     * @return int
     */
    public function get_num_comments() {
        global $DB;
        return $DB->count_records('studentquiz_comment', [
                'questionid' => $this->get_question()->id,
                'deleted' => 0
        ]);
    }

    /**
     * Query for comments
     *
     * @param string $numbertoshow - Number to show.
     * @param array $params - Params for comment conditions.
     * @return array - Array of comment.
     */
    public function query_comments($numbertoshow, $params) {
        global $DB;

        $params['questionid'] = $this->get_question()->id;

        // Set limit.
        if (is_numeric($numbertoshow) && $numbertoshow > 0) {
            $this->currentlimit = $numbertoshow;
        }

        // Set order.
        $order = $this->basicorder;
        // If have limit, get latest.
        if ($this->currentlimit > 0) {
            $order = 'created DESC';
        }

        // Retrieve comments from question.
        $roots = $DB->get_records('studentquiz_comment', $params, $order, '*', $this->currentoffset, $this->currentlimit);

        $data = [];
        if (!empty($roots)) {
            if ($this->currentlimit > 0) {
                $roots = array_reverse($roots, true);
            }
            list($ids, $listids) = $DB->get_in_or_equal(array_column($roots, 'id'));
            $query = "SELECT *
                        FROM {studentquiz_comment}
                       WHERE parentid $ids
                    ORDER BY created ASC";
            $comments = $DB->get_records_sql($query, $listids);

            $data = $roots + $comments;

            $rownumber = 1;
            foreach ($data as &$comment) {
                $comment->rownumber = $rownumber;
                $rownumber++;
            }
        }

        return $data;
    }

    /**
     * Get a comment and its replies by comment id. Null if not found.
     *
     * @param int $id
     * @return comment|null
     */
    public function query_comment_by_id($id) {
        global $DB;

        // First fetch to check it's a comment or reply.
        $record = $DB->get_record('studentquiz_comment', ['id' => $id]);
        if (!$record) {
            return null;
        }

        // It is a reply.
        if ($record->parentid != self::PARENTID) {
            $parentdata = $DB->get_record('studentquiz_comment', ['parentid' => $id]);
            $this->set_user_list([$record]);
            return $this->build_comment($record, $parentdata);
        }

        // It's a comment.
        $comments = $this->fetch(1, ['parentid' => self::PARENTID, 'id' => $record->id]);
        if (!isset($comments[0])) {
            return null;
        }
        return $comments[0];
    }

    /**
     * Build data comment into comment class.
     *
     * @param \stdClass $commentdata - Comment data.
     * @param null $parentdata - Parent comment data, null if top level comment.
     * @return comment
     */
    private function build_comment($commentdata, $parentdata = null) {
        return new comment($this, $commentdata, $parentdata);
    }

    /**
     * Create new comment.
     *
     * @param \stdClass $data - Data of comment will be created.
     * @return int - ID of created comment.
     */
    public function create_comment($data) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $comment = new \stdClass();
        $comment->comment = $data->message['text'];
        $comment->questionid = $this->question->id;
        $comment->userid = $this->get_user()->id;
        $comment->parentid = $data->replyto != self::PARENTID ? $data->replyto : self::PARENTID;
        $comment->created = time();
        $id = $DB->insert_record('studentquiz_comment', $comment);
        // Write log.
        $this->log(self::COMMENT_CREATED, $comment);
        $transaction->allow_commit();
        return $id;
    }

    /**
     * Writing log.
     *
     * @param string $action - Action name.
     * @param \stdClass $data - data of comment.
     */
    public function log($action, $data) {
        $coursemodule = $this->get_cm();
        if ($action == self::COMMENT_CREATED) {
            mod_studentquiz_notify_comment_added($data, $this->get_course(), $coursemodule);
        } else if ($action == self::COMMENT_DELETED) {
            mod_studentquiz_notify_comment_deleted($data, $this->get_course(), $coursemodule);
        }
    }

    /**
     * Set users list.
     *
     * @param array $comments
     */
    public function set_user_list($comments) {
        global $DB;
        $userids = [];
        foreach ($comments as $comment) {
            if (!in_array($comment->userid, $userids)) {
                $userids[] = $comment->userid;
            }
            if (!in_array($comment->deleteuserid, $userids)) {
                $userids[] = $comment->deleteuserid;
            }
        }
        // Retrieve users from db.
        if (!empty($userids)) {
            list($idsql, $params) = $DB->get_in_or_equal($userids);
            $fields = get_all_user_name_fields(true);
            $query = "SELECT id, $fields
                        FROM {user}
                       WHERE id $idsql";
            $users = $DB->get_records_sql($query, $params);
            foreach ($users as $user) {
                $user->fullname = fullname($user);
                $this->userlist[$user->id] = $user;
            }
        }
    }

    /**
     * Get user from users list.
     *
     * @param int $id - Id of user.
     * @return mixed|null
     */
    public function get_user_from_user_list(int $id) {
        return $this->userlist[$id];
    }

    /**
     * Users can't see other comment authors user names except ismoderator.
     * Should use in construct only.
     *
     * @return bool
     * @throws \coding_exception
     */
    public function can_view_username() {
        if ($this->ismoderator) {
            return true;
        }
        if (has_capability('mod/studentquiz:unhideanonymous', $this->get_context())) {
            return true;
        }
        return !$this->get_studentquiz()->anonymrank;
    }

    /**
     * View deleted permission.
     *
     * @return bool
     */
    public function can_view_deleted() {
        return $this->canviewdeleted;
    }

    /**
     * Get deletion period select list.
     *
     * @return array
     */
    public static function get_deletion_period_options() {
        return range(self::DELETION_PERIOD_MIN, self::DELETION_PERIOD_MAX);
    }

    /**
     * Check if user already commented.
     *
     * @param integer $questionid
     * @param integer $userid
     * @return bool
     */
    public static function has_comment(int $questionid, $userid) {
        global $DB;
        return $DB->record_exists('studentquiz_comment', [
                'questionid' => $questionid,
                'userid' => $userid,
                'deleted' => 0
        ]);
    }

    /**
     * In case if any comments in area change (insert/delete).
     * Use to refresh flag user has commented.
     *
     * @return $this
     */
    public function refresh_has_comment() {
        if (!$this->get_studentquiz()->forcecommenting) {
            $this->checkhascomment = true;
        } else {
            $this->checkhascomment = self::has_comment($this->get_question()->id, $this->get_user()->id);
        }
        return $this;
    }

    /**
     * Check user has commented.
     *
     * @return bool
     */
    public function check_has_comment() {
        return $this->checkhascomment;
    }

    /**
     * Get reporting emails list.
     *
     * @return array
     */
    public function get_reporting_emails() {
        return $this->reportemails;
    }
}
