<?php
/**
 * Internal library of functions for module StudentQuiz
 *
 * All the StudentQuiz specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');

/** @var string default quiz behaviour */
const STUDENTQUIZ_BEHAVIOUR = 'studentquiz';
/** @var int legacy course section id for the orphaned activities, only used for import fixes */
const STUDENTQUIZ_OLD_ORPHANED_SECTION_NUMBER = 999;
/** @var string generated student quiz placeholder */
const STUDENTQUIZ_GENERATE_QUIZ_PLACEHOLDER = 'quiz';
/** @var string generated student quiz intro */
const STUDENTQUIZ_GENERATE_QUIZ_INTRO = 'Studentquiz';
/** @var string generated student quiz overduehandling */
const STUDENTQUIZ_GENERATE_QUIZ_OVERDUEHANDLING = 'autosubmit';
/** @var string default course section name for the orphaned activities */
const STUDENTQUIZ_COURSE_SECTION_NAME = 'studentquiz quizzes';
/** @var string default course section summary for the orphaned activities */
const STUDENTQUIZ_COURSE_SECTION_SUMMARY = 'all student quizzes';
/** @var string default course section summaryformat for the orphaned activities */
const STUDENTQUIZ_COURSE_SECTION_SUMMARYFORMAT = 1;
/** @var string default course section visible for the orphaned activities */
const STUDENTQUIZ_COURSE_SECTION_VISIBLE = false;
/** @var string default StudentQuiz quiz practice behaviour */
const STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR = 'immediatefeedback';

/**
 * Load studentquiz from coursemodule id
 *
 * @param int cmid course module id
 * @param int context id id of the context of this course module
 * @return stdClass|bool studentquiz or false
 * TODO: Should we refactor dependency on questionlib by inserting category as parameter?
 */
function mod_studentquiz_load_studentquiz($cmid, $contextid) {
    global $DB;
    if ($studentquiz = $DB->get_record('studentquiz', array('coursemodule' => $cmid))) {
        if ($studentquiz->category = question_get_default_category($contextid)) {
            $studentquiz->categoryid = $studentquiz->category->id;
            return $studentquiz;
        }
    }
    return false;
}

/**
 * Flip a question's approval status.
 * TODO: Ensure question is part of a studentquiz context.
 * @param int questionid index number of question
 */
function mod_studentquiz_flip_approved($questionid) {
    global $DB;

    $approved = $DB->get_field('studentquiz_question', 'approved', array('questionid' => $questionid));

    // TODO: Handle record not found!
    $DB->set_field('studentquiz_question', 'approved', !$approved, array('questionid' => $questionid));
}

/**
 * Returns quiz module id
 * @return int
 */
function mod_studentquiz_get_quiz_module_id() {
    global $DB;
    return $DB->get_field('modules', 'id', array('name' => 'quiz'));
}

/**
 * Check if user has permission to see creator
 * @return bool
 * TODO: Define studentquiz capabilities and check against those only!
 */
function mod_studentquiz_check_created_permission($cmid) {
    $context = context_module::instance($cmid);
    return has_capability('moodle/question:editall', $context);
}

/**
 * Prepare message for notify.
 * @param stdClass $question object
 * @param stdClass $recepient user object receiving the notification
 * @param int $actor user object triggering the notification
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return stdClass Data object with course, module, question, student and teacher info
 */

function mod_studentquiz_prepare_notify_data($question, $recepient, $actor, $course, $module) {
    global $CFG;

    // Prepare message.
    $time = new DateTime('now', core_date::get_user_timezone_object());

    $data = new stdClass();

    // Course info.
    $data->courseid        = $course->id;
    $data->coursename      = $course->fullname;
    $data->courseshortname = $course->shortname;

    // Module info.
    $data->modulename      = $module->name;

    // Question info.
    $data->questionname    = $question->name;
    $data->questionurl     = $CFG->wwwroot . '/question/question.php?cmid=' . $course->id . '&id=' . $question->id;

    // Notification timestamp.
    // TODO: Note: userdate will format for the actor, not for the recepient.
    $data->timestamp    = userdate($time->getTimestamp(), get_string('strftimedatetime', 'langconfig'));

    // Recepient who receives the notification
    $data->recepientidnumber = $recepient->idnumber;
    $data->recepientname     = fullname($recepient);
    $data->recepientusername = $recepient->username;

    // User who triggered the noticication
    $data->actorname     = fullname($actor);
    $data->actorusername = $recepient->username;
    return $data;
}

