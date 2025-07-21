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
 * Plugin settings
 *
 * @package    mod_openai_chat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2024 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Add admin report page.
    $ADMIN->add('reports', new admin_externalpage(
        'mod_openai_chat_report', 
        get_string('openai_chat_logs', 'mod_openai_chat'), 
        new moodle_url("$CFG->wwwroot/mod/openai_chat/adminreport.php"),
        'mod/openai_chat:viewallreports'
    ));
}

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot .'/mod/openai_chat/lib.php');

    $type = get_type_to_display();
    $assistant_array = [];
    if ($type === 'assistant') {
        $assistant_array = fetch_assistants_array();
    }

    global $PAGE;
    $PAGE->requires->js_call_amd('mod_openai_chat/settings', 'init');

    $settings->add(new admin_setting_configtext(
        'mod_openai_chat/apikey',
        get_string('apikey', 'mod_openai_chat'),
        get_string('apikeydesc', 'mod_openai_chat'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configselect(
        'mod_openai_chat/type',
        get_string('type', 'mod_openai_chat'),
        get_string('typedesc', 'mod_openai_chat'),
        'chat',
        ['chat' => 'chat', 'assistant' => 'assistant', 'azure' => 'azure']
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_openai_chat/restrictusage',
        get_string('restrictusage', 'mod_openai_chat'),
        get_string('restrictusagedesc', 'mod_openai_chat'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'mod_openai_chat/assistantname',
        get_string('assistantname', 'mod_openai_chat'),
        get_string('assistantnamedesc', 'mod_openai_chat'),
        'Assistant',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'mod_openai_chat/username',
        get_string('username', 'mod_openai_chat'),
        get_string('usernamedesc', 'mod_openai_chat'),
        'User',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_openai_chat/logging',
        get_string('logging', 'mod_openai_chat'),
        get_string('loggingdesc', 'mod_openai_chat'),
        0
    ));

    // Assistant settings.
    if ($type === 'assistant') {
        $settings->add(new admin_setting_heading(
            'mod_openai_chat/assistantheading',
            get_string('assistantheading', 'mod_openai_chat'),
            get_string('assistantheadingdesc', 'mod_openai_chat')
        ));

        if (count($assistant_array)) {
            $settings->add(new admin_setting_configselect(
                'mod_openai_chat/assistant',
                get_string('assistant', 'mod_openai_chat'),
                get_string('assistantdesc', 'mod_openai_chat'),
                count($assistant_array) ? reset($assistant_array) : null,
                $assistant_array
            ));
        } else {
            $settings->add(new admin_setting_description(
                'mod_openai_chat/noassistants',
                get_string('assistant', 'mod_openai_chat'),
                get_string('noassistants', 'mod_openai_chat'),
            ));
        }

        $settings->add(new admin_setting_configcheckbox(
            'mod_openai_chat/persistconvo',
            get_string('persistconvo', 'mod_openai_chat'),
            get_string('persistconvodesc', 'mod_openai_chat'),
            1
        ));

    } else {
        // Chat settings.
        if ($type === 'azure') {
            $settings->add(new admin_setting_heading(
                'mod_openai_chat/azureheading',
                get_string('azureheading', 'mod_openai_chat'),
                get_string('azureheadingdesc', 'mod_openai_chat')
            ));

            $settings->add(new admin_setting_configtext(
                'mod_openai_chat/resourcename',
                get_string('resourcename', 'mod_openai_chat'),
                get_string('resourcenamedesc', 'mod_openai_chat'),
                "",
                PARAM_TEXT
            ));

            $settings->add(new admin_setting_configtext(
                'mod_openai_chat/deploymentid',
                get_string('deploymentid', 'mod_openai_chat'),
                get_string('deploymentiddesc', 'mod_openai_chat'),
                "",
                PARAM_TEXT
            ));

            $settings->add(new admin_setting_configtext(
                'mod_openai_chat/apiversion',
                get_string('apiversion', 'mod_openai_chat'),
                get_string('apiversiondesc', 'mod_openai_chat'),
                "2023-09-01-preview",
                PARAM_TEXT
            ));
        }

        $settings->add(new admin_setting_heading(
            'mod_openai_chat/chatheading',
            get_string('chatheading', 'mod_openai_chat'),
            get_string('chatheadingdesc', 'mod_openai_chat')
        ));

        $settings->add(new admin_setting_configtextarea(
            'mod_openai_chat/prompt',
            get_string('prompt', 'mod_openai_chat'),
            get_string('promptdesc', 'mod_openai_chat'),
            "Below is a conversation between a user and a support assistant for a Moodle site, where users go for online learning.",
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtextarea(
            'mod_openai_chat/sourceoftruth',
            get_string('sourceoftruth', 'mod_openai_chat'),
            get_string('sourceoftruthdesc', 'mod_openai_chat'),
            '',
            PARAM_TEXT
        ));
    }

    // Advanced Settings.
    $settings->add(new admin_setting_heading(
        'mod_openai_chat/advanced',
        get_string('advanced', 'mod_openai_chat'),
        get_string('advanceddesc', 'mod_openai_chat')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_openai_chat/allowinstancesettings',
        get_string('allowinstancesettings', 'mod_openai_chat'),
        get_string('allowinstancesettingsdesc', 'mod_openai_chat'),
        0
    ));

    if ($type === 'assistant') {
        // No additional advanced settings for assistant.
    } else {
        $settings->add(new admin_setting_configselect(
            'mod_openai_chat/model',
            get_string('model', 'mod_openai_chat'),
            get_string('modeldesc', 'mod_openai_chat'),
            'text-davinci-003',
            get_models()['models']
        ));

        $settings->add(new admin_setting_configtext(
            'mod_openai_chat/temperature',
            get_string('temperature', 'mod_openai_chat'),
            get_string('temperaturedesc', 'mod_openai_chat'),
            0.5,
            PARAM_FLOAT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_openai_chat/maxlength',
            get_string('maxlength', 'mod_openai_chat'),
            get_string('maxlengthdesc', 'mod_openai_chat'),
            500,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_openai_chat/topp',
            get_string('topp', 'mod_openai_chat'),
            get_string('toppdesc', 'mod_openai_chat'),
            1,
            PARAM_FLOAT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_openai_chat/frequency',
            get_string('frequency', 'mod_openai_chat'),
            get_string('frequencydesc', 'mod_openai_chat'),
            1,
            PARAM_FLOAT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_openai_chat/presence',
            get_string('presence', 'mod_openai_chat'),
            get_string('presencedesc', 'mod_openai_chat'),
            1,
            PARAM_FLOAT
        ));
    }
}