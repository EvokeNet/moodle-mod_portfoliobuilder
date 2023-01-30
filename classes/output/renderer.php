<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use renderable;

/**
 * Main portfolio's renderer.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
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

        return $this->render_from_template("mod_portfoliobuilder/layouts/{$data['type']}/preview", $data);
    }
}
