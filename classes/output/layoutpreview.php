<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Layout preview renderable class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class layoutpreview implements renderable, templatable {

    public $portfoliobuilder;
    public $context;
    public $type;

    public function __construct($portfoliobuilder, $context, $type) {
        $this->portfoliobuilder = $portfoliobuilder;
        $this->context = $context;
        $this->type = $type;
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
        $data = [
            'id' => $this->portfoliobuilder->id,
            'name' => $this->portfoliobuilder->name,
            'cmid' => $this->context->instanceid,
            'courseid' => $this->portfoliobuilder->course,
            'contextid' => $this->context->id,
            'type' => $this->type
        ];

        return $data;
    }
}
