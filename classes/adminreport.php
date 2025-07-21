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
 * Admin log table for all activities
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_intebchat;
defined('MOODLE_INTERNAL') || die;

class adminreport extends \table_sql {
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        // Define the list of columns to show.
        $columns = array('userid', 'user_name', 'course_name', 'activity_name', 'usermessage', 'airesponse', 'timecreated');
        $this->define_columns($columns);
        $this->no_sorting('usermessage');
        $this->no_sorting('airesponse');

        // Define the titles of columns to show in header.
        $headers = array(
            get_string('userid', 'mod_intebchat'), 
            get_string('username', 'mod_intebchat'), 
            get_string('course'), 
            get_string('activity'), 
            get_string('usermessage', 'mod_intebchat'), 
            get_string('airesponse', 'mod_intebchat'), 
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

    function col_course_name($values) {
        if ($this->is_downloading()) {
            return $values->course_name;
        } else {
            global $DB;
            $cm = $DB->get_record_sql(
                "SELECT cm.id 
                 FROM {course_modules} cm 
                 JOIN {modules} m ON m.id = cm.module 
                 WHERE m.name = 'intebchat' AND cm.instance = ?",
                [$values->instanceid]
            );
            if ($cm) {
                return "<a href='/course/view.php?id=".$values->course."'>".$values->course_name."</a>";
            }
            return $values->course_name;
        }
    }

    function col_activity_name($values) {
        if ($this->is_downloading()) {
            return $values->activity_name;
        } else {
            global $DB;
            $cm = $DB->get_record_sql(
                "SELECT cm.id 
                 FROM {course_modules} cm 
                 JOIN {modules} m ON m.id = cm.module 
                 WHERE m.name = 'intebchat' AND cm.instance = ?",
                [$values->instanceid]
            );
            if ($cm) {
                return "<a href='/mod/intebchat/view.php?id=$cm->id'>$values->activity_name</a>";
            }
            return $values->activity_name;
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