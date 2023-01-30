/**
 * Tribute JS initialization
 *
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

define(['core/config', 'mod_portfoliobuilder/tribute', 'core/ajax'], function(mdlcfg, Tribute, Ajax) {
    var TributeInit = function() {
        this.initialize();
    };

    TributeInit.prototype.initialize = function() {
        this.attachobject = new Tribute({
            values: function(text, cb) {
                this.remoteSearch(text, users => cb(users));
            }.bind(this),
            selectTemplate: function(item) {
                if (typeof item === "undefined") {
                    return null;
                }

                if (this.range.isContentEditable(this.current.element)) {
                    const courseid = document.getElementById("entries").dataset.courseid;

                    return (
                        '<span contenteditable="false">' +
                        '<a href="' + mdlcfg.wwwroot + '/user/view.php?id=' + item.original.id + '&course=' + courseid + '"' +
                        ' target="_blank" class="usermentioned" data-uid="' + item.original.id + '">' + item.original.fullname +
                        "</a></span>"
                    );
                }

                return '@' + item.original.fullname + '@';
            },
            noMatchTemplate: function() {
                return '<span style="visibility: hidden;"></span>';
            },
            menuItemTemplate: function(item) {
                return '<img src="' + item.original.picture + '">' + item.string;
            },
            requireLeadingSpace: false,
            allowSpaces: true,
            menuShowMinLength: 3,
            lookup: 'fullname'
        });

        this.attachobject.attach(document.querySelectorAll(".post-comment-input"));

        return this;
    };

    TributeInit.prototype.reload = function() {
        this.attachobject.detach(document.querySelectorAll(".post-comment-input"));

        this.initialize();
    };

    TributeInit.prototype.attachobject = null;

    TributeInit.prototype.remoteSearch = function(text, cb) {
        const courseid = document.getElementById("entries").dataset.courseid;

        var request = Ajax.call([{
            methodname: 'mod_portfoliobuilder_enrolledusers',
            args: {
                search: {
                    courseid: courseid,
                    name: text
                }
            }
        }]);

        request[0].done(function(data) {
            cb(data.users);
        });
    };

    return {
        'init': function() {
            return new TributeInit();
        }
    };
});