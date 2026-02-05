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
 * Upgrade logic for the quizaccess_oneconnection plugin.
 *
 * @package     quizaccess_oneconnection
 * @category    upgrade
 * @copyright   2025 lern.link GmbH <team@lernlink.de>, Adrian Sarmas, Vadym Nersesov
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade routine for quizaccess_oneconnection.
 *
 * @param int $oldversion The version of the plugin that is currently installed.
 * @return bool True on success.
 */
function xmldb_quizaccess_oneconnection_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    // Version 2024010802 release notes:
    // - Change sessionhash field to a larger size to accommodate stronger hashing algorithms.
    if ($oldversion < 2024010802) {
        // Since the hash format is changing, old hashes are invalid and must be cleared.
        $DB->delete_records('quizaccess_oneconnection_sess');

        $table = new xmldb_table('quizaccess_oneconnection_sess');
        $field = new xmldb_field('sessionhash', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'attemptid');
        $dbman->change_field_precision($table, $field);

        upgrade_plugin_savepoint(true, 2024010802, 'quizaccess', 'oneconnection');
    }

    // Version 2025092600 release notes:
    // - Add a log table to audit manual unlocks.
    // - Rename capability from 'unlockattempt' to 'allowchange' for clarity.
    if ($oldversion < 2025092600) {
        $table = new xmldb_table('quizaccess_oneconnection_log');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('unlockedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeunlocked', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('attemptid', XMLDB_KEY_FOREIGN, ['attemptid'], 'quiz_attempts', ['id']);
        $table->add_key('unlockedby', XMLDB_KEY_FOREIGN, ['unlockedby'], 'user', ['id']);
        $table->add_key('quizid', XMLDB_KEY_FOREIGN, ['quizid'], 'quiz', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Rename the old capability for a smoother upgrade.
        if ($DB->record_exists('capabilities', ['name' => 'quizaccess/oneconnection:unlockattempt'])) {
            $DB->set_field('capabilities', 'name', 'quizaccess/oneconnection:allowchange', ['name' => 'quizaccess/oneconnection:unlockattempt']);
        }

        upgrade_plugin_savepoint(true, 2025092600, 'quizaccess', 'oneconnection');
    }

    // Version 2025092607 release notes:
    // - Add quizid field to the session table for easier cleanup.
    if ($oldversion < 2025092607) {
        $table = new xmldb_table('quizaccess_oneconnection_sess');
        $field = new xmldb_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'id');

        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025092607, 'quizaccess', 'oneconnection');
    }

    // Version 2025092608 release notes:
    // - Safety net to ensure all plugin tables exist, in case of a failed previous upgrade.
    if ($oldversion < 2025092608) {
        // Table 1: quizaccess_oneconnection (per-quiz settings).
        $table = new xmldb_table('quizaccess_oneconnection');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('quizid', XMLDB_KEY_FOREIGN_UNIQUE, ['quizid'], 'quiz', ['id']);

            $dbman->create_table($table);
        }

        // Table 2: quizaccess_oneconnection_sess (session locks).
        $table = new xmldb_table('quizaccess_oneconnection_sess');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sessionhash', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('quizid', XMLDB_KEY_FOREIGN, ['quizid'], 'quiz', ['id']);
            $table->add_key('attemptid', XMLDB_KEY_FOREIGN_UNIQUE, ['attemptid'], 'quiz_attempts', ['id']);

            $dbman->create_table($table);
        }

        // Table 3: quizaccess_oneconnection_log (audit log).
        $table = new xmldb_table('quizaccess_oneconnection_log');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('attemptid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('unlockedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timeunlocked', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('quizid', XMLDB_KEY_FOREIGN, ['quizid'], 'quiz', ['id']);
            $table->add_key('attemptid', XMLDB_KEY_FOREIGN, ['attemptid'], 'quiz_attempts', ['id']);
            $table->add_key('unlockedby', XMLDB_KEY_FOREIGN, ['unlockedby'], 'user', ['id']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025092608, 'quizaccess', 'oneconnection');
    }

    // Version 2025092609 release notes:
    // - Add an index to the log table to improve performance of the 'Allow connections' report.
    if ($oldversion < 2025092609) {
        $table = new xmldb_table('quizaccess_oneconnection_log');
        $index = new xmldb_index('attempt_time_idx', XMLDB_INDEX_NOTUNIQUE, ['attemptid', 'timeunlocked']);
        if ($dbman->table_exists($table) && !$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2025092609, 'quizaccess', 'oneconnection');
    }

    return true;
}
