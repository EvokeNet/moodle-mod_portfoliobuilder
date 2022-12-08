<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

use user_picture;

/**
 * Evoke utility class helper
 *
 * @copyright   2021 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class user {
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
