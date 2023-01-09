<?php

namespace mod_portfoliobuilder\external;

use context;
use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;

/**
 * Grade external api class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class grade extends external_api {
    /**
     * Grade parameters
     *
     * @return external_function_parameters
     */
    public static function grade_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course module'),
            'jsonformdata' => new external_value(PARAM_RAW, 'The data from the chapter form, encoded as a json array')
        ]);
    }

    /**
     * Grade method
     *
     * @param int $contextid
     * @param int $chapterid
     * @param int $userid
     * @param string $jsonformdata
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function grade($contextid, $jsonformdata) {
        global $DB;

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::grade_parameters(), [
            'contextid' => $contextid,
            'jsonformdata' => $jsonformdata
        ]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);

        $serialiseddata = json_decode($params['jsonformdata']);

        $data = [];
        parse_str($serialiseddata, $data);

        $gradeutil = new \mod_portfoliobuilder\util\grade();
        $portfolio = $gradeutil->get_portfolio_with_evaluation($data['courseid']);

        if (!$portfolio) {
            throw new \moodle_exception('missingportfoliowithevaluation', 'mod_portfoliobuilder');
        }

        $gradeutil->grade_user($portfolio, $data['userid'], $data['grade']);

        return [
            'status' => 'ok',
            'message' => get_string('grading_success', 'mod_portfoliobuilder'),
            'assessmenttext' => get_string('assessment', 'mod_portfoliobuilder') . ': ' . $gradeutil->get_user_grade_string($portfolio ,$data['userid'])
        ];
    }

    /**
     * Grade return fields
     *
     * @return external_single_structure
     */
    public static function grade_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'message' => new external_value(PARAM_TEXT, 'Return message'),
                'assessmenttext' => new external_value(PARAM_TEXT, 'Assessment text message')
            )
        );
    }
}