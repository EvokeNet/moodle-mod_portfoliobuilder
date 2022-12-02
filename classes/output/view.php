<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * View renderable class.
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class view implements renderable, templatable {

    public $portfoliobuilder;
    public $context;

    public function __construct($portfoliobuilder, $context) {
        $this->portfoliobuilder = $portfoliobuilder;
        $this->context = $context;
    }

    /**
     * Export the data
     *
     * @param renderer_base $output
     *
     * @return array|\stdClass
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function export_for_template(renderer_base $output) {

        $prefname = 'portfoliolayout-course-' . $this->portfoliobuilder->course;

        $userchoselayout = get_user_preferences($prefname, false);

        $data = [
            'id' => $this->portfoliobuilder->id,
            'name' => $this->portfoliobuilder->name,
            'intro' => format_module_intro('portfoliobuilder', $this->portfoliobuilder, $this->context->instanceid),
            'cmid' => $this->context->instanceid,
            'courseid' => $this->portfoliobuilder->course,
            'contextid' => $this->context->id,
            'cangrade' => has_capability('mod/portfoliobuilder:grade', $this->context),
            'isevaluated' => $this->portfoliobuilder->grade != 0,
            'userchoselayout' => $userchoselayout
        ];

        return $data;
    }
}
