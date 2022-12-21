<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

use mod_portfoliobuilder\util\group;
use mod_portfoliobuilder\util\user;

/**
 * Evoke portfolio timeline class helper
 *
 * @copyright   2021 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class timeline {
    public $courseid;
    private $portfoliocontexts = [];

    public function __construct($courseid) {
        $this->courseid = $courseid;
    }

    public function loadmy($offset = 0) {
        $entryutil = new entry();

        $entries = $entryutil->get_user_course_entries($this->courseid);

        if (!$entries) {
            return [];
        }
        return $entries;
    }

    public function loadteam($portfolioid, $offset = 0) {
        global $DB, $USER;

        $portfolio = $DB->get_record('evokeportfolio', ['id' => $portfolioid], '*', MUST_EXIST);

        $submissionutil = new submission();

        $groupsutil = new group();

        $usercoursegroups = $groupsutil->get_user_groups_ids($this->courseid);

        $teamsubmissions = [];
        if ($usercoursegroups) {
            $teamsubmissions = $submissionutil->get_portfolio_submissions($portfolio, $this->get_portfolio_context($portfolio->id), null, $usercoursegroups, 10, $offset);
        }

        $userpicture = user::get_user_image_or_avatar($USER);

        $data = [
            'userpicture' => $userpicture,
            'userfullname' => fullname($USER),
            'submissions' => $teamsubmissions,
            'hasmoreitems' => !empty($teamsubmissions),
            'cangrade' => has_capability('mod/evokeportfolio:grade', $this->get_portfolio_context($portfolio->id)),
            'isevaluated' => $portfolio->grade != 0
        ];

        return $data;
    }

    public function loadnetwork($portfolioid, $offset = 0) {
        global $DB, $USER;

        $portfolio = $DB->get_record('evokeportfolio', ['id' => $portfolioid], '*', MUST_EXIST);

        $submissionutil = new submission();

        $networksubmissions = $submissionutil->get_portfolio_submissions($portfolio, $this->get_portfolio_context($portfolio->id), null, null, 10, $offset);

        $userpicture = user::get_user_image_or_avatar($USER);

        $data = [
            'userpicture' => $userpicture,
            'userfullname' => fullname($USER),
            'submissions' => $networksubmissions,
            'hasmoreitems' => !empty($networksubmissions),
            'cangrade' => has_capability('mod/evokeportfolio:grade', $this->get_portfolio_context($portfolio->id)),
            'isevaluated' => $portfolio->grade != 0
        ];

        return $data;
    }

    private function get_portfolio_context($portfolioid) {
        if (isset($this->portfoliocontexts[$portfolioid])) {
            return $this->portfoliocontexts[$portfolioid];
        }

        $coursemodule = get_coursemodule_from_instance('evokeportfolio', $portfolioid);

        $this->portfoliocontexts[$portfolioid] = \context_module::instance($coursemodule->id);

        return $this->portfoliocontexts[$portfolioid];
    }
}