/**
 * Notify student that someone has edited his question. (Info to question author)
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_changed($questionid, $course, $module) {
    return mod_studentquiz_event_notification_question('changed', $questionid, $course, $module);
}

/**
 * Notify student that someone has deleted his question. (Info to question author)
 * @param int $questionid ID of the author's question.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_deleted($questionid, $course, $module) {
    return mod_studentquiz_event_notification_question('deleted', $questionid, $course, $module);
}

/**
 * Notify student that someone has approved or unapproved his question. (Info to question author)
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_approved($questionid, $course, $module) {
    global $DB;

    $approved = $DB->get_field('studentquiz_question', 'approved', array('questionid' => $questionid));
    return mod_studentquiz_event_notification_question(($approved)? 'approved': 'unapproved', $questionid, $course, $module, 'approved');
}

/**
 * Notify student that someone has commented to his question. (Info to question author)
 * @param stdClass comment that was just added to the question
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_comment_added($comment, $course, $module) {
    return mod_studentquiz_event_notification_comment('added', $comment, $course, $module);
}

/**
 * Notify student that someone has deleted their comment to his question. (Info to question author)
 * Notify student that someone has deleted his comment to someone's question. (Info to comment author)
 * @param stdClass comment that was just added to the question
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_notify_comment_deleted($comment, $course, $module) {
    $successtoauthor = mod_studentquiz_event_notification_comment('deleted', $comment, $course, $module);
    $successtocommenter = mod_studentquiz_event_notification_minecomment('deleted', $comment, $course, $module);
    return $successtoauthor || $successtocommenter;
}

/**
 * Notify question author that an event occured when the autor has this capabilty
 * @param string $event The name of the event, used to automatically get capability and mail contents
 * @param int $questionid ID of the student's questions.
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @param string $othercapability
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_event_notification_question($event, $questionid, $course, $module, $othercapability='') {
    global $DB, $USER;

    $question = $DB->get_record('question', array('id' => $questionid), 'id, name, timemodified, createdby, modifiedby');

    // Creator and Actor must be different.
    if ($question->createdby != $USER->id) {
        $users = user_get_users_by_id(array($question->createdby, $USER->id));
        $recipient = $users[$question->createdby];
        $actor = $users[$USER->id];
        $data = mod_studentquiz_prepare_notify_data($question, $recipient, $actor, $course, $module);

        return mod_studentquiz_send_notification($event, $recipient, $actor, $data);
    }
    return false;
}

/**
 * Notify question author that an event occured when the autor has this capabilty
 * @param string $event The name of the event, used to automatically get capability and mail contents
 * @param stdClass comment that was just added to the question
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_event_notification_comment($event, $comment, $course, $module) {
    global $DB, $USER;

    $questionid = $comment->questionid;
    $question = $DB->get_record('question', array('id' => $questionid), 'id, name, timemodified, createdby, modifiedby');

    // Creator and Actor must be different.
    // If the comment and question is the same recipient, only send the minecomment notification (see function below).
    if ($question->createdby != $USER->id && $comment->userid != $question->createdby) {
        $users = user_get_users_by_id(array($question->createdby, $USER->id));
        $recipient = $users[$question->createdby];
        $actor = $users[$USER->id];
        $data = mod_studentquiz_prepare_notify_data($question, $recipient, $actor, $course, $module);
        $data->commenttext = $comment->comment;
        $data->commenttime = userdate($comment->created, get_string('strftimedatetime', 'langconfig'));

        return mod_studentquiz_send_notification('comment' . $event, $recipient, $actor, $data);
    }

    return false;
}

/**
 * Notify question author that an event occured when the autor has this capabilty
 * @param string $event The name of the event, used to automatically get capability and mail contents
 * @param stdClass comment that was just added to the question
 * @param stdClass $course course object
 * @param stdClass $module course module object
 * @return bool True if sucessfully sent, false otherwise.
 */
function mod_studentquiz_event_notification_minecomment($event, $comment, $course, $module) {
    global $DB, $USER;

    $questionid = $comment->questionid;
    $question = $DB->get_record('question', array('id' => $questionid), 'id, name, timemodified, createdby, modifiedby');

    // Creator and Actor must be different.
    if ($comment->userid != $USER->id) {
        $users = user_get_users_by_id(array($comment->userid, $USER->id));
        $recipient = $users[$comment->userid];
        $actor = $users[$USER->id];
        $data = mod_studentquiz_prepare_notify_data($question, $recipient, $actor, $course, $module);
        $data->commenttext = $comment->comment;
        $data->commenttime = userdate($comment->created, get_string('strftimedatetime', 'langconfig'));

        return mod_studentquiz_send_notification('minecomment' . $event, $recipient, $actor, $data);
    }

    return false;
}

/**
 * Sends notification messages to the interested parties that assign the role capability
 *
 * @param string $event message event string
 * @param stdClass $recipient user object of the intended recipient
 * @param stdClass $submitter user object of the sender
 * @param stdClass $data object of replaceable fields for the templates
 *
 * @return int|false as for {@link message_send()}.
 */
function mod_studentquiz_send_notification($event, $recipient, $submitter, $data) {
    // Recipient info for template.
    $data->useridnumber = $recipient->idnumber;
    $data->username     = fullname($recipient);
    $data->userusername = $recipient->username;

    // Prepare the message.
    $eventdata = new \core\message\message();
    $eventdata->component         = 'mod_studentquiz';
    $eventdata->name              = $event;
    $eventdata->notification      = 1;
    $eventdata->courseid          = $data->courseid;

    $eventdata->userfrom          = $submitter;
    $eventdata->userto            = $recipient;
    $eventdata->subject           = get_string('email' . $event . 'subject', 'studentquiz', $data);
    $eventdata->smallmessage      = get_string('email' . $event . 'small', 'studentquiz', $data);
    $eventdata->fullmessage       = get_string('email' . $event . 'body', 'studentquiz', $data);
    $eventdata->fullmessageformat = FORMAT_PLAIN;
    $eventdata->fullmessagehtml   = '';

    $eventdata->contexturl        = $data->questionurl;
    $eventdata->contexturlname    = $data->questionname;

    // ... and send it.
    return message_send($eventdata);
}

/**
 * Creates a new default category for StudentQuiz
 * @param $context
 * @param string $name Append the name of the module if the context hasn't it yet.
 * @return stdClass The default category - the category in the course context
 * @internal param stdClass $contexts The context objects for this context and all parent contexts.
 */
