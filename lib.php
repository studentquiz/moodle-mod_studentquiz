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
 * Library of interface functions and constants for module StudentQuiz
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the StudentQuiz specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_studentquiz\commentarea\container;
use mod_studentquiz\utils;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/questionlib.php');
require_once(__DIR__ . '/locallib.php');

/* Core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@see plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function studentquiz_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_USES_QUESTIONS:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COLLABORATION;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the StudentQuiz into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $studentquiz Submitted data from the form in mod_form.php
 * @param mod_studentquiz_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted studentquiz record
 */
function studentquiz_add_instance(stdClass $studentquiz, mod_studentquiz_mod_form $mform = null) {
    global $DB;

    $studentquiz->timecreated = time();

    // TODO unify parsing of submitted variables! mform and studentquiz are quite the same? or where do they exactly differ?

    if (!empty($mform->anonymrank)) {
        $studentquiz->anonymrank = $mform->anonymrank;
    }
    if (empty($studentquiz->anonymrank)) {
        $studentquiz->anonymrank = 0;
    }

    if (!isset($studentquiz->allowedqtypes)) {
        $studentquiz->allowedqtypes = 'ALL';
    } else {
        $studentquiz->allowedqtypes = implode(',', array_keys($studentquiz->allowedqtypes));
    }

    if (!isset($studentquiz->hiddensection)) {
        $studentquiz->hiddensection = 0;
    }

    if (isset($mform->hiddensection)) {
        $studentquiz->hiddensection = $mform->hiddensection;
    }

    $studentquiz->excluderoles = (!empty($studentquiz->excluderoles))
        ? implode(',', array_keys($studentquiz->excluderoles)) : '';

    if (!isset($studentquiz->forcerating)) {
        $studentquiz->forcerating = 0;
    }

    if (!isset($studentquiz->forcecommenting)) {
        $studentquiz->forcecommenting = 0;
    }

    if (!isset($studentquiz->privatecommenting)) {
        $studentquiz->privatecommenting = 0;
    }

    // New StudentQuiz instances use the aggregated mode.
    $studentquiz->aggregated = 1;

    // You may have to add extra stuff in here.
    $studentquiz->id = $DB->insert_record('studentquiz', $studentquiz);
    $context = context_module::instance($studentquiz->coursemodule);

    // Early update context in database so default categories know where the instance can be found.
    $DB->set_field('course_modules', 'instance', $studentquiz->id, array('id' => $context->instanceid));

    // Add default module context question category.
    question_make_default_categories(array($context));
    studentquiz_process_event($studentquiz);

    return $studentquiz->id;
}

