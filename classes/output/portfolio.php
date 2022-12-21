<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use mod_portfoliobuilder\util\user;
use mod_portfoliobuilder\util\entry;

/**
 * Portfolio renderable class.
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class portfolio implements renderable, templatable {
    protected $course;
    protected $user;

    public function __construct($course, $user) {
        $this->course = $course;
        $this->user = $user;
    }

    public function export_for_template(renderer_base $output) {
        $userutil = new user();
        $data = [
            'userfullname' => fullname($this->user),
            'userimage' => $userutil->get_user_image_or_avatar($this->user)
        ];

        $entryutil = new entry();
        $entries = $entryutil->get_user_course_entries($this->course->id, $this->user->id);

        $data['hasentries'] = !empty($entries);

        $layoututil = new \mod_portfoliobuilder\util\layout();
        $layout = $layoututil->get_user_layout($this->course->id, $this->user->id);

        $data['entries'] = $output->render_from_template("mod_portfoliobuilder/layouts/{$layout}/card", ['entries' => $entries]);

        return $data;
    }
}
