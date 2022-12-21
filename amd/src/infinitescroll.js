/**
 * Add comment js logic.
 *
 * @package
 * @subpackage mod_portfoliobuilder
 * @copyright  2022 World Bank Group <https://worldbank.org>
 * @author     Willian Mano <willianmanoaraujo@gmail.com>
 */
/* eslint-disable */
define(['jquery', 'core/ajax', 'core/templates', 'mod_portfoliobuilder/tribute_init'], function($, Ajax, Templates, TributeInit) {
    var InfiniteScroll = function(courseid) {
        this.courseid = courseid;

        this.tribute = TributeInit.init();

        this.targetdiv = '#' + this.type + 'portfolio';

        this.controlbutton = document.getElementById(this.type + 'portfolio-tab');

        this.type = this.controlbutton.dataset.timeline_type;
        this.offset = parseInt(this.controlbutton.dataset.timeline_offset);
        this.hasmoreitems = this.controlbutton.dataset.timeline_hasmoreitems;

        this.loadItems();

        document.addEventListener('scroll', function(event) {
            var scrollTop = event.target.scrollingElement.scrollTop;
            var scrollHeight = event.target.scrollingElement.scrollHeight;
            var offsetHeight = event.target.scrollingElement.offsetHeight;

            if (this.hasmoreitems && !this.wait && (scrollTop + offsetHeight > scrollHeight - 40)) {
                if (!this.hasmoreitems) {
                    return;
                }

                if (!this.wait) {
                    this.loadItems();
                }
            }
        }.bind(this), false);

        $('.nav-tabs .nav-link, .nav-pills .nav-link').click(function(event) {
            this.controlbutton = event.target;

            this.type = event.target.dataset.timeline_type;
            this.offset = parseInt(event.target.dataset.timeline_offset);
            this.hasmoreitems = event.target.dataset.timeline_hasmoreitems === 'true';

            this.targetdiv = event.target.dataset.target;

            this.loadItems();
        }.bind(this));
    }

    InfiniteScroll.prototype.loadItems = function() {
        this.wait = true;

        const request = Ajax.call([{
            methodname: 'mod_portfoliobuilder_loadtimeline',
            args: {
                courseid: this.courseid,
                type: this.type,
                offset: this.offset
            }
        }]);

        request[0].done(function(response) {
            var data = JSON.parse(response.data);

            this.offset = parseInt(this.offset + 1);
            this.controlbutton.dataset.timeline_offset = this.offset;
            this.hasmoreitems = data.hasmoreitems;
            this.controlbutton.dataset.timeline_hasmoreitems = data.hasmoreitems;

            this.handleLoadData(data);
        }.bind(this));
    };

    InfiniteScroll.prototype.handleLoadData = function(data) {
        console.log(data);
        const targetdiv = $(this.targetdiv);

        targetdiv.find('.entry_loading-placeholder').addClass('hidden');

        $.each(data, function(index, value) {
            console.log(value);
            Templates.render('mod_portfoliobuilder/entry', value).then(function(content) {
                targetdiv.find('.entries').append(content);
            });
        });

        this.tribute.reload();

        this.wait = false;
    };

    InfiniteScroll.prototype.courseid = 0;

    InfiniteScroll.prototype.type = 'my';

    InfiniteScroll.prototype.offset = 0;

    InfiniteScroll.prototype.hasmoreitems = true;

    InfiniteScroll.prototype.targetdiv = '#myportfolio';

    InfiniteScroll.prototype.wait = false;

    InfiniteScroll.prototype.tribute = null;

    InfiniteScroll.prototype.controlbutton = null;

    return {
        'init': function(courseid) {
            return new InfiniteScroll(courseid);
        }
    };
});
