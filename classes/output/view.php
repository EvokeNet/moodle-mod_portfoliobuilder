<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use mod_portfoliobuilder\util\user;
use mod_portfoliobuilder\util\entry;
use mod_portfoliobuilder\util\grade;
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

        $isloggedin = isloggedin();

        $userutil = new user();
        $userdata = [
            'id' => $USER->id,
            'fullname' => fullname($USER),
            'picture' => $userutil->get_user_image_or_avatar($USER)
        ];

        $layoututil = new \mod_portfoliobuilder\util\layout();
        $layout = $layoututil->get_user_layout($this->portfoliobuilder->course);

        $gradeutil = new grade();
        $grade = $gradeutil->get_user_course_grade($this->portfoliobuilder->course, $USER->id);

        $publicurl = new \moodle_url('/mod/portfoliobuilder/portfolio.php', ['id' => $this->context->instanceid, 'u' => $USER->id]);
        $data = [
            'id' => $this->portfoliobuilder->id,
            'name' => $this->portfoliobuilder->name,
            'intro' => format_module_intro('portfoliobuilder', $this->portfoliobuilder, $this->context->instanceid),
            'cmid' => $this->context->instanceid,
            'courseid' => $this->portfoliobuilder->course,
            'userid' => $userdata['id'],
            'userfullname' => $userdata['fullname'],
            'userpicture' => $userdata['picture'],
            'contextid' => $this->context->id,
            'grade' => $grade,
            'encodedpublicurl' => htmlentities($publicurl),
            'isloggedin' => $isloggedin
        ];

        $entryutil = new entry();
        $entries = $entryutil->get_user_course_entries($this->portfoliobuilder->course);

        $data['hasentries'] = !empty($entries);

        $data['entries'] = $output->render_from_template("mod_portfoliobuilder/layouts/{$layout}/entries",
            ['entries' => $entries, 'user' => $userdata, 'courseid' => $this->portfoliobuilder->course, 'isloggedin' => $isloggedin]);

        return $data;
    }
}
