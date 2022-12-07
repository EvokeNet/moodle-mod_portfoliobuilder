<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Layout renderable class.
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class layout implements renderable, templatable {

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
        $layoututil = new \mod_portfoliobuilder\util\layout();
        $layout = $layoututil->get_user_layout($this->portfoliobuilder->course);

        $data = [
            'id' => $this->portfoliobuilder->id,
            'name' => $this->portfoliobuilder->name,
            'cmid' => $this->context->instanceid,
            'courseid' => $this->portfoliobuilder->course,
            'contextid' => $this->context->id,
            'sesskey' => sesskey(),
            'istimeline' => $layout === 'timeline',
            'ismansory' => $layout === 'mansory',
            'isblog' => $layout === 'blog',
        ];

        return $data;
    }
}
