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
 * Define all the backup steps that will be used by the backup_openai_chat_activity_task
 *
 * @package    mod_openai_chat
 * @category   backup
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete openai_chat structure for backup
 */
class backup_openai_chat_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // Get know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the openai_chat instance.
        $openai_chat = new backup_nested_element('openai_chat', array('id'), array(
            'name', 'intro', 'introformat', 'showlabels',
            'sourceoftruth', 'prompt', 'instructions', 'username',
            'assistantname', 'apikey', 'model', 'temperature',
            'maxlength', 'topp', 'frequency', 'presence',
            'assistant', 'persistconvo', 'timecreated', 'timemodified'
        ));

        // Define chat logs.
        $logs = new backup_nested_element('logs');
        $log = new backup_nested_element('log', array('id'), array(
            'userid', 'usermessage', 'airesponse', 'timecreated'
        ));

        // Build the tree.
        $openai_chat->add_child($logs);
        $logs->add_child($log);

        // Define data sources.
        $openai_chat->set_source_table('openai_chat', array('id' => backup::VAR_ACTIVITYID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $log->set_source_table('mod_openai_chat_log', array('instanceid' => backup::VAR_PARENTID), 'id ASC');
        }

        // Define id annotations.
        $log->annotate_ids('user', 'userid');

        // Define file annotations.
        $openai_chat->annotate_files('mod_openai_chat', 'intro', null);

        // Return the root element (openai_chat), wrapped into standard activity structure.
        return $this->prepare_activity_structure($openai_chat);
    }
}