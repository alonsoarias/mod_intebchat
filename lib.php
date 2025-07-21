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
 * Library of interface functions and constants.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the information on whether the module supports a feature
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function intebchat_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the intebchat into the database
 *
 * @param stdClass $intebchat Submitted data from the form in mod_form.php
 * @param mod_intebchat_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted intebchat record
 */
function intebchat_add_instance(stdClass $intebchat, mod_intebchat_mod_form $mform = null) {
    global $DB;

    $intebchat->timecreated = time();
    $intebchat->timemodified = time();

    // Process standard intro fields
    if (!isset($intebchat->intro)) {
        $intebchat->intro = '';
    }
    if (!isset($intebchat->introformat)) {
        $intebchat->introformat = FORMAT_HTML;
    }

    // Ensure apitype is set
    if (empty($intebchat->apitype)) {
        $config = get_config('mod_intebchat');
        $intebchat->apitype = $config->type ?: 'chat';
    }

    // Set defaults for unchecked checkboxes
    if (!isset($intebchat->showlabels)) {
        $intebchat->showlabels = 0;
    }
    if (!isset($intebchat->persistconvo)) {
        $intebchat->persistconvo = 0;
    }

    // Clean up fields based on API type
    if ($intebchat->apitype === 'assistant') {
        // Clear chat-specific fields
        $intebchat->sourceoftruth = null;
        $intebchat->prompt = null;
        $intebchat->model = null;
        $intebchat->temperature = null;
        $intebchat->maxlength = null;
        $intebchat->topp = null;
        $intebchat->frequency = null;
        $intebchat->presence = null;
    } else {
        // Clear assistant-specific fields
        $intebchat->assistant = null;
        $intebchat->instructions = null;
        $intebchat->persistconvo = 0;
    }

    if ($intebchat->apitype !== 'azure') {
        // Clear Azure-specific fields
        $intebchat->resourcename = null;
        $intebchat->deploymentid = null;
        $intebchat->apiversion = null;
    }

    // Insert the record
    $intebchat->id = $DB->insert_record('intebchat', $intebchat);

    return $intebchat->id;
}

/**
 * Updates an instance of the intebchat in the database
 *
 * @param stdClass $intebchat An object from the form in mod_form.php
 * @param mod_intebchat_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function intebchat_update_instance(stdClass $intebchat, mod_intebchat_mod_form $mform = null) {
    global $DB;

    $intebchat->timemodified = time();
    $intebchat->id = $intebchat->instance;

    // Ensure apitype is set
    if (empty($intebchat->apitype)) {
        $current = $DB->get_record('intebchat', array('id' => $intebchat->id), 'apitype');
        $intebchat->apitype = $current->apitype ?: 'chat';
    }

    // Set defaults for unchecked checkboxes
    if (!isset($intebchat->showlabels)) {
        $intebchat->showlabels = 0;
    }
    if (!isset($intebchat->persistconvo)) {
        $intebchat->persistconvo = 0;
    }

    // Clean up fields based on API type
    if ($intebchat->apitype === 'assistant') {
        $intebchat->sourceoftruth = null;
        $intebchat->prompt = null;
        $intebchat->model = null;
        $intebchat->temperature = null;
        $intebchat->maxlength = null;
        $intebchat->topp = null;
        $intebchat->frequency = null;
        $intebchat->presence = null;
    } else {
        $intebchat->assistant = null;
        $intebchat->instructions = null;
        $intebchat->persistconvo = 0;
    }

    if ($intebchat->apitype !== 'azure') {
        $intebchat->resourcename = null;
        $intebchat->deploymentid = null;
        $intebchat->apiversion = null;
    }

    return $DB->update_record('intebchat', $intebchat);
}

/**
 * Removes an instance of the intebchat from the database
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Fail
 */
function intebchat_delete_instance($id) {
    global $DB;

    if (!$intebchat = $DB->get_record('intebchat', array('id' => $id))) {
        return false;
    }

    // Delete all associated log entries
    $DB->delete_records('mod_intebchat_log', array('instanceid' => $intebchat->id));

    // Delete the instance itself
    $DB->delete_records('intebchat', array('id' => $intebchat->id));

    return true;
}

/**
 * Check if user has exceeded token limit
 *
 * @param int $userid User ID to check
 * @return array ['allowed' => bool, 'used' => int, 'limit' => int, 'reset_time' => int]
 */
