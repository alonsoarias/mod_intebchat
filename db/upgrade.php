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
 * Plugin upgrade steps are defined here.
 *
 * @package    mod_intebchat
 * @category   upgrade
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute mod_intebchat upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_intebchat_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025021800) {
        // Add apitype field to intebchat table if it doesn't exist
        $table = new xmldb_table('intebchat');
        $field = new xmldb_field('apitype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'chat', 'showlabels');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add token tracking fields to log table
        $table = new xmldb_table('mod_intebchat_log');
        
        $field = new xmldb_field('prompttokens', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'airesponse');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('completiontokens', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'prompttokens');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('totaltokens', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'completiontokens');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Create token usage table
        $table = new xmldb_table('mod_intebchat_token_usage');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('tokensused', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('periodstart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('periodtype', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'day');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

            $table->add_index('user-period', XMLDB_INDEX_UNIQUE, ['userid', 'periodstart', 'periodtype']);
            $table->add_index('periodstart', XMLDB_INDEX_NOTUNIQUE, ['periodstart']);

            $dbman->create_table($table);
        }

        // Update existing records to have default apitype
        $DB->execute("UPDATE {intebchat} SET apitype = 'chat' WHERE apitype IS NULL OR apitype = ''");

        upgrade_mod_savepoint(true, 2025021800, 'intebchat');
    }

    if ($oldversion < 2025021900) {
        // Remove username field if it exists
        $table = new xmldb_table('intebchat');
        $field = new xmldb_field('username');
        
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025021900, 'intebchat');
    }

    if ($oldversion < 2025022000) {
        // Remove Azure-related fields
        $table = new xmldb_table('intebchat');
        
        // Remove resourcename field
        $field = new xmldb_field('resourcename');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        // Remove deploymentid field
        $field = new xmldb_field('deploymentid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        // Remove apiversion field
        $field = new xmldb_field('apiversion');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        // Update any existing records that have 'azure' as apitype to 'chat'
        $DB->execute("UPDATE {intebchat} SET apitype = 'chat' WHERE apitype = 'azure'");
        
        // Clean up any Azure settings from config
        unset_config('resourcename', 'mod_intebchat');
        unset_config('deploymentid', 'mod_intebchat');
        unset_config('apiversion', 'mod_intebchat');
        
        // Update the type config if it was set to 'azure'
        $currenttype = get_config('mod_intebchat', 'type');
        if ($currenttype === 'azure') {
            set_config('type', 'chat', 'mod_intebchat');
        }

        upgrade_mod_savepoint(true, 2025022000, 'intebchat');
    }

    if ($oldversion < 2025022100) {
        // Create usage tracking table for detailed token usage by agent/assistant
        $table = new xmldb_table('mod_intebchat_usage');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('agentid', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'default');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('model', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('prompttokens', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('completiontokens', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('totaltokens', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('instanceid', XMLDB_KEY_FOREIGN, ['instanceid'], 'intebchat', ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

            $table->add_index('agentid-time', XMLDB_INDEX_NOTUNIQUE, ['agentid', 'timecreated']);
            $table->add_index('userid-time', XMLDB_INDEX_NOTUNIQUE, ['userid', 'timecreated']);

            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2025022100, 'intebchat');
    }

    return true;
}