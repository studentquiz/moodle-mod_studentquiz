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
    $yesno = array(0 => get_string('no'),
                   1 => get_string('yes'));

    $settings->add(new admin_setting_configselect('studentquiz/removeattempts',
        get_string('settings_remove_attempts', 'studentquiz'),
        get_string('settings_remove_attempts_description', 'studentquiz'), 0, $yesno));

    if (!empty($_POST) && isset($_POST['s_studentquiz_removeattempts'])) {
    	$removeattempts = intval($_POST['s_studentquiz_removeattempts']);

    	if ($removeattempts) {
		    $DB->delete_records('question_attempts', array('behaviour' => 'studentquiz'));
		    $_POST['s_studentquiz_removeattempts'] = '0';
    	}
	}
}

