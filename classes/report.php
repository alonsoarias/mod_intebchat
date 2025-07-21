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
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_intebchat;
defined('MOODLE_INTERNAL') || die;

class report extends \table_sql {
    protected $showTokens = false;
    
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        
        // Check if token tracking is enabled
        $config = get_config('mod_intebchat');
        $this->showTokens = !empty($config->enabletokenlimit);
        
        // Define the list of columns to show
        $columns = array('userid', 'user_name', 'usermessage', 'airesponse');
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
            return "<a href='/user/profile.php?id=$values->userid'>$user->firstname $user->lastname</a>";
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
                return '<span class="badge badge-info" title="' . implode(', ', $breakdown) . '">' . 
                       $values->totaltokens . '</span>';
            }
        }
        return '-';
    }
    
    /**
     * Get the total tokens used for the current query
     * @return int Total tokens
     */
    public function get_total_tokens() {
        global $DB;
        
        if (!$this->showTokens) {
            return 0;
        }
        
        // Use the same SQL conditions as the main query
        $sql = "SELECT SUM(ocl.totaltokens) as total
                FROM {mod_intebchat_log} ocl 
                JOIN {user} u ON u.id = ocl.userid
                WHERE " . $this->sql->where;
        
        $result = $DB->get_record_sql($sql, $this->sql->params);
        return $result ? (int)$result->total : 0;
    }
}