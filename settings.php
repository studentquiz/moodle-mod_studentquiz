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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Default display settings.
    $settings->add(new admin_setting_heading(
        'studentquiz/ratingsettings', 
        get_string('rankingsettingsheader', 'studentquiz'), '')
    );

    $settings->add(new admin_setting_configtext(
        'studentquiz_add_question_quantifier', 
        get_string('settings_add_q_quantifier', 'studentquiz'),
        get_string('config_add_q_quantifier', 'studentquiz'), 
        100, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz_vote_quantifier', 
        get_string('settings_vote_quantifier', 'studentquiz'),
        get_string('config_vote_quantifier', 'studentquiz'), 
        50, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz_correct_answered_question_quantifier', 
        get_string('settings_correct_answered_q_quantifier', 'studentquiz'),
        get_string('config_correct_answered_q_quantifier', 'studentquiz'), 
        75, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'studentquiz_incorrect_answered_question_quantifier', 
        get_string('settings_incorrect_answered_q_quantifier', 'studentquiz'),
        get_string('config_incorrect_answered_q_quantifier', 'studentquiz'), 
        10, PARAM_INT
    ));
}