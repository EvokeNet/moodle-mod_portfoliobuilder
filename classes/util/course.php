<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

use context_course;
use user_picture;

/**
 * Course utility class helper
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class course {
    /**
     * Get all users enrolled in a course by name
     *
     * @param string $name
     * @param \stdClass $course
     * @param context_course $context
     *
     * @return array
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function get_enrolled_users_by_name($name, context_course $context) {
        global $DB;

        list($ufields, $searchparams, $wherecondition) = $this->get_basic_search_conditions($name, $context);

        list($esql, $enrolledparams) = get_enrolled_sql($context);

        $sql = "SELECT $ufields
                FROM {user} u
                JOIN ($esql) je ON je.id = u.id
                WHERE $wherecondition";

        list($sort, $sortparams) = users_order_by_sql('u');
        $sql = "$sql ORDER BY $sort";

        $params = array_merge($searchparams, $enrolledparams, $sortparams);

        $users = $DB->get_records_sql($sql, $params, 0, 10);

        if (!$users) {
            return [];
        }

        return array_values($users);
    }

    /**
     * Helper method used by get_enrolled_users_by_name().
     *
     * @param string $search the search term, if any.
     * @param context_course $context course context
     *
     * @return array with three elements:
     *     string list of fields to SELECT,
     *     array query params. Note that the SQL snippets use named parameters,
     *     string contents of SQL WHERE clause.
     */
    protected function get_basic_search_conditions($search, context_course $context) {
        global $DB, $CFG, $USER;

        // Add some additional sensible conditions.
        $tests = ["u.id <> :guestid", "u.deleted = 0", "u.confirmed = 1", "u.id <> :loggedinuser"];
        $params = [
            'guestid' => $CFG->siteguest,
            'loggedinuser' => $USER->id
        ];

        if (!empty($search)) {
            $conditions = get_extra_user_fields($context);
            foreach (get_all_user_name_fields() as $field) {
                $conditions[] = 'u.'.$field;
            }

            $conditions[] = $DB->sql_fullname('u.firstname', 'u.lastname');

            $searchparam = '%' . $search . '%';

            $i = 0;
            foreach ($conditions as $key => $condition) {
                $conditions[$key] = $DB->sql_like($condition, ":con{$i}00", false);
                $params["con{$i}00"] = $searchparam;
                $i++;
            }

            $tests[] = '(' . implode(' OR ', $conditions) . ')';
        }

        $wherecondition = implode(' AND ', $tests);

        $fields = \core_user\fields::for_identity($context, false)->excluding('username', 'lastaccess');

        $extrafields = $fields->get_required_fields();
        $extrafields[] = 'username';
        $extrafields[] = 'lastaccess';
        $extrafields[] = 'maildisplay';

        $ufields = user_picture::fields('u', $extrafields);

        return [$ufields, $params, $wherecondition];
    }
}
