<?php
/**
 * The mod_studentquiz settings.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'studentquiz/ratingsettings',
        get_string('rankingsettingsheader', 'studentquiz'),
        get_string('rankingsettingsdescription', 'studentquiz')
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz/addquestion',
        get_string('settings_questionquantifier_label', 'studentquiz'),
        get_string('settings_questionquantifier_help', 'studentquiz'),
        10, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz/approved',
        get_string('settings_approvedquantifier_label', 'studentquiz'),
        get_string('settings_approvedquantifier_help', 'studentquiz'),
        5, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz/vote',
        get_string('settings_votequantifier_label', 'studentquiz'),
        get_string('settings_votequantifier_help', 'studentquiz'),
        3, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz/correctanswered',
        get_string('settings_correctanswerquantifier_label', 'studentquiz'),
        get_string('settings_correctanswerquantifier_help', 'studentquiz'),
        2, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz/incorrectanswered',
        get_string('settings_incorrectanswerquantifier_label', 'studentquiz'),
        get_string('settings_incorrectanswerquantifier_help', 'studentquiz'),
        -1, PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'studentquiz/importsettings',
        get_string('importsettingsheader', 'studentquiz'),
        get_string('importsettingsdescription', 'studentquiz')
    ));

    // Option to refuse the import functions to automatically remove empty sections. This option is required for
    // the removal of section 999. But since this plugin is actively trying to remove stuff it's primarly not
    // responsible for, thus can lead to side-effects, we need to give the admin the option to opt out from it.
    $settings->add(new admin_setting_configcheckbox('studentquiz/removeemptysections',
        get_string('settings_removeemptysections_label', 'studentquiz'),
        get_string('settings_removeemptysections_help', 'studentquiz'),
        '1'
    ));

    // Show a onetime settings option as info, that we'll uninstall the questionbehavior plugin automatically.
    // Will not show this option if this plugin doesn't exist.
    if (array_key_exists('studentquiz', core_component::get_plugin_list('qbehaviour'))) {
        $url = new moodle_url('/admin/plugins.php', array('sesskey' => sesskey(), 'uninstall' => 'qbehaviour_studentquiz'));
        $settings->add(new admin_setting_configcheckbox('studentquiz/removeqbehavior',
            get_string('settings_removeqbehavior_label', 'studentquiz'),
            get_string('settings_removeqbehavior_help', 'studentquiz', $url->out()),
            '1'
        ));
    }

}
