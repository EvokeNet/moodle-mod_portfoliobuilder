<?php

namespace mod_portfoliobuilder\external;

use context;
use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;
use mod_portfoliobuilder\form\entry as entryform;

/**
 * Portfolio external api class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class portfolio extends external_api {
    /**
     * Create badge parameters
     *
     * @return external_function_parameters
     */
    public static function load_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The course id'),
            'type' => new external_value(PARAM_ALPHANUM, 'The portfolio type(group or network)'),
            'chapter' => new external_value(PARAM_INT, 'The chapter id', VALUE_OPTIONAL),
            'groupid' => new external_value(PARAM_INT, 'The group id', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Create badge method
     *
     * @param int $courseid
     * @param string $type
     * @param int $chapter
     * @param int $groupid
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function load($courseid, $type, $chapter = null, $groupid = null) {
        global $PAGE;

        if ($chapter == -1) {
            $chapter = null;
        }

        if ($groupid == -1) {
            $groupid = null;
        }

        // We always must pass webservice params through validate_parameters.
        self::validate_parameters(self::load_parameters(), ['courseid' => $courseid, 'type' => $type, 'chapter' => $chapter, 'groupid' => $groupid]);

        $context = \context_course::instance($courseid);

        $PAGE->set_context($context);

        $portfolioutil = new \mod_portfoliobuilder\util\portfolio($context, $courseid);

        $portfolios = [];
        if ($type == 'team') {
            $portfolios = $portfolioutil->get_user_course_groups_portfolios();
        }

        if ($type == 'network') {
            if ($groupid) {
                $portfolios = $portfolioutil->get_course_group_portfolios($groupid, $chapter);
            } else {
                $portfolios = $portfolioutil->get_course_portfolios($chapter);
            }
        }

        return [
            'data' => json_encode($portfolios)
        ];
    }

    /**
     * Create badge return fields
     *
     * @return external_single_structure
     */
    public static function load_returns() {
        return new external_single_structure([
            'data' => new external_value(PARAM_RAW, 'Return data')
        ]);
    }
}