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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/questionlib.php');
require_once(__DIR__ . '/locallib.php');

/* Core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
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
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
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

    if (!isset($studentquiz->anonymrank)) {
        $studentquiz->anonymrank = 0;
    }

    if (isset($mform->anonymrank)) {
        $studentquiz->anonymrank = $mform->anonymrank;
    }

    if ((!isset($studentquiz->hiddensection))) {
        $studentquiz->hiddensection = 0;
    }

    if (isset($mform->hiddensection)) {
        $studentquiz->hiddensection = $mform->hiddensection;
    }

    // You may have to add extra stuff in here.
    $studentquiz->id = $DB->insert_record('studentquiz', $studentquiz);

    $role = $DB->get_record('role', array('shortname' => 'student'));
    $context = context_module::instance($studentquiz->coursemodule);

    $capabilities = array(
        'moodle/question:usemine',
        'moodle/question:useall',
        'moodle/question:editmine',
        'moodle/question:add'
    );

    foreach ($capabilities as $capability) {
        $obj = new stdClass();
        $obj->contextid = $context->id;
        $obj->roleid = $role->id;
        $obj->capability = $capability;
        $obj->permission = 1;
        $obj->timemodified = time();
        $obj->modifierid = 0;

        $DB->insert_record('role_capabilities', $obj, false);
    }

    // Add default category.
    $questioncategory = question_make_default_categories(array($context));
    $questioncategory->name .= $studentquiz->name;
    $questioncategory->parent = 0;
    $DB->update_record('question_categories', $questioncategory);

    studentquiz_grade_item_update($studentquiz);

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

    $studentquiz->timemodified = time();
    $studentquiz->id = $studentquiz->instance;

    if (!isset($studentquiz->anonymrank)) {
        $studentquiz->anonymrank = 0;
    }

    // You may have to add extra stuff in here.

    $result = $DB->update_record('studentquiz', $studentquiz);

    studentquiz_grade_item_update($studentquiz);

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

    if (! $studentquiz = $DB->get_record('studentquiz', array('id' => $id))) {
        return false;
    }

    $role = $DB->get_record('role', array('shortname' => 'student'));
    $context = context_module::instance($studentquiz->coursemodule);
    $DB->delete_records('role_capabilities', array('roleid' => $role->id, 'contextid' => $context->id));

    $DB->delete_records('studentquiz', array('id' => $studentquiz->id));

    studentquiz_grade_item_delete($studentquiz);

    return true;
}

/**
 * Clean up studentquiz question categories when deleting studentquiz
 * @Warning: This callback is only triggered in Moodle Version >=3.1!
 * @param cm_info|stdClass $mod The course module info object or record
 * @return true|false
 */
