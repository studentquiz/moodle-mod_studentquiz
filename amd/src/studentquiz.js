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
 * Javascript for save rating and save, remove and listing comments
 *
 * @module    mod_studentquiz/studentquiz
 * @copyright 2017 HSR (http://www.hsr.ch)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* jshint latedef:nofunc */

define(['jquery'], function($) {
    return {
        initialise: function(forcerating, forcecommenting, isanswered) {

            var ratingElements = $(".studentquiz_behaviour .rate .rating .rateable");
            // Ajax request POST on CLICK for add rating.
            ratingElements.off("click").on("click", function() {
                addRating(this);
            });
            // Ajax request POST for add rating when "Enter" or "Space" is pressed.
            ratingElements.on("keypress", function(e) {
                if (e.keyCode === 13 || e.keyCode === 32) {
                    e.preventDefault();
                    addRating(this);
                }
            });

            // On CLICK check if student submitted result and has rated if not abort and show error for rating.
            $('input[name="next"], input[name="previous"], input[name="finish"]').off('click').on('click', function() {
                var $that = $(this);

                if (isanswered) {
                    var hasrated = $('.rating span').hasClass('star');
                    var hascommented = $('.studentquiz-comment-container').hasClass('has-comment');

                    if (forcerating && !hasrated) {
                        $('.studentquiz_behaviour > .rate > .rate_error').removeClass('hide');
                    }
                    if (forcecommenting && !hascommented) {
                        $('.studentquiz_behaviour > .comments .comment-error').removeClass('hide');
                    }

                    // Set focus.
                    if (forcerating && !hasrated) {
                        // Set focus to the first star.
                        $('.studentquiz_behaviour .rate .rating .rateable:first-child').focus();
                    } else if (forcecommenting && !hascommented) {
                        // Set focus to atto editor.
                        $('.studentquiz_behaviour .comments .studentquiz-comment-container .editor_atto_content').focus();
                    }

                    if ((!forcerating || hasrated) && (!forcecommenting || hascommented)) {
                        $that.submit();
                        return true;
                    }
                    return false;
                } else {
                    $that.submit();
                    return true;
                }
            });
        },

        // Set focus to sorted head of question table.
        setFocus: function() {
            $(document).ready(function() {
                var sortIcon = $('#categoryquestions .iconsort');
                if (sortIcon) {
                    sortIcon.parent().focus();
                }
            });
        },

        // Select all questions.
        selectAllQuestions: function() {
            let headerCheckbox = document.getElementById('qbheadercheckbox');

            require(['core/checkbox-toggleall'], () => {
                if (!headerCheckbox.checked) {
                    headerCheckbox.click();
                }
            });
        }
    };

    /**
     * Add rating to question.
     *
     * @param {DOM} element
     */
    function addRating(element) {
        var $element = $(element);
        var rate = $element.attr('data-rate');
        var $that = $element;
        var $cmIdField = $element.closest('form').find('.cmid_field');
        var cmid = $cmIdField.attr('value');
        $.post(M.cfg.wwwroot + '/mod/studentquiz/save.php',
            {
                save: 'rate',
                cmid: cmid,
                studentquizquestionid: $element.attr('data-studentquizquestionid'),
                sesskey: M.cfg.sesskey,
                rate: rate
            },
            function() {
                var $ratingStars = $that.closest('.rating').children('span');
                $ratingStars.removeClass('star');
                $ratingStars.addClass('star-empty');
                $ratingStars.each(function() {
                    if ($(this).attr('data-rate') <= rate) {
                        $(this).removeClass('star-empty');
                        $(this).addClass('star');
                    }
                });
                $('.studentquiz_behaviour > .rate > .rate_error').addClass('hide');
            }
        );
    }
});
