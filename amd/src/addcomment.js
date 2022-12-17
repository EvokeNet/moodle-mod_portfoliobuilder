/**
 * Add comment js logic.
 *
 * @package
 * @subpackage mod_portfoliobuilder
 * @copyright  2022 World Bank Group <https://worldbank.org>
 * @author     Willian Mano <willianmanoaraujo@gmail.com>
 */

define(['jquery', 'core/str', 'core/ajax', 'mod_portfoliobuilder/sweetalert'], function($, Str, Ajax, Swal) {
    var AddComment = function() {
        this.registerEventListeners();
    };

    AddComment.prototype.registerEventListeners = function() {
        $(document).on('click', '.post-comment-btn', function(event) {
            var target = $(event.currentTarget).closest('.input-group').children('.post-comment-input');

            if (target) {
                this.saveComment(target, target.html());
            }
        }.bind(this));
    };

    AddComment.prototype.saveComment = function(postinput, value) {
        if (value === '') {
            return;
        }

        var entrydiv = postinput.closest('.entry');

        postinput.empty();

        if (entrydiv.length === 0 || entrydiv.length > 1) {
            this.showToast('error', 'Error trying to find the discussion for this comment.');

            return;
        }

        var id = entrydiv.data('id');

        var request = Ajax.call([{
            methodname: 'mod_portfoliobuilder_addcomment',
            args: {
                comment: {
                    entryid: id,
                    message: value,
                }
            }
        }]);

        request[0].done(function(data) {
            this.addCommentToEntry(entrydiv, data.comment);
        }.bind(this)).fail(function(error) {
            var message = error.message;

            if (!message) {
                message = error.error;
            }

            this.showToast('error', message);
        }.bind(this));
    };

    AddComment.prototype.showToast = function(type, message) {
        var Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 8000,
            timerProgressBar: true,
            onOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        Toast.fire({
            icon: type,
            title: message
        });
    };

    AddComment.prototype.addCommentToEntry = function(entrydiv, comment) {
        Str.get_string('editcomment', 'mod_portfoliobuilder').then(function(editcomment) {
            var userimg = entrydiv.find('.add-comment .userimg').clone();
            var userfullname = userimg.attr('alt');
            var loadallcomments = entrydiv.find('.loadmore');

            var commentcontainer = $("<div class='submissioncomment fadeIn'>" +
                "<div class='userinfo'>" +
                "<div class='userimg'>" + $('<div/>').append(userimg).html() + "</div>" +
                "<div class='nameanddate'>" +
                "<p class='username'>" + userfullname + "</p>" +
                "<span class='small'>" + comment.humantimecreated + "</span>"+
                "</div>"+
                "<div class='editbutton ml-auto'>"+
                "<a class='btn-editcomment btn btn-sm btn-primary' data-id='"+comment.id+"'>"+editcomment+"</a>"+
                "</div>"+
                "</div>"+
                "<p class='text'>" + comment.message + "</p>" +
                "</div>");

            if (loadallcomments.length > 0) {
                commentcontainer.insertBefore(loadallcomments);
            } else {
                commentcontainer.insertBefore(entrydiv.find('.add-comment'));
            }

            var totalcommentsspan = entrydiv.find('.reactions .actions .commentbutton .totalcomments');
            var totalcomments = entrydiv.find('.submissioncomment').length;

            totalcommentsspan.empty();

            totalcommentsspan.text(totalcomments);
        });
    };

    return {
        'init': function() {
            return new AddComment();
        }
    };
});
