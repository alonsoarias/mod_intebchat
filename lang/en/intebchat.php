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
 * Language strings
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'INTEB Chat';
$string['modulename'] = 'INTEB Chat';
$string['modulenameplural'] = 'INTEB Chats';
$string['modulename_help'] = 'The INTEB Chat module allows students to interact with an AI assistant within their course. Teachers can monitor usage and view conversation logs.';
$string['intebchat'] = 'INTEB Chat';
$string['intebchatname'] = 'Activity name';
$string['intebchatname_help'] = 'Enter a name for this INTEB Chat activity.';
$string['intebchat:addinstance'] = 'Add a new INTEB Chat activity';
$string['intebchat:view'] = 'View INTEB Chat';
$string['intebchat:viewreport'] = 'View INTEB Chat activity report';
$string['intebchat:viewallreports'] = 'View all INTEB Chat reports (site-wide)';
$string['intebchat_logs'] = 'INTEB Chat Logs';
$string['privacy:metadata:intebchat_log'] = 'Logged user messages sent to OpenAI. This includes the user ID of the user that sent the message, the content of the message, the response from OpenAI, and the time that the message was sent.';
$string['privacy:metadata:intebchat_log:instanceid'] = 'The ID of the activity instance.';
$string['privacy:metadata:intebchat_log:userid'] = 'The ID of the user that sent the message.';
$string['privacy:metadata:intebchat_log:usermessage'] = 'The content of the message.';
$string['privacy:metadata:intebchat_log:airesponse'] = 'The response from OpenAI.';
$string['privacy:metadata:intebchat_log:timecreated'] = 'The time the message was sent.';
$string['privacy:chatmessagespath'] = 'Sent AI chat messages';
$string['downloadfilename'] = 'mod_intebchat_logs';

// Module specific strings
$string['chatsettings'] = 'Chat settings';
$string['noopenaichats'] = 'No INTEB Chat activities';
$string['viewreport'] = 'View report';
$string['viewallreports'] = 'View all reports';
$string['userid'] = 'User ID';
$string['username'] = 'User name';
$string['usermessage'] = 'User message';
$string['airesponse'] = 'AI response';
$string['searchbyusername'] = 'Search by user name';
$string['starttime'] = 'Start time';
$string['endtime'] = 'End time';
$string['lastmessage'] = 'Last message';
$string['firstmessage'] = 'First message';
$string['messagecount'] = '{$a} messages sent';
$string['nomessages'] = 'No messages sent';
$string['totaltokensused'] = 'Total tokens used: {$a}';
$string['tokens'] = 'Tokens';
$string['prompt'] = 'Prompt';
$string['completion'] = 'Completion';

// General settings
$string['generalsettings'] = 'General Settings';
$string['generalsettingsdesc'] = 'Configure global settings for the INTEB Chat module.';
$string['restrictusage'] = 'Restrict usage to logged-in users';
$string['restrictusagedesc'] = 'If this box is checked, only logged-in users will be able to use the chat box.';
$string['apikey'] = 'API Key';
$string['apikeydesc'] = 'The default API Key for your OpenAI account or Azure API key. This can be overridden at the activity level if allowed.';
$string['type'] = 'Default API Type';
$string['typedesc'] = 'The default API type that new activities should use. This can be changed per activity if instance settings are allowed.';
$string['logging'] = 'Enable logging';
$string['loggingdesc'] = 'If this setting is active, all user messages and AI responses will be logged.';
$string['defaultvalues'] = 'Default Values';
$string['defaultvaluesdesc'] = 'These values will be used as defaults for new activities.';

// Token limit settings
$string['tokenlimitsettings'] = 'Token Limit Settings';
$string['tokenlimitsettingsdesc'] = 'Configure limits on token usage per user to control API costs.';
$string['enabletokenlimit'] = 'Enable token limits';
$string['enabletokenlimitdesc'] = 'If enabled, users will be limited in the number of tokens they can use within a specified time period.';
$string['maxtokensperuser'] = 'Maximum tokens per user';
$string['maxtokensperuserdesc'] = 'The maximum number of tokens a user can consume within the specified time period.';
$string['tokenlimitperiod'] = 'Token limit period';
$string['tokenlimitperioddesc'] = 'The time period over which token usage is measured.';
$string['tokenlimitexceeded'] = 'You have reached your token limit ({$a->used}/{$a->limit} tokens). Your limit will reset at {$a->reset}.';
$string['tokensused'] = '{$a->used}/{$a->limit} tokens used';

