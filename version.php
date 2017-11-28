<?php
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
$plugin->version      = 2017112603;
$plugin->release      = 'v2.2.1';
$plugin->requires     = 2016052300; // Version MOODLE_31, 3.1.0.
$plugin->maturity     = MATURITY_STABLE;
$plugin->cron         = 0;
