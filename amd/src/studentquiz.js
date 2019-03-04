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
 * @package    mod_studentquiz
 * @copyright  2017 HSR (http://www.hsr.ch)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* jshint latedef:nofunc */

define(['jquery'], function($) {
    return {
        initialise: function() {
            // Ajax request POST on CLICK for add comment.
            $('.studentquiz_behaviour .add_comment').off('click').on('click', function() {
                // Uncomment if it should be prevented to close the window without saving
                // disablePreventUnload();
                var $comments = $(this).closest('.comments');
                var $field = $comments.find('.add_comment_field');
                var questionid = $field.attr('name').substr(1);
                var $cmidfield = $(this).closest('form').find('.cmid_field');
                var cmid = $cmidfield.attr('value');
                var $commentlist = $comments.children('.comment_list');

                $.post(M.cfg.wwwroot + '/mod/studentquiz/save.php',
                    {save: 'comment', cmid: cmid, questionid: questionid, sesskey: M.cfg.sesskey, text: $field.val()},
                    function() {
                        $field.val('');
                        getCommentList(questionid, $commentlist, cmid);
                    }
                ).always(function() {
                    // Uncomment if it should be prevented to close the window without saving
                    // ensurePreventUnload();
                });
            });

            // Ajax request POST on CLICK for add rating.
            $('.studentquiz_behaviour .rate .rating .rateable').off('click').on('click', function() {
                // Uncomment if it should be prevented to close the window without saving
                // disablePreventUnload();
                var rate = $(this).attr('data-rate');
                var $that = $(this);
                var $cmidfield = $(this).closest('form').find('.cmid_field');
                var cmid = $cmidfield.attr('value');
                $.post(M.cfg.wwwroot + '/mod/studentquiz/save.php',
                    {save: 'rate', cmid: cmid, questionid: $(this).attr('data-questionid'), sesskey: M.cfg.sesskey, rate: rate},
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
                ).always(function() {
                    // Uncomment if it should be prevented to close the window without saving
                    // ensurePreventUnload();
                });
            });

            // On CLICK check if student submitted result and has rated if not abort and show error for rating.
            $('input[name="next"], input[name="previous"], input[name="finish"]').off('click').on('click', function() {
                var $that = $(this);

                if (
                    !$('.im-controls input[type="submit"]').length ||
                    $('.im-controls input[type="submit"]').filter(function() {
                        return this.name.match(/^q.+-submit$/);
                    }).is(':disabled')
                ) {
                    var hasRated = false;
                    $('.rating span').each(function() {
                        if ($(this).hasClass('star')) {
                            hasRated = true;
                        }
                    });

                    if (hasRated) {
                        $that.submit();
                        return true;
                    }

                    $('.studentquiz_behaviour > .rate > .rate_error').removeClass('hide');
                    return false;
                } else {
                    $that.submit();
                    return true;
                }
            });

            // Uncomment if it should be prevented to close the window without saving
            // $('.add_comment_field').on('keyup', ensurePreventUnload);

            // Bind the show more and show less buttons
            bindButtons();
        }
    };

    /**
     * Binding action buttons after refresh comment list.
     */
    function bindButtons() {
        // Uncomment if it should be prevented to close the window without saving
        // disablePreventUnload();
        $('.studentquiz_behaviour .remove_action').off('click').on('click', function() {
            var $cmidfield = $(this).closest('form').find('.cmid_field');
            var cmid = $cmidfield.attr('value');
            var questionid = $(this).attr('data-question_id');
            var $commentlist = $(this).closest('.comments').children('.comment_list');
            $.post($('#baseurlmoodle').val() + '/mod/studentquiz/remove.php',
                {id: $(this).attr('data-id'), cmid: cmid, sesskey: M.cfg.sesskey},
                function() {
                    getCommentList(questionid, $commentlist, cmid);
                }
            ).always(function() {
                // Uncomment if it should be prevented to close the window without saving
                // ensurePreventUnload();
            });
        });
    }

    /**
     * Ajax request GET to get comment list
     * @param {int}           questionid Question id
     * @param {jQueryElement} $commentlist jQuery HtmlElement for comments list div
     * @param {int}           cmid course module id
     */
    function getCommentList(questionid, $commentlist, cmid) {
        var commentlisturl = $('#baseurlmoodle').val() + '/mod/studentquiz/comment_list.php?questionid=';
        commentlisturl += questionid + '&cmid=' + cmid + '&sesskey=' + M.cfg.sesskey;
        $.get(commentlisturl,
            function(data) {
                $commentlist.html(data);
                bindButtons();
            }
        );
    }

    /**
     * Kindly ask to prevent leaving page when there's a unsaved comment
     * 
     * It seems to be pretty browser specific how the beforeunload event is processed. Observations when event was set:
     * Chrome: Allows POSTing data (via) but prevents navigating since they're also
     *   form submissions, and also prevents closing of the window and navigating using other links
     * Firefox: Whatever you try to do, POSTing or navigating, always prevents it, even when returning nothing or void.
     *   Unknown what the expected behaviour by spec should be. All proposed solutions were all not working...
     * 
     * That's why we need to carefully enable and disable the beforeunload event. Rule of thumb is, enable whenever
     * the comment box is not empty, but disable whenever a quiz interaction button is pressed (add comment,
     * quiz navigation)
     * Whenever the action is done, it should be enabled again, if the comment textarea is still not empty.
     * 
     * Note: Only in preview is the commenting visible without answering the question. If someone has filled the
     * textarea and afterwards answers the question, he'll get the dialogue, which is fine.
     */

    /**
     * Enable the unload prevention conditionally by comment textarea
     */
    // Uncomment if it should be prevented to close the window without saving
    // function ensurePreventUnload() {
    //     if ($('.add_comment_field').val() != "") {
    //         enablePreventUnload();
    //     } else {
    //         disablePreventUnload();
    //     }
    // }

    /**
     * Set the beforeunload event.
     */
    // Uncomment if it should be prevented to close the window without saving
    // function enablePreventUnload() {
    //     // Kindly warn user when he tries to leave page while he has still input in the comment textarea
    //     $(window).on('beforeunload', function() {
    //         $('.studentquiz_behaviour > .comments > .comment_error').removeClass('hide');
    //         return true;
    //     });
    // }

    /**
     * Remove the beforeunload event.
     */
    // Uncomment if it should be prevented to close the window without saving
    // function disablePreventUnload() {
    //     $('.studentquiz_behaviour > .comments > .comment_error').addClass('hide');
    //     $(window).off('beforeunload');
    // }
});