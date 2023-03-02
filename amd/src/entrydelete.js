/**
 * Delete entry js logic.
 *
 * @copyright  2023 World Bank Group <https://worldbank.org>
 * @author     Willian Mano <willianmanoaraujo@gmail.com>
 */

define(['jquery', 'core/ajax', 'core/str', 'mod_portfoliobuilder/sweetalert'], function($, Ajax, Str, Swal) {
    var STRINGS = {
        CONFIRM_TITLE: 'Are you sure?',
        CONFIRM_MSG: 'Once deleted, the item cannot be recovered!',
        CONFIRM_YES: 'Yes, delete it!',
        CONFIRM_NO: 'Cancel',
        SUCCESS: 'Entry successfully deleted.'
    };

    var componentStrings = [
        {
            key: 'entrydelete_confirm_title',
            component: 'mod_portfoliobuilder'
        },
        {
            key: 'entrydelete_confirm_msg',
            component: 'mod_portfoliobuilder'
        },
        {
            key: 'entrydelete_confirm_yes',
            component: 'mod_portfoliobuilder'
        },
        {
            key: 'entrydelete_confirm_no',
            component: 'mod_portfoliobuilder'
        },
        {
            key: 'entrydelete_success',
            component: 'mod_portfoliobuilder'
        },
    ];

    var EntryDelete = function() {
        this.getStrings();

        this.registerEventListeners();
    };

    EntryDelete.prototype.getStrings = function() {
        var stringsPromise = Str.get_strings(componentStrings);

        $.when(stringsPromise).done(function(strings) {
            STRINGS.CONFIRM_TITLE = strings[0];
            STRINGS.CONFIRM_MSG = strings[1];
            STRINGS.CONFIRM_YES = strings[2];
            STRINGS.CONFIRM_NO = strings[3];
            STRINGS.SUCCESS = strings[4];
        });
    };

    EntryDelete.prototype.registerEventListeners = function() {
        $("body").on("click", ".delete-portfoliobuilder-entry", function(event) {
            event.preventDefault();

            var eventTarget = $(event.currentTarget);

            Swal.fire({
                title: STRINGS.CONFIRM_TITLE,
                text: STRINGS.CONFIRM_MSG,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: STRINGS.CONFIRM_YES,
                cancelButtonText: STRINGS.CONFIRM_NO
            }).then(function(result) {
                if (result.value) {
                    this.deleteEntryItem(eventTarget);
                }
            }.bind(this));
        }.bind(this));
    };

    EntryDelete.prototype.deleteEntryItem = function(eventTarget) {
        var request = Ajax.call([{
            methodname: 'mod_portfoliobuilder_entrydelete',
            args: {
                id: eventTarget.data('id')
            }
        }]);

        request[0].done(function() {
            window.location.reload();
        }.bind(this)).fail(function(error) {
            var message = error.message;

            if (!message) {
                message = error.error;
            }
            this.showToast('error', message);
        }.bind(this));
    };

    EntryDelete.prototype.showToast = function(type, message) {
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
            return new EntryDelete();
        }
    };
});