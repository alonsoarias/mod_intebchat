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
 * API endpoint for retrieving GPT completion
 *
 * @package    mod_openai_chat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2023 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_openai_chat\completion;

require_once('../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/mod/openai_chat/lib.php');

global $DB, $PAGE;

if (get_config('mod_openai_chat', 'restrictusage') !== "0") {
    require_login();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: $CFG->wwwroot");
    die();
}

$body = json_decode(file_get_contents('php://input'), true);
$message = clean_param($body['message'], PARAM_NOTAGS);
$history = clean_param_array($body['history'], PARAM_NOTAGS, true);
$instance_id = clean_param($body['instanceId'], PARAM_INT, true);
$thread_id = clean_param($body['threadId'], PARAM_NOTAGS, true);

// Get the instance record
$instance = $DB->get_record('openai_chat', ['id' => $instance_id], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $instance->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('openai_chat', $instance->id, $course->id, false, MUST_EXIST);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

// Prepare instance settings
$instance_settings = [];
$setting_names = [
    'sourceoftruth', 
    'prompt',
    'instructions',
    'username', 
    'assistantname', 
    'apikey', 
    'model', 
    'temperature', 
    'maxlength', 
    'topp', 
    'frequency', 
    'presence',
    'assistant'
];
foreach ($setting_names as $setting) {
    if (property_exists($instance, $setting)) {
        $instance_settings[$setting] = $instance->$setting ? $instance->$setting : "";
    } else {
        $instance_settings[$setting] = "";
    }
}

$engine_class;
$model = get_config('mod_openai_chat', 'model');
$api_type = get_config('mod_openai_chat', 'type');
$engine_class = "\mod_openai_chat\completion\\$api_type";

$completion = new $engine_class(...[$model, $message, $history, $instance_settings, $thread_id]);
$response = $completion->create_completion($context);

// Format the markdown of each completion message into HTML.
$response["message"] = format_text($response["message"], FORMAT_MARKDOWN, ['context' => $context]);

// Log the message
log_message($instance_id, $message, $response['message'], $context);

$response = json_encode($response);
echo $response;