function mod_studentquiz_add_default_question_category($context, $name='') {
    global $DB;

    $questioncategory = question_make_default_categories(array($context));
    if ($name !== '') {
        $questioncategory->name .= $name;
    }
    $questioncategory->parent = -1;
    $DB->update_record('question_categories', $questioncategory);
    return $questioncategory;
}

/**
 * Generate an attempt with question usage
 * @param array $ids of question ids to be used in this attempt
 * @param stdClass $studentquiz generating this attempt
 * @param userid attempting this StudentQuiz
 * @return stdClass attempt from generate quiz or false on error
 * TODO: Remove dependency on persistence from factory!
 */
function mod_studentquiz_generate_attempt($ids, $studentquiz, $userid) {

    global $DB;

    // Load context of studentquiz activity.
    // TODO: use: this->get_context()?
    $context = context_module::instance($studentquiz->coursemodule);

    $questionusage = question_engine::make_questions_usage_by_activity('mod_studentquiz', $context);

    $attempt = new stdClass();

    // Add further attempt default values here.
    // TODO: Check if get category id always points to lowest context level category of our studentquiz activity.
    $attempt->categoryid = $studentquiz->categoryid;
    $attempt->userid = $userid;

    // TODO: Configurable on Activity Level.
    $questionusage->set_preferred_behaviour(STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR);
    // TODO: Check if this is instance id from studentquiz table.
    $attempt->studentquizid = $studentquiz->id;

    // Add questions to usage
    shuffle($ids);
    $usageorder = array();
    foreach ($ids as $i => $questionid) {
        $questiondata = question_bank::load_question($questionid);
        $usageorder[$i] = $questionusage->add_question($questiondata);
    }

    // Persistence.
    // TODO: Is it necessary to start all questions here, or just the current one?
    $questionusage->start_all_questions();

    question_engine::save_questions_usage_by_activity($questionusage);

    $attempt->questionusageid = $questionusage->get_id();

    $attempt->id = $DB->insert_record('studentquiz_attempt', $attempt);

    return $attempt;
}


/**
 * Trigger Report viewed Event
 */
function mod_studentquiz_report_viewed($cmid, $context)
{
    // TODO: How about $cmid from $context?
    $params = array(
        'objectid' => $cmid,
        'context' => $context
    );

    $event = \mod_studentquiz\event\studentquiz_report_quiz_viewed::create($params);
    $event->trigger();
}

