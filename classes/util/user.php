<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

use user_picture;
use context_course;

/**
 * Evoke utility class helper
 *
 * @copyright   2021 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class user {
    /**
     * Get all users enrolled in a course by id
     *
     * @param int $userid
     * @param context_course $context
     *
     * @return \stdClass
     * @throws \dml_exception
     */
    public function get_by_id($userid, context_course $context) {
        global $DB;

        $ufields = user_picture::fields('u');

        list($esql, $enrolledparams) = get_enrolled_sql($context);

        $sql = "SELECT $ufields
                FROM {user} u
                JOIN ($esql) je ON je.id = u.id
                WHERE u.id = :userid";

        $params = array_merge($enrolledparams, ['userid' => $userid]);

        return $DB->get_record_sql($sql, $params, MUST_EXIST);
    }

    public function get_user_image_or_avatar($user) {
        global $PAGE;

        if ($PAGE->theme->name == 'moove' && function_exists('theme_moove_get_user_avatar_or_image')) {
            $userpicture = theme_moove_get_user_avatar_or_image($user);
        }

        if (!$userpicture) {
            $userpicture = new user_picture($user);
            $userpicture->size = 1;
            $userpicture = $userpicture->get_url($PAGE);
        }

        if ($userpicture instanceof \moodle_url) {
            return $userpicture->out();
        }

        return $userpicture;
    }
}
