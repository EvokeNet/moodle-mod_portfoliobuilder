/**
 * Toggle like js logic.
 *
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

/* eslint-disable */
define(['jquery', 'core/ajax', 'mod_portfoliobuilder/sweetalert'], function($, Ajax, Swal) {
    var ToggleLike = function() {
        this.registerEventListeners();
    };

    ToggleLike.prototype.registerEventListeners = function() {
        $(document).on('click', '.likebutton', function(event) {
            var entrydiv = $(event.currentTarget).closest('.entry');

            if (entrydiv.length === 0 || entrydiv.length > 1) {
                this.showToast('error', 'Error trying to find the discussion for this comment.');

                return;
            }

            var id = entrydiv.data('id');

            var request = Ajax.call([{
                methodname: 'mod_portfoliobuilder_togglereaction',
                args: {
                    entryid: id,
                    reactionid: 1
                }
            }]);

            request[0].done(function(data) {
                var likebutton = entrydiv.find('.actions .likebutton');
                var totalreactionsspan = entrydiv.find('.actions .likebutton .totalreactions');

                totalreactionsspan.empty();

                likebutton.toggleClass('hasreacted');

                if (data.message == false || data.message == 'false') {
                    return;
                }

                totalreactionsspan.text(data.message);
            }.bind(this)).fail(function(error) {
                var message = error.message;

                if (!message) {
                    message = error.error;
                }

                this.showToast('error', message);
            }.bind(this));
        }.bind(this));
    };

    ToggleLike.prototype.showToast = function(type, message) {
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
        'init': function() {
            return new ToggleLike();
        }
    };
});
