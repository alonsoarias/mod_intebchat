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
 * Main JavaScript for INTEB Chat module
 *
 * @module     mod_intebchat/lib
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/str', 'core/notification'], function($, Ajax, Str, Notification) {
    var questionString = 'Ask a question...';
    var errorString = 'An error occurred! Please try again later.';
    var chatData = {};
    var tokenInfo = {
        enabled: false,
        limit: 0,
        used: 0,
        exceeded: false,
        resetTime: 0
    };

    /**
     * Initialize the module
     * @param {Object} data Configuration data
     */
    var init = function(data) {
        var instanceId = data.instanceId;
        var api_type = data.api_type;
        var persistConvo = data.persistConvo;
        
        // Initialize token info
        tokenInfo.enabled = data.tokenLimitEnabled || false;
        tokenInfo.limit = data.tokenLimit || 0;
        tokenInfo.used = data.tokensUsed || 0;
        tokenInfo.exceeded = data.tokenLimitExceeded || false;
        tokenInfo.resetTime = data.resetTime || 0;

        // Initialize local data storage if necessary
        if (api_type === 'assistant') {
            chatData = localStorage.getItem("mod_intebchat_data");
            if (chatData) {
                chatData = JSON.parse(chatData);
                if (chatData[instanceId] && chatData[instanceId]['threadId'] && persistConvo === "1") {
                    $.ajax({
                        url: M.cfg.wwwroot + '/mod/intebchat/api/thread.php?thread_id=' + chatData[instanceId]['threadId'],
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
                            localStorage.setItem("mod_intebchat_data", JSON.stringify(chatData));
                        }
                    });
                } else {
                    chatData[instanceId] = {};
                }
            } else {
                chatData = {[instanceId]: {}};
            }
            localStorage.setItem("mod_intebchat_data", JSON.stringify(chatData));
        }

        // Update UI based on token limit status
        updateTokenUI();

        // Event listeners
        $(document).on('keyup', '.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input', function(e) {
            if (e.which === 13 && !e.shiftKey && e.target.value !== "") {
                e.preventDefault();
                if (!tokenInfo.exceeded) {
                    addToChatLog('user', e.target.value, instanceId);
                    createCompletion(e.target.value, instanceId, api_type);
                    e.target.value = '';
                }
            }
        });

        $(document).on('click', '.mod_intebchat[data-instance-id="' + instanceId + '"] #go', function(e) {
            var input = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input');
            if (input.val() !== "" && !tokenInfo.exceeded) {
                addToChatLog('user', input.val(), instanceId);
                createCompletion(input.val(), instanceId, api_type);
                input.val('');
            }
        });

        $(document).on('click', '.mod_intebchat[data-instance-id="' + instanceId + '"] #refresh', function(e) {
            clearHistory(instanceId);
        });

        // Auto-resize textarea
        $(document).on('input', '.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input', function(e) {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Load strings
        var strings = [
            {
                key: 'askaquestion',
                component: 'mod_intebchat'
            },
            {
                key: 'erroroccurred',
                component: 'mod_intebchat'
            },
        ];
        Str.get_strings(strings).then(function(results) {
            questionString = results[0];
            errorString = results[1];
        });

        // Check token limit periodically if enabled
        if (tokenInfo.enabled) {
            setInterval(checkTokenReset, 60000); // Check every minute
        }
    };

    /**
     * Update UI based on token limit status
     */
    var updateTokenUI = function() {
        if (!tokenInfo.enabled) {
            return;
        }

        var $container = $('.mod_intebchat');
        var $input = $container.find('#openai_input');
        var $submitBtn = $container.find('#go');
        var $progressBar = $container.find('.progress-bar');

        if (tokenInfo.exceeded) {
            $input.prop('disabled', true);
            $submitBtn.prop('disabled', true);
        } else {
            $input.prop('disabled', false);
            $submitBtn.prop('disabled', false);
        }

        // Update progress bar
        if ($progressBar.length) {
            var percentage = (tokenInfo.used / tokenInfo.limit * 100);
            $progressBar.css('width', percentage + '%');
            
            // Update color based on usage
            if (percentage > 90) {
                $progressBar.css('background', 'linear-gradient(135deg, #e53e3e 0%, #c53030 100%)');
            } else if (percentage > 75) {
                $progressBar.css('background', 'linear-gradient(135deg, #dd6b20 0%, #c05621 100%)');
            }
        }
    };

    /**
     * Check if token limit has reset
     */
    var checkTokenReset = function() {
        var now = Date.now() / 1000;
        if (tokenInfo.exceeded && now > tokenInfo.resetTime) {
            // Reload page to refresh token status
            window.location.reload();
        }
    };

    /**
     * Add a message to the chat UI
     * @param {string} type Which side of the UI the message should be on. Can be "user" or "bot"
     * @param {string} message The text of the message to add
     * @param {int} instanceId The ID of the instance to manipulate
     */
    var addToChatLog = function(type, message, instanceId) {
        var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');
        
        var messageElem = $('<div></div>').addClass('openai_message').addClass(type);
        var messageText = $('<span></span>').html(message);
        messageElem.append(messageText);

        // Add timestamp
        var timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        var timestampElem = $('<span></span>').addClass('message-timestamp').text(timestamp);
        messageElem.append(timestampElem);

        messageContainer.append(messageElem);
        
        // Smooth scroll to bottom
        messageContainer.animate({
            scrollTop: messageContainer[0].scrollHeight
        }, 300);
    };

    /**
     * Clears the thread ID from local storage and removes the messages from the UI
     * @param {int} instanceId
     */
    var clearHistory = function(instanceId) {
        chatData = localStorage.getItem("mod_intebchat_data");
        if (chatData) {
            chatData = JSON.parse(chatData);
            if (chatData[instanceId]) {
                chatData[instanceId] = {};
                localStorage.setItem("mod_intebchat_data", JSON.stringify(chatData));
            }
        }
        $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log').html("");
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
            chatData = localStorage.getItem("mod_intebchat_data");
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

        $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').addClass('disabled');
        $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').removeClass('error');
        $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').attr('placeholder', questionString);
        $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').blur();
        addToChatLog('bot loading', '...', instanceId);

        $.ajax({
            url: M.cfg.wwwroot + '/mod/intebchat/api/completion.php',
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
                var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');
                messageContainer.children().last().remove();
                $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');

                if (data.message) {
                    addToChatLog('bot', data.message, instanceId);
                    if (data.thread_id) {
                        chatData[instanceId]['threadId'] = data.thread_id;
                        localStorage.setItem("mod_intebchat_data", JSON.stringify(chatData));
                    }
                    
                    // Update token usage if provided
                    if (data.tokenInfo && tokenInfo.enabled) {
                        tokenInfo.used += data.tokenInfo.total || 0;
                        updateTokenUI();
                        
                        // Check if limit exceeded
                        if (tokenInfo.used >= tokenInfo.limit) {
                            tokenInfo.exceeded = true;
                            updateTokenUI();
                            Notification.addNotification({
                                message: M.util.get_string('tokenlimitexceeded', 'mod_intebchat'),
                                type: 'error'
                            });
                        }
                    }
                } else if (data.error) {
                    if (data.error.type === 'token_limit_exceeded') {
                        tokenInfo.exceeded = true;
                        updateTokenUI();
                        Notification.addNotification({
                            message: data.error.message,
                            type: 'error'
                        });
                    } else {
                        addToChatLog('bot error', data.error.message, instanceId);
                    }
                }
                $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').focus();
            },
            error: function(xhr, status, error) {
                var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');
                messageContainer.children().last().remove();
                $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');
                
                var errorMsg = errorString;
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMsg = response.error;
                    }
                } catch (e) {
                    // Use default error message
                }
                
                addToChatLog('bot error', errorMsg, instanceId);
                $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').addClass('error');
                $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').attr('placeholder', errorString);
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
        $('.mod_intebchat[data-instance-id="' + instanceId + '"] .openai_message').each(function(index, element) {
            var messages = $('.mod_intebchat[data-instance-id="' + instanceId + '"] .openai_message');
            if (index === messages.length - 1) {
                return;
            }

            var user = userName;
            if ($(element).hasClass('bot')) {
                user = assistantName;
            }
            
            // Remove timestamp from message text
            var messageText = $(element).clone();
            messageText.find('.message-timestamp').remove();
            
            transcript.push({"user": user, "message": messageText.text().trim()});
        });

        return transcript;
    };

    return {
        init: init
    };
});