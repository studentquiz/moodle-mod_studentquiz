<?php
/**
 * Capability definitions for the StudentQuiz module
 *
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 *
 * It is important that capability names are unique. The naming convention
 * for capabilities that are specific to modules and blocks is as follows:
 *   [mod/block]/<plugin_name>:<capabilityname>
 *
 * component_name should be the same as the directory name of the mod or block.
 *
 * Core moodle capabilities are defined thus:
 *    moodle/<capabilityclass>:<capabilityname>
 *
 * Examples: mod/forum:viewpost
 *           block/recent_activity:view
 *           moodle/site:deleteuser
 *
 * The variable name for the capability definitions array is $capabilities
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'mod/studentquiz:addinstance' => array(
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'clonepermissionsfrom' => 'mod/studentquiz:manage'
    ),
    'mod/studentquiz:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'clonepermissionsfrom' => 'mod/studentquiz:submit'
    ),
    'mod/studentquiz:submit' => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),
    'mod/studentquiz:manage' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),
    'mod/studentquiz:unhideanonymous' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'clonepermissionsfrom' => 'moodle/course:manage'
    ),
    // Notifications
    'mod/studentquiz:emailnotifychanged' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'clonepermissionsfrom' => 'moodle/course:manage'
    ),
    'mod/studentquiz:emailnotifydeleted' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'clonepermissionsfrom' => 'moodle/course:manage'
    ),
    'mod/studentquiz:emailnotifyapproved' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'clonepermissionsfrom' => 'moodle/course:manage'
    ),
    'mod/studentquiz:emailnotifycommentadded' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'clonepermissionsfrom' => 'mod/studentquiz:submit'
    ),
    'mod/studentquiz:emailnotifycommentdeleted' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'clonepermissionsfrom' => 'mod/studentquiz:submit'
    ),
);