/**
 * Trigger Completion api and view Event
 *
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 */
function mod_studentquiz_overview_viewed($course, $cm, $context) {

    $params = array(
        'objectid' => $cm->id,
        'context' => $context
    );

    $event = \mod_studentquiz\event\course_module_viewed::create($params);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Helper to get ids from prefexed ids in raw submit data
 */
function mod_studentquiz_helper_get_ids_by_raw_submit($rawdata) {
    if (!isset($rawdata)&& empty($rawdata)) {
        return false;
    }
    $ids = array();
    foreach ($rawdata as $key => $value) {
        if (preg_match('!^q([0-9]+)$!', $key, $matches)) {
            $ids[] = $matches[1];
        }
    }
    if (!count($ids)) {
        return false;
    }
    return $ids;
}

/**
 * @param module_context context
 * TODO: Refactor! This check not only checks but also updates!
 */
function mod_studentquiz_check_question_category($context) {
    global $DB;
    $questioncategory = $DB->get_record('question_categories', array('contextid' => $context->id));

    if ($questioncategory->parent != -1) {
        return;
    }

    $parentqcategory = $DB->get_records('question_categories',
        array('contextid' => $context->get_parent_context()->id, 'parent' => 0));
    // If there are multiple parents category with parent == 0, use the one with the lowest id.
    if (!empty($parentqcategory)) {
        $questioncategory->parent = reset($parentqcategory)->id;

        foreach ($parentqcategory as $category) {
            if ($questioncategory->parent > $category->id) {
                $questioncategory->parent = $category->id;
            }
        }
        // TODO: Why is this update necessary?
        $DB->update_record('question_categories', $questioncategory);
    }
}

/**
 * Returns comment records joined with their user first & lastname
 * @param int $questionid
 */
function mod_studentquiz_get_comments_with_creators($questionid) {
    global $DB;

    $sql = 'SELECT co.*, u.firstname, u.lastname FROM {studentquiz_comment} co'
            .' JOIN {user} u on u.id = co.userid'
            .' WHERE co.questionid = :questionid'
            .' ORDER BY co.created ASC';

    return $DB->get_records_sql($sql, array( 'questionid' => $questionid));
}


/**
 * Generate some HTML to render comments
 *
 * @param array $comments from studentquiz_coments joind with user.firstname, user.lastname on comment.userid
 *        ordered by comment->created ASC
 * @param int $userid, viewing user id
 * @param bool $anonymize Display or hide other author names
 * @param bool $ismoderator True renders edit buttons to all comments false, only those for createdby userid
 * @return string HTML fragment
 * TODO: Render function should move to renderers!
 */
function mod_studentquiz_comment_renderer($comments, $userid, $anonymize, $ismoderator) {

    $output = '';

    $modname = 'studentquiz';

    if (empty($comments)) {
        return html_writer::div(get_string('no_comments', $modname));
    }

    $authorids = array();

    $num = 0;
    foreach ($comments as $comment) {

        // Collect distinct comment author ids chronologically.
        if (!in_array($comment->userid, $authorids)) {
            $authorids[] = $comment->userid;
        }

        $date = userdate($comment->created, get_string('strftimedatetime', 'langconfig'));

        $canedit = $ismoderator || $comment->userid == $userid;
        $seename = !$anonymize || $comment->userid == $userid;

        if ($seename) {
            $username = $comment->firstname . ' ' . $comment->lastname;
        } else {
            $username = get_string('creator_anonym_firstname', 'studentquiz')
                . ' #' . (1 + array_search($comment->userid, $authorids));
        }

        if ($canedit) {
            $editspan = html_writer::span('remove', 'remove_action',
                array(
                    'data-id' => $comment->id,
                    'data-question_id' => $comment->questionid
                ));
        } else {
            $editspan = '';
        }

        $output .= html_writer::div( $editspan
            . html_writer::tag('p', $date . ' | ' . $username)
            . html_writer::tag('p', $comment->comment),
            ($num>=2)? 'hidden': ''
        );
        $num++;
    }

    if (count($comments) > 2) {
        $output .= html_writer::div(
            html_writer::tag('button', get_string('show_more', $modname), array('type' => 'button', 'class' => 'show_more'))
            . html_writer::tag('button', get_string('show_less', $modname)
                , array('type' => 'button', 'class' => 'show_less hidden')), 'button_controls'
        );
    }

    return $output;
}

/**
 * gets the Stats of the user for the actual StudentQuiz
 * @param int $userid
 * @param int $cmid course module id of studentquiz activity
 * @return array
 * @deprecated
 * TODO: Refactor!
 */
function mod_studentquiz_get_user_quiz_stats($userid, $cmid) {
    global $DB;
    $sql = 'select ( '
        . '  SELECT count(1) '
        . '  FROM {question} q '
        . '    LEFT JOIN {question_categories} qc ON q.category = qc.id '
        . '    LEFT JOIN {context} c ON qc.contextid = c.id '
        . '  WHERE c.instanceid = :cmid AND q.parent = 0 AND c.contextlevel = 70 '
        . ') AS TotalNrOfQuestions, '
        . '  (SELECT count(1) '
        . '   FROM {question} q '
        . '     LEFT JOIN {question_categories} qc ON q.category = qc.id '
        . '     LEFT JOIN {context} c ON qc.contextid = c.id '
        . '   WHERE c.instanceid = :cmid2 AND q.parent = 0 AND c.contextlevel = 70 AND q.createdby = :userid '
        . '  ) AS TotalUsersQuestions, '
        . '  (select count(DISTINCT att.questionid) '
        . '   from {question_attempt_steps} ats '
        . '     left JOIN {question_attempts} att on att.id = ats.questionattemptid '
        . '   WHERE ats.userid = :userid2 AND ats.state = \'gradedright\' '
        . '         AND att.questionid in (SELECT q.id '
        . '                            FROM {question} q '
        . '                              LEFT JOIN {question_categories} qc ON q.category = qc.id '
        . '                              LEFT JOIN {context} c ON qc.contextid = c.id '
        . '                            WHERE c.instanceid = :cmid3 AND c.contextlevel = 70)'
        . '          AND ats.id IN (SELECT max(suatsmax.id)'
        . '           FROM {question_attempt_steps} suatsmax LEFT JOIN {question_attempts} suattmax'
        . '                 ON suatsmax.questionattemptid = suattmax.id'
        . '             WHERE suatsmax.state IN (\'gradedright\', \'gradedpartial\', \'gradedwrong\') AND'
        . '                   suatsmax.userid = ats.userid'
        . '             GROUP BY suattmax.questionid)) AS TotalRightAnswers ,'
        . '     (select  COALESCE(round(avg(v.vote), 1), 0.0)'
        . ' from {studentquiz_vote} v'
        . ' where v.questionid in (SELECT q.id'
        . '            FROM {question} q LEFT JOIN'
        . '              {question_categories} qc'
        . '                ON q.category = qc.id'
        . '              LEFT JOIN {context} c'
        . '                ON qc.contextid = c.id'
        . '            WHERE c.instanceid = :cmid4 AND'
        . '                  c.contextlevel = 70'
        . '                  and q.createdby = :userid3)) as avgvotes,'
        . ' (select COALESCE(sum(v.approved), 0)'
        . ' from {studentquiz_question} v'
        . ' WHERE v.questionid in (SELECT q.id'
        . '            FROM {question} q LEFT JOIN'
        . '              {question_categories} qc'
        . '                ON q.category = qc.id'
        . '              LEFT JOIN {context} c'
        . '                ON qc.contextid = c.id'
        . '            WHERE c.instanceid = :cmid5 AND'
        . '                  c.contextlevel = 70'
        . '                  and q.createdby = :userid4)) as numapproved ';
    $record = $DB->get_record_sql($sql, array(
        'cmid' => $cmid, 'cmid2' => $cmid, 'cmid3' => $cmid,
        'cmid4' => $cmid, 'cmid5' => $cmid,
        'userid' => $userid, 'userid2' => $userid, 'userid3' => $userid, 'userid4' => $userid));
    return $record;
}

/**
 * Get all users in a course
 * @param int $courseid
 * @return array stdClass userid, courseid, firstname, lastname$
 * TODO: Refactor! We don't want to load all users of a course into memory ever.
 * @deprecated
 */
function mod_studentquiz_get_all_users_in_course($courseid) {
    global $DB;
    $sql = 'SELECT u.id as userid, c.id as courseid, u.firstname, u.lastname'
        . '     FROM {user} u'
        . '     INNER JOIN {user_enrolments} ue ON ue.userid = u.id'
        . '     INNER JOIN {enrol} e ON e.id = ue.enrolid'
        . '     INNER JOIN {course} c ON e.courseid = c.id'
        . '     WHERE c.id = :courseid';

    return $DB->get_records_sql($sql, array(
        'courseid' => $courseid
    ));
}

/**
 * @param $userid
 * @return array usermaxmark usermark stuquizmaxmark
 * @deprecated TODO: We don't want to call this vor every user in the table!
 */
function mod_studentquiz_get_user_quiz_grade($userid, $cmid) {
    global $DB;
    $sql = 'SELECT COALESCE(round(sum(sub.maxmark), 1), 0.0) as usermaxmark, '
        .'         COALESCE(round(sum(sub.mark), 1), 0.0) usermark, '
        .'         COALESCE((SELECT round(sum(q.defaultmark), 1) '
        .'                   FROM {question} q '
        .'                   LEFT JOIN {question_categories} qc ON q.category = qc.id '
        .'                   LEFT JOIN {context} c ON qc.contextid = c.id '
        .'                   WHERE q.parent = 0 AND c.instanceid = :cmid'
        .'                            AND c.contextlevel = 70), 0.0) as stuquizmaxmark '
        .'   FROM ( '
        .'         SELECT suatt.id, suatt.questionid, questionattemptid, max(fraction) as fraction, suatt.maxmark,  '
        .'         max(fraction) * suatt.maxmark as mark '
        .'         from {question_attempt_steps} suats '
        .'         LEFT JOIN {question_attempts} suatt on suats.questionattemptid = suatt.id '
        .'         WHERE state in (\'gradedright\', \'gradedpartial\', \'gradedwrong\') '
        .'            AND userid = :userid AND suatt.questionid'
        .'              IN ('
        .'               SELECT q.id '
        .'               FROM {question} q '
        .'               LEFT JOIN {question_categories} qc ON q.category = qc.id '
        .'               LEFT JOIN {context} c ON qc.contextid = c.id '
        .'               WHERE q.parent = 0 AND c.instanceid = :cmid2 AND c.contextlevel = 70'
        .'              ) '
        .'            AND suats.id '
        .'              IN ('
        .'                SELECT max(suatsmax.id)'
        .'                FROM {question_attempt_steps} suatsmax'
        .'                LEFT JOIN {question_attempts} suattmax ON suatsmax.questionattemptid = suattmax.id'
        .'                where suatsmax.state '
        .'                IN (\'gradedright\', \'gradedpartial\', \'gradedwrong\')'
        .'                AND suatsmax.userid = suats.userid'
        .'                GROUP BY suattmax.questionid'
        .'               )'
        .'  GROUP BY suatt.questionid, suatt.id, suatt.questionid, suatt.maxmark, suats.questionattemptid) as sub ';
    $record = $DB->get_record_sql($sql, array(
        'cmid' => $cmid, 'cmid2' => $cmid,
        'userid' => $userid));
    return $record;
}

/**
 * Get Paginated ranking data ordered (DESC) by points, questions_created, questions_approved, votes_average
 * @param int $cmid Course module id of the StudentQuiz considered.
 * @param stdClass $quantifiers ad-hoc class containing quantifiers for weighted points score.
 * @param int $limitfrom return a subset of records, starting at this point (optional).
 * @param int $limitnum return a subset comprising this many records (optional, required if $limitfrom is set).
 * @return moodle_recordset of paginated ranking table
 */
function mod_studentquiz_get_user_ranking_table($cmid, $quantifiers, $limitfrom = 0, $limitnum = 0) {
    global $DB;
    $sql_select = mod_studentquiz_helper_attempt_stat_select();
    $sql_joins = mod_studentquiz_helper_attempt_stat_joins();
    $sql_order = ' ORDER BY points DESC, questions_created DESC, questions_approved DESC, votes_average DESC, '
            .' question_attempts_correct DESC, question_attempts_incorrect ASC ';

    return $DB->get_recordset_sql($sql_select.$sql_joins.$sql_order,
        array('cmid1' => $cmid, 'cmid2' => $cmid, 'cmid3' => $cmid,
              'cmid4' => $cmid, 'cmid5' => $cmid, 'cmid6' => $cmid
        , 'questionquantifier' => $quantifiers->question
        , 'approvedquantifier' => $quantifiers->approved
        , 'votequantifier' => $quantifiers->vote
        , 'correctanswerquantifier' => $quantifiers->correctanswer
        , 'incorrectanswerquantifier' => $quantifiers->incorrectanswer
        ), $limitfrom, $limitnum);
}

/**
 * Get aggregated studentquiz data
 * @param int $cmid Course module id of the StudentQuiz considered.
 * @param stdClass $quantifiers ad-hoc class containing quantifiers for weighted points score.
 * @return moodle_recordset of paginated ranking table
 */
function mod_studentquiz_community_stats($cmid, $quantifiers) {
    global $DB;
    $sql_select = 'SELECT '
        .' count(*) participants,'
        // calculate points
        // TODO: Calc Points if needed - it's messy.
        // questions created
        .' COALESCE(sum(creators.countq), 0) questions_created,'
        // questions approved
        .' COALESCE(sum(approvals.countq), 0) questions_approved,'
        // questions rating received
        .' COALESCE(votes.countv, 0) votes_received,'
        .' COALESCE(COALESCE(sum(votes.sumv), 0) / COALESCE(sum(votes.countv), 0),0) votes_average,'
        // question attempts
        .' COALESCE(sum(attempts.counta), 0) question_attempts,'
        .' COALESCE(sum(attempts.countright), 0) question_attempts_correct,'
        .' COALESCE(COALESCE(sum(attempts.counta), 0) - COALESCE(sum(attempts.countright), 0),0) question_attempts_incorrect,'
        // last attempt
        .' COALESCE(sum(lastattempt.last_attempt_exists), 0) last_attempt_exists,'
        .' COALESCE(sum(lastattempt.last_attempt_correct), 0) last_attempt_correct';
    $sql_joins = mod_studentquiz_helper_attempt_stat_joins();
    return $DB->get_record_sql($sql_select.$sql_joins,
        array('cmid1' => $cmid, 'cmid2' => $cmid, 'cmid3' => $cmid,
            'cmid4' => $cmid, 'cmid5' => $cmid, 'cmid6' => $cmid
        ));
}

/**
 * Get aggregated studentquiz data
 * @param int $cmid Course module id of the StudentQuiz considered.
 * @param stdClass $quantifiers ad-hoc class containing quantifiers for weighted points score.
 * @param int $userid
 * @return record of user ranking stats
 */
function mod_studentquiz_user_stats($cmid, $quantifiers, $userid) {
    global $DB;
    $sql_select = mod_studentquiz_helper_attempt_stat_select();
    $sql_joins = mod_studentquiz_helper_attempt_stat_joins();
    $sql_add_where = ' AND u.id = :userid ';
    return $DB->get_record_sql($sql_select.$sql_joins.$sql_add_where,
        array('cmid1' => $cmid, 'cmid2' => $cmid, 'cmid3' => $cmid,
            'cmid4' => $cmid, 'cmid5' => $cmid, 'cmid6' => $cmid
        , 'questionquantifier' => $quantifiers->question
        , 'approvedquantifier' => $quantifiers->approved
        , 'votequantifier' => $quantifiers->vote
        , 'correctanswerquantifier' => $quantifiers->correctanswer
        , 'incorrectanswerquantifier' => $quantifiers->incorrectanswer
            , 'userid' => $userid
        ));
}

/**
 * @return
 * TODO: Refactor: There must be a better way to do this!
 */
function mod_studentquiz_helper_attempt_stat_select() {
    return 'SELECT '
        .' u.id userid,'
        .' u.firstname firstname,'
        .' u.lastname lastname,'
        // calculate points
        .' COALESCE ( ROUND('
        .' COALESCE(creators.countq, 0) * :questionquantifier  ' // questions created
        .'+ COALESCE(approvals.countq, 0) * :approvedquantifier  ' // questions approved
        .'+ COALESCE(COALESCE(votes.sumv, 0) / COALESCE(votes.countv, 0),0) * :votequantifier  ' // voting
        .'+ COALESCE(attempts.countright, 0) * :correctanswerquantifier  ' // correct answers
        .'+ COALESCE(COALESCE(attempts.counta, 0) - COALESCE(attempts.countright, 0),0) * :incorrectanswerquantifier ' // incorrect answers
        .' , 1) , 0) points, '
        // questions created
        .' COALESCE(creators.countq, 0) questions_created,'
        // questions approved
        .' COALESCE(approvals.countq, 0) questions_approved,'
        // questions rating received
        .' COALESCE(votes.countv, 0) votes_received,'
        .' COALESCE(COALESCE(votes.sumv, 0) / COALESCE(votes.countv, 0),0) votes_average,'
        // question attempts
        .' COALESCE(attempts.counta, 0) question_attempts,'
        .' COALESCE(attempts.countright, 0) question_attempts_correct,'
        .' COALESCE(COALESCE(attempts.counta, 0) - COALESCE(attempts.countright, 0),0) question_attempts_incorrect,'
        // last attempt
        .' COALESCE(lastattempt.last_attempt_exists, 0) last_attempt_exists,'
        .' COALESCE(lastattempt.last_attempt_correct, 0) last_attempt_correct';
}

/**
 * @return string
 * TODO: Refactor: There must be a better way to do this!
 */
function mod_studentquiz_helper_attempt_stat_joins() {
    return ' FROM {studentquiz} sq'
        // get this Studentquiz Question category
        .' JOIN {context} con ON con.instanceid = sq.coursemodule'
        .' JOIN {question_categories} qc ON qc.contextid = con.id'
        // only enrolled users
        .' JOIN {course} c ON c.id = sq.course'
        .' JOIN {enrol} e ON e.courseid = c.id'
        .' JOIN {user_enrolments} ue ON ue.enrolid = e.id'
        .' JOIN {user} u ON ue.userid = u.id'
        // question created by user
        .' LEFT JOIN'
        .' ( SELECT count(*) countq, q.createdby creator'
        .' FROM {studentquiz} sq'
        .' JOIN {context} con ON con.instanceid = sq.coursemodule'
        .' JOIN {question_categories} qc ON qc.contextid = con.id'
        .' JOIN {question} q on q.category = qc.id'
        .' WHERE q.hidden = 0 AND sq.coursemodule = :cmid4'
        .' GROUP BY creator'
        .' ) creators ON creators.creator = u.id'
        // Approved questions
        .' LEFT JOIN'
        .' ( SELECT count(*) countq, q.createdby creator'
        .' FROM {studentquiz} sq'
        .' JOIN {context} con ON con.instanceid = sq.coursemodule'
        .' JOIN {question_categories} qc ON qc.contextid = con.id'
        .' JOIN {question} q on q.category = qc.id'
        .' JOIN {studentquiz_question} sqq ON q.id = sqq.questionid'
        .' where q.hidden = 0 AND sqq.approved = 1 AND sq.coursemodule = :cmid5'
        .' group by creator'
        .' ) approvals ON approvals.creator = u.id'
        // Rating for own questions received
        .' LEFT JOIN'
        .' ( SELECT count(*) countv, sum(sqv.vote) sumv, q.createdby creator'
        .' FROM {studentquiz} sq'
        .' JOIN {context} con ON con.instanceid = sq.coursemodule'
        .' JOIN {question_categories} qc ON qc.contextid = con.id'
        .' JOIN {question} q on q.category = qc.id'
        .' JOIN {studentquiz_vote} sqv ON q.id = sqv.questionid'
        .' where q.hidden = 0 AND sq.coursemodule = :cmid6'
        .' group by q.createdby'
        .' ) votes ON votes.creator = u.id'
        // question attempts: sum of number of graded attempts per questoin
        .' LEFT JOIN'
        .' ('
        .' SELECT count(*) counta,'
        .' SUM(CASE WHEN state = \'gradedright\' THEN 1 ELSE 0 END) countright,'
        .' sqa.userid userid'
        .' FROM {studentquiz} sq'
        .' JOIN {studentquiz_attempt} sqa ON sq.id = sqa.studentquizid'
        .' JOIN {question_usages} qu ON qu.id = sqa.questionusageid'
        .' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
        .' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
        .' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
        .' WHERE sq.coursemodule = 3'
        // Todo: Note: There are grading states by manual grading as well.
        .' AND qas.state in (\'gradedright\', \'gradedwrong\', \'gradedpartial\')'
        // only count grading triggered by submits
        .' AND qasd.name = \'answer\''
        .' group by sqa.userid'
        .' ) attempts ON attempts.userid = u.id'
        // LEFT JOIN latest attempts
        .' LEFT JOIN'
        .' ('
        .' SELECT'
        .' sqa.userid,'
        .' count(*) last_attempt_exists,'
        .' SUM(CASE WHEN qas.state = \'gradedright\' THEN 1 ELSE 0 END) last_attempt_correct'
        .' FROM {studentquiz} sq'
        .' JOIN {studentquiz_attempt} sqa ON sq.id = sqa.studentquizid'
        .' JOIN {question_usages} qu ON qu.id = sqa.questionusageid'
        .' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
        .' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
        .' LEFT JOIN {question_attempt_step_data} qasd ON'
        .' qasd.attemptstepid = qas.id and'
        .' qasd.id in ('
        // SELECT only latest states (its a constant result)
        .' SELECT max(qasd.id) latest_grading_event'
        .' FROM {studentquiz} sq'
        .' JOIN {studentquiz_attempt} sqa ON sq.id = sqa.studentquizid'
        .' JOIN {question_usages} qu ON qu.id = sqa.questionusageid'
        .' JOIN {question_attempts} qa ON qa.questionusageid = qu.id'
        .' JOIN {question_attempt_steps} qas ON qas.questionattemptid = qa.id'
        .' LEFT JOIN {question_attempt_step_data} qasd ON qasd.attemptstepid = qas.id'
        .' WHERE sq.coursemodule = :cmid1 AND qas.state in (\'gradedright\', \'gradedwrong\', \'gradedpartial\') AND qasd.name = \'answer\''
        .' group by sqa.userid, questionid'
        .' )'
        .' WHERE sq.coursemodule = :cmid2'
        .' AND qas.state in (\'gradedright\', \'gradedwrong\')'
        // only count grading triggered by submits
        .' AND qasd.name = \'answer\''
        .' group by userid'
        .' ) lastattempt ON lastattempt.userid = u.id'
        .' WHERE sq.coursemodule = :cmid3';
}

/**
 * @param $studentquizid
 * @param $userid
 * @return array
 * @deprecated
 * TODO: Add pagination!
 */
function mod_studentquiz_get_user_attempts($studentquizid, $userid) {
    global $DB;
    return $DB->get_records('studentquiz_attempt',
        array('studentquizid' => $studentquizid, 'userid' => $userid));
}

/**
 * @param $usageid
 * @param $total
 * @deprecated
 * TODO: We dont want to sum this in memory for each attempt for each user!
 */
function mod_studentquiz_get_attempt_stats($usageid, &$total) {
    $quba = question_engine::load_questions_usage_by_activity($usageid);
    foreach ($quba->get_slots() as $slot) {
        $fraction = $quba->get_question_fraction($slot);
        $maxmarks = $quba->get_question_max_mark($slot);
        $total->obtainedmarks += $fraction * $maxmarks;
        if ($fraction > 0) {
            ++$total->questionsright;
        }
        ++$total->questionsanswered;
    }
}

/**
 * Lookup available question types.
 * @return array question types with identifier as key and name as value
 */
function mod_studentquiz_get_question_types() {
    $types = question_bank::get_creatable_qtypes();
    $returntypes = array();

    foreach ($types as $name => $qtype) {
        if ($name != 'randomsamatch') {
            $returntypes[$name] = $qtype->local_name();
        }
    }
    return $returntypes;
}

/**
 * Add capabilities to teacher (Non editing teacher) and
 * Student roles in the context of this context
 * @param stdClass $context of the studentquiz activity
 * @return true or exception
 */
function mod_studentquiz_add_question_capabilities($context) {
    $archtyperoles = array('student', 'teacher');
    $roles = array();
    foreach($archtyperoles as $archtyperole) {
        foreach(get_archetype_roles($archtyperole) as $role) {
            $roles[] = $role;
        }
    }
    $capabilities = array(
        'moodle/question:add',
        'moodle/question:usemine',
        'moodle/question:viewmine',
        'moodle/question:editmine');
    foreach($capabilities as $capability) {
        foreach($roles as $role) {
            assign_capability($capability, CAP_ALLOW, $role->id, $context->id, false);
        }
    }
    return true;
}

/**
 * @param int|null $course_id
 */
function mod_studentquiz_migrate_old_quiz_usage(int $course_id=null) {
    global $DB;

    $courseids = array();
    if (!empty($course_id)) {
        $courseids[] = $course_id;
    } else {
        $courseids = $DB->get_fieldset_sql('
            select distinct cm.course
            from {course_modules} cm
            inner join {context} c on cm.id = c.instanceid
            inner join {question_categories} cats on cats.contextid = c.id
            inner join {modules} m on cm.module = m.id
            where m.name = :modulename
        ', array(
            'modulename' => 'studentquiz'
        ));
    }

    foreach($courseids as $courseid) {
        $studentquizzes = $DB->get_records_sql('
            select s.id, s.name, cm.id as cmid, c.id as contextid, cats.id as categoryid
            from {studentquiz} s
            inner join {course_modules} cm on s.id = cm.instance
            inner join {context} c on cm.id = c.instanceid
            inner join {question_categories} cats on cats.contextid = c.id
            inner join {modules} m on cm.module = m.id
            where m.name = :modulename
            and cm.course = :course
        ', array(
            'modulename' => 'studentquiz',
            'course' => $courseid
        ));

        foreach ($studentquizzes as $studentquiz) {

            $oldquizzes = $DB->get_records_sql('
                select q.id, cm.id as cmid, cm.section as sectionid, c.id as contextid, qu.id as qusageid
                from {quiz} q
                inner join {course_modules} cm on q.id = cm.instance
                inner join {context} c on cm.id = c.instanceid
                inner join {modules} m on cm.module = m.id
                inner join {question_usages} qu on c.id = qu.contextid
                where m.name = :modulename
                and cm.course = :course
                and q.name = :name
            ', array(
                'modulename' => 'quiz',
                'course' => $courseid,
                'name' => $studentquiz->name
            ));

            // For each old quiz we need to move the question usage.
            foreach ($oldquizzes as $oldquiz) {
                $DB->set_field('question_usages', 'component', 'mod_studentquiz', array('id' => $oldquiz->qusageid));
                $DB->set_field('question_usages', 'contextid', $studentquiz->contextid, array('id' => $oldquiz->qusageid));
                $DB->set_field('question_usages', 'preferredbehaviour', STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR, array('id' => $oldquiz->qusageid));
                $DB->set_field('question_attempts', 'behaviour', STUDENTQUIZ_DEFAULT_QUIZ_BEHAVIOUR, array('questionusageid' => $oldquiz->qusageid));

                // Now we need each user as own attempt.
                $userids = $DB->get_fieldset_sql('
                    select distinct qas.userid
                    from {question_attempt_steps} qas
                    inner join {question_attempts} qa on qas.questionattemptid = qa.id
                    where qa.questionusageid = :qusageid
                ', array(
                    'qusageid' => $oldquiz->qusageid
                ));
                foreach ($userids as $userid) {
                    $DB->insert_record('studentquiz_attempt', (object)array(
                        'studentquizid' => $studentquiz->id,
                        'userid' => $userid,
                        'questionusageid' => $oldquiz->qusageid,
                        'categoryid' => $studentquiz->categoryid,
                    ));
                }
                // So that quiz doesn't remove the question usages.
                $DB->delete_records('quiz_attempts', array('quiz' => $oldquiz->id));
                // And delete the quiz finally.
                quiz_delete_instance($oldquiz->id);
            }
        }

        // Try to clean up sections. Need to be exactly as created by v2.0.3 and before. Otherwise manual removal needed as it
        // can't be detected properly.
        $oldsections = $DB->get_fieldset_sql('
                SELECT s.id
                FROM {course_sections} s
                left join {course_modules} m on s.id = m.section
                where s.course = :course
                and m.id is NULL
                and s.name = :sectionname
                and s.summary = :sectionsummary
            ', array(
            'course' => $courseid,
            'sectionname' => STUDENTQUIZ_COURSE_SECTION_NAME,
            'sectionsummary' => STUDENTQUIZ_COURSE_SECTION_SUMMARY
        ));
        foreach($oldsections as $sectionid) {
            $DB->delete_records('course_sections', array(
                    'id' => $sectionid
                )
            );
        }
    }
}


function mod_studentquiz_count_questions($cmid) {
    global $DB;
    return $DB->count_records_sql(
        'SELECT COUNT(*) FROM {question} q '
            . ' LEFT JOIN {question_categories} qc ON q.category = qc.id'
            . ' LEFT JOIN {context} c ON qc.contextid = c.id'
            . ' WHERE c.instanceid = :cmid', array('cmid' => $cmid));
}