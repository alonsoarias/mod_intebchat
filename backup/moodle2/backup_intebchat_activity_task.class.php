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
 * The task that provides all the steps to perform a complete backup is defined here.
 *
 * @package    mod_intebchat
 * @category   backup
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/intebchat/backup/moodle2/backup_intebchat_stepslib.php');

/**
 * Provides all the settings and steps to perform a complete backup of the activity
 */
class backup_intebchat_activity_task extends backup_activity_task {

    /**
     * Defines particular settings for the activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines particular steps for the backup process
     */
    protected function define_my_steps() {
        $this->add_step(new backup_intebchat_activity_structure_step('intebchat_structure', 'intebchat.xml'));
    }

    /**
     * Codes the transformations to perform in the activity in order to get transportable (encoded) links
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of intebchats
        $search = "/(".$base."\/mod\/intebchat\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@OPENAICHATINDEX*$2@$', $content);

        // Link to intebchat view by moduleid
        $search = "/(".$base."\/mod\/intebchat\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@OPENAICHATVIEWBYID*$2@$', $content);

        return $content;
    }
}