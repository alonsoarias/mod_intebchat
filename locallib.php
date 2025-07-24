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
 * Local library functions for token usage reporting
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get token usage by agent/assistant for a given time period
 * 
 * @param int $starttime Start timestamp
 * @param int $endtime End timestamp
 * @param int $instanceid Optional instance ID filter
 * @return array Usage data grouped by agent
 */
function intebchat_get_usage_by_agent($starttime = 0, $endtime = 0, $instanceid = 0) {
    global $DB;
    
    $params = [];
    $where = [];
    
    if ($starttime > 0) {
        $where[] = 'timecreated >= :starttime';
        $params['starttime'] = $starttime;
    }
    
    if ($endtime > 0) {
        $where[] = 'timecreated <= :endtime';
        $params['endtime'] = $endtime;
    }
    
    if ($instanceid > 0) {
        $where[] = 'instanceid = :instanceid';
        $params['instanceid'] = $instanceid;
    }
    
    $wheresql = '';
    if (!empty($where)) {
        $wheresql = 'WHERE ' . implode(' AND ', $where);
    }
    
    $sql = "SELECT agentid,
                   COUNT(*) as request_count,
                   SUM(prompttokens) as total_prompt_tokens,
                   SUM(completiontokens) as total_completion_tokens,
                   SUM(totaltokens) as total_tokens,
                   MIN(timecreated) as first_use,
                   MAX(timecreated) as last_use
              FROM {mod_intebchat_usage}
              $wheresql
          GROUP BY agentid
          ORDER BY total_tokens DESC";
    
    return $DB->get_records_sql($sql, $params);
}

/**
 * Get token usage by user for a given time period
 * 
 * @param int $starttime Start timestamp
 * @param int $endtime End timestamp
 * @param int $instanceid Optional instance ID filter
 * @return array Usage data grouped by user
 */
function intebchat_get_usage_by_user($starttime = 0, $endtime = 0, $instanceid = 0) {
    global $DB;
    
    $params = [];
    $where = ['userid IS NOT NULL'];
    
    if ($starttime > 0) {
        $where[] = 'u.timecreated >= :starttime';
        $params['starttime'] = $starttime;
    }
    
    if ($endtime > 0) {
        $where[] = 'u.timecreated <= :endtime';
        $params['endtime'] = $endtime;
    }
    
    if ($instanceid > 0) {
        $where[] = 'u.instanceid = :instanceid';
        $params['instanceid'] = $instanceid;
    }
    
    $wheresql = 'WHERE ' . implode(' AND ', $where);
    
    $sql = "SELECT u.userid,
                   usr.firstname,
                   usr.lastname,
                   COUNT(*) as request_count,
                   SUM(u.prompttokens) as total_prompt_tokens,
                   SUM(u.completiontokens) as total_completion_tokens,
                   SUM(u.totaltokens) as total_tokens,
                   MIN(u.timecreated) as first_use,
                   MAX(u.timecreated) as last_use
              FROM {mod_intebchat_usage} u
              JOIN {user} usr ON usr.id = u.userid
              $wheresql
          GROUP BY u.userid, usr.firstname, usr.lastname
          ORDER BY total_tokens DESC";
    
    return $DB->get_records_sql($sql, $params);
}

/**
 * Get daily token usage for charting
 * 
 * @param int $days Number of days to look back (default 30)
 * @param int $instanceid Optional instance ID filter
 * @return array Daily usage data
 */
function intebchat_get_daily_usage($days = 30, $instanceid = 0) {
    global $DB;
    
    $starttime = strtotime("-$days days");
    $params = ['starttime' => $starttime];
    $instancewhere = '';
    
    if ($instanceid > 0) {
        $instancewhere = ' AND instanceid = :instanceid';
        $params['instanceid'] = $instanceid;
    }
    
    $sql = "SELECT DATE(FROM_UNIXTIME(timecreated)) as usage_date,
                   COUNT(*) as request_count,
                   SUM(prompttokens) as daily_prompt_tokens,
                   SUM(completiontokens) as daily_completion_tokens,
                   SUM(totaltokens) as daily_total_tokens
              FROM {mod_intebchat_usage}
             WHERE timecreated >= :starttime
                   $instancewhere
          GROUP BY DATE(FROM_UNIXTIME(timecreated))
          ORDER BY usage_date ASC";
    
    return $DB->get_records_sql($sql, $params);
}

/**
 * Get top models by usage
 * 
 * @param int $limit Number of top models to return
 * @param int $starttime Optional start timestamp
 * @return array Model usage data
 */
function intebchat_get_top_models($limit = 10, $starttime = 0) {
    global $DB;
    
    $params = [];
    $timewhere = '';
    
    if ($starttime > 0) {
        $timewhere = 'WHERE timecreated >= :starttime';
        $params['starttime'] = $starttime;
    }
    
    $sql = "SELECT model,
                   COUNT(*) as request_count,
                   SUM(totaltokens) as total_tokens
              FROM {mod_intebchat_usage}
              $timewhere
          GROUP BY model
          ORDER BY total_tokens DESC";
    
    return $DB->get_records_sql($sql, $params, 0, $limit);
}

/**
 * Calculate estimated cost based on token usage
 * Note: These are example rates and should be updated based on actual OpenAI pricing
 * 
 * @param int $prompttokens Number of prompt tokens
 * @param int $completiontokens Number of completion tokens
 * @param string $model Model name
 * @return float Estimated cost in USD
 */
function intebchat_calculate_estimated_cost($prompttokens, $completiontokens, $model) {
    // Example pricing per 1K tokens (update these based on actual OpenAI pricing)
    $pricing = [
        'gpt-4' => ['prompt' => 0.03, 'completion' => 0.06],
        'gpt-4-turbo' => ['prompt' => 0.01, 'completion' => 0.03],
        'gpt-4o' => ['prompt' => 0.005, 'completion' => 0.015],
        'gpt-4o-mini' => ['prompt' => 0.00015, 'completion' => 0.0006],
        'gpt-3.5-turbo' => ['prompt' => 0.0005, 'completion' => 0.0015],
    ];
    
    // Default pricing if model not found
    $default_pricing = ['prompt' => 0.002, 'completion' => 0.002];
    
    // Find the pricing for this model
    $model_pricing = $default_pricing;
    foreach ($pricing as $model_prefix => $rates) {
        if (strpos($model, $model_prefix) !== false) {
            $model_pricing = $rates;
            break;
        }
    }
    
    // Calculate cost
    $prompt_cost = ($prompttokens / 1000) * $model_pricing['prompt'];
    $completion_cost = ($completiontokens / 1000) * $model_pricing['completion'];
    
    return round($prompt_cost + $completion_cost, 4);
}