function intebchat_check_token_limit($userid) {
    global $DB;
    
    $config = get_config('mod_intebchat');
    
    // If token limit is not enabled, allow unlimited
    if (empty($config->enabletokenlimit)) {
        return ['allowed' => true, 'used' => 0, 'limit' => 0, 'reset_time' => 0];
    }
    
    $limit = (int)$config->maxtokensperuser;
    $period = $config->tokenlimitperiod ?: 'day';
    
    // Calculate period start time
    $now = time();
    switch ($period) {
        case 'hour':
            $periodstart = $now - 3600;
            break;
        case 'week':
            $periodstart = $now - (7 * 24 * 3600);
            break;
        case 'month':
            $periodstart = $now - (30 * 24 * 3600);
            break;
        case 'day':
        default:
            $periodstart = $now - (24 * 3600);
            break;
    }
    
    // Get or create token usage record
    $usage = $DB->get_record('mod_intebchat_token_usage', [
        'userid' => $userid,
        'periodtype' => $period,
        'periodstart' => $periodstart
    ]);
    
    if (!$usage) {
        // Create new usage record
        $usage = new stdClass();
        $usage->userid = $userid;
        $usage->tokensused = 0;
        $usage->periodstart = $periodstart;
        $usage->periodtype = $period;
        $usage->timecreated = $now;
        $usage->timemodified = $now;
        $usage->id = $DB->insert_record('mod_intebchat_token_usage', $usage);
    }
    
    // Clean up old records
    $DB->delete_records_select('mod_intebchat_token_usage', 
        'periodstart < :periodstart AND periodtype = :periodtype',
        ['periodstart' => $periodstart, 'periodtype' => $period]
    );
    
    // Calculate when the limit resets
    $reset_time = $periodstart + intebchat_get_period_duration($period);
    
    return [
        'allowed' => $usage->tokensused < $limit,
        'used' => $usage->tokensused,
        'limit' => $limit,
        'reset_time' => $reset_time
    ];
}

/**
 * Get period duration in seconds
 *
 * @param string $period Period type
 * @return int Duration in seconds
 */
function intebchat_get_period_duration($period) {
    switch ($period) {
        case 'hour':
            return 3600;
        case 'week':
            return 7 * 24 * 3600;
        case 'month':
            return 30 * 24 * 3600;
        case 'day':
        default:
            return 24 * 3600;
    }
}

/**
 * Update token usage for a user
 *
 * @param int $userid User ID
 * @param int $tokens Number of tokens to add
 * @return bool Success
 */
function intebchat_update_token_usage($userid, $tokens) {
    global $DB;
    
    $config = get_config('mod_intebchat');
    
    // If token limit is not enabled, don't track
    if (empty($config->enabletokenlimit)) {
        return true;
    }
    
    $period = $config->tokenlimitperiod ?: 'day';
    $now = time();
    
    // Calculate current period start
    $periodstart = $now - intebchat_get_period_duration($period);
    
    // Get or create usage record
    $usage = $DB->get_record('mod_intebchat_token_usage', [
        'userid' => $userid,
        'periodtype' => $period
    ], '*', IGNORE_MULTIPLE);
    
    if (!$usage || $usage->periodstart < $periodstart) {
        // Create new record for current period
        $usage = new stdClass();
        $usage->userid = $userid;
        $usage->tokensused = $tokens;
        $usage->periodstart = $now;
        $usage->periodtype = $period;
        $usage->timecreated = $now;
        $usage->timemodified = $now;
        $DB->insert_record('mod_intebchat_token_usage', $usage);
    } else {
        // Update existing record
        $usage->tokensused += $tokens;
        $usage->timemodified = $now;
        $DB->update_record('mod_intebchat_token_usage', $usage);
    }
    
    return true;
}

/**
 * Log message with token tracking
 *
 * @param int $instanceid The module instance ID
 * @param string $usermessage The text sent from the user
 * @param string $airesponse The text returned by the AI
 * @param object $context The context object
 * @param array $tokeninfo Token usage information ['prompt' => int, 'completion' => int, 'total' => int]
 */
function log_message($instanceid, $usermessage, $airesponse, $context, $tokeninfo = null) {
    global $USER, $DB;

    if (!get_config('mod_intebchat', 'logging')) {
        return;
    }

    $record = new stdClass();
    $record->instanceid = $instanceid;
    $record->userid = $USER->id;
    $record->usermessage = $usermessage;
    $record->airesponse = $airesponse;
    $record->contextid = $context->id;
    $record->timecreated = time();
    
    // Add token information if provided
    if ($tokeninfo) {
        $record->prompttokens = $tokeninfo['prompt'] ?? 0;
        $record->completiontokens = $tokeninfo['completion'] ?? 0;
        $record->totaltokens = $tokeninfo['total'] ?? 0;
        
        // Update user's token usage
        if ($record->totaltokens > 0) {
            intebchat_update_token_usage($USER->id, $record->totaltokens);
        }
    }

    $DB->insert_record('mod_intebchat_log', $record);
}

