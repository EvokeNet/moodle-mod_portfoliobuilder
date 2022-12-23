<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Grade utility class helper
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class grade {
    public function get_portfolio_with_evaluation($courseid) {
        global $DB;

        $sql = 'SELECT * FROM {portfoliobuilder} WHERE course = :courseid AND grade <> 0';

        return $DB->get_record_sql($sql, ['courseid' => $courseid]);
    }

    public function user_has_grade($portfolio, $userid) {
        $usergrade = $this->get_user_grade($portfolio, $userid);

        if ($usergrade) {
            return true;
        }

        return false;
    }

    public function get_user_grade($portfolio, $userid) {
        global $DB;

        if ($portfolio->grade == 0) {
            return false;
        }

        $usergrade = $DB->get_record('evokeportfolio_grades',
            [
                'portfolioid' => $portfolio->id,
                'userid' => $userid
            ]
        );

        if (!$usergrade) {
            return false;
        }

        return $usergrade->grade;
    }

    public function get_user_grade_string($portfolio, $userid) {
        global $DB;

        $usergrade = $this->get_user_grade($portfolio, $userid);

        if (!$usergrade) {
            return false;
        }

        if ($portfolio->grade > 0) {
            return (int)$usergrade;
        }

        $scale = $DB->get_record('scale', ['id' => abs($portfolio->grade)], '*', MUST_EXIST);

        $scales = explode(',', $scale->scale);

        $scaleindex = (int)$usergrade - 1;

        return $scales[$scaleindex];
    }
}
