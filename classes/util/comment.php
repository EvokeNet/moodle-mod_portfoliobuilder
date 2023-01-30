<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Reaction utility class helper
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class comment {
    public function get_total_course_comments($courseid, $userid) {
        global $DB;

        $sql = 'SELECT count(*)
                FROM {portfoliobuilder_comments} c
                INNER JOIN {portfoliobuilder_entries} e ON e.id = c.entryid
                WHERE e.courseid = :courseid AND e.userid = :userid';

        return $DB->count_records_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
    }
}
