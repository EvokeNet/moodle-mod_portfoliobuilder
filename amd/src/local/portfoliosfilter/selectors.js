/**
 * Module containing the selectors for user filters.
 *
 * @module     mod_portfoliobuilder/local/user_filter/selectors
 * @copyright  2023 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const getFilterRegion = region => `[data-filterregion="${region}"]`;
const getFilterAction = action => `[data-filteraction="${action}"]`;
const getFilterField = field => `[data-filterfield="${field}"]`;

export default {
    filter: {
        region: getFilterRegion('filter'),
        actions: {
            remove: getFilterAction('remove'),
        },
        fields: {
            join: getFilterField('join'),
            type: getFilterField('type'),
        },
        regions: {
            values: getFilterRegion('value'),
        },
        byName: name => `${getFilterRegion('filter')}[data-filter-type="${name}"]`,
    },
    filterset: {
        region: getFilterRegion('actions'),
        actions: {
            addRow: getFilterAction('add'),
            applyFilters: getFilterAction('apply'),
            resetFilters: getFilterAction('reset'),
        },
        regions: {
            filtermatch: getFilterRegion('filtermatch'),
            filterlist: getFilterRegion('filters'),
            datasource: getFilterRegion('filtertypedata'),
        },
        fields: {
            join: `${getFilterRegion('filtermatch')} ${getFilterField('join')}`,
        },
    },
    data: {
        fields: {
            byName: name => `[data-field-name="${name}"]`,
            all: `${getFilterRegion('filtertypedata')} [data-field-name]`,
        },
        typeList: getFilterRegion('filtertypelist'),
        typeListSelect: `select${getFilterRegion('filtertypelist')}`,
    },
};
