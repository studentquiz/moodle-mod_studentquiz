<?php
/**
 * This file keeps track of upgrades to the StudentQuiz module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__) . '/locallib.php');

/**
 * Execute StudentQuiz upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_studentquiz_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    // The first view upgrade processes were not precicely documented.
    if ($oldversion < 2007040100) {

        // Define field course to be added to studentquiz.
        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');

        // Add field course.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field intro to be added to studentquiz.
        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'name');

        // Add field intro.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field introformat to be added to studentquiz.
        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'intro');

        // Add field introformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2007040100, 'studentquiz');
    }

    if ($oldversion < 2007040101) {

        // Define field timecreated to be added to studentquiz.
        $table = new xmldb_table('studentquiz_question');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'introformat');

        // Add field timecreated.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timemodified to be added to studentquiz.
        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'timecreated');

        // Add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index course (not unique) to be added to studentquiz.
        $table = new xmldb_table('studentquiz');
        $index = new xmldb_index('courseindex', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Add index to course field.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, 2007040101, 'studentquiz');
    }

    // Third example, the next day, 2007/04/02 (with the trailing 00),
    // some actions were performed to install.php related with the module.
    if ($oldversion < 2007040200) {

        // Insert code here to perform some actions (same as in install.php).

        upgrade_mod_savepoint(true, 2007040200, 'studentquiz');
    }

    // For version ???.
    if ($oldversion < 2017021601) {

        // Define table studentquiz_question to be created.
        $table = new xmldb_table('studentquiz_question');

        // Adding fields to table studentquiz_question.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('approved', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table studentquiz_question.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));

        // Conditionally launch create table for studentquiz_question.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2017021601, 'studentquiz');
    }

    // Introduce field `hiddensection` to studentquiz table not needed any more.
    // For future reference.
    if ($oldversion < 2017110600) {

        // Define field hiddensection.
        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('hiddensection', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'name');

        // Add field hiddensection.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add key.
        $table->add_key('hiddensectionid', XMLDB_KEY_FOREIGN, array('hiddensection'), 'course_sections', array('id'));

        upgrade_mod_savepoint(true, 2017110600, 'studentquiz');
    }

    // Introduce table studentquiz_progress
    if ($oldversion < 2017110701) {

        // Setup a new table.
        $table = new xmldb_table('studentquiz_progress');

        // Adding fields to table studentquiz_question.
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('studentquizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastanswercorrect', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('attempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('correctattempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        // Add key.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('questionid', 'userid'));
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('studentquizid', XMLDB_KEY_FOREIGN, array('studentquizid'), 'studentquiz', array('id'));

        // Conditionally launch create table for studentquiz_progress.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2017110701, 'studentquiz');
    }

    // Introduce table studentquiz_attempt
    if ($oldversion < 2017111001) {

        // Setup a new table.
        $table = new xmldb_table('studentquiz_attempt');

        // Adding fields to table studentquiz_attempt
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('studentquizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('questionusageid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Add key.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('studentquizid', XMLDB_KEY_FOREIGN, array('studentquizid'), 'studentquiz', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('questionusageid', XMLDB_KEY_FOREIGN, array('questionusageid'), 'question_usages', array('id'));
        $table->add_key('categoryid', XMLDB_KEY_FOREIGN, array('categoryid'), 'question_categories', array('id'));

        // Conditionally launch create table for studentquiz_attempt.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2017111001, 'studentquiz');
    }

    // Remove hidden section from studentquiz.
    if ($oldversion < 2017111300) {
        // Define field hiddensection.
        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('hiddensection', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'name');
        $key = new xmldb_key('hiddensectionid', XMLDB_KEY_FOREIGN, array('hiddensection'), 'course_sections', array('id'));

        // Remove field and key hiddensection if exists.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_key($table, $key);
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017111300, 'studentquiz');
    }

    // Add Ranking quantifiers on activity level.
    if ($oldversion < 2017111800) {
        $table = new xmldb_table('studentquiz');

        // Add questionquantifier.
        $field = new xmldb_field('questionquantifier', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'quizpracticebehaviour');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add questionquantifier.
        $field = new xmldb_field('approvedquantifier', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'questionquantifier');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add ratequantifier.
        $field = new xmldb_field('votequantifier', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'approvedquantifier');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add correctanswerquantifier.
        $field = new xmldb_field('correctanswerquantifier', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'votequantifier');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add incorrectanswerquantifier.
        $field = new xmldb_field('incorrectanswerquantifier', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'correctanswerquantifier');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017111800, 'studentquiz');
    }

    // Cleanup deprecated questionbehavior studentquiz.
    if ($oldversion < 2017111903) {
        if (array_key_exists('studentquiz', core_component::get_plugin_list('qbehaviour'))) {
            $DB->set_field('question_attempts', 'behaviour', 'immediatefeedback', array(
                'behaviour' => 'studentquiz'
            ));
            uninstall_plugin('qbehaviour', 'studentquiz');
        }
        upgrade_mod_savepoint(true, 2017111903, 'studentquiz');
    }

    // Add allowed qtypes field for the activity.
    if ($oldversion < 2017111904) {
        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('allowedqtypes', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'incorrectanswerquantifier');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017111904, 'studentquiz');
    }

    // Migrate old quiz activity data into new data structure.
    if ($oldversion < 2017112406) {
        // this is also used in import, so it had to be extracted.
        mod_studentquiz_migrate_old_quiz_usage();

        upgrade_mod_savepoint(true, 2017112406, 'studentquiz');
    }

    // Update capabilities list and permission types, to make sure the defaults are set after this upgrade.
    if ($oldversion < 2017112602) {
        // Load current access definition for easier iteration.
        require_once(dirname(__DIR__) . '/db/access.php');
        // Load all contexts this has to be defined.
        // Only system context needed, as by default it's inherited from there and if someone did make an override, it's intentional.
        $context = context_system::instance();
        // Load the roles for easier name to id assignment
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        // And finally update them for every context.
        foreach ($capabilities as $capname => $capability) {
            if (!empty($capability['archetypes'])) {
                foreach ($capability['archetypes'] as $role => $captype) {
                    role_change_permission($roleids[$role], $context, $capname, $captype);
                }
            }
        }

        upgrade_mod_savepoint(true, 2017112602, 'studentquiz');
    }

    // Rename vote to rate in all occurences.
    if ($oldversion < 2017120201) {
        $table = new xmldb_table('studentquiz_vote');
        $tablenew = new xmldb_table('studentquiz_rate');
        $field = new xmldb_field('vote', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if($dbman->table_exists($table) && $dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'rate');
        }

        if ($dbman->table_exists($table)) {
            if(!$dbman->table_exists($tablenew)) {
                $dbman->rename_table($table, 'studentquiz_rate');
            }
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('votequantifier', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'ratequantifier');
        }

        upgrade_mod_savepoint(true, 2017120201, 'studentquiz');
    }

    // Change all quantifier fields to int.
    // Hint for history: these fields haven't been rolled out yet in type float
    if ($oldversion < 2017120202) {
        $table = new xmldb_table('studentquiz');

        $fieldnames = array('questionquantifier', 'approvedquantifier', 'ratequantifier', 'correctanswerquantifier', 'incorrectanswerquantifier');
        foreach($fieldnames as $fieldname) {
            $field = new xmldb_field($fieldname, XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if($dbman->field_exists($table, $field)) {
                $dbman->change_field_type($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2017120202, 'studentquiz');
    }

    return true;
}
