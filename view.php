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
 * Prints a particular instance of intebchat
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID
$n  = optional_param('n', 0, PARAM_INT);  // intebchat instance ID

if ($id) {
    $cm         = get_coursemodule_from_id('intebchat', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $intebchat  = $DB->get_record('intebchat', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $intebchat  = $DB->get_record('intebchat', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $intebchat->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('intebchat', $intebchat->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_intebchat\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $intebchat);
$event->trigger();

// Print the page header.
$PAGE->set_url('/mod/intebchat/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($intebchat->name));
$PAGE->set_heading(format_string($course->fullname));

// Check if API key is configured.
$config = get_config('mod_intebchat');
$apiconfig = intebchat_get_api_config($intebchat);
$apikey_configured = !empty($apiconfig['apikey']);

// Check token limit for current user
$token_limit_info = intebchat_check_token_limit($USER->id);

// Prepare data for JavaScript
$persistconvo = $intebchat->persistconvo && $config->allowinstancesettings ? $intebchat->persistconvo : $config->persistconvo;
$api_type = $config->type ?: 'chat'; // Always use global API type

// Pass data to JavaScript
$jsdata = [
    'instanceId' => $intebchat->id,
    'api_type' => $api_type,
    'persistConvo' => $persistconvo,
    'tokenLimitEnabled' => !empty($config->enabletokenlimit),
    'tokenLimit' => $token_limit_info['limit'],
    'tokensUsed' => $token_limit_info['used'],
    'tokenLimitExceeded' => !$token_limit_info['allowed'],
    'resetTime' => $token_limit_info['reset_time']
];

$PAGE->requires->js_call_amd('mod_intebchat/lib', 'init', [$jsdata]);

// Add professional CSS
$PAGE->requires->css('/mod/intebchat/styles.css');

// Output starts here
echo $OUTPUT->header();

// Show activity name and description
echo $OUTPUT->heading($intebchat->name);

if ($intebchat->intro) {
    echo $OUTPUT->box(format_module_intro('intebchat', $intebchat, $cm->id), 'generalbox mod_introbox', 'intebchatintro');
}

// Determine name labels visibility
$showlabelscss = '';
if (!$intebchat->showlabels) {
    $showlabelscss = '
        .openai_message:before {
            display: none;
        }
        .openai_message {
            margin-bottom: 0.5rem;
        }
    ';
}

// Get assistant and user names
$assistantname = $intebchat->assistantname ?: ($config->assistantname ?: get_string('defaultassistantname', 'mod_intebchat'));
// Use the logged-in user's first name
$username = $USER->firstname ?: get_string('defaultusername', 'mod_intebchat');

// Retrieve previous chat history for this user if logging and persistence are enabled
$historylogs = [];
if ($config->logging && $persistconvo && isloggedin()) {
    $historylogs = $DB->get_records('mod_intebchat_log', [
        'instanceid' => $intebchat->id,
        'userid' => $USER->id
    ], 'timecreated ASC');
}

$assistantname = format_string($assistantname, true, ['context' => $PAGE->context]);
$username = format_string($username, true, ['context' => $PAGE->context]);

// Chat interface HTML
?>
<div class="mod_intebchat" data-instance-id="<?php echo $intebchat->id; ?>">
    <script>
        var assistantName = "<?php echo addslashes($assistantname); ?>";
        var userName = "<?php echo addslashes($username); ?>";
    </script>

    <style>
        <?php echo $showlabelscss; ?>
        .openai_message.user:before {
            content: "<?php echo addslashes($username); ?>";
        }
        .openai_message.bot:before {
            content: "<?php echo addslashes($assistantname); ?>";
        }
    </style>

    <?php if (!$apikey_configured): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle"></i> <?php echo get_string('apikeymissing', 'mod_intebchat'); ?>
        </div>
    <?php elseif (!$token_limit_info['allowed']): ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-circle"></i> 
            <?php echo get_string('tokenlimitexceeded', 'mod_intebchat', [
                'used' => $token_limit_info['used'],
                'limit' => $token_limit_info['limit'],
                'reset' => userdate($token_limit_info['reset_time'])
            ]); ?>
        </div>
    <?php else: ?>
        <?php if (!empty($config->enabletokenlimit)): ?>
            <div class="token-usage-info">
                <div class="token-display">
                    <span class="token-label"><?php echo get_string('tokensused', 'mod_intebchat', [
                        'used' => $token_limit_info['used'],
                        'limit' => $token_limit_info['limit']
                    ]); ?></span>
                </div>
                <div class="progress">
                    <div class="progress-bar<?php 
                        $percentage = ($token_limit_info['used'] / $token_limit_info['limit'] * 100);
                        if ($percentage > 90) echo ' danger';
                        elseif ($percentage > 75) echo ' warning';
                    ?>" role="progressbar" 
                         style="width: <?php echo ($token_limit_info['used'] / $token_limit_info['limit'] * 100); ?>%"
                         aria-valuenow="<?php echo $token_limit_info['used']; ?>" 
                         aria-valuemin="0" 
                         aria-valuemax="<?php echo $token_limit_info['limit']; ?>">
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div id="intebchat_log" role="log" aria-live="polite">
            <?php foreach ($historylogs as $log): ?>
                <div class="openai_message user">
                    <span><?php echo s($log->usermessage); ?></span>
                    <span class="message-timestamp"><?php echo userdate($log->timecreated, '%H:%M'); ?></span>
                </div>
                <?php if (!empty($log->airesponse)) : ?>
                <div class="openai_message bot">
                    <span><?php echo format_text($log->airesponse, FORMAT_HTML, ['context' => $PAGE->context]); ?></span>
                    <span class="message-timestamp"><?php echo userdate($log->timecreated, '%H:%M'); ?></span>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div id="control_bar">
            <?php if ($config->logging): ?>
                <div class="logging-info">
                    <i class="fa fa-info-circle"></i> <?php echo get_string('loggingenabled', 'mod_intebchat'); ?>
                </div>
            <?php endif; ?>
            
            <div class="openai_input_bar" id="input_bar">
                <textarea aria-label="<?php echo get_string('askaquestion', 'mod_intebchat'); ?>" 
                          rows="2" 
                          id="openai_input" 
                          placeholder="<?php echo get_string('askaquestion', 'mod_intebchat'); ?>" 
                          name="message"
                          <?php echo !$token_limit_info['allowed'] ? 'disabled' : ''; ?>></textarea>
                <button class='openai_input_submit_btn btn btn-primary' 
                        title="<?php echo get_string('askaquestion', 'mod_intebchat'); ?>" 
                        id="go"
                        <?php echo !$token_limit_info['allowed'] ? 'disabled' : ''; ?>>
                    <i class="fa fa-paper-plane"></i>
                </button>
            </div>
            <button class='openai_input_refresh_btn btn btn-secondary' 
                    title="<?php echo get_string('new_chat', 'mod_intebchat'); ?>" 
                    id="refresh">
                <i class="fa fa-sync"></i>
            </button>
        </div>
    <?php endif; ?>
</div>

<?php
// Show report link if user has capability
if (has_capability('mod/intebchat:viewreport', context_module::instance($cm->id))) {
    echo '<div class="mt-3">';
    echo '<a href="' . new moodle_url('/mod/intebchat/report.php', array('id' => $cm->id)) . '" class="btn btn-info">';
    echo '<i class="fa fa-chart-bar"></i> ' . get_string('viewreport', 'mod_intebchat');
    echo '</a>';
    echo '</div>';
}

// Finish the page
echo $OUTPUT->footer();