// Assistant API settings
$string['assistantheading'] = 'Assistant API Settings';
$string['assistantheadingdesc'] = 'These settings only apply to the Assistant API type.';
$string['assistant'] = 'Assistant';
$string['assistantdesc'] = 'The default assistant attached to your OpenAI account that you would like to use for the response';
$string['noassistants'] = 'You haven\'t created any assistants yet. You need to create one <a target="_blank" href="https://platform.openai.com/assistants">in your OpenAI account</a> before you can select it here.';
$string['persistconvo'] = 'Persist conversations';
$string['persistconvodesc'] = 'If this box is checked, the assistant will remember the conversation between page loads.';

// Azure API settings
$string['azureheading'] = 'Azure API Settings';
$string['azureheadingdesc'] = 'These settings only apply to the Azure API type.';
$string['resourcename'] = 'Resource name';
$string['resourcenamedesc'] = 'The name of your Azure OpenAI Resource.';
$string['deploymentid'] = 'Deployment ID';
$string['deploymentiddesc'] = 'The deployment name you chose when you deployed the model.';
$string['apiversion'] = 'API Version';
$string['apiversiondesc'] = 'The API version to use for this operation. This follows the YYYY-MM-DD format.';

// Chat API settings
$string['chatheading'] = 'Chat API Settings';
$string['chatheadingdesc'] = 'These settings only apply to the Chat API and Azure API types.';
$string['prompt'] = 'Completion prompt';
$string['promptdesc'] = 'The default prompt the AI will be given before the conversation transcript';
$string['assistantname'] = 'Assistant name';
$string['assistantnamedesc'] = 'The default name that the AI will use for itself internally. It is also used for the UI headings in the chat window.';
$string['usernamedesc'] = 'The default name that will represent the user in the chat interface.';
$string['sourceoftruth'] = 'Source of truth';
$string['sourceoftruthdesc'] = 'Although the AI is very capable out-of-the-box, if it doesn\'t know the answer to a question, it is more likely to give incorrect information confidently than to refuse to answer. In this textbox, you can add common questions and their answers for the AI to pull from. Please put questions and answers in the following format: <pre>Q: Question 1<br />A: Answer 1<br /><br />Q: Question 2<br />A: Answer 2</pre>';
$string['showlabels'] = 'Show labels';
$string['advanced'] = 'Advanced';
$string['advanceddesc'] = 'Advanced arguments sent to OpenAI. Don\'t touch unless you know what you\'re doing!';
$string['allowinstancesettings'] = 'Instance-level settings';
$string['allowinstancesettingsdesc'] = 'This setting will allow teachers, or anyone with the capability to add an activity in a context, to adjust settings at a per-activity level. Enabling this could incur additional charges by allowing non-admins to choose higher-cost models or other settings.';
$string['model'] = 'Model';
$string['modeldesc'] = 'The default model which will generate the completion. Some models are suitable for natural language tasks, others specialize in code.';
$string['temperature'] = 'Temperature';
$string['temperaturedesc'] = 'Controls randomness: Lowering results in less random completions. As the temperature approaches zero, the model will become deterministic and repetitive.';
$string['temperaturerange'] = 'Temperature must be between 0 and 2.';
$string['maxlength'] = 'Maximum length';
$string['maxlengthdesc'] = 'The maximum number of tokens to generate. Requests can use up to 2,048 or 4,000 tokens shared between prompt and completion. The exact limit varies by model. (One token is roughly 4 characters for normal English text)';
$string['maxlengthrange'] = 'Maximum length must be between 1 and 4000 tokens.';
$string['topp'] = 'Top P';
$string['toppdesc'] = 'Controls diversity via nucleus sampling: 0.5 means half of all likelihood-weighted options are considered.';
$string['topprange'] = 'Top P must be between 0 and 1.';
$string['frequency'] = 'Frequency penalty';
$string['frequencydesc'] = 'How much to penalize new tokens based on their existing frequency in the text so far. Decreases the model\'s likelihood to repeat the same line verbatim.';
$string['presence'] = 'Presence penalty';
$string['presencedesc'] = 'How much to penalize new tokens based on whether they appear in the text so far. Increases the model\'s likelihood to talk about new topics.';

