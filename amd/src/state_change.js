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
 * @module    mod_studentquiz/state_change
 * @copyright 2019 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    var t = {

        SELECTOR: {
            STATE_SELECT: '#menustatetype',
            CHANGE_STATE_BUTTON: 'div.singlebutton.continue_state_change [type=submit]',
            STATE_VALUE_INPUT: 'input[name=state]',
            SUBMIT_STATE_BUTTON: '#change_state',
            CHANGE_STATE_NOTIFICATION: 'span.change-question-state',
            CURRENT_STATE_TEXT: 'div.current-state',
        },

        init: function() {
            const submitStateButton = $(t.SELECTOR.SUBMIT_STATE_BUTTON);
            let lastSelectedState = submitStateButton.attr('data-currentstate');

            // TODO: Something rerenders or manipulates the modal elements, so the event has to be attached to the
            // document - see #278. Better would be using the dialog framework similiar to the comment_area instead of
            // putting the dialog together manually in the renderer functions.
            $(document).on('change', t.SELECTOR.STATE_SELECT, {}, function() {
                // Since it happens that the modal elements gets rerendered or manipulated in some way, the elements
                // need to be obtained freshly.
                var stateChangeSelect = $(t.SELECTOR.STATE_SELECT);
                var changeStateButton = $(t.SELECTOR.CHANGE_STATE_BUTTON);
                var stateValueInput = $(t.SELECTOR.STATE_VALUE_INPUT);
                var submitStateButton = $(t.SELECTOR.SUBMIT_STATE_BUTTON);

                if (stateChangeSelect.val() !== '' && stateChangeSelect.val() !== lastSelectedState) {
                    stateValueInput.val(stateChangeSelect.val());
                    changeStateButton.removeAttr('disabled');
                    submitStateButton.removeAttr('disabled');
                } else {
                    changeStateButton.attr('disabled', 'disabled');
                    submitStateButton.attr('disabled', 'disabled');
                }
            });

            $(document).on('click', t.SELECTOR.SUBMIT_STATE_BUTTON, {}, function() {
                // Since it happens that the modal elements gets rerendered or manipulated in some way, the elements
                // need to be obtained freshly.
                var stateChangeSelect = $(t.SELECTOR.STATE_SELECT);
                var submitStateButton = $(t.SELECTOR.SUBMIT_STATE_BUTTON);
                const currentStateText = $(t.SELECTOR.CURRENT_STATE_TEXT);

                submitStateButton.attr('disabled', 'disabled');
                var pendingPromise = t.addPendingJSPromise('studentquizStateChange');
                require(['core/loadingicon'], function(LoadingIcon) {
                    var parentElement = $(t.SELECTOR.CHANGE_STATE_NOTIFICATION);
                    LoadingIcon.addIconToContainerRemoveOnCompletion(parentElement, pendingPromise);
                });
                var args = {
                    courseid: submitStateButton.attr('data-courseid'),
                    cmid: submitStateButton.attr('data-cmid'),
                    studentquizquestionid: submitStateButton.attr('data-studentquizquestionid'),
                    state: stateChangeSelect.val()
                };
                var failure;
                var promise = Ajax.call([{methodname: 'mod_studentquiz_set_state', args: args}], true, true);
                promise[0].then(function(results) {
                    Notification.alert(results.status, results.message);
                    pendingPromise.resolve();
                    lastSelectedState = stateChangeSelect.val();
                    const currentSelectedText = stateChangeSelect.find('option:selected').text();
                    Str.get_string('changecurrentstate', 'studentquiz', currentSelectedText).done(newString => {
                        currentStateText.html(newString);
                    });
                    // Reload the Studentquiz page.
                    window.opener.location.reload();
                    // Each then() should return a value or throw (promise/always-return).
                    return;
                }).fail(failure);
            });
        },

        /**
         * Add Pending Promise to current session.
         *
         *
         * @param {string} pendingKey JSPending key
         * @returns {*|jQuery|{}} Pending Promise
         */
        addPendingJSPromise: function(pendingKey) {
            M.util.js_pending(pendingKey);

            var pendingPromise = $.Deferred();
            pendingPromise.then(function() {
                M.util.js_complete(pendingKey);
                return arguments[0];
            }).catch(Notification.exception);

            return pendingPromise;
        },
    };

    return t;
});
