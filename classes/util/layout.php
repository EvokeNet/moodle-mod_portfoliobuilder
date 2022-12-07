<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Layout utility class helper
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class layout {
    public function user_chose_layout($courseid) {
        $userlayout = $this->get_user_layout($courseid);

        if (!$userlayout) {
            return false;
        }

        return true;
    }

    public function get_user_layout($courseid) {
        $prefname = 'portfoliolayout-course-' . $courseid;

        return get_user_preferences($prefname, 'timeline');
    }

    public function set_user_layout($courseid, $layout) {
        $prefname = 'portfoliolayout-course-' . $courseid;

        return set_user_preference($prefname, $layout);
    }
}