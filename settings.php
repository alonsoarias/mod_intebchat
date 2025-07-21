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
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2024 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Add admin report page.
    $ADMIN->add('reports', new admin_externalpage(
        'mod_intebchat_report', 
        get_string('intebchat_logs', 'mod_intebchat'), 
        new moodle_url("$CFG->wwwroot/mod/intebchat/adminreport.php"),
        'mod/intebchat:viewallreports'
    ));
}

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot .'/mod/intebchat/lib.php');

    $type = get_type_to_display();
    $assistant_array = [];
    if ($type === 'assistant') {
        $assistant_array = fetch_assistants_array();
    }

    global $PAGE;
    $PAGE->requires->js_call_amd('mod_intebchat/settings', 'init');

    $settings->add(new admin_setting_configtext(
        'mod_intebchat/apikey',
        get_string('apikey', 'mod_intebchat'),
        get_string('apikeydesc', 'mod_intebchat'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configselect(
        'mod_intebchat/type',
        get_string('type', 'mod_intebchat'),
        get_string('typedesc', 'mod_intebchat'),
        'chat',
        ['chat' => 'chat', 'assistant' => 'assistant', 'azure' => 'azure']
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_intebchat/restrictusage',
        get_string('restrictusage', 'mod_intebchat'),
        get_string('restrictusagedesc', 'mod_intebchat'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'mod_intebchat/assistantname',
        get_string('assistantname', 'mod_intebchat'),
        get_string('assistantnamedesc', 'mod_intebchat'),
        'Assistant',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'mod_intebchat/username',
        get_string('username', 'mod_intebchat'),
        get_string('usernamedesc', 'mod_intebchat'),
        'User',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_intebchat/logging',
        get_string('logging', 'mod_intebchat'),
        get_string('loggingdesc', 'mod_intebchat'),
        0
    ));

    // Assistant settings.
    if ($type === 'assistant') {
        $settings->add(new admin_setting_heading(
            'mod_intebchat/assistantheading',
            get_string('assistantheading', 'mod_intebchat'),
            get_string('assistantheadingdesc', 'mod_intebchat')
        ));

        if (count($assistant_array)) {
            $settings->add(new admin_setting_configselect(
                'mod_intebchat/assistant',
                get_string('assistant', 'mod_intebchat'),
                get_string('assistantdesc', 'mod_intebchat'),
                count($assistant_array) ? reset($assistant_array) : null,
                $assistant_array
            ));
        } else {
            $settings->add(new admin_setting_description(
                'mod_intebchat/noassistants',
                get_string('assistant', 'mod_intebchat'),
                get_string('noassistants', 'mod_intebchat'),
            ));
        }

        $settings->add(new admin_setting_configcheckbox(
            'mod_intebchat/persistconvo',
            get_string('persistconvo', 'mod_intebchat'),
            get_string('persistconvodesc', 'mod_intebchat'),
            1
        ));

    } else {
        // Chat settings.
        if ($type === 'azure') {
            $settings->add(new admin_setting_heading(
                'mod_intebchat/azureheading',
                get_string('azureheading', 'mod_intebchat'),
                get_string('azureheadingdesc', 'mod_intebchat')
            ));

            $settings->add(new admin_setting_configtext(
                'mod_intebchat/resourcename',
                get_string('resourcename', 'mod_intebchat'),
                get_string('resourcenamedesc', 'mod_intebchat'),
                "",
                PARAM_TEXT
            ));

            $settings->add(new admin_setting_configtext(
                'mod_intebchat/deploymentid',
                get_string('deploymentid', 'mod_intebchat'),
                get_string('deploymentiddesc', 'mod_intebchat'),
                "",
                PARAM_TEXT
            ));

            $settings->add(new admin_setting_configtext(
                'mod_intebchat/apiversion',
                get_string('apiversion', 'mod_intebchat'),
                get_string('apiversiondesc', 'mod_intebchat'),
                "2023-09-01-preview",
                PARAM_TEXT
            ));
        }

        $settings->add(new admin_setting_heading(
            'mod_intebchat/chatheading',
            get_string('chatheading', 'mod_intebchat'),
            get_string('chatheadingdesc', 'mod_intebchat')
        ));

        $settings->add(new admin_setting_configtextarea(
            'mod_intebchat/prompt',
            get_string('prompt', 'mod_intebchat'),
            get_string('promptdesc', 'mod_intebchat'),
            "Below is a conversation between a user and a support assistant for a Moodle site, where users go for online learning.",
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtextarea(
            'mod_intebchat/sourceoftruth',
            get_string('sourceoftruth', 'mod_intebchat'),
            get_string('sourceoftruthdesc', 'mod_intebchat'),
            '',
            PARAM_TEXT
        ));
    }

    // Advanced Settings.
    $settings->add(new admin_setting_heading(
        'mod_intebchat/advanced',
        get_string('advanced', 'mod_intebchat'),
        get_string('advanceddesc', 'mod_intebchat')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_intebchat/allowinstancesettings',
        get_string('allowinstancesettings', 'mod_intebchat'),
        get_string('allowinstancesettingsdesc', 'mod_intebchat'),
        0
    ));

    if ($type === 'assistant') {
        // No additional advanced settings for assistant.
    } else {
        $settings->add(new admin_setting_configselect(
            'mod_intebchat/model',
            get_string('model', 'mod_intebchat'),
            get_string('modeldesc', 'mod_intebchat'),
            'text-davinci-003',
            get_models()['models']
        ));

        $settings->add(new admin_setting_configtext(
            'mod_intebchat/temperature',
            get_string('temperature', 'mod_intebchat'),
            get_string('temperaturedesc', 'mod_intebchat'),
            0.5,
            PARAM_FLOAT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_intebchat/maxlength',
            get_string('maxlength', 'mod_intebchat'),
            get_string('maxlengthdesc', 'mod_intebchat'),
            500,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_intebchat/topp',
            get_string('topp', 'mod_intebchat'),
            get_string('toppdesc', 'mod_intebchat'),
            1,
            PARAM_FLOAT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_intebchat/frequency',
            get_string('frequency', 'mod_intebchat'),
            get_string('frequencydesc', 'mod_intebchat'),
            1,
            PARAM_FLOAT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_intebchat/presence',
            get_string('presence', 'mod_intebchat'),
            get_string('presencedesc', 'mod_intebchat'),
            1,
            PARAM_FLOAT
        ));
    }
}