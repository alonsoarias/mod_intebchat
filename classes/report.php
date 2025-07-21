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
 * Log table for individual activity
 *
 * @package    mod_openai_chat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2024 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_openai_chat;
defined('MOODLE_INTERNAL') || die;

class report extends \table_sql {
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = array('userid', 'user_name', 'usermessage', 'airesponse', 'timecreated');
        $this->define_columns($columns);
        $this->no_sorting('usermessage');
        $this->no_sorting('airesponse');

        // Define the titles of columns to show in header.
        $headers = array(
            get_string('userid', 'mod_openai_chat'), 
            get_string('username', 'mod_openai_chat'), 
            get_string('usermessage', 'mod_openai_chat'), 
            get_string('airesponse', 'mod_openai_chat'), 
            get_string('time')
        );
        $this->define_headers($headers);
    }

    function col_user_name($values) {
        global $DB;
        $user = $DB->get_record('user', ['id' => $values->userid]);

        if ($this->is_downloading()) {
            return "$user->firstname $user->lastname";
        } else {
            return "<a href='/user/profile.php?id=$values->userid'>$user->firstname $user->lastname</a>";
        }
    }

    function col_timecreated($values) {
        return userdate($values->timecreated);
    }

    function col_usermessage($values) {
        $message = strip_tags($values->usermessage);
        if (strlen($message) > 100 && !$this->is_downloading()) {
            return substr($message, 0, 100) . '...';
        }
        return $message;
    }

    function col_airesponse($values) {
        $response = strip_tags($values->airesponse);
        if (strlen($response) > 100 && !$this->is_downloading()) {
            return substr($response, 0, 100) . '...';
        }
        return $response;
    }
}