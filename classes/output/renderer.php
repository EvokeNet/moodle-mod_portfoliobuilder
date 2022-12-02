<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use renderable;

/**
 * Main portfolio's renderer.
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class renderer extends plugin_renderer_base {

    /**
     * Defer the instance in course to template.
     *
     * @param renderable $page
     *
     * @return bool|string
     *
     * @throws \moodle_exception
     */
    public function render_view(renderable $page) {
        $data = $page->export_for_template($this);

        if (has_capability('mod/portfoliobuilder:grade', $page->context)) {
            return $this->render_from_template('mod_portfoliobuilder/view_admin', $data);
        }

        return $this->render_from_template('mod_portfoliobuilder/view', $data);
    }

    /**
     * Defer the instance in course to template.
     *
     * @param renderable $page
     *
     * @return bool|string
     *
     * @throws \moodle_exception
     */
    public function render_layoutpreview(renderable $page) {
        $data = $page->export_for_template($this);

        return $this->render_from_template("mod_portfoliobuilder/layoutpreview/{$data['type']}", $data);
    }
}
