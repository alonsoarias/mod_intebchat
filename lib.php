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
 * @package    mod_openai_chat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function openai_chat_supports($feature) {
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
 * Saves a new instance of the openai_chat into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $openai_chat Submitted data from the form in mod_form.php
 * @param mod_openai_chat_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted openai_chat record
 */
function openai_chat_add_instance(stdClass $openai_chat, mod_openai_chat_mod_form $mform = null) {
    global $DB;

    $openai_chat->timecreated = time();
    $openai_chat->timemodified = time();

    // Process standard intro fields.
    if (!isset($openai_chat->intro)) {
        $openai_chat->intro = '';
    }
    if (!isset($openai_chat->introformat)) {
        $openai_chat->introformat = FORMAT_HTML;
    }

    // Insert the record.
    $openai_chat->id = $DB->insert_record('openai_chat', $openai_chat);

    return $openai_chat->id;
}

/**
 * Updates an instance of the openai_chat in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $openai_chat An object from the form in mod_form.php
 * @param mod_openai_chat_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function openai_chat_update_instance(stdClass $openai_chat, mod_openai_chat_mod_form $mform = null) {
    global $DB;

    $openai_chat->timemodified = time();
    $openai_chat->id = $openai_chat->instance;

    return $DB->update_record('openai_chat', $openai_chat);
}

/**
 * Removes an instance of the openai_chat from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Fail
 */
function openai_chat_delete_instance($id) {
    global $DB;

    if (!$openai_chat = $DB->get_record('openai_chat', array('id' => $id))) {
        return false;
    }

    // Delete all associated log entries.
    $DB->delete_records('mod_openai_chat_log', array('instanceid' => $openai_chat->id));

    // Delete the instance itself.
    $DB->delete_records('openai_chat', array('id' => $openai_chat->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $openai_chat The openai_chat instance record
 * @return stdClass|null
 */
function openai_chat_user_outline($course, $user, $mod, $openai_chat) {
    global $DB;

    $logs = $DB->get_records('mod_openai_chat_log', 
        array('instanceid' => $openai_chat->id, 'userid' => $user->id), 
        'timecreated DESC', 'id, timecreated', 0, 1);

    if ($logs) {
        $log = reset($logs);
        $result = new stdClass();
        $result->info = get_string('lastmessage', 'mod_openai_chat');
        $result->time = $log->timecreated;
        return $result;
    }
    return null;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $openai_chat the module instance record
 * @return void, is supposed to echo directly
 */
function openai_chat_user_complete($course, $user, $mod, $openai_chat) {
    global $DB, $OUTPUT;

    $logs = $DB->get_records('mod_openai_chat_log', 
        array('instanceid' => $openai_chat->id, 'userid' => $user->id), 
        'timecreated ASC', 'timecreated');

    if ($logs) {
        echo $OUTPUT->heading(get_string('messagecount', 'mod_openai_chat', count($logs)));
        echo '<p>'.get_string('firstmessage', 'mod_openai_chat').': '.userdate(reset($logs)->timecreated).'</p>';
        echo '<p>'.get_string('lastmessage', 'mod_openai_chat').': '.userdate(end($logs)->timecreated).'</p>';
    } else {
        echo '<p>'.get_string('nomessages', 'mod_openai_chat').'</p>';
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in openai_chat activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function openai_chat_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link openai_chat_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function openai_chat_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@link openai_chat_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function openai_chat_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function openai_chat_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function openai_chat_get_extra_capabilities() {
    return array();
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function openai_chat_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for openai_chat file areas
 *
 * @package mod_openai_chat
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function openai_chat_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the openai_chat file areas
 *
 * @package mod_openai_chat
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the openai_chat's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function openai_chat_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding openai_chat nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the openai_chat module instance
 * @param stdClass $course current course record
 * @param stdClass $module current openai_chat instance record
 * @param cm_info $cm course module information
 */
function openai_chat_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    global $PAGE;
    
    if (has_capability('mod/openai_chat:viewreport', context_module::instance($cm->id))) {
        $url = new moodle_url('/mod/openai_chat/report.php', array('id' => $cm->id));
        $navref->add(get_string('viewreport', 'mod_openai_chat'), $url, navigation_node::TYPE_SETTING);
    }
}

/**
 * Extends the settings navigation with the openai_chat settings
 *
 * This function is called when the context for the page is a openai_chat module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $openai_chatnode openai_chat administration node
 */
function openai_chat_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $openai_chatnode = null) {
    global $PAGE, $DB, $CFG;

    if (!$openai_chatnode) {
        return;
    }

    if (has_capability('mod/openai_chat:viewreport', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/openai_chat/report.php', array('id' => $PAGE->cm->id));
        $openai_chatnode->add(get_string('viewreport', 'mod_openai_chat'), $url, navigation_node::TYPE_SETTING);
    }

    if (has_capability('mod/openai_chat:viewallreports', context_system::instance())) {
        $url = new moodle_url('/mod/openai_chat/adminreport.php');
        $openai_chatnode->add(get_string('viewallreports', 'mod_openai_chat'), $url, navigation_node::TYPE_SETTING);
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
    $stored_type = get_config('mod_openai_chat', 'type');
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
    global $DB;

    if (!$apikey) {
        $apikey = get_config('mod_openai_chat', 'apikey');
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

    $response = $curl->get("https://api.openai.com/v1/assistants?order=desc");
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
        ],
        "types" => [
            'gpt-4o-2024-11-20'          =>  'chat',
            'gpt-4o-2024-08-06'          =>  'chat',
            'gpt-4o-2024-05-13'          =>  'chat',
            'gpt-4o'                     =>  'chat',
            'gpt-4o-mini-2024-07-18'     =>  'chat',
            'gpt-4o-mini'                =>  'chat',
            'gpt-4-turbo-preview'        =>  'chat',
            'gpt-4-turbo-2024-04-09'     =>  'chat',
            'gpt-4-turbo'                =>  'chat',
            'gpt-4-32k-0314'             =>  'chat',
            'gpt-4-1106-preview'         =>  'chat',
            'gpt-4-0613'                 =>  'chat',
            'gpt-4-0314'                 =>  'chat',
            'gpt-4-0125-preview'         =>  'chat',
            'gpt-4'                      =>  'chat',
            'gpt-3.5-turbo-16k-0613'     =>  'chat',
            'gpt-3.5-turbo-16k'          =>  'chat',
            'gpt-3.5-turbo-1106'         =>  'chat',
            'gpt-3.5-turbo-0125'         =>  'chat',
            'gpt-3.5-turbo'              =>  'chat'
        ]
    ];
}

/**
 * If setting is enabled, log the user's message and the AI response
 * @param int $instanceid The module instance ID
 * @param string $usermessage The text sent from the user
 * @param string $airesponse The text returned by the AI 
 * @param object $context The context object
 */
function log_message($instanceid, $usermessage, $airesponse, $context) {
    global $USER, $DB;

    if (!get_config('mod_openai_chat', 'logging')) {
        return;
    }

    $DB->insert_record('mod_openai_chat_log', (object) [
        'instanceid' => $instanceid,
        'userid' => $USER->id,
        'usermessage' => $usermessage,
        'airesponse' => $airesponse,
        'contextid' => $context->id,
        'timecreated' => time()
    ]);
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $openai_chat   openai_chat object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function openai_chat_view($openai_chat, $course, $cm, $context) {
    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $openai_chat->id
    );

    $event = \mod_openai_chat\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('openai_chat', $openai_chat);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}