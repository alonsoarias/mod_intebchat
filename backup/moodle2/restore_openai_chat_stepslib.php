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
 * Define all the restore steps that will be used by the restore_openai_chat_activity_task
 *
 * @package    mod_openai_chat
 * @category   backup
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one openai_chat activity
 */
class restore_openai_chat_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {@link restore_path_element}
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('openai_chat', '/activity/openai_chat');
        if ($userinfo) {
            $paths[] = new restore_path_element('openai_chat_log', '/activity/openai_chat/logs/log');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_openai_chat($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        // Create the openai_chat instance.
        $newitemid = $DB->insert_record('openai_chat', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_openai_chat_log($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->instanceid = $this->get_new_parentid('openai_chat');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('mod_openai_chat_log', $data);
        $this->set_mapping('openai_chat_log', $oldid, $newitemid);
    }

    /**
     * Post-execution actions
     */
    protected function after_execute() {
        // Add openai_chat related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_openai_chat', 'intro', null);
    }
}