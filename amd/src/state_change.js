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
 * Javascript for state change dialog
 *
 * @package mod_studentquiz
 * @author Huong Nguyen <huongnv13@gmail.com>
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    var t = {

        SELECTOR: {
            STATE_SELECT: '#menustatetype',
            CHANGE_STATE_BUTTON: 'div.singlebutton button.btn-primary',
            STATE_VALUE_INPUT: 'input[name=state]',
            SUBMIT_STATE_BUTTON: '#change_state'
        },

        init: function() {
            var stateChangeSelect = $(t.SELECTOR.STATE_SELECT);
            var changeStateButton = $(t.SELECTOR.CHANGE_STATE_BUTTON);
            var stateValueInput = $(t.SELECTOR.STATE_VALUE_INPUT);
            var submitStateButton = $(t.SELECTOR.SUBMIT_STATE_BUTTON);

            stateChangeSelect.on('change', function() {
                if (stateChangeSelect.val() !== '') {
                    stateValueInput.val(stateChangeSelect.val());
                    changeStateButton.removeAttr('disabled');
                } else {
                    changeStateButton.attr('disabled', 'disabled');
                }
            });

            submitStateButton.on('click', function() {
                var args = {
                    courseid: submitStateButton.attr('data-courseid'),
                    cmid: submitStateButton.attr('data-cmid'),
                    questionid: submitStateButton.attr('data-questionid'),
                    state: stateChangeSelect.val()
                };
                var failure;
                var promise = Ajax.call([{methodname: 'mod_studentquiz_set_state', args: args}], true, true);
                promise[0].then(function(results) {
                    Notification.alert(results.status, results.message);
                }).fail(failure);
            });
        },
    };

    return t;
});
