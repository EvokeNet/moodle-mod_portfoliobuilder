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
    public function get_total_course_comments($courseid, $userid, $chapter = null) {
        global $DB;

        $sql = 'SELECT count(*)
                FROM {portfoliobuilder_comments} c
                INNER JOIN {portfoliobuilder_entries} e ON e.id = c.entryid
                INNER JOIN {portfoliobuilder} p ON p.id = e.portfolioid
                WHERE e.courseid = :courseid AND e.userid = :userid';

        $parameters = ['userid' => $userid, 'courseid' => $courseid];

        if (!is_null($chapter)) {
            $sql .= ' AND p.chapter = :chapter';

            $parameters['chapter'] = $chapter;
        }

        return $DB->count_records_sql($sql, $parameters);
    }
}
