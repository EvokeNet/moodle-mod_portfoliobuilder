<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use mod_portfoliobuilder\util\group;
use renderable;
use templatable;
use renderer_base;

/**
 * Index renderable class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class index implements renderable, templatable {

    public $context;
    public $course;

    public function __construct($context, $course) {
        $this->context = $context;
        $this->course = $course;
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
        $groupsutil = new group();

        $usercoursegroups = $groupsutil->get_user_groups($this->course->id);

        return [
            'courseid' => $this->course->id,
            'hasgroup' => !empty($usercoursegroups)
        ];
    }
}
