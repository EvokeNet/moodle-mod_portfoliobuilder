<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use mod_portfoliobuilder\util\entry;
use mod_portfoliobuilder\util\user;

/**
 * Public renderable class.
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class portfolio implements renderable, templatable {

    public $context;
    public $portfoliobuilder;
    public $user;

    public function __construct($context, $portfoliobuilder, $user) {
        $this->context = $context;
        $this->portfoliobuilder = $portfoliobuilder;
        $this->user = $user;
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
        $userutil = new user();
        $data = [
            'id' => $this->portfoliobuilder->id,
            'name' => $this->portfoliobuilder->name,
            'userfullname' => fullname($this->user),
            'userimage' => $userutil->get_user_image_or_avatar($this->user)
        ];

        $entryutil = new entry();
        $entries = $entryutil->get_user_course_entries($this->portfoliobuilder->course, $this->user->id);

        $data['hasentries'] = !empty($entries);

        $layoututil = new \mod_portfoliobuilder\util\layout();
        $layout = $layoututil->get_user_layout($this->portfoliobuilder->course, $this->user->id);

        $data['entries'] = $output->render_from_template("mod_portfoliobuilder/layouts/{$layout}/card", ['entries' => $entries]);

        return $data;
    }
}
