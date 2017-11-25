<?php
/**
 * Provides code to be executed during the module installation
 *
 * This file replaces the legacy STATEMENTS section in db/install.xml,
 * lib.php/modulename_install() post installation hook and partially defaults.php.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch) <your@email.adress>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_studentquiz_install() {
}

/**
 * Post installation recovery procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_studentquiz_install_recovery() {
}