/**
 * Get API configuration for an instance
 *
 * @param object $instance The intebchat instance
 * @return array Configuration array
 */
function intebchat_get_api_config($instance) {
    $config = get_config('mod_intebchat');
    
    // Start with global config
    $apiconfig = [
        'apikey' => $config->apikey,
        'type' => $instance->apitype ?: $config->type,
        'model' => $config->model,
        'temperature' => $config->temperature,
        'maxlength' => $config->maxlength,
        'topp' => $config->topp,
        'frequency' => $config->frequency,
        'presence' => $config->presence,
        'assistant' => $config->assistant,
        'resourcename' => $config->resourcename,
        'deploymentid' => $config->deploymentid,
        'apiversion' => $config->apiversion,
    ];
    
    // Override with instance settings if allowed and present
    if ($config->allowinstancesettings) {
        if (!empty($instance->apikey)) {
            $apiconfig['apikey'] = $instance->apikey;
        }
        if (!empty($instance->model)) {
            $apiconfig['model'] = $instance->model;
        }
        if (isset($instance->temperature)) {
            $apiconfig['temperature'] = $instance->temperature;
        }
        if (isset($instance->maxlength)) {
            $apiconfig['maxlength'] = $instance->maxlength;
        }
        if (isset($instance->topp)) {
            $apiconfig['topp'] = $instance->topp;
        }
        if (isset($instance->frequency)) {
            $apiconfig['frequency'] = $instance->frequency;
        }
        if (isset($instance->presence)) {
            $apiconfig['presence'] = $instance->presence;
        }
        if (!empty($instance->assistant)) {
            $apiconfig['assistant'] = $instance->assistant;
        }
        if (!empty($instance->resourcename)) {
            $apiconfig['resourcename'] = $instance->resourcename;
        }
        if (!empty($instance->deploymentid)) {
            $apiconfig['deploymentid'] = $instance->deploymentid;
        }
        if (!empty($instance->apiversion)) {
            $apiconfig['apiversion'] = $instance->apiversion;
        }
    }
    
    return $apiconfig;
}

