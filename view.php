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
 * Prints a particular instance of openai_chat
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_openai_chat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... openai_chat instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('openai_chat', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $openai_chat  = $DB->get_record('openai_chat', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $openai_chat  = $DB->get_record('openai_chat', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $openai_chat->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('openai_chat', $openai_chat->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_openai_chat\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $openai_chat);
$event->trigger();

// Print the page header.
$PAGE->set_url('/mod/openai_chat/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($openai_chat->name));
$PAGE->set_heading(format_string($course->fullname));

// Check if API key is configured.
$apikey_configured = false;
$config = get_config('mod_openai_chat');
if (!empty($config->apikey) || (!empty($openai_chat->apikey) && $config->allowinstancesettings)) {
    $apikey_configured = true;
}

// Prepare data for JavaScript.
$persistconvo = $config->persistconvo;
if (!empty($openai_chat->persistconvo) && $config->allowinstancesettings) {
    $persistconvo = $openai_chat->persistconvo;
}

$api_type = $config->type ? $config->type : 'chat';

// Pass data to JavaScript.
$PAGE->requires->js_call_amd('mod_openai_chat/lib', 'init', [[
    'instanceId' => $openai_chat->id,
    'api_type' => $api_type,
    'persistConvo' => $persistconvo
]]);

// Output starts here.
echo $OUTPUT->header();

// Show activity name and description.
echo $OUTPUT->heading($openai_chat->name);

if ($openai_chat->intro) {
    echo $OUTPUT->box(format_module_intro('openai_chat', $openai_chat, $cm->id), 'generalbox mod_introbox', 'openai_chatintro');
}

// Determine name labels.
$showlabelscss = '';
if (!$openai_chat->showlabels) {
    $showlabelscss = '
        .openai_message:before {
            display: none;
        }
        .openai_message {
            margin-bottom: 0.5rem;
        }
    ';
}

// Get assistant and user names.
$assistantname = $config->assistantname ? $config->assistantname : get_string('defaultassistantname', 'mod_openai_chat');
$username = $config->username ? $config->username : get_string('defaultusername', 'mod_openai_chat');

// Override with instance settings if available.
if ($config->allowinstancesettings) {
    if (!empty($openai_chat->assistantname)) {
        $assistantname = $openai_chat->assistantname;
    }
    if (!empty($openai_chat->username)) {
        $username = $openai_chat->username;
    }
}

$assistantname = format_string($assistantname, true, ['context' => $PAGE->context]);
$username = format_string($username, true, ['context' => $PAGE->context]);

// Chat interface HTML.
?>
<div class="mod_openai_chat" data-instance-id="<?php echo $openai_chat->id; ?>">
    <script>
        var assistantName = "<?php echo $assistantname; ?>";
        var userName = "<?php echo $username; ?>";
    </script>

    <style>
        <?php echo $showlabelscss; ?>
        .openai_message.user:before {
            content: "<?php echo $username; ?>";
        }
        .openai_message.bot:before {
            content: "<?php echo $assistantname; ?>";
        }
    </style>

    <?php if ($apikey_configured): ?>
        <div id="openai_chat_log" role="log"></div>
        <div id="control_bar">
            <?php if ($config->logging): ?>
                <div class="alert alert-info">
                    <?php echo get_string('loggingenabled', 'mod_openai_chat'); ?>
                </div>
            <?php endif; ?>
            <div class="openai_input_bar" id="input_bar">
                <textarea aria-label="<?php echo get_string('askaquestion', 'mod_openai_chat'); ?>" 
                          rows="1" 
                          id="openai_input" 
                          placeholder="<?php echo get_string('askaquestion', 'mod_openai_chat'); ?>" 
                          type="text" 
                          name="message"></textarea>
                <button class='openai_input_submit_btn btn btn-primary' 
                        title="<?php echo get_string('askaquestion', 'mod_openai_chat'); ?>" 
                        id="go">
                    <i class="fa fa-paper-plane"></i>
                </button>
            </div>
            <button class='openai_input_refresh_btn btn btn-secondary' 
                    title="<?php echo get_string('new_chat', 'mod_openai_chat'); ?>" 
                    id="refresh">
                <i class="fa fa-sync"></i>
            </button>
        </div>
    <?php else: ?>
        <div class="alert alert-danger">
            <?php echo get_string('apikeymissing', 'mod_openai_chat'); ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Finish the page.
echo $OUTPUT->footer();