function studentquiz_pre_course_module_delete($cm) {
    global $DB;

    // Skip if $cm is not a studentquiz module.
    if (! $studentquiz = $DB->get_record('studentquiz', array('id' => $cm->instance))) {
        return false;
    }
    $context = context_module::instance($studentquiz->coursemodule);

    $DB->delete_records('question_categories', array('contextid' => $context->id));
    return true;
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
 * {@link studentquiz_print_recent_mod_activity()}.
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
 * Prints single activity item prepared by {@link studentquiz_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
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
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of StudentQuiz?
 *
 * This function returns if a scale is being used by one StudentQuiz
 * if it has support for grading and scales.
 *
 * @param int $studentquizid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given StudentQuiz instance
 */
function studentquiz_scale_used($studentquizid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('studentquiz', array('id' => $studentquizid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of StudentQuiz.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any StudentQuiz instance
 */
function studentquiz_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('studentquiz', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given StudentQuiz instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $studentquiz instance object with extra cmidnumber and modname property
 * @param array or string $grades 'reset' grades in the gradebook
 * @return int Returns GRADE_UPDATE_OK, GRADE_UPDATE_FAILED, GRADE_UPDATE_MULTIPLE or GRADE_UPDATE_ITEM_LOCKED
 */
function studentquiz_grade_item_update(stdClass $studentquiz, $grades=null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($studentquiz->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($studentquiz->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $studentquiz->grade;
        $item['grademin']  = 0;
    } else if ($studentquiz->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$studentquiz->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $item['reset'] = true;
    }

    return grade_update('mod/studentquiz', $studentquiz->course, 'mod', 'studentquiz',
            $studentquiz->id, 0, $grades, $item);
}

/**
 * Delete grade item for given StudentQuiz instance
 *
 * @param stdClass $studentquiz instance object
 * @return grade_item
 */
function studentquiz_grade_item_delete($studentquiz) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/studentquiz', $studentquiz->course, 'mod', 'studentquiz',
            $studentquiz->id, 0, null, array('deleted' => 1));
}

/**
 * Update StudentQuiz grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $studentquiz instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function studentquiz_update_grades(stdClass $studentquiz, $userid = 0) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();

    grade_update('mod/studentquiz', $studentquiz->course, 'mod', 'studentquiz', $studentquiz->id, 0, $grades);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
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

    require_login($course, true, $cm);

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

    // Require {@link questionlib.php}
    // Included here as we only ever want to include this file if we really need to.
    require_once($CFG->libdir . '/questionlib.php');

    // We want to add these new nodes after the Edit settings node, and before the
    // Locally assigned roles node. Of course, both of those are controlled by capabilities.
    $keys = $studentquiznode->get_children_key_list();
    $beforekey = null;
    $i = array_search('modedit', $keys);
    if ($i === false and array_key_exists(0, $keys)) {
        $beforekey = $keys[0];
    } else if (array_key_exists($i + 1, $keys)) {
        $beforekey = $keys[$i + 1];
    }

    // Add the navigation items.
    $studentquiznode->add_node(navigation_node::create(get_string('modulename', 'studentquiz'),
        new moodle_url('/mod/studentquiz/view.php', array('id' => $PAGE->cm->id)),
        navigation_node::TYPE_SETTING, null, 'mod_studentquiz_dashboard',
        new pix_icon('i/cohort', '')), $beforekey);
    $studentquiznode->add_node(navigation_node::create(get_string('reportquiz_dashboard_title', 'studentquiz'),
        new moodle_url('/mod/studentquiz/reportquiz.php', array('id' => $PAGE->cm->id)),
        navigation_node::TYPE_SETTING, null, 'mod_studentquiz_statistics',
        new pix_icon('i/report', '')), $beforekey);
    $studentquiznode->add_node(navigation_node::create(get_string('nav_report_rank', 'studentquiz'),
        new moodle_url('/mod/studentquiz/reportrank.php', array('id' => $PAGE->cm->id)),
        navigation_node::TYPE_SETTING, null, 'mod_studentquiz_rank',
        new pix_icon('i/scales', '')), $beforekey);

    if (mod_studentquiz_check_created_permission($PAGE->cm->id)) {
        $context = context_module::instance($PAGE->cm->id);
        $category = question_get_default_category($context->id);
        $cat = $category->id . ',' . $context->id;

        $studentquiznode->add_node(navigation_node::create(get_string('nav_export', 'studentquiz'),
            new moodle_url('/mod/studentquiz/export.php', array('cmid' => $PAGE->cm->id, 'cat' => $cat)),
            navigation_node::TYPE_SETTING, null, 'mod_studentquiz_export',
            new pix_icon('i/export', '')), $beforekey);
        $studentquiznode->add_node(navigation_node::create(get_string('nav_import', 'studentquiz'),
            new moodle_url('/mod/studentquiz/import.php', array('cmid' => $PAGE->cm->id, 'cat' => $cat)),
            navigation_node::TYPE_SETTING, null, 'mod_studentquiz_import',
            new pix_icon('i/import', '')), $beforekey);

        question_extend_settings_navigation($studentquiznode, $PAGE->cm->context)->trim_if_empty();
    }

}

/**
 * Check permission if is no student
 *
 * @return boolean the current user is not a student
 */
function studentquiz_check_created_permission($commentid) {
    global $USER, $DB;

    // Check if user is admin.
    $admins = get_admins();
    foreach ($admins as $admin) {
        if ($USER->id == $admin->id) {
            return true;
        }
    }

    // Check if user is comment creator.
    if ($DB->get_field('studentquiz_comment', 'userid', array('id' => $commentid)) == $USER->id) {
        return true;
    }

    return false;
}

/**
 * Generate some HTML to render comments
 *
 * @param  int $questionid Question id
 * @return string HTML fragment
 */
function studentquiz_comment_renderer($questionid) {
    global $DB;
    $modname = 'mod_studentquiz';

    $comments = $DB->get_records(
        'studentquiz_comment', array('questionid' => $questionid),
        'id DESC'
    );

    if (empty($comments)) {
        return html_writer::div(get_string('no_comments', $modname));
    }

    $html = '';
    $index = 0;
    foreach ($comments as $comment) {
        $hide = '';
        if ($index > 1) {
            $hide = 'hidden';
        }
        $date = date('d.m.Y H:i', $comment->created);
        $user = $DB->get_record('user', array('id' => $comment->userid));
        $username = ($user !== false ? $user->username : '');
        $html .= html_writer::div(
            (studentquiz_check_created_permission($comment->id) ? html_writer::span('remove', 'remove_action',
                array(
                    'data-id' => $comment->id,
                    'data-question_id' => $comment->questionid
                )) : '')
            . html_writer::tag('p', $date . ' | ' . $username)
            . html_writer::tag('p', $comment->comment),
            $hide
        );

        ++$index;
    }

    if (count($comments) > 2) {
        $html .= html_writer::div(
            html_writer::tag('button', get_string('show_more', $modname), array('type' => 'button', 'class' => 'show_more'))
            . html_writer::tag('button', get_string('show_less', $modname)
                , array('type' => 'button', 'class' => 'show_less hidden')), 'button_controls'
        );
    }

    return $html;
}
