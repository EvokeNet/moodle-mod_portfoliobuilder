<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Log utility class helper
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class log {
    public function get_last_time_accessed_portfolios($courseid, $userid = null) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $sql = 'SELECT id, timecreated
                FROM {logstore_standard_log}
                WHERE
                    component = :component
                    AND target = :target
                    AND contextlevel = 50
                    AND courseid = :courseid
                    AND userid = :userid 
                ORDER BY id DESC LIMIT 1 OFFSET 1';

        $record = $DB->get_record_sql($sql, [
            'component' => 'mod_portfoliobuilder',
            'target' => 'course_module_instance_list',
            'courseid' => $courseid, 'userid' => $userid
        ]);

        if (!$record) {
            return 0;
        }

        return $record->timecreated;
    }
}
