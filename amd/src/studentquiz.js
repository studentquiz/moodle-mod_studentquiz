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
                var $comments = $(this).closest('.comments');
                var $field = $comments.find('.add_comment_field');
                var questionid = $field.attr('name').substr(1);
                var $cmidfield = $(this).closest('form').find('.cmid_field');
                var cmid = $cmidfield.attr('value');
                var $commentlist = $comments.children('.comment_list');

                $.post($('#baseurlmoodle').val() + '/mod/studentquiz/save.php',
                    {save: 'comment', cmid: cmid, questionid: questionid, sesskey: M.cfg.sesskey, text: $field.val()},
                    function() {
                        $field.val('');
                        get_comment_list(questionid, $commentlist, cmid);
                    });
            });

            // Ajax request POST on CLICK for add rating.
            $('.studentquiz_behaviour .rate .rating .rateable').off('click').on('click', function() {
                var rate = $(this).attr('data-rate');
                var $that = $(this);
                var $cmidfield = $(this).closest('form').find('.cmid_field');
                var cmid = $cmidfield.attr('value');
                $.post($('#baseurlmoodle').val() + '/mod/studentquiz/save.php',
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

                        $('.studentquiz_behaviour > .rate > .error').addClass('hide');
                    });
            });

            // On CLICK check if student submitted result and has rated if not abort next and show error for rating.
            $('input[name="next"]').off('click').on('click', function() {
                var $that = $(this);

                if (
                    !$('.im-controls input[type="submit"]').length ||
                    $('.im-controls input[type="submit"]').filter(function() {
                        return this.name.match(/^q.+\-submit$/);
                    }).is(':disabled')
                ) {
                    var has_rated = false;
                    $('.rating span').each(function() {
                        if ($(this).hasClass('star')) {
                            has_rated = true;
                        }
                    });

                    if (has_rated) {
                        $that.submit();
                        return true;
                    }

                    $('.studentquiz_behaviour > .rate > .error').removeClass('hide');
                    return false;
                } else {
                    $that.submit();
                    return true;
                }
            });

            // On CLICK check if student submitted result and has rated if not abort finish and show error for rating.
            $('input[name="finish"]').off('click').on('click', function() {
                var $that = $(this);

                if (
                    !$('.im-controls input[type="submit"]').length ||
                    $('.im-controls input[type="submit"]').filter(function() {
                        return this.name.match(/^q.+\-submit$/);
                    }).is(':disabled')
                ) {
                    var has_rated = false;
                    $('.rating span').each(function() {
                        if ($(this).hasClass('star')) {
                            has_rated = true;
                        }
                    });

                    if (has_rated) {
                        $that.submit();
                        return true;
                    }

                    $('.studentquiz_behaviour > .rate > .error').removeClass('hide');
                    return false;
                } else {
                    $that.submit();
                    return true;
                }
            });

            bind_buttons();
        }
    };

    /**
     * Binding action buttons after refresh comment list.
     */
    function bind_buttons() {
        $('.studentquiz_behaviour .show_more').off('click').on('click', function() {
            $('.studentquiz_behaviour .comment_list div').removeClass('hidden');
            $(this).addClass('hidden');
            $('.studentquiz_behaviour .show_less').removeClass('hidden');
        });

        $('.studentquiz_behaviour .show_less').off('click').on('click', function() {
            $('.studentquiz_behaviour .comment_list div').each(function(index) {
                if (index > 1 && !$(this).hasClass('button_controls')) {
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
            $.post($('#baseurlmoodle').val() + '/mod/studentquiz/remove.php',
                {id: $(this).attr('data-id'), cmid: cmid, sesskey: M.cfg.sesskey}, function() {
                    get_comment_list(questionid, $commentlist, cmid);
                });
        });
    }

    /**
     * Ajax request GET to get comment list
     * @param {int}    questionid Question id
     */
    function get_comment_list(questionid, $commentlist, cmid) {
        var commentlisturl = $('#baseurlmoodle').val() + '/mod/studentquiz/comment_list.php?questionid=';
        commentlisturl += questionid + '&cmid=' + cmid + '&sesskey=' + M.cfg.sesskey;
        $.get(commentlisturl,
            function(data) {
                $commentlist.html(data);
                bind_buttons();
            });
    }
});
