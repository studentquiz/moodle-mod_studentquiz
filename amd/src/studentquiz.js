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
        initialise: function(forcerating, forcecommenting) {

            // Ajax request POST on CLICK for add comment.
            $('.studentquiz_behaviour .add_comment').off('click').on('click', function() {
                var $comments = $(this).closest('.comments');
                var $field = $comments.find('.add_comment_field');
                var questionid = $field.attr('name').substr(1);
                var $cmidfield = $(this).closest('form').find('.cmid_field');
                var cmid = $cmidfield.attr('value');
                var $commentlist = $comments.children('.comment_list');

                if ($field.val() == "") {
                    return;
                }

                $.post(M.cfg.wwwroot + '/mod/studentquiz/save.php',
                    {save: 'comment', cmid: cmid, questionid: questionid, sesskey: M.cfg.sesskey, text: $field.val()},
                    function() {
                        $field.val('');
                        $field.trigger("keyup");
                        getCommentList(questionid, $commentlist, cmid);

                        $('.studentquiz_behaviour > .comments > .comment_error').addClass('hide');
                    }
                );
                return;
            });

            // Ajax request POST on CLICK for add rating.
            $('.studentquiz_behaviour .rate .rating .rateable').off('click').on('click', function() {
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
                );
            });

            // On CLICK check if student submitted result and has rated if not abort and show error for rating.
            $('input[name="next"], input[name="previous"], input[name="finish"]').off('click').on('click', function() {
                var $that = $(this);

                var afterquestion = !$('.im-controls input[type="submit"]').length ||
                    $('.im-controls input[type="submit"]').filter(function() {
                        return this.name.match(/^q.+-submit$/);
                    }).is(':disabled');
                if (afterquestion) {
                    var hasrated = $('.rating span').hasClass('star');
                    var hascommented = $('.studentquiz_behaviour .comment_list > div').hasClass('fromcreator');

                    if (forcerating) {
                        if (!hasrated) {
                            $('.studentquiz_behaviour > .rate > .rate_error').removeClass('hide');
                        }
                    }
                    if (forcecommenting) {
                        if (!hascommented) {
                            $('.studentquiz_behaviour > .comments > .comment_error').removeClass('hide');
                        }
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

            $('.add_comment_field').on('keyup', ensurePreventUnload);

            // Bind the show more and show less buttons
            bindButtons();
        }
    };

    /**
     * Binding action buttons after refresh comment list.
     */
    function bindButtons() {
        $('.studentquiz_behaviour .show_more').off('click').on('click', function() {
            $('.studentquiz_behaviour .comment_list div').removeClass('hidden');
            $(this).addClass('hidden');
            $('.studentquiz_behaviour .show_less').removeClass('hidden');
        });

        $('.studentquiz_behaviour .show_less').off('click').on('click', function() {
            $('.studentquiz_behaviour .comment_list > div').each(function(index) {
                if (index > 10 && !$(this).hasClass('button_controls')) {
                    $(this).addClass('hidden');
                }
            });

            $(this).addClass('hidden');
            $('.studentquiz_behaviour .show_more').removeClass('hidden');
        });

        $('.studentquiz_behaviour .remove_action').off('click').on('click', function() {
            var $cmidfield = $(this).closest('form').find('.cmid_field');
            var cmid = $cmidfield.attr('value');
            var questionid = $(this).attr('data-question_id');
            var $commentlist = $(this).closest('.comments').children('.comment_list');
            $.post(M.cfg.wwwroot + '/mod/studentquiz/remove.php',
                {id: $(this).attr('data-id'), cmid: cmid, sesskey: M.cfg.sesskey},
                function() {
                    getCommentList(questionid, $commentlist, cmid);
                }
            );
        });
    }

    /**
     * Ajax request GET to get comment list
     * @param {int}           questionid Question id
     * @param {jQueryElement} $commentlist jQuery HtmlElement for comments list div
     * @param {int}           cmid course module id
     */
    function getCommentList(questionid, $commentlist, cmid) {
        var commentlisturl = M.cfg.wwwroot + '/mod/studentquiz/comment_list.php?questionid=';
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
     * Note: Only in preview is the commenting visible without answering the question. If someone has filled the
     * textarea and afterwards answers the question, he'll get the dialogue, which is fine.
     */

    /**
     * Enable the unload prevention conditionally by comment textarea
     */
    function ensurePreventUnload() {
        if ($('.add_comment_field').val() != "") {
            enablePreventUnload();
        } else {
            disablePreventUnload();
        }
    }

    /**
     * Set the beforeunload event.
     */
    function enablePreventUnload() {
        // Kindly warn user when he tries to leave page while he has still input in the comment textarea
        $(window).on('beforeunload', function() {
            $('.studentquiz_behaviour > .comments > .comment_error_unsaved').removeClass('hide');
            return true;
        });
    }

    /**
     * Remove the beforeunload event.
     */
    function disablePreventUnload() {
        $('.studentquiz_behaviour > .comments > .comment_error_unsaved').addClass('hide');
        $(window).off('beforeunload');
    }
});