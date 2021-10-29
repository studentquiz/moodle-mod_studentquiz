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
 * Javascript for question-nav-tabs.
 *
 * @module    mod_studentquiz/question_nav_tabs
 * @copyright 2021 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Update the current active tab to user preferences.
 *
 * @private
 * @param {Object} e Event
 * @return {Promise} The promise object.
 */
const updateActiveTab = (e) => {
    return M.util.set_user_preference('mod_studentquiz_question_active_tab', e.target.dataset.tabId);
};

/**
 * Init the question-nav-tabs.
 *
 */
export const init = () => {
    let tabs = document.querySelectorAll('.question-nav-tabs > .nav-tabs > .nav-item.nav-link');

    tabs.forEach((tab) => {
        tab.addEventListener('click', updateActiveTab);
    });
};
