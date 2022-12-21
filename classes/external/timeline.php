<?php

namespace mod_portfoliobuilder\external;

use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;

/**
 * Timeline external api class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class timeline extends external_api {
    /**
     * Create chapter parameters
     *
     * @return external_function_parameters
     */
    public static function load_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The block course id'),
            'type' => new external_value(PARAM_ALPHAEXT, 'The offset value'),
            'offset' => new external_value(PARAM_INT, 'The offset value')
        ]);
    }

    /**
     * Create chapter method
     *
     * @param int $courseid
     * @param int $type
     * @param int $offset
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function load($courseid, $type, $offset) {
        global $PAGE;

        // We always must pass webservice params through validate_parameters.
        self::validate_parameters(self::load_parameters(), [
            'courseid' => $courseid,
            'type' => $type,
            'offset' => $offset
        ]);

        $context = \context_course::instance($courseid);
        $PAGE->set_context($context);

        $timelineutil = new \mod_portfoliobuilder\util\timeline($courseid);

        if ($type == 'my') {
            $returndata = $timelineutil->loadmy($offset);
        }

        if ($type == 'team') {
            $returndata = $timelineutil->loadteam($offset);
        }

        if ($type == 'network') {
            $returndata = $timelineutil->loadnetwork($offset);
        }

        return [
            'status' => 'ok',
            'data' => json_encode($returndata)
        ];
    }

    /**
     * Create chapter return fields
     *
     * @return external_single_structure
     */
    public static function load_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'data' => new external_value(PARAM_RAW, 'Return data')
            )
        );
    }

    /**
     * Load evokation parameters
     *
     * @return external_function_parameters
     */
    public static function loadevokation_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The block course id'),
            'type' => new external_value(PARAM_ALPHAEXT, 'The offset value'),
            'offset' => new external_value(PARAM_INT, 'The offset value')
        ]);
    }

    /**
     * Load evokation method
     *
     * @param int $courseid
     * @param int $type
     * @param int $offset
     * @param int $portfolioid
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function loadevokation($courseid, $type, $offset) {
        global $PAGE, $CFG;

        // We always must pass webservice params through validate_parameters.
        self::validate_parameters(self::loadevokation_parameters(), [
            'courseid' => $courseid,
            'type' => $type,
            'offset' => $offset,
        ]);

        $context = \context_course::instance($courseid);
        $PAGE->set_context($context);

        $timelineutil = new \mod_portfoliobuilder\util\timeline\evokation($courseid);

        if ($type == 'my') {
            $returndata = $timelineutil->loadmy($offset);
        }

        if ($type == 'team') {
            $returndata = $timelineutil->loadteam($offset);
        }

        if ($type == 'network') {
            $returndata = $timelineutil->loadnetwork($offset);
        }

        $returndata['wwwroot'] = $CFG->wwwroot;

        return [
            'status' => 'ok',
            'data' => json_encode($returndata)
        ];
    }

    /**
     * Load evokation return fields
     *
     * @return external_single_structure
     */
    public static function loadevokation_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'data' => new external_value(PARAM_RAW, 'Return data')
            )
        );
    }

    /**
     * Create chapter parameters
     *
     * @return external_function_parameters
     */
    public static function loadgroup_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The block course id'),
            'type' => new external_value(PARAM_ALPHAEXT, 'The offset value'),
            'offset' => new external_value(PARAM_INT, 'The offset value'),
            'portfolioid' => new external_value(PARAM_INT, 'The portfolio id value', VALUE_OPTIONAL)
        ]);
    }

    /**
     * Create chapter method
     *
     * @param int $courseid
     * @param int $type
     * @param int $offset
     * @param int $portfolioid
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function loadgroup($courseid, $type, $offset, $portfolioid = null) {
        global $PAGE, $CFG;

        // We always must pass webservice params through validate_parameters.
        self::validate_parameters(self::loadgroup_parameters(), [
            'courseid' => $courseid,
            'type' => $type,
            'offset' => $offset,
            'portfolioid' => $portfolioid
        ]);

        $context = \context_course::instance($courseid);
        $PAGE->set_context($context);

        $timelineutil = new \mod_portfoliobuilder\util\timeline\portfoliogroup($courseid);

        if ($type == 'team') {
            $returndata = $timelineutil->loadteam($portfolioid, $offset);
        }

        if ($type == 'network') {
            $returndata = $timelineutil->loadnetwork($portfolioid, $offset);
        }

        $returndata['wwwroot'] = $CFG->wwwroot;

        return [
            'status' => 'ok',
            'data' => json_encode($returndata)
        ];
    }

    /**
     * Create chapter return fields
     *
     * @return external_single_structure
     */
    public static function loadgroup_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'data' => new external_value(PARAM_RAW, 'Return data')
            )
        );
    }
}