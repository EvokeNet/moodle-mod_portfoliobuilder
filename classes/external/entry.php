<?php

namespace mod_portfoliobuilder\external;

use core\notification;
use core_external\external_api;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_function_parameters;
use mod_portfoliobuilder\forms\chapter_form;

/**
 * Entry external api class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class entry extends external_api {
    /**
     * Delete chapter parameters
     *
     * @return external_function_parameters
     */
    public static function delete_parameters() {
        return new external_function_parameters([
            'id' => new external_value(PARAM_INT, 'The entry id', VALUE_REQUIRED)
        ]);
    }

    /**
     * Delete chapter method
     *
     * @param array $id
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function delete($id) {
        self::validate_parameters(self::delete_parameters(), ['id' => $id]);

        $entryutil = new \mod_portfoliobuilder\util\entry();

        $entryutil->delete_entry($id);

        notification::success(get_string('entrydelete_success', 'mod_portfoliobuilder'));

        return [
            'status' => 'ok',
            'message' => get_string('entrydelete_success', 'mod_portfoliobuilder')
        ];
    }

    /**
     * Delete chapter return fields
     *
     * @return external_single_structure
     */
    public static function delete_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'message' => new external_value(PARAM_TEXT, 'Return message')
            )
        );
    }
}
