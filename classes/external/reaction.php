<?php

namespace mod_portfoliobuilder\external;

use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;
use mod_portfoliobuilder\util\reaction as reactionutil;

/**
 * Reaction external api class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class reaction extends external_api {
    /**
     * Create chapter parameters
     *
     * @return external_function_parameters
     */
    public static function toggle_parameters() {
        return new external_function_parameters([
            'entryid' => new external_value(PARAM_INT, 'The submission id'),
            'reactionid' => new external_value(PARAM_INT, 'The reaction id')
        ]);
    }

    /**
     * Create chapter method
     *
     * @param int $entryid
     * @param int $reactionid
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function toggle($entryid, $reactionid) {
        // We always must pass webservice params through validate_parameters.
        self::validate_parameters(self::toggle_parameters(),
            ['entryid' => $entryid, 'reactionid' => $reactionid]);

        $reactionutil = new reactionutil();

        $message = $reactionutil->toggle_reaction($entryid, $reactionid);

        return [
            'status' => 'ok',
            'message' => $message
        ];
    }

    /**
     * Create chapter return fields
     *
     * @return external_single_structure
     */
    public static function toggle_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'message' => new external_value(PARAM_RAW, 'Return message')
            )
        );
    }
}
