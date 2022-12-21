/**
 * Add comment js logic.
 *
 * @package
 * @subpackage mod_portfoliobuilder
 * @copyright  2022 World Bank Group <https://worldbank.org>
 * @author     Willian Mano <willianmanoaraujo@gmail.com>
 */
/* eslint-disable */
define(['jquery', 'core/ajax', 'core/templates'], function($, Ajax, Templates) {
    var LoadPortfolios = function(courseid) {
        this.courseid = courseid;

        this.targetdiv = '#' + this.type + 'portfolio';

        this.controlbutton = document.getElementById(this.type + 'portfolio-tab');

        this.type = this.controlbutton.dataset.type;

        this.loadItems();

        $('.nav-tabs .nav-link, .nav-pills .nav-link').click(function(event) {
            this.controlbutton = event.target;

            this.type = event.target.dataset.type;

            this.targetdiv = event.target.dataset.target;

            console.log(event.target.dataset.loaded);
            if (event.target.dataset.loaded === 'false') {
                this.loadItems();
            }
        }.bind(this));
    }

    LoadPortfolios.prototype.loadItems = function() {
        const request = Ajax.call([{
            methodname: 'mod_portfoliobuilder_loadportfolios',
            args: {
                courseid: this.courseid,
                type: this.type
            }
        }]);

        request[0].done(function(response) {
            var data = JSON.parse(response.data);

            this.handleLoadData(data);
        }.bind(this));
    };

    LoadPortfolios.prototype.handleLoadData = function(data) {
        const targetdiv = $(this.targetdiv);

        targetdiv.find('.entry_loading-placeholder').addClass('hidden');

        $.each(data, function(index, value) {
            Templates.render('mod_portfoliobuilder/portfolio_card', value).then(function(content) {
                targetdiv.find('.entries .card-columns').append(content);
            });
        });

        this.controlbutton.dataset.loaded = true;
    };

    LoadPortfolios.prototype.courseid = 0;

    LoadPortfolios.prototype.type = 'team';

    LoadPortfolios.prototype.targetdiv = '#teamportfolio';

    LoadPortfolios.prototype.controlbutton = null;

    return {
        'init': function(courseid) {
            return new LoadPortfolios(courseid);
        }
    };
});
