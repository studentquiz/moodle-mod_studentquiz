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

use mod_studentquiz\utils;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');

/**
 * Execute StudentQuiz upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 * @throws ddl_change_structure_exception
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 * @throws dml_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
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

    // Introduce table studentquiz_progress.
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

    // Introduce table studentquiz_attempt.
    if ($oldversion < 2017111001) {

        // Setup a new table.
        $table = new xmldb_table('studentquiz_attempt');

        // Adding fields to table studentquiz_attempt.
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

    // Add Ranking quantifiers on activity level. Before migration set useful default values.
    if ($oldversion < 2017111800) {
        $table = new xmldb_table('studentquiz');

        $definitions = array(
            array(
                'name' => 'questionquantifier',
                'previous' => 'quizpracticebehaviour',
                'default' => '10',
            ), array(
                'name' => 'approvedquantifier',
                'previous' => 'questionquantifier',
                'default' => '5',
            ), array(
                'name' => 'votequantifier',
                'previous' => 'approvedquantifier',
                'default' => '3',
            ), array(
                'name' => 'correctanswerquantifier',
                'previous' => 'votequantifier',
                'default' => '2',
            ), array(
                'name' => 'incorrectanswerquantifier',
                'previous' => 'correctanswerquantifier',
                'default' => '-1',
            ),
        );

        // Add column and set useful default values during creation.
        foreach ($definitions as $definition) {
            $field = new xmldb_field($definition['name'], XMLDB_TYPE_INTEGER, '10', null,
                XMLDB_NOTNULL, null, '0', $definition['previous']);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Set the correct default values for the StudentQuiz instances.
        foreach ($definitions as $definition) {
            $DB->set_field('studentquiz', $definition['name'], $definition['default']);
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
        $field = new xmldb_field('allowedqtypes', XMLDB_TYPE_TEXT, 'medium', null,
            null, null, null, 'incorrectanswerquantifier');  // Text fields cannot have default.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017111904, 'studentquiz');
    }

    // Migrate old quiz activity data into new data structure.
    if ($oldversion < 2017112406) {
        // Removed as this migration step is now out of support range, just here for historical purposes because this
        // is the upgrade file with savepoints: mod_studentquiz_migrate_old_quiz_usage().
        upgrade_mod_savepoint(true, 2017112406, 'studentquiz');
    }

    // Update capabilities list and permission types, to make sure the defaults are set after this upgrade.
    if ($oldversion < 2017112602) {
        // Load current access definition for easier iteration.
        require_once(__DIR__ . '/../db/access.php');
        // Load all contexts this has to be defined.
        // Only system context needed, as by default it's inherited from there.
        // if someone did make an override, it's intentional.
        $context = context_system::instance();
        // And finally update them for every context.
        foreach ($capabilities as $capname => $capability) {
            if (!empty($capability['archetypes'])) {
                foreach ($capability['archetypes'] as $archetype => $captype) {
                    foreach (get_archetype_roles($archetype) as $role) {
                        role_change_permission($role->id, $context, $capname, $captype);
                    }
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

        if ($dbman->table_exists($table) && $dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'rate');
        }

        if ($dbman->table_exists($table)) {
            if (!$dbman->table_exists($tablenew) && $dbman->table_exists($table)) {
                $dbman->rename_table($table, 'studentquiz_rate');
            }
            if ($dbman->table_exists($table) && $dbman->table_exists($tablenew)) {
                $dbman->drop_table($table);
            }
        }

        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('votequantifier', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'ratequantifier');
        }

        upgrade_mod_savepoint(true, 2017120201, 'studentquiz');
    }

    // Change all quantifier fields to int.
    // Hint for history: these fields haven't been rolled out yet in type float.
    if ($oldversion < 2017120202) {
        $table = new xmldb_table('studentquiz');

        $fieldnames = array('questionquantifier', 'approvedquantifier', 'ratequantifier',
            'correctanswerquantifier', 'incorrectanswerquantifier');
        foreach ($fieldnames as $fieldname) {
            $field = new xmldb_field($fieldname, XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            if ($dbman->field_exists($table, $field)) {
                $dbman->change_field_type($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2017120202, 'studentquiz');
    }

    // Fix wrong parent in question categories if applicable.
    if ($oldversion < 2018051300) {
        // Removed afterwards because of #173, just here for historical purposes because this is
        // the upgrade file with savepoints: mod_studentquiz_fix_wrong_parent_in_question_categories().
        upgrade_mod_savepoint(true, 2018051300, 'studentquiz');
    }

    if ($oldversion < 2018121101) {
        // Repair table studentquiz_progress.
        $table = new xmldb_table('studentquiz_progress');

        // Adding fields to table studentquiz_progress.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('studentquizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('lastanswercorrect', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('attempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
        $table->add_field('correctattempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        // Add key.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('questionid', XMLDB_KEY_FOREIGN, array('questionid'), 'question', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('studentquizid', XMLDB_KEY_FOREIGN, array('studentquizid'), 'studentquiz', array('id'));

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Conditionally launch create table for studentquiz_progress.
        $dbman->create_table($table);

        $table = new xmldb_table('studentquiz_attempt');
        $field = new xmldb_field('ids', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);

        // Add field intro.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018121101, 'studentquiz');
    }

    if ($oldversion < 2018121102) {
        // Repair table studentquiz_progress.

        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('aggregated', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Add field intro.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018121102, 'studentquiz');
    }
    if ($oldversion < 2018121800) {
        $table = new xmldb_table('studentquiz_progress');

        $dbman->add_key($table, new xmldb_key('questioniduseridstudentquizid', XMLDB_KEY_UNIQUE, array(
            'questionid', 'userid', 'studentquizid'
        )));

        upgrade_mod_savepoint(true, 2018121800, 'studentquiz');
    }

    if ($oldversion < 2018122500) {
        $table = new xmldb_table('studentquiz');
        $fieldnames = ['opensubmissionfrom', 'closesubmissionfrom', 'openansweringfrom', 'closeansweringfrom'];

        foreach ($fieldnames as $fieldname) {
            $field = new xmldb_field($fieldname, XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, '0');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2018122500, 'studentquiz');
    }

    // Properties excluderoles, forcecommenting, forcerating are introduced. Add fields and set their default values.
    if ($oldversion < 2019032002) {
        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('excluderoles', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'aggregated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('forcerating', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'excluderoles');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('forcecommenting', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'forcerating');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2019032002, 'studentquiz');
    }

    // XMLDB "Check defaults" issues.
    if ($oldversion < 2019051700) {

        $table = new xmldb_table('studentquiz_progress');

        // Changing the default of field lastanswercorrect on table studentquiz_progress to drop it.
        $field = new xmldb_field('lastanswercorrect', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null, 'studentquizid');

        // Launch change of default for field lastanswercorrect.
        $dbman->change_field_default($table, $field);

        // Changing the default of field attempts on table studentquiz_progress to drop it.
        $field = new xmldb_field('attempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'lastanswercorrect');

        // Launch change of default for field attempts.
        $dbman->change_field_default($table, $field);

        // Changing the default of field correctattempts on table studentquiz_progress to drop it.
        $field = new xmldb_field('correctattempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'attempts');

        // Launch change of default for field correctattempts.
        $dbman->change_field_default($table, $field);

        // Studentquiz savepoint reached.
        upgrade_mod_savepoint(true, 2019051700, 'studentquiz');
    }

    if ($oldversion < 2019060401) {
        // Rename field approved on table studentquiz_question to state.
        $table = new xmldb_table('studentquiz_question');
        $field = new xmldb_field('approved', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'questionid');
        if ($dbman->field_exists($table, $field)) {
            // Launch rename field state.
            $dbman->rename_field($table, $field, 'state');
        }
        // Create new hidden fields.
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Create new column publishnewquestion on studentquiz table.
        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('publishnewquestion', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Studentquiz savepoint reached.
        upgrade_mod_savepoint(true, 2019060401, 'studentquiz');
    }

    if ($oldversion < 2019071700) {
        // Migrate from question usage attempt step data to internal progress table.
        mod_studentquiz_migrate_all_studentquiz_instances_to_aggregated_state();

        upgrade_mod_savepoint(true, 2019071700, 'studentquiz');
    }

    if ($oldversion < 2020011601) {

        $table = new xmldb_table('studentquiz_comment');
        $field = new xmldb_field('parentid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'created');
        $index = new xmldb_index('parentidindex', XMLDB_INDEX_NOTUNIQUE, ['parentid']);
        if (!$dbman->field_exists($table, $field)) {
            // Add parentid field.
            $dbman->add_field($table, $field);
            // Add index to parentid field.
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'parentid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('deleteuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'deleted');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('commentdeletionperiod', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '10',
            'publishnewquestion');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2020011601, 'studentquiz');
    }

    if ($oldversion < 2020011602) {

        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('reportingemail', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'commentdeletionperiod');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2020011602, 'studentquiz');
    }

    // Remove unused practice database tables and old quiz practice columns.
    if ($oldversion < 2020043000) {

        $table = new xmldb_table('studentquiz_practice');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('studentquiz');
        $field = new xmldb_field('quizpracticebehaviour');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2020043000, 'studentquiz');
    }

    if ($oldversion < 2020050400) {

        $table = new xmldb_table('studentquiz');
        // Define field digesttype to be added to studentquiz.
        $field = new xmldb_field('digesttype', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'reportingemail');

        // Conditionally launch add field digesttype.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field digestfirstday to be added to studentquiz.
        $field = new xmldb_field('digestfirstday', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'digesttype');

        // Conditionally launch add field digestfirstday.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Studentquiz savepoint reached.
        upgrade_mod_savepoint(true, 2020050400, 'studentquiz');
    }

    if ($oldversion < 2020050404) {

        // Define table studentquiz_notification to be created.
        $table = new xmldb_table('studentquiz_notification');

        // Adding fields to table studentquiz_notification.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('studentquizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'studentquizid');
        $table->add_field('recipientid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'content');
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'recipientid');
        $table->add_field('timetosend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'status');

        // Adding keys to table studentquiz_notification.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('studentquizid', XMLDB_KEY_FOREIGN, ['studentquizid'], 'studentquiz', ['id']);
        $table->add_key('recipientid', XMLDB_KEY_FOREIGN, ['recipientid'], 'user', ['id']);

        // Conditionally launch create table for studentquiz_notification.
        if (!$dbman->table_exists('studentquiz_notification')) {
            $dbman->create_table($table);
        }

        // Studentquiz savepoint reached.
        upgrade_mod_savepoint(true, 2020050404, 'studentquiz');
    }

    // Hotfix reapply this upgrade step for upgrading to v4.3.1 (v4.3.0 broken because of this). See #233.
    if ($oldversion < 2020051199) { // Was 2020021300.

        $table = new xmldb_table('studentquiz_comment');
        $field = new xmldb_field('edited', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'deleteuserid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('edituserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'edited');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2020051199, 'studentquiz');
    }

    if ($oldversion < 2020051200) {

        // Define table studentquiz_comment_history to be created.
        $table = new xmldb_table('studentquiz_comment_history');

        // Adding fields to table studentquiz_comment_history.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('commentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table studentquiz_comment_history.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('commentid', XMLDB_KEY_FOREIGN, ['commentid'], 'studentquiz_comment', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for studentquiz_comment_history.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Update table studentquiz_comment.
        $table = new xmldb_table('studentquiz_comment');

        // Define field status to be added to studentquiz_comment.
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'edituserid');

        // Conditionally launch add field status.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timemodified to be added to studentquiz_comment.
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'status');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field usermodified to be added to studentquiz_comment.
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timemodified');

        // Conditionally launch add field usermodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define key usermodified (foreign) to be added to studentquiz_comment.
        $key = new xmldb_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Launch add key usermodified.
        $dbman->add_key($table, $key);

        $comments = $DB->get_records('studentquiz_comment');
        foreach ($comments as $comment) {
            $commenthistory = new stdClass();
            $commenthistory->commentid = $comment->id;
            $commenthistory->content = $comment->comment;
            $commenthistory->userid = $comment->userid;
            $commenthistory->action = utils::COMMENT_HISTORY_CREATE;
            $commenthistory->timemodified = $comment->created;
            $DB->insert_record('studentquiz_comment_history', $commenthistory);

            $comment->status = utils::COMMENT_HISTORY_CREATE;
            $comment->usermodified = $comment->userid;
            $comment->timemodified = $comment->created;

            if ($comment->edited > 0) {
                $commenthistory = new stdClass();
                $commenthistory->commentid = $comment->id;
                $commenthistory->content = $comment->comment;
                $commenthistory->userid = $comment->edituserid;
                $commenthistory->action = utils::COMMENT_HISTORY_EDIT;
                $commenthistory->timemodified = $comment->edited;
                $DB->insert_record('studentquiz_comment_history', $commenthistory);

                $comment->status = utils::COMMENT_HISTORY_EDIT;
                $comment->usermodified = $comment->edituserid;
                $comment->timemodified = $comment->edited;
            }
            if ($comment->deleted > 0) {
                $commenthistory = new stdClass();
                $commenthistory->commentid = $comment->id;
                $commenthistory->content = '';
                $commenthistory->userid = $comment->deleteuserid;
                $commenthistory->action = utils::COMMENT_HISTORY_DELETE;
                $commenthistory->timemodified = $comment->deleted;
                $DB->insert_record('studentquiz_comment_history', $commenthistory);

                $comment->status = utils::COMMENT_HISTORY_DELETE;
                $comment->usermodified = $comment->deleteuserid;
                $comment->timemodified = $comment->deleted;
            }
            $DB->update_record('studentquiz_comment', $comment);
        }

        // Remove unused fields.
        // Define field deleted to be dropped from studentquiz_comment.
        $table = new xmldb_table('studentquiz_comment');
        $field = new xmldb_field('deleted');

        // Conditionally launch drop field deleted.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field deleteuserid to be dropped from studentquiz_comment.
        $field = new xmldb_field('deleteuserid');

        // Conditionally launch drop field deleteuserid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field edited to be dropped from studentquiz_comment.
        $field = new xmldb_field('edited');

        // Conditionally launch drop field edited.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field edituserid to be dropped from studentquiz_comment.
        $field = new xmldb_field('edituserid');

        // Conditionally launch drop field edituserid.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2020051200, 'studentquiz');
    }

    return true;
}