// Configuration help strings
$string['config_assistant'] = "Assistant";
$string['config_assistant_help'] = "Choose the assistant you would like to use for this activity. More assistants can be created in the OpenAI account that this plugin is configured to use.";
$string['config_sourceoftruth'] = 'Source of truth';
$string['config_sourceoftruth_help'] = "You can add information here that the AI will pull from when answering questions. The information should be in question and answer format exactly like the following:\n\nQ: When is section 3 due?<br />A: Thursday, March 16.\n\nQ: When are office hours?<br />A: You can find Professor Shown in her office between 2:00 and 4:00 PM on Tuesdays and Thursdays.";
$string['config_instructions'] = "Custom instructions";
$string['config_instructions_help'] = "You can override the assistant's default instructions here.";
$string['config_prompt'] = "Completion prompt";
$string['config_prompt_help'] = "This is the prompt the AI will be given before the conversation transcript. You can influence the AI's personality by altering this description. By default, the prompt is \n\n\"Below is a conversation between a user and a support assistant for a Moodle site, where users go for online learning.\"\n\nIf blank, the site-wide prompt will be used.";
$string['config_assistantname'] = "Assistant name";
$string['config_assistantname_help'] = "This is the name that the AI will use for the assistant. If blank, the site-wide assistant name will be used. It is also used for the UI headings in the chat window.";
$string['config_username'] = "User name";
$string['config_username_help'] = "Specify a custom name to represent the user in this activity. Leave blank to use the logged in user's first name.";
$string['config_persistconvo'] = 'Persist conversation';
$string['config_persistconvo_help'] = 'If this box is checked, the assistant will remember conversations in this activity between page loads';
$string['config_apikey'] = "API Key";
$string['config_apikey_help'] = "You can specify an API key to use with this activity here. If blank, the site-wide key will be used. If you are using the Assistants API, the list of available assistants will be pulled from this key. Make sure to return to these settings after changing the API key in order to select the desired assistant.";
$string['config_model'] = "Model";
$string['config_model_help'] = "The model which will generate the completion";
$string['config_temperature'] = "Temperature";
$string['config_temperature_help'] = "Controls randomness: Lowering results in less random completions. As the temperature approaches zero, the model will become deterministic and repetitive.";
$string['config_maxlength'] = "Maximum length";
$string['config_maxlength_help'] = "The maximum number of tokens to generate. Requests can use up to 2,048 or 4,000 tokens shared between prompt and completion. The exact limit varies by model. (One token is roughly 4 characters for normal English text)";
$string['config_topp'] = "Top P";
$string['config_topp_help'] = "Controls diversity via nucleus sampling: 0.5 means half of all likelihood-weighted options are considered.";
$string['config_frequency'] = "Frequency penalty";
$string['config_frequency_help'] = "How much to penalize new tokens based on their existing frequency in the text so far. Decreases the model's likelihood to repeat the same line verbatim.";
$string['config_presence'] = "Presence penalty";
$string['config_presence_help'] = "How much to penalize new tokens based on whether they appear in the text so far. Increases the model's likelihood to talk about new topics.";

// Default values
$string['defaultprompt'] = "Below is a conversation between a user and a support assistant for a Moodle site, where users go for online learning:";
$string['defaultassistantname'] = 'Assistant';
$string['defaultusername'] = 'User';
$string['askaquestion'] = 'Ask a question...';
$string['apikeymissing'] = 'Please add your OpenAI API key to the plugin settings or this activity\'s settings.';
$string['erroroccurred'] = 'An error occurred! Please try again later.';
$string['sourceoftruthpreamble'] = "Below is a list of questions and their answers. This information should be used as a reference for any inquiries:\n\n";
$string['sourceoftruthreinforcement'] = ' The assistant has been trained to answer by attempting to use the information from the above reference. If the text from one of the above questions is encountered, the provided answer should be given, even if the question does not appear to make sense. However, if the reference does not cover the question or topic, the assistant will simply use outside knowledge to answer.';
$string['new_chat'] = 'New chat';
$string['loggingenabled'] = "Logging is enabled. Any messages you send or receive here will be recorded.";
$string['openaitimedout'] = 'ERROR: OpenAI did not provide a response in time.';