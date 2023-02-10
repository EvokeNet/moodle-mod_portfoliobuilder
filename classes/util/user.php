<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

use user_picture;
use context_course;

/**
 * User utility class helper
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
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

    public function get_user_ids_with_grade_capability($context) {
        global $DB, $CFG;

        $fields = 'DISTINCT u.id, u.firstname, u.lastname, u.email';

        $capjoin = get_enrolled_with_capabilities_join($context, '', 'mod/portfoliobuilder:grade');

        $from = ' {user} u ' . $capjoin->joins;

        $sql = "SELECT {$fields} FROM {$from} WHERE {$capjoin->wheres}";

        $users = $DB->get_records_sql($sql, $capjoin->params);

        $ids = [];

        $siteadmins = explode(',', $CFG->siteadmins);

        if (is_array($siteadmins)) {
            $ids = $siteadmins;
        } else if (!empty($siteadmins)) {
            $ids = [(int) $siteadmins];
        }

        if (!$users) {
            return $ids;
        }

        foreach ($users as $user) {
            $ids[] = $user->id;
        }

        return $ids;
    }
}