/**
 * Updates an instance of the StudentQuiz in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $studentquiz An object from the form in mod_form.php
 * @param mod_studentquiz_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function studentquiz_update_instance(stdClass $studentquiz, mod_studentquiz_mod_form $mform = null) {
    global $DB;

    // For checkboxes, when deselected, $1 still contains the data from the database, because browser doesn't
    // send it.

    $studentquiz->timemodified = time();
    $studentquiz->id = $studentquiz->instance;

    if (!isset($studentquiz->anonymrank)) {
        $studentquiz->anonymrank = 0;
    }

    if (!isset($studentquiz->publishnewquestion)) {
        $studentquiz->publishnewquestion = 0;
    }

    $studentquiz->allowedqtypes = implode(',', array_keys($studentquiz->allowedqtypes));

    $studentquiz->excluderoles = (!empty($studentquiz->excluderoles))
        ? implode(',', array_keys($studentquiz->excluderoles)) : '';

    if (!isset($studentquiz->forcerating)) {
        $studentquiz->forcerating = 0;
    }

    if (!isset($studentquiz->forcecommenting)) {
        $studentquiz->forcecommenting = 0;
    }

    if (!isset($studentquiz->privatecommenting)) {
        $studentquiz->privatecommenting = 0;
    }

    if (!isset($studentquiz->commentdeletionperiod)) {
        $studentquiz->commentdeletionperiod = get_config('studentquiz', 'commentediting_deletionperiod');
    }

    $currentdata = $DB->get_record('studentquiz', ['id' => $studentquiz->instance]);
    if ($currentdata->digesttype != $studentquiz->digesttype) {
        $params = [
                'objectid' => $currentdata->coursemodule,
                'context' => context_module::instance($currentdata->coursemodule),
                'other' => [
                        'olddigesttype' => $currentdata->digesttype,
                        'newdigesttype' => $studentquiz->digesttype
                ]
        ];
        if ($currentdata->digesttype == 2) {
            $params['other']['olddigestfirstday'] = $currentdata->digestfirstday;
        }
        $event = \mod_studentquiz\event\studentquiz_digest_changed::create($params);
        $event->trigger();
    }

    $result = $DB->update_record('studentquiz', $studentquiz);
    studentquiz_process_event($studentquiz);
    return $result;
}

/**
 * Removes an instance of the StudentQuiz from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function studentquiz_delete_instance($id) {
    global $DB;

    if (! $studentquiz = $DB->get_record('studentquiz', ['id' => $id])) {
        return false;
    }
    // Delete event in calendar when deleting studentquiz.
    $studentquiz->completionexpected = null;
    studentquiz_process_event($studentquiz);

    $sql = "studentquizquestionid IN (SELECT id FROM {studentquiz_question} WHERE studentquizid = :studentquizid)";
    $params = ['studentquizid' => $id];

    $DB->delete_records_select('studentquiz_rate', $sql, $params);
    $DB->delete_records_select('studentquiz_progress', $sql, $params);
    $comments = $DB->get_records_select('studentquiz_comment',
        $sql, $params, '', 'id');
    if ($comments) {
        $commentids = array_column($comments, 'id');
        list($commentsql, $commentparams) = $DB->get_in_or_equal($commentids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('studentquiz_comment_history', "commentid $commentsql", $commentparams);
    }
    $DB->delete_records_select('studentquiz_comment', $sql, $params);
    $DB->delete_records_select('studentquiz_state_history', $sql, $params);
    $DB->delete_records_select('question_references', 'itemid IN (SELECT id FROM {studentquiz_question}
         WHERE studentquizid = :studentquizid) AND component = :component AND questionarea = :questionarea',
        ['studentquizid' => $id, 'component' => 'mod_studentquiz', 'questionarea' => 'studentquiz_question']);
    $DB->delete_records('studentquiz_attempt', $params);
    $DB->delete_records('studentquiz_notification', $params);
    $DB->delete_records('studentquiz_question', $params);

    $role = $DB->get_record('role', array('shortname' => 'student'));
    $context = context_module::instance($studentquiz->coursemodule);
    $DB->delete_records('role_capabilities', array('roleid' => $role->id, 'contextid' => $context->id));

    $DB->delete_records('studentquiz', array('id' => $studentquiz->id));

    return true;
}

/**
 * Add student quiz event to calendar.
 *
 * @param object $studentquiz
 */
