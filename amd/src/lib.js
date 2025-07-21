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
 * Main JavaScript for OpenAI Chat module
 *
 * @module     mod_openai_chat/lib
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/str', 'core/notification'], function($, Ajax, Str, Notification) {
    var questionString = 'Ask a question...';
    var errorString = 'An error occurred! Please try again later.';
    var chatData = {};

    /**
     * Initialize the module
     * @param {Object} data Configuration data
     */
    var init = function(data) {
        var instanceId = data.instanceId;
        var api_type = data.api_type;
        var persistConvo = data.persistConvo;

        // Initialize local data storage if necessary
        if (api_type === 'assistant') {
            chatData = localStorage.getItem("mod_openai_chat_data");
            if (chatData) {
                chatData = JSON.parse(chatData);
                if (chatData[instanceId] && chatData[instanceId]['threadId'] && persistConvo === "1") {
                    $.ajax({
                        url: M.cfg.wwwroot + '/mod/openai_chat/api/thread.php?thread_id=' + chatData[instanceId]['threadId'],
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            for (var i = 0; i < data.length; i++) {
                                addToChatLog(data[i].role === 'user' ? 'user' : 'bot', data[i].message, instanceId);
                            }
                        },
                        error: function() {
                            // Reset thread if error
                            chatData[instanceId] = {};
                            localStorage.setItem("mod_openai_chat_data", JSON.stringify(chatData));
                        }
                    });
                } else {
                    chatData[instanceId] = {};
                }
            } else {
                chatData = {[instanceId]: {}};
            }
            localStorage.setItem("mod_openai_chat_data", JSON.stringify(chatData));
        }

        // Event listeners
        $(document).on('keyup', '.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_input', function(e) {
            if (e.which === 13 && e.target.value !== "") {
                addToChatLog('user', e.target.value, instanceId);
                createCompletion(e.target.value, instanceId, api_type);
                e.target.value = '';
            }
        });

        $(document).on('click', '.mod_openai_chat[data-instance-id="' + instanceId + '"] #go', function(e) {
            var input = $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_input');
            if (input.val() !== "") {
                addToChatLog('user', input.val(), instanceId);
                createCompletion(input.val(), instanceId, api_type);
                input.val('');
            }
        });

        $(document).on('click', '.mod_openai_chat[data-instance-id="' + instanceId + '"] #refresh', function(e) {
            clearHistory(instanceId);
        });

        // Load strings
        var strings = [
            {
                key: 'askaquestion',
                component: 'mod_openai_chat'
            },
            {
                key: 'erroroccurred',
                component: 'mod_openai_chat'
            },
        ];
        Str.get_strings(strings).then(function(results) {
            questionString = results[0];
            errorString = results[1];
        });
    };

    /**
     * Add a message to the chat UI
     * @param {string} type Which side of the UI the message should be on. Can be "user" or "bot"
     * @param {string} message The text of the message to add
     * @param {int} instanceId The ID of the instance to manipulate
     */
    var addToChatLog = function(type, message, instanceId) {
        var messageContainer = $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_chat_log');
        
        var messageElem = $('<div></div>').addClass('openai_message').addClass(type);
        var messageText = $('<span></span>').html(message);
        messageElem.append(messageText);

        messageContainer.append(messageElem);
        
        if (messageText.width()) {
            messageElem.css('width', (messageText.width() + 40) + "px");
        }
        
        messageContainer.scrollTop(messageContainer[0].scrollHeight);
    };

    /**
     * Clears the thread ID from local storage and removes the messages from the UI
     * @param {int} instanceId
     */
    var clearHistory = function(instanceId) {
        chatData = localStorage.getItem("mod_openai_chat_data");
        if (chatData) {
            chatData = JSON.parse(chatData);
            if (chatData[instanceId]) {
                chatData[instanceId] = {};
                localStorage.setItem("mod_openai_chat_data", JSON.stringify(chatData));
            }
        }
        $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_chat_log').html("");
    };

    /**
     * Makes an API request to get a completion from GPT
     * @param {string} message The text to get a completion for
     * @param {int} instanceId The ID of the instance
     * @param {string} api_type "assistant" | "chat" The type of API to use
     */
    var createCompletion = function(message, instanceId, api_type) {
        var threadId = null;
        
        // If the type is assistant, attempt to fetch a thread ID
        if (api_type === 'assistant') {
            chatData = localStorage.getItem("mod_openai_chat_data");
            if (chatData) {
                chatData = JSON.parse(chatData);
                if (chatData[instanceId]) {
                    threadId = chatData[instanceId]['threadId'] || null;
                }
            } else {
                chatData = {[instanceId]: {}};
            }
        }  

        var history = buildTranscript(instanceId);

        $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #control_bar').addClass('disabled');
        $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_input').removeClass('error');
        $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_input').attr('placeholder', questionString);
        $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_input').blur();
        addToChatLog('bot loading', '...', instanceId);

        $.ajax({
            url: M.cfg.wwwroot + '/mod/openai_chat/api/completion.php',
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                message: message,
                history: history,
                instanceId: instanceId,
                threadId: threadId
            }),
            success: function(data) {
                var messageContainer = $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_chat_log');
                messageContainer.children().last().remove();
                $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');

                if (data.message) {
                    addToChatLog('bot', data.message, instanceId);
                    if (data.thread_id) {
                        chatData[instanceId]['threadId'] = data.thread_id;
                        localStorage.setItem("mod_openai_chat_data", JSON.stringify(chatData));
                    }
                } else if (data.error) {
                    addToChatLog('bot', data.error.message, instanceId);
                }
                $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_input').focus();
            },
            error: function(xhr, status, error) {
                var messageContainer = $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_chat_log');
                messageContainer.children().last().remove();
                $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');
                $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_input').addClass('error');
                $('.mod_openai_chat[data-instance-id="' + instanceId + '"] #openai_input').attr('placeholder', errorString);
            }
        });
    };

    /**
     * Using the existing messages in the chat history, create a string that can be used to aid completion
     * @param {int} instanceId The instance from which to build the history
     * @return {Array} A transcript of the conversation up to this point
     */
    var buildTranscript = function(instanceId) {
        var transcript = [];
        $('.mod_openai_chat[data-instance-id="' + instanceId + '"] .openai_message').each(function(index, element) {
            var messages = $('.mod_openai_chat[data-instance-id="' + instanceId + '"] .openai_message');
            if (index === messages.length - 1) {
                return;
            }

            var user = userName;
            if ($(element).hasClass('bot')) {
                user = assistantName;
            }
            transcript.push({"user": user, "message": $(element).text()});
        });

        return transcript;
    };

    return {
        init: init
    };
});