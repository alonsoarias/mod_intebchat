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
 * The main openai_chat configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_openai_chat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/openai_chat/lib.php');

/**
 * Module instance settings form
 */
class mod_openai_chat_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $type = get_type_to_display();
        $config = get_config('mod_openai_chat');

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('openai_chatname', 'mod_openai_chat'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'openai_chatname', 'mod_openai_chat');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Chat settings.
        $mform->addElement('header', 'chatsettings', get_string('chatsettings', 'mod_openai_chat'));

        $mform->addElement('advcheckbox', 'showlabels', get_string('showlabels', 'mod_openai_chat'));
        $mform->setDefault('showlabels', 1);

        if ($type === 'assistant') {
            // Assistant settings.
            if ($config->allowinstancesettings) {
                // Get assistants using the global API key first.
                $globalkey = $config->apikey;
                $assistants = fetch_assistants_array($globalkey);
                
                $mform->addElement('select', 'assistant', get_string('assistant', 'mod_openai_chat'), $assistants);
                $mform->setDefault('assistant', $config->assistant);
                $mform->setType('assistant', PARAM_TEXT);
                $mform->addHelpButton('assistant', 'config_assistant', 'mod_openai_chat');

                $mform->addElement('advcheckbox', 'persistconvo', get_string('persistconvo', 'mod_openai_chat'));
                $mform->addHelpButton('persistconvo', 'config_persistconvo', 'mod_openai_chat');
                $mform->setDefault('persistconvo', 1);

                $mform->addElement('textarea', 'instructions', get_string('config_instructions', 'mod_openai_chat'), 
                    'rows="4" cols="50"');
                $mform->setDefault('instructions', '');
                $mform->setType('instructions', PARAM_TEXT);
                $mform->addHelpButton('instructions', 'config_instructions', 'mod_openai_chat');

                $mform->addElement('text', 'username', get_string('username', 'mod_openai_chat'));
                $mform->setDefault('username', '');
                $mform->setType('username', PARAM_TEXT);
                $mform->addHelpButton('username', 'config_username', 'mod_openai_chat');
        
                $mform->addElement('text', 'assistantname', get_string('assistantname', 'mod_openai_chat'));
                $mform->setDefault('assistantname', '');
                $mform->setType('assistantname', PARAM_TEXT);
                $mform->addHelpButton('assistantname', 'config_assistantname', 'mod_openai_chat');
            }
        } else {
            // Chat settings.
            $mform->addElement('textarea', 'sourceoftruth', get_string('sourceoftruth', 'mod_openai_chat'), 
                'rows="6" cols="50"');
            $mform->setDefault('sourceoftruth', '');
            $mform->setType('sourceoftruth', PARAM_TEXT);
            $mform->addHelpButton('sourceoftruth', 'config_sourceoftruth', 'mod_openai_chat');
    
            if ($config->allowinstancesettings) {
                $mform->addElement('textarea', 'prompt', get_string('prompt', 'mod_openai_chat'), 
                    'rows="4" cols="50"');
                $mform->setDefault('prompt', '');
                $mform->setType('prompt', PARAM_TEXT);
                $mform->addHelpButton('prompt', 'config_prompt', 'mod_openai_chat');
            
                $mform->addElement('text', 'username', get_string('username', 'mod_openai_chat'));
                $mform->setDefault('username', '');
                $mform->setType('username', PARAM_TEXT);
                $mform->addHelpButton('username', 'config_username', 'mod_openai_chat');
        
                $mform->addElement('text', 'assistantname', get_string('assistantname', 'mod_openai_chat'));
                $mform->setDefault('assistantname', '');
                $mform->setType('assistantname', PARAM_TEXT);
                $mform->addHelpButton('assistantname', 'config_assistantname', 'mod_openai_chat');
            }
        }

        // Advanced settings if allowed.
        if ($config->allowinstancesettings) {
            $mform->addElement('header', 'config_adv_header', get_string('advanced', 'mod_openai_chat'));

            $mform->addElement('text', 'apikey', get_string('apikey', 'mod_openai_chat'));
            $mform->setDefault('apikey', '');
            $mform->setType('apikey', PARAM_TEXT);
            $mform->addHelpButton('apikey', 'config_apikey', 'mod_openai_chat');

            if ($type !== 'assistant') {
                $mform->addElement('select', 'model', get_string('model', 'mod_openai_chat'), get_models()['models']);
                $mform->setDefault('model', $config->model);
                $mform->setType('model', PARAM_TEXT);
                $mform->addHelpButton('model', 'config_model', 'mod_openai_chat');

                $mform->addElement('text', 'temperature', get_string('temperature', 'mod_openai_chat'));
                $mform->setDefault('temperature', 0.5);
                $mform->setType('temperature', PARAM_FLOAT);
                $mform->addHelpButton('temperature', 'config_temperature', 'mod_openai_chat');
            
                $mform->addElement('text', 'maxlength', get_string('maxlength', 'mod_openai_chat'));
                $mform->setDefault('maxlength', 500);
                $mform->setType('maxlength', PARAM_INT);
                $mform->addHelpButton('maxlength', 'config_maxlength', 'mod_openai_chat');

                $mform->addElement('text', 'topp', get_string('topp', 'mod_openai_chat'));
                $mform->setDefault('topp', 1);
                $mform->setType('topp', PARAM_FLOAT);
                $mform->addHelpButton('topp', 'config_topp', 'mod_openai_chat');

                $mform->addElement('text', 'frequency', get_string('frequency', 'mod_openai_chat'));
                $mform->setDefault('frequency', 1);
                $mform->setType('frequency', PARAM_FLOAT);
                $mform->addHelpButton('frequency', 'config_frequency', 'mod_openai_chat');

                $mform->addElement('text', 'presence', get_string('presence', 'mod_openai_chat'));
                $mform->setDefault('presence', 1);
                $mform->setType('presence', PARAM_FLOAT);
                $mform->addHelpButton('presence', 'config_presence', 'mod_openai_chat');
            }
        }

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        $config = get_config('mod_openai_chat');
        
        // Check if API key is configured (either globally or instance level).
        if (empty($config->apikey) && empty($data['apikey'])) {
            $errors['apikey'] = get_string('apikeymissing', 'mod_openai_chat');
        }
        
        return $errors;
    }

    /**
     * Process data before displaying form
     *
     * @param array $default_values
     */
    public function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);
    }
}