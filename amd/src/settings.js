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
 * Settings page JavaScript
 *
 * @module     mod_intebchat/settings
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {
    var init = function() {
        $('#id_s_mod_intebchat_type').on('change', function(e) {
            // If the API Type is changed, programmatically hit save so the page automatically reloads with the new options
            $('.settingsform').addClass('mod_intebchat');
            $('.settingsform').addClass('disabled');
            $('.settingsform button[type="submit"]').click();
        });
    };

    return {
        init: init
    };
});