/**
 * Course ID filter.
 *
 * @module     mod_portfoliobuilder/local/portfoliosfilter/filtertypes/courseid
 * @copyright  2023 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Filter from '../filter';

export default class extends Filter {
    constructor(filterType, filterSet) {
        super(filterType, filterSet);
    }

    async addValueSelector() {
        // eslint-disable-line no-empty-function
    }

    /**
     * Get the composed value for this filter.
     *
     * @returns {Object}
     */
    get filterValue() {
        return {
            name: this.name,
            jointype: 1,
            values: [parseInt(this.rootNode.dataset.tableCourseId, 10)],
        };
    }
}
