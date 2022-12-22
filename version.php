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
 * Defines the version and other meta-info about the plugin
 *
 * Setting the $plugin->version to 0 prevents the plugin from being installed.
 * See https://docs.moodle.org/dev/version.php for more info.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch) <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component    = 'mod_studentquiz';
$plugin->version      = 2022052401;
$plugin->incompatible = 400; // A Moodle 4.0 version will be released when it is ready, hopefully soon.
$plugin->release      = 'v4.7.1';
$plugin->requires     = 2020061500; // Version MOODLE_3.9, 3.9.0+.
$plugin->maturity     = MATURITY_STABLE;
$plugin->cron         = 0;
