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
 * Javascript to toggle filter checkbox.
 *
 * @module     mod_studentquiz/toggle_filter_checkbox
 * @package    mod_studentquiz
 * @copyright  2019 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/key_codes'], function($, keyCodes) {
    var t = {

        SELECTOR: {
            TIME_EDT: {
                CALENDAR: "a[name='timecreated_edt[calendar]",
                SELECT_DAY_CALENDAR: "select[name='timecreated_edt[day]",
                INPUT: "input[name='timecreated_edt[enabled]']"
            },
            TIME_SDT: {
                CALENDAR: "a[name='timecreated_sdt[calendar]",
                SELECT_DAY_CALENDAR: "select[name='timecreated_sdt[day]",
                INPUT: "input[name='timecreated_sdt[enabled]']"
            }
        },

        /**
         * Elements (jQuery) are depend on hidden checkboxes.
         * They will disable if the checkbox is checked.
         */
        dependencies: [],

        /**
         * Initialise events on link-toggle.
         */
        init: function() {
            var linkToggles = $('a.link-toggle');

            linkToggles.each(function(index, linkToggle) {
                var link = $(linkToggle);
                var data = link.data('disableelements');
                var disableElements = data.split(',');

                for (index in disableElements) {
                    var disableId = 'id_' + disableElements[index];
                    if (t.dependencies[disableId] === undefined) {
                        t.dependencies[disableId] = [];
                    }
                    t.dependencies[disableId].push($('#' + link.attr('for')));
                }
            });

            linkToggles.click(function(e) {
                e.preventDefault();
                t.linkToggleHandler($(this));
            }).keypress(function(e) {
                if (e.keyCode === keyCodes.space) {
                    e.preventDefault();
                    t.linkToggleHandler($(this));
                }
            });

            // Init.
            t.initCalendarTabIndex();
        },

        /**
         * Toggle property checked for a hidden checkbox.
         *
         * @param {jQuery} linkToggle
         */
        linkToggleHandler: function(linkToggle) {
            var checkbox = $('.toggle#' + linkToggle.attr('for'));
            var toggle = !checkbox.prop('checked');
            checkbox.prop('checked', toggle);
            var data = linkToggle.data('disableelements');

            if (data.length > 0) {
                var disableElements = data.split(','),
                    index;

                for (index in disableElements) {
                    var disableId = 'id_' + disableElements[index];
                    // Check depend on before disable.
                    if (!toggle && t.checkDependOn(disableId)) {
                        toggle = true;
                    }

                    $('#' + disableId).attr('disabled', toggle);
                }
            }
        },

        /**
         * Check if an element depend on the check box by id.
         *
         * @param {String} id
         * @returns {boolean}
         */
        checkDependOn: function(id) {
            var dependOn = t.dependencies[id],
                index;

            for (index in dependOn) {
                if (dependOn[index].prop('checked')) {
                    return true;
                }
            }

            return false;
        },

        /**
         * Init Calendar Tab Index, should not tab-able when checkbox is not checked.
         */
        initCalendarTabIndex: function() {
            $(t.SELECTOR.TIME_SDT.INPUT).change(function() {
                var calendar = $(t.SELECTOR.TIME_SDT.CALENDAR);
                var focus = $(t.SELECTOR.TIME_SDT.SELECT_DAY_CALENDAR);
                var isCheck = $(this).is(":checked");
                t.toggleCalendarTabIndex(calendar, focus, isCheck);
            });
            $(t.SELECTOR.TIME_EDT.INPUT).change(function() {
                var calendar = $(t.SELECTOR.TIME_EDT.CALENDAR);
                var focus = $(t.SELECTOR.TIME_EDT.SELECT_DAY_CALENDAR);
                var isCheck = $(this).is(":checked");
                t.toggleCalendarTabIndex(calendar, focus, isCheck);
            });
        },

        /**
         * Toggle tab index for calendar.
         *
         * @param {jQuery} calendar
         * @param {jQuery} focusElement
         * @param {Boolean} isCheck
         */
        toggleCalendarTabIndex: function(calendar, focusElement, isCheck) {
            if (isCheck) {
                calendar.removeAttr('tabindex');
                focusElement.focus();
            } else {
                calendar.attr('tabindex', -1);
            }
        }
    };

    return t;
});
