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
 * A JavaScript module to initialize question bank bulk move modal.
 *
 * @module     mod_studentquiz/modal_question_bank_bulkmove
 * @copyright  2025 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import ModalQuestionBankBulkMove from 'qbank_bulkmove/modal_question_bank_bulkmove';
import Pending from 'core/pending';

/**
 * Initialize question bank bulk move modal with custom trigger.
 *
 * @async
 * @param {number} contextId The context id.
 * @param {number} categoryId The category id.
 * @returns {Promise<void>} Pending promise for async event handling.
 */
export const init = async(contextId, categoryId) => {
    document.addEventListener('click', async(e) => {
        const trigger = e.target;
        if (trigger.classList.contains('moveto-button')) {
            e.preventDefault();
            const pending = new Pending('mod_studentquiz/bulkmove:modal');
            try {
                const modal = await ModalQuestionBankBulkMove.create({
                    contextId,
                    title: getString('bulkmoveheader', 'qbank_bulkmove'),
                    show: true,
                    categoryId: categoryId,
                });

                // Override the redirect behavior after moving questions.
                if (modal) {
                    const originalMove = modal.moveQuestionsAfterConfirm.bind(modal);
                    modal.moveQuestionsAfterConfirm = async function(targetContextId, targetCategoryId) {
                        await originalMove(targetContextId, targetCategoryId);
                        this.hide();
                        window.location.reload();
                    };
                }
            } finally {
                pending.resolve();
            }
        }
    });
};
