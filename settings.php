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
 * The mod_studentquiz settings.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_studentquiz\utils;

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/locallib.php');

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'studentquiz/sectionranking',
        get_string('settings_section_header_ranking', 'studentquiz'),
        get_string('settings_section_description_default', 'studentquiz')
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz/addquestion',
        get_string('settings_questionquantifier', 'studentquiz'),
        get_string('settings_questionquantifier_help', 'studentquiz'),
        10, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz/approved',
        get_string('settings_approvedquantifier', 'studentquiz'),
        get_string('settings_approvedquantifier_help', 'studentquiz'),
        5, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz/rate',
        get_string('settings_ratequantifier', 'studentquiz'),
        get_string('settings_ratequantifier_help', 'studentquiz'),
        3, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz/correctanswered',
        get_string('settings_lastcorrectanswerquantifier', 'studentquiz'),
        get_string('settings_lastcorrectanswerquantifier_help', 'studentquiz'),
        2, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz/incorrectanswered',
        get_string('settings_lastincorrectanswerquantifier', 'studentquiz'),
        get_string('settings_lastincorrectanswerquantifier_help', 'studentquiz'),
        -1, PARAM_INT
    ));

    // Get all roles available on system.
    $roles = mod_studentquiz_get_roles();
    // Replace all value to 0 for default value.
    $defaultroles = array_map(function($val) {
        return 0;
    }, $roles);

    $settings->add(new admin_setting_configmulticheckbox('studentquiz/excluderoles',
        get_string('settings_excluderoles', 'studentquiz'),
        get_string('settings_excluderoles_help', 'studentquiz'),
        $defaultroles,
        $roles
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

    $settings->add(new admin_setting_heading(
        'studentquiz/sectionquestion',
        get_string('settings_section_header_question', 'studentquiz'),
        get_string('settings_section_description_default', 'studentquiz')
    ));

    // Get all question types available on system.
    $qtypes = mod_studentquiz_get_question_types();
    // Replace all value to 1 for default value.
    $defaultqtypes = array_map(function($val) {
        return 1;
    }, $qtypes);

    $settings->add(new admin_setting_configmulticheckbox('studentquiz/defaultqtypes',
        get_string('settings_allowedqtypes', 'studentquiz'),
        get_string('settings_allowedqtypes_help', 'studentquiz'),
        $defaultqtypes,
        $qtypes
    ));

    $settings->add(new admin_setting_configcheckbox('studentquiz/forcerating',
        get_string('settings_forcerating', 'studentquiz'),
        get_string('settings_forcerating_help', 'studentquiz'),
        '1'
    ));

    $settings->add(new admin_setting_configcheckbox('studentquiz/forcecommenting',
        get_string('settings_forcecommenting', 'studentquiz'),
        get_string('settings_forcecommenting_help', 'studentquiz'),
        '0'
    ));

    $settings->add(new admin_setting_configselect('studentquiz/commentediting_deletionperiod',
            get_string('settings_commentdeletionperiod', 'studentquiz'),
            get_string('settings_commentdeletionperiod_help', 'studentquiz'),
            \mod_studentquiz\commentarea\container::DELETION_PERIOD_DEFAULT,
            \mod_studentquiz\commentarea\container::get_deletion_period_options()
    ));

    $settings->add(new admin_setting_configtextarea('studentquiz/comment_editor_toolbar',
            get_string('settings_comment_editor_toolbar', 'studentquiz'),
            get_string('settings_comment_editor_toolbar_des', 'studentquiz'),
            utils::ATTO_TOOLBAR
    ));

}
