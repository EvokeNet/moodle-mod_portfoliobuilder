/**
 * Some UI stuff for portfolios page.
 *
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

import * as DynamicTable from 'core_table/dynamic';
import * as Str from 'core/str';
import CheckboxToggleAll from 'core/checkbox-toggleall';
import CustomEvents from 'core/custom_interaction_events';
import DynamicTableSelectors from 'core_table/local/dynamic/selectors';
import ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import Pending from 'core/pending';
import jQuery from 'jquery';
import {showAddNote, showSendMessage} from 'mod_portfoliobuilder/local/portfolios/bulkactions';
/* eslint-disable */
const Selectors = {
    bulkActionSelect: "#formactionid",
    bulkUserSelectedCheckBoxes: "input[data-togglegroup='portfolios-table'][data-toggle='slave']:checked",
    checkCountButton: "#checkall",
    showCountText: '[data-region="portfolios-count"]',
    showCountToggle: '[data-action="showcount"]',
    stateHelpIcon: '[data-region="state-help-icon"]',
    tableForm: uniqueId => `form[data-table-unique-id="${uniqueId}"]`,
};

export const init = ({
                         uniqueid,
                         noteStateNames = {},
                     }) => {
    const root = document.querySelector(Selectors.tableForm(uniqueid));
    const getTableFromUniqueId = uniqueId => root.querySelector(DynamicTableSelectors.main.fromRegionId(uniqueId));

    /**
     * Private method.
     *
     * @method registerEventListeners
     * @private
     */
    const registerEventListeners = () => {
        CustomEvents.define(Selectors.bulkActionSelect, [CustomEvents.events.accessibleChange]);
        jQuery(Selectors.bulkActionSelect).on(CustomEvents.events.accessibleChange, e => {
            const bulkActionSelect = e.target.closest('select');
            const action = bulkActionSelect.value;
            const tableRoot = getTableFromUniqueId(uniqueid);
            const checkboxes = tableRoot.querySelectorAll(Selectors.bulkUserSelectedCheckBoxes);
            const pendingPromise = new Pending('mod_portfoliobuilder/portfolios:bulkActionSelect');

            if (action.indexOf('#') !== -1) {
                e.preventDefault();

                const ids = [];
                checkboxes.forEach(checkbox => {
                    ids.push(checkbox.getAttribute('name').replace('user', ''));
                });

                let bulkAction;
                if (action === '#messageselect') {
                    bulkAction = showSendMessage(ids);
                } else if (action === '#addgroupnote') {
                    bulkAction = showAddNote(
                        root.dataset.courseId,
                        ids,
                        noteStateNames,
                        root.querySelector(Selectors.stateHelpIcon)
                    );
                }

                if (bulkAction) {
                    const pendingBulkAction = new Pending('mod_portfoliobuilder/portfolios:bulkActionSelected');
                    bulkAction
                        .then(modal => {
                            modal.getRoot().on(ModalEvents.hidden, () => {
                                // Focus on the action select when the dialog is closed.
                                bulkActionSelect.focus();
                            });

                            pendingBulkAction.resolve();
                            return modal;
                        })
                        .catch(Notification.exception);
                }
            } else if (action !== '' && checkboxes.length) {
                bulkActionSelect.form.submit();
            }

            resetBulkAction(bulkActionSelect);
            pendingPromise.resolve();
        });

        root.addEventListener('click', e => {
            // Handle clicking of the "Show [all|count]" and "Select all" actions.
            const showCountLink = root.querySelector(Selectors.showCountToggle);
            const checkCountButton = root.querySelector(Selectors.checkCountButton);

            const showCountLinkClicked = showCountLink && showCountLink.contains(e.target);
            const checkCountButtonClicked = checkCountButton && checkCountButton.contains(e.target);

            if (showCountLinkClicked || checkCountButtonClicked) {
                e.preventDefault();

                const tableRoot = getTableFromUniqueId(uniqueid);

                DynamicTable.setPageSize(tableRoot, showCountLink.dataset.targetPageSize)
                    .then(tableRoot => {
                        // Always update the toggle state.
                        // This ensures that the bulk actions are disabled after changing the page size.
                        CheckboxToggleAll.setGroupState(root, 'portfolios-table', checkCountButtonClicked);

                        return tableRoot;
                    })
                    .catch(Notification.exception);
            }
        });

        // When the content is refreshed, update the row counts in various places.
        root.addEventListener(DynamicTable.Events.tableContentRefreshed, e => {
            const showCountLink = root.querySelector(Selectors.showCountToggle);
            const checkCountButton = root.querySelector(Selectors.checkCountButton);

            const tableRoot = e.target;

            const defaultPageSize = parseInt(root.dataset.tableDefaultPerPage, 10);
            const currentPageSize = parseInt(tableRoot.dataset.tablePageSize, 10);
            const totalRowCount = parseInt(tableRoot.dataset.tableTotalRows, 10);

            CheckboxToggleAll.updateSlavesFromMasterState(root, 'portfolios-table');

            const pageCountStrings = [
                {
                    key: 'countparticipantsfound',
                    component: 'core_user',
                    param: totalRowCount,
                },
            ];


            if (totalRowCount <= defaultPageSize) {
                // There are fewer than the default page count numbers of rows.
                showCountLink.classList.add('hidden');

                if (checkCountButton) {
                    checkCountButton.classList.add('hidden');
                }
            } else if (totalRowCount <= currentPageSize) {
                // The are fewer than the current page size.
                pageCountStrings.push({
                    key: 'showperpage',
                    component: 'core',
                    param: defaultPageSize,
                });

                pageCountStrings.push({
                    key: 'selectalluserswithcount',
                    component: 'core',
                    param: defaultPageSize,
                });

                // Show the 'Show [x]' link.
                showCountLink.classList.remove('hidden');
                showCountLink.dataset.targetPageSize = defaultPageSize;

                if (checkCountButton) {
                    // The 'Check all [x]' button is only visible when there are values to set.
                    checkCountButton.classList.add('hidden');
                }
            } else {
                pageCountStrings.push({
                    key: 'showall',
                    component: 'core',
                    param: totalRowCount,
                });

                pageCountStrings.push({
                    key: 'selectalluserswithcount',
                    component: 'core',
                    param: totalRowCount,
                });
                console.log(showCountLink);
                // Show both the 'Show [x]' link, and the 'Check all [x]' button.
                showCountLink.classList.remove('hidden');
                showCountLink.dataset.targetPageSize = totalRowCount;

                if (checkCountButton) {
                    checkCountButton.classList.remove('hidden');
                }
            }

            Str.get_strings(pageCountStrings)
                .then(([showingParticipantCountString, showCountString, selectCountString]) => {
                    const showingParticipantCount = root.querySelector(Selectors.showCountText);
                    console.log(showingParticipantCount);
                    showingParticipantCount.innerHTML = showingParticipantCountString;

                    if (showCountString) {
                        showCountLink.innerHTML = showCountString;
                    }

                    if (selectCountString && checkCountButton) {
                        checkCountButton.value = selectCountString;
                    }

                    return;
                })
                .catch(Notification.exception);
        });
    };

    const resetBulkAction = bulkActionSelect => {
        bulkActionSelect.value = '';
    };

    registerEventListeners();
};
