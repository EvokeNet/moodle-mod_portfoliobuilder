/**
 * Edit chapter js logic.
 *
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
/* eslint-disable */
define([
        'jquery',
        'core/str',
        'core/modal_factory',
        'core/modal_events',
        'core/ajax',
        'mod_portfoliobuilder/tribute_init',
        'mod_portfoliobuilder/sweetalert',
        'core/yui'],
    function($, Str, ModalFactory, ModalEvents, Ajax, TributeInit, Swal, Y) {

        var EditComment = function(contextid) {
            this.contextid = contextid;

            this.registerEventListeners();
        };

        /**
         * @var {int} contextid
         * @private
         */
        EditComment.prototype.contextid = -1;

        /**
         * @var eventtarget
         * @private
         */
        EditComment.prototype.eventtarget = null;

        EditComment.prototype.registerEventListeners = function() {
            $("body").on("click", ".btn-editcomment", function (event) {
                event.preventDefault();

                this.eventtarget = $(event.currentTarget);

                let commentId = this.eventtarget.data('id');

                var textdiv = this.eventtarget.closest('.submissioncomment').children('.text');

                Str.get_string('editcomment', 'mod_portfoliobuilder').then(function(title) {
                    ModalFactory.create({
                        type: ModalFactory.types.SAVE_CANCEL,
                        title: title,
                        body: '<div class="input-group">' +
                            '<p class="post-comment-input" contenteditable="true" data-tribute="true">' + textdiv.html() + '</p>' +
                            '</div>',
                    }).then(function(modal) {
                        modal.getRoot().on(ModalEvents.save, function () {
                            var newMessage = modal.getBody().find('.post-comment-input').html();

                            this.saveComment(commentId, newMessage);
                        }.bind(this));

                        modal.show().then(TributeInit.init());
                    }.bind(this));
                }.bind(this));
            }.bind(this));
        }

        EditComment.prototype.saveComment = function(id, message) {
            var request = Ajax.call([{
                methodname: 'mod_portfoliobuilder_editcomment',
                args: {
                    comment: {
                        id: id,
                        message: message,
                    }
                }
            }]);

            request[0].done(function(data) {
                this.replaceOriginalMessage(message);

                this.showToast('success', data.message);
            }.bind(this)).fail(function(error) {
                var message = error.message;

                if (!message) {
                    message = error.error;
                }

                this.showToast('error', message);
            }.bind(this));
        };

        EditComment.prototype.replaceOriginalMessage = function(newMessage) {
            var submissioncommentdiv = this.eventtarget.closest('.submissioncomment');
            var textdiv = submissioncommentdiv.children('.text');

            submissioncommentdiv.css('background-color', '#cceecb');
            submissioncommentdiv.css('transition', 'background 1s');

            window.setInterval(function() {
                submissioncommentdiv.css('background-color', 'initial');
            }, 1000);

            textdiv.html(newMessage);
        }

        EditComment.prototype.showToast = function(type, message) {
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

        return {
            init: function(contextid) {
                return new EditComment(contextid);
            }
        };
    }
);
