<?php

namespace mod_portfoliobuilder\external;

use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_function_parameters;
use core\context\module as context_module;

/**
 * Comment external api class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class alchemy extends external_api {
    /**
     * Create comment parameters
     *
     * @return external_function_parameters
     */
    public static function comment_add_parameters() {
        return new external_function_parameters([
            'entryid' => new external_value(PARAM_INT, 'The entry id', VALUE_REQUIRED),
            'message' => new external_value(PARAM_RAW, 'The post message', VALUE_REQUIRED)
        ]);
    }

    /**
     * Create comment method
     *
     * @param $entryid
     * @param $message
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function comment_add($entryid, $message) {
        global $DB, $USER;

        self::validate_parameters(self::comment_add_parameters(), ['entryid' => $entryid, 'message' => $message]);

        $sql = 'SELECT e.id, e.userid, p.id as portfolioid, p.course, p.name as portfolioname
                FROM {portfoliobuilder_entries} e
                INNER JOIN {portfoliobuilder} p ON p.id = e.portfolioid
                WHERE e.id = :entryid';

        $utildata = $DB->get_record_sql($sql, ['entryid' => $entryid], MUST_EXIST);
        $cm = get_coursemodule_from_instance('portfoliobuilder', $utildata->portfolioid);

        $contextmodule = context_module::instance($cm->id);

        $usercomment = new \stdClass();
        $usercomment->entryid = $entryid;
        $usercomment->userid = $USER->id;
        $usercomment->text = $message;
        $usercomment->timecreated = time();
        $usercomment->timemodified = time();

        $insertedid = $DB->insert_record('portfoliobuilder_comments', $usercomment);
        $usercomment->id = $insertedid;

        $params = array(
            'context' => $contextmodule,
            'objectid' => $insertedid,
            'courseid' => $utildata->course,
            'relateduserid' => $utildata->userid
        );
        $event = \mod_portfoliobuilder\event\comment_added::create($params);
        $event->add_record_snapshot('portfoliobuilder_comments', $usercomment);
        $event->trigger();

        return [
            'status' => 'ok'
        ];
    }

    /**
     * Create comment return fields
     *
     * @return external_single_structure
     */
    public static function comment_add_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status')
            )
        );
    }

    /**
     * Create comment parameters
     *
     * @return external_function_parameters
     */
    public static function comment_get_parameters() {
        return new external_function_parameters([
            'starttime' => new external_value(PARAM_TEXT, 'The start time in Y-m-d H:i:s format', VALUE_REQUIRED),
            'endtime' => new external_value(PARAM_TEXT, 'The end time in Y-m-d H:i:s format', VALUE_REQUIRED),
            'courseid' => new external_value(PARAM_TEXT, 'The entries course id', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Create comment method
     *
     * @param $starttime
     * @param $endtime
     * @param $courseid
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function comment_get($starttime, $endtime, $courseid = null) {
        global $DB;

        self::validate_parameters(self::comment_get_parameters(), ['starttime' => $starttime, 'endtime' => $endtime, 'courseid' => $courseid]);

        $startdatetime = \DateTime::createFromFormat( 'Y-m-d H:i:s', $starttime, \core_date::get_server_timezone_object());
        $enddatetime = \DateTime::createFromFormat( 'Y-m-d H:i:s', $endtime, \core_date::get_server_timezone_object());

        $starttime = $startdatetime->getTimestamp();
        $endtime = $enddatetime->getTimestamp();

        if ($starttime >= $endtime) {
            throw new \Exception('Start time needs to be less than end time');
        }

        if ($starttime >= time()) {
            throw new \Exception('Start time needs to be less than now');
        }

        unset($startdatetime, $enddatetime);

        $sql = 'SELECT e.id, e.userid, e.title, e.content, e.contentformat, p.id as portfolioid, p.name as portfolioname, p.course AS courseid, p.intro, p.introformat
                FROM {portfoliobuilder_entries} e
                INNER JOIN {portfoliobuilder} p ON p.id = e.portfolioid
                WHERE (e.content IS NOT NULL AND e.content <> "") AND e.timecreated >= :starttime AND e.timecreated <= :endtime';

        $params = [
            'starttime' => $starttime,
            'endtime' => $endtime
        ];

        if ($courseid) {
            $sql .= ' AND e.courseid = :courseid';

            $params['courseid'] = $courseid;
        }

        $entries = $DB->get_records_sql($sql, $params);

        if (!$entries) {
            return ['entries' => []];
        }

        $data = [];
        foreach ($entries as $entry) {

            $content = strip_tags(format_text($entry->content, $entry->contentformat));
            $intro = strip_tags(format_text($entry->intro, $entry->introformat));

            if (empty($content)) {
                continue;
            }

            $data[] = [
                'id' => $entry->id,
                'userid' => $entry->userid,
                'title' => $entry->title,
                'content' => $content,
                'portfolioid' => $entry->portfolioid,
                'portfolioname' => $entry->portfolioname,
                'portfoliodescription' => $intro,
                'courseid' => $entry->courseid,
            ];
        }

        return ['entries' => $data];
    }

    /**
     * Create comment return fields
     *
     * @return external_single_structure
     */
    public static function comment_get_returns() {
        return new external_function_parameters(
            array(
                'entries' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'The entry id'),
                            'userid' => new external_value(PARAM_INT, "The user id"),
                            'title' => new external_value(PARAM_TEXT, "The entry title"),
                            'content' => new external_value(PARAM_RAW, "The entry content text"),
                            'portfolioid' => new external_value(PARAM_INT, "The portfolio id"),
                            'portfolioname' => new external_value(PARAM_TEXT, "The portfolio name"),
                            'portfoliodescription' => new external_value(PARAM_TEXT, "The portfolio intro/description text"),
                            'courseid' => new external_value(PARAM_INT, "The course id"),
                        )
                    )
                )
            )
        );
    }
}