function studentquiz_process_event(object $studentquiz): void {
    $completiontimeexpected = !empty($studentquiz->completionexpected) ? $studentquiz->completionexpected : null;
    \core_completion\api::update_completion_date_event($studentquiz->coursemodule,
        'studentquiz', $studentquiz->id, $completiontimeexpected);
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $studentquiz The StudentQuiz instance record
 * @return stdClass|null
 */
function studentquiz_user_outline($course, $user, $mod, $studentquiz) {
    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $studentquiz the module instance record
 */
function studentquiz_user_complete($course, $user, $mod, $studentquiz) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in StudentQuiz activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function studentquiz_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@see studentquiz_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function studentquiz_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see studentquiz_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@see get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function studentquiz_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function studentquiz_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function studentquiz_get_extra_capabilities() {
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    return question_get_all_capabilities();
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@see file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function studentquiz_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for StudentQuiz file areas
 *
 * @package mod_studentquiz
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function studentquiz_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the StudentQuiz file areas
 *
 * @package mod_studentquiz
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the StudentQuiz's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function studentquiz_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, false, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the settings navigation with the StudentQuiz settings
 *
 * This function is called when the context for the page is a StudentQuiz module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $studentquiznode StudentQuiz administration node
 */
function studentquiz_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $studentquiznode) {
    global $PAGE, $CFG;

    // Require questionlib.
    // Included here as we only ever want to include this file if we really need to.
    require_once($CFG->libdir . '/questionlib.php');

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $studentquiznode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false && array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    // Add the navigation items.
    $studentquiznode->add_node(navigation_node::create(get_string('reportquiz_stats_title', 'studentquiz'),
        new moodle_url('/mod/studentquiz/reportstat.php', array('id' => $PAGE->cm->id)),
        navigation_node::TYPE_SETTING, null, 'mod_studentquiz_statistics',
        new pix_icon('i/report', '')), $beforekey);
    $studentquiznode->add_node(navigation_node::create(get_string('reportrank_title', 'studentquiz'),
        new moodle_url('/mod/studentquiz/reportrank.php', array('id' => $PAGE->cm->id)),
        navigation_node::TYPE_SETTING, null, 'mod_studentquiz_rank',
        new pix_icon('i/scales', '')), $beforekey);

    if (mod_studentquiz_check_created_permission($PAGE->cm->id)) {
        question_extend_settings_navigation($studentquiznode, $PAGE->cm->context)->trim_if_empty();
    }
}

/**
 * Called via pluginfile.php -> mod_studentquiz_question_pluginfile to serve files belonging to
 * a question from a studentquiz activity.
 *
 * @package  mod_studentquiz
 * @category files
 * @param stdClass $course course settings object
 * @param stdClass $context context object
 * @param string   $component the name of the component we are serving files for.
 * @param string   $filearea the name of the file area.
 * @param int      $qubaid the attempt usage id.
 * @param int      $slot the id of a question in this quiz attempt.
 * @param array    $args the remaining bits of the file path.
 * @param bool     $forcedownload whether the user must be forced to download the file.
 * @param array    $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function mod_studentquiz_question_pluginfile($course, $context, $component,
                                            $filearea, $qubaid, $slot, $args, $forcedownload, array $options = array()) {

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/$component/$filearea/$relativepath";
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Comment form fragment.
 *
 * @param array $params - Params used to load comment form.
 * @return string
 */
function mod_studentquiz_output_fragment_commentform($params) {
    if (!isset($params['replyto'])) {
        throw new moodle_exception('missingparam', 'studentquiz');
    }
    $cancelbutton = isset($params['cancelbutton']) ? $params['cancelbutton'] : false;
    // Assign data to edit post form, this will also check for session key.
    $mform = new \mod_studentquiz\commentarea\form\comment_form([
            'studentquizquestionid' => $params['studentquizquestionid'],
            'cmid' => $params['cmid'],
            'replyto' => $params['replyto'],
            'forcecommenting' => $params['forcecommenting'],
            'cancelbutton' => $cancelbutton,
            'type' => $params['type']
    ]);
    return $mform->get_html();
}

/**
 * Edit comment form fragment.
 *
 * @param array $params - Params used to load comment form.
 * @return string
 */
function mod_studentquiz_output_fragment_commenteditform($params) {
    if (!isset($params['commentid'])) {
        throw new moodle_exception('missingparam', 'studentquiz');
    }
    $cancelbutton = isset($params['cancelbutton']) ? $params['cancelbutton'] : false;

    $studentquizquestion = utils::get_data_for_comment_area($params['studentquizquestionid'], $params['cmid']);
    $commentarea = new container($studentquizquestion, null, '', $params['type']);
    $comment = $commentarea->query_comment_by_id($params['commentid']);
    if (!$comment) {
        throw new moodle_exception('invalidcomment', 'studentquiz');
    }

    $formdata = ['text' => $comment->get_comment_data()->comment];
    $mform = new \mod_studentquiz\commentarea\form\comment_form([
            'studentquizquestionid' => $params['studentquizquestionid'],
            'cmid' => $params['cmid'],
            'commentid' => $params['commentid'],
            'forcecommenting' => $params['forcecommenting'],
            'cancelbutton' => $cancelbutton,
            'editmode' => true,
            'type' => $params['type'],
            'formdata' => $formdata
    ]);

    return $mform->get_html();
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview/timeline in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_studentquiz_core_calendar_provide_event_action(calendar_event $event,
    \core_calendar\action_factory $factory, int $userid = 0): ?\core_calendar\local\event\entities\action_interface {
    global $USER;
    if (!$userid) {
        $userid = $USER->id;
    }
    $cm = get_fast_modinfo($event->courseid, $userid)->instances['studentquiz'][$event->instance];
    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }
    $completion = new \completion_info($cm->get_course());
    $completiondata = $completion->get_data($cm, false, $userid);
    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }
    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/studentquiz/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Standard callback used by questions_in_use.
 *
 * @param array $questionids array of question ids.
 * @return bool whether any of these questions are attempted in this studentquiz instance.
 */
function studentquiz_questions_in_use(array $questionids): bool {
    return question_engine::questions_in_use($questionids,
        new qubaid_join('{studentquiz_attempt} sa', 'sa.questionusageid'));
}
