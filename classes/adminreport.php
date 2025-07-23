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
    protected $showTokens = false;
    
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        
        // Check if token tracking is enabled
        $config = get_config('mod_intebchat');
        $this->showTokens = !empty($config->enabletokenlimit);
        
        // Define the list of columns to show
        $columns = array('userid', 'user_name', 'course_name', 'activity_name', 'usermessage', 'airesponse');
        if ($this->showTokens) {
            $columns[] = 'totaltokens';
        }
        $columns[] = 'timecreated';
        
        $this->define_columns($columns);
        $this->no_sorting('usermessage');
        $this->no_sorting('airesponse');

        // Define the titles of columns to show in header
        $headers = array(
            get_string('userid', 'mod_intebchat'), 
            get_string('username', 'mod_intebchat'), 
            get_string('course'), 
            get_string('activity'), 
            get_string('usermessage', 'mod_intebchat'), 
            get_string('airesponse', 'mod_intebchat')
        );
        if ($this->showTokens) {
            $headers[] = get_string('tokens', 'mod_intebchat');
        }
        $headers[] = get_string('time');
        
        $this->define_headers($headers);
    }

    function col_user_name($values) {
        global $DB;
        $user = $DB->get_record('user', ['id' => $values->userid]);

        if ($this->is_downloading()) {
            return "$user->firstname $user->lastname";
        } else {
            return "<a href='/user/profile.php?id=$values->userid' class='user-link'>
                <i class='fa fa-user-circle'></i> $user->firstname $user->lastname</a>";
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
                return "<a href='/course/view.php?id=".$values->course."' class='course-link'>
                    <i class='fa fa-graduation-cap'></i> ".$values->course_name."</a>";
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
                return "<a href='/mod/intebchat/view.php?id=$cm->id' class='activity-link'>
                    <i class='fa fa-comments'></i> $values->activity_name</a>";
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
            return '<div class="message-preview" title="' . s($message) . '">' . 
                   substr($message, 0, 100) . '...</div>';
        }
        return $message;
    }

    function col_airesponse($values) {
        $response = strip_tags($values->airesponse);
        if (strlen($response) > 100 && !$this->is_downloading()) {
            return '<div class="response-preview" title="' . s($response) . '">' . 
                   substr($response, 0, 100) . '...</div>';
        }
        return $response;
    }
    
    function col_totaltokens($values) {
        if ($values->totaltokens > 0) {
            $breakdown = [];
            if ($values->prompttokens > 0) {
                $breakdown[] = get_string('prompt', 'mod_intebchat') . ': ' . $values->prompttokens;
            }
            if ($values->completiontokens > 0) {
                $breakdown[] = get_string('completion', 'mod_intebchat') . ': ' . $values->completiontokens;
            }
            
            if ($this->is_downloading()) {
                return $values->totaltokens . ' (' . implode(', ', $breakdown) . ')';
            } else {
                $percentage = 0;
                // Get user's token limit to show visual indicator
                $token_info = intebchat_check_token_limit($values->userid);
                if ($token_info['limit'] > 0) {
                    $percentage = ($token_info['used'] / $token_info['limit']) * 100;
                }
                
                $class = 'badge-info';
                if ($percentage > 90) {
                    $class = 'badge-danger';
                } elseif ($percentage > 75) {
                    $class = 'badge-warning';
                }
                
                return '<span class="badge ' . $class . '" title="' . implode(', ', $breakdown) . '">' . 
                       '<i class="fa fa-coins"></i> ' . $values->totaltokens . '</span>';
            }
        }
        return '<span class="text-muted">-</span>';
    }
    
    /**
     * Get global statistics across all instances
     * @return object Global statistics
     */
    public function get_global_stats() {
        global $DB;
        
        $stats = new \stdClass();
        
        // Total messages
        $stats->total_messages = $DB->count_records('mod_intebchat_log');
        
        // Total tokens
        $stats->total_tokens = $DB->get_field_sql(
            "SELECT SUM(totaltokens) FROM {mod_intebchat_log}"
        ) ?: 0;
        
        // Active users
        $stats->active_users = $DB->get_field_sql(
            "SELECT COUNT(DISTINCT userid) FROM {mod_intebchat_log}"
        ) ?: 0;
        
        // Most active courses
        $stats->top_courses = $DB->get_records_sql(
            "SELECT c.id, c.fullname, COUNT(ocl.id) as message_count, 
                    SUM(ocl.totaltokens) as total_tokens
             FROM {course} c
             JOIN {intebchat} oc ON oc.course = c.id
             JOIN {mod_intebchat_log} ocl ON ocl.instanceid = oc.id
             GROUP BY c.id, c.fullname
             ORDER BY total_tokens DESC
             LIMIT 5"
        );
        
        // Most active users
        $stats->top_users = $DB->get_records_sql(
            "SELECT u.id, u.firstname, u.lastname, 
                    COUNT(ocl.id) as message_count,
                    SUM(ocl.totaltokens) as total_tokens
             FROM {user} u
             JOIN {mod_intebchat_log} ocl ON ocl.userid = u.id
             GROUP BY u.id, u.firstname, u.lastname
             ORDER BY total_tokens DESC
             LIMIT 10"
        );
        
        return $stats;
    }
}