// Keep all existing functions from the original file below...

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 */
function intebchat_user_outline($course, $user, $mod, $intebchat) {
    global $DB;

    $logs = $DB->get_records('mod_intebchat_log', 
        array('instanceid' => $intebchat->id, 'userid' => $user->id), 
        'timecreated DESC', 'id, timecreated', 0, 1);

    if ($logs) {
        $log = reset($logs);
        $result = new stdClass();
        $result->info = get_string('lastmessage', 'mod_intebchat');
        $result->time = $log->timecreated;
        return $result;
    }
    return null;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 */
function intebchat_user_complete($course, $user, $mod, $intebchat) {
    global $DB, $OUTPUT;

    $logs = $DB->get_records('mod_intebchat_log', 
        array('instanceid' => $intebchat->id, 'userid' => $user->id), 
        'timecreated ASC');

    if ($logs) {
        echo $OUTPUT->heading(get_string('messagecount', 'mod_intebchat', count($logs)));
        
        // Calculate total tokens if token limit is enabled
        $config = get_config('mod_intebchat');
        if (!empty($config->enabletokenlimit)) {
            $totaltokens = 0;
            foreach ($logs as $log) {
                $totaltokens += $log->totaltokens;
            }
            echo '<p>' . get_string('totaltokensused', 'mod_intebchat', $totaltokens) . '</p>';
        }
        
        $firstlog = reset($logs);
        $lastlog = end($logs);
        echo '<p>'.get_string('firstmessage', 'mod_intebchat').': '.userdate($firstlog->timecreated).'</p>';
        echo '<p>'.get_string('lastmessage', 'mod_intebchat').': '.userdate($lastlog->timecreated).'</p>';
    } else {
        echo '<p>'.get_string('nomessages', 'mod_intebchat').'</p>';
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in intebchat activities and print it out.
 */
function intebchat_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 */
function intebchat_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link intebchat_get_recent_mod_activity()}
 */
function intebchat_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 */
function intebchat_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 */
function intebchat_get_extra_capabilities() {
    return array();
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 */
function intebchat_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for intebchat file areas
 */
function intebchat_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the intebchat file areas
 */
function intebchat_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding intebchat nodes if there is a relevant content
 */
function intebchat_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    if (has_capability('mod/intebchat:viewreport', context_module::instance($cm->id))) {
        $url = new moodle_url('/mod/intebchat/report.php', array('id' => $cm->id));
        $navref->add(get_string('viewreport', 'mod_intebchat'), $url, navigation_node::TYPE_SETTING);
    }
}

/**
 * Extends the settings navigation with the intebchat settings
 */
function intebchat_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $intebchatnode = null) {
    global $PAGE;

    if (!$intebchatnode) {
        return;
    }

    if (has_capability('mod/intebchat:viewreport', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/intebchat/report.php', array('id' => $PAGE->cm->id));
        $intebchatnode->add(get_string('viewreport', 'mod_intebchat'), $url, navigation_node::TYPE_SETTING);
    }

    if (has_capability('mod/intebchat:viewallreports', context_system::instance())) {
        $url = new moodle_url('/mod/intebchat/adminreport.php');
        $intebchatnode->add(get_string('viewallreports', 'mod_intebchat'), $url, navigation_node::TYPE_SETTING);
    }
}

/**
 * Helper functions from original block plugin
 */

/**
 * Fetch the current API type from the database, defaulting to "chat"
 * @return String: the API type (chat|azure|assistant)
 */
function get_type_to_display() {
    $stored_type = get_config('mod_intebchat', 'type');
    if ($stored_type) {
        return $stored_type;
    }
    
    return 'chat';
}

/**
 * Use an API key to fetch a list of assistants from a user's OpenAI account
 * @param String (optional): The API key to use. If not provided, will use site-wide key.
 * @return Array: The list of assistants
 */
function fetch_assistants_array($apikey = null) {
    if (!$apikey) {
        $apikey = get_config('mod_intebchat', 'apikey');
    }

    if (!$apikey) {
        return [];
    }

    $curl = new \curl();
    $curl->setopt(array(
        'CURLOPT_HTTPHEADER' => array(
            'Authorization: Bearer ' . $apikey,
            'Content-Type: application/json',
            'OpenAI-Beta: assistants=v2'
        ),
    ));

    $response = $curl->get("https://api.openai.com/v1/assistants?order=desc&limit=100");
    $response = json_decode($response);
    $assistant_array = [];
    if (property_exists($response, 'data')) {
        foreach ($response->data as $assistant) {
            $assistant_array[$assistant->id] = $assistant->name;
        }
    }

    return $assistant_array;
}

/**
 * Return a list of available models
 * @return Array: The list of model info
 */
function get_models() {
    return [
        "models" => [
            'gpt-4o' => 'gpt-4o',
            'gpt-4o-2024-11-20' => 'gpt-4o-2024-11-20',
            'gpt-4o-2024-08-06' => 'gpt-4o-2024-08-06',
            'gpt-4o-2024-05-13' => 'gpt-4o-2024-05-13',
            'gpt-4o-mini-2024-07-18' => 'gpt-4o-mini-2024-07-18',
            'gpt-4o-mini' => 'gpt-4o-mini',
            'gpt-4-turbo-preview' => 'gpt-4-turbo-preview',
            'gpt-4-turbo-2024-04-09' => 'gpt-4-turbo-2024-04-09',
            'gpt-4-turbo' => 'gpt-4-turbo',
            'gpt-4-32k-0314' => 'gpt-4-32k-0314',
            'gpt-4-1106-preview' => 'gpt-4-1106-preview',
            'gpt-4-0613' => 'gpt-4-0613',
            'gpt-4-0314' => 'gpt-4-0314',
            'gpt-4-0125-preview' => 'gpt-4-0125-preview',
            'gpt-4' => 'gpt-4',
            'gpt-3.5-turbo-16k-0613' => 'gpt-3.5-turbo-16k-0613',
            'gpt-3.5-turbo-16k' => 'gpt-3.5-turbo-16k',
            'gpt-3.5-turbo-1106' => 'gpt-3.5-turbo-1106',
            'gpt-3.5-turbo-0125' => 'gpt-3.5-turbo-0125',
            'gpt-3.5-turbo' => 'gpt-3.5-turbo'
        ]
    ];
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $intebchat   intebchat object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function intebchat_view($intebchat, $course, $cm, $context) {
    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $intebchat->id
    );

    $event = \mod_intebchat\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('intebchat', $intebchat);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}