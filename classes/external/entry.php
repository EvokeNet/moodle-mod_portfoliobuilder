<?php

namespace mod_portfoliobuilder\external;

use context;
use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;
use mod_portfoliobuilder\form\entry as entryform;

/**
 * Badge criteria external api class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 Willian Mano {@link https://conecti.me}
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class entry extends external_api {
    /**
     * Create badge parameters
     *
     * @return external_function_parameters
     */
    public static function create_parameters() {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'The context id for the course module'),
            'courseid' => new external_value(PARAM_INT, 'The course id'),
            'portfolioid' => new external_value(PARAM_INT, 'The portfolio id'),
            'jsonformdata' => new external_value(PARAM_RAW, 'The data from the badge form, encoded as a json array')
        ]);
    }

    /**
     * Create badge method
     *
     * @param int $contextid
     * @param int $course
     * @param int $portfolioid
     * @param string $jsonformdata
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function create($contextid, $courseid, $portfolioid, $jsonformdata) {
        global $DB;

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(self::create_parameters(),
            ['contextid' => $contextid, 'courseid' => $courseid, 'portfolioid' => $portfolioid, 'jsonformdata' => $jsonformdata]);

        $context = context::instance_by_id($params['contextid'], MUST_EXIST);

        // We always must call validate_context in a webservice.
        self::validate_context($context);

        $serialiseddata = json_decode($params['jsonformdata']);

        $data = [];
        parse_str($serialiseddata, $data);

        $mform = new entryform($data, $data);

        $validateddata = $mform->get_data();

        if (!$validateddata) {
            throw new \moodle_exception('invalidformdata');
        }

        // TODO: Validar quando pointsarr for vazio.

        $now = time();
//
//        $skill = new \stdClass();
//        $skill->courseid = $courseid;
//        $skill->name = $validateddata->name;
//        $skill->points = json_encode($pointsarr);
//        $skill->timecreated = $now;
//        $skill->timemodified = $now;
//
//        $insertedid = $DB->insert_record('gamechanger_skills', $skill);
//
//        $skill->id = $insertedid;

        return [
            'status' => 'ok',
            'message' => get_string('createentry_success', 'mod_portfoliobuilder'),
            'data' => json_encode($skill)
        ];
    }

    /**
     * Create badge return fields
     *
     * @return external_single_structure
     */
    public static function create_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'message' => new external_value(PARAM_RAW, 'Return message'),
                'data' => new external_value(PARAM_RAW, 'Return data')
            )
        );
    }
}