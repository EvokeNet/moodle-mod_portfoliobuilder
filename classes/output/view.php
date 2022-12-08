<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use mod_portfoliobuilder\util\entry;
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
        global $USER;

        $layoututil = new \mod_portfoliobuilder\util\layout();
        $layout = $layoututil->get_user_layout($this->portfoliobuilder->course);

        $data = [
            'id' => $this->portfoliobuilder->id,
            'name' => $this->portfoliobuilder->name,
            'intro' => format_module_intro('portfoliobuilder', $this->portfoliobuilder, $this->context->instanceid),
            'cmid' => $this->context->instanceid,
            'courseid' => $this->portfoliobuilder->course,
            'userid' => $USER->id,
            'contextid' => $this->context->id,
            'cangrade' => has_capability('mod/portfoliobuilder:grade', $this->context),
            'isevaluated' => $this->portfoliobuilder->grade != 0
        ];

        $entryutil = new entry();
        $entries = $entryutil->get_user_course_entries($this->portfoliobuilder->course);

        $data['hasentries'] = !empty($entries);

        $data['entries'] = $output->render_from_template("mod_portfoliobuilder/layouts/{$layout}/card", ['entries' => $entries]);

        return $data;
    }
}
