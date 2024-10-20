<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Chapter utility class helper
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class chapter {
    public function get_chapters_used_in_course($courseid) {
        global $DB;

        $sql  = 'SELECT chapter
                 FROM {portfoliobuilder}
                 WHERE course = :courseid
                 GROUP BY chapter
                 ORDER BY chapter';

        $chapters = $DB->get_records_sql($sql, ['courseid' => $courseid]);

        if (!$chapters) {
            return false;
        }

        $data = [];

        foreach ($chapters as $chapter) {
            if ($chapter->chapter < 6) {
                $data[] = [
                    'key' => $chapter->chapter,
                    'value' => get_string('chapter' . $chapter->chapter, 'mod_portfoliobuilder')
                ];

                continue;
            }
            $data[] = [
                'key' => $chapter->chapter,
                'value' => get_string('chapter', 'mod_portfoliobuilder') . ' ' . $chapter->chapter
            ];
        }

        return $data;
    }
}
