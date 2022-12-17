<?php

namespace mod_portfoliobuilder\external;

use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;
use external_multiple_structure;
use mod_portfoliobuilder\util\user;

/**
 * Course external api class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class course extends external_api {
    /**
     * Get enrolled users parameters
     *
     * @return external_function_parameters
     */
    public static function enrolledusers_parameters() {
        return new external_function_parameters([
            'search' => new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'The course id', VALUE_REQUIRED),
                'name' => new external_value(PARAM_TEXT, 'The user name', VALUE_REQUIRED)
            ])
        ]);
    }

    /**
     * Get the list of all course's users
     *
     * @param array $search
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function enrolledusers($search) {
        global $DB, $PAGE;

        self::validate_parameters(self::enrolledusers_parameters(), ['search' => $search]);

        $search = (object)$search;

        $course = $DB->get_record('course', ['id' => $search->courseid], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);

        $PAGE->set_context($context);

        if (!is_enrolled($context) && !is_siteadmin()) {
            return [];
        }

        $courseutil = new \mod_portfoliobuilder\util\course();

        $users = $courseutil->get_enrolled_users_by_name($search->name, $context);

        $returndata = [];

        foreach ($users as $user) {
            $userpicture = new \user_picture($user);
            $returndata[] = [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => fullname($user),
                'picture' => $userpicture->get_url($PAGE)->out()
            ];
        }

        return [
            'users' => $returndata
        ];
    }

    /**
     * Get enrolled users return fields
     *
     * @return external_single_structure
     */
    public static function enrolledusers_returns() {
        return new external_function_parameters(
            array(
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'The user id'),
                            'username' => new external_value(PARAM_TEXT, "The user username"),
                            'fullname' => new external_value(PARAM_TEXT, "The user fullname"),
                            'picture' => new external_value(PARAM_TEXT, "The user picture url")
                        )
                    )
                )
            )
        );
    }
}