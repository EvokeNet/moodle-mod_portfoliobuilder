<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use mod_portfoliobuilder\util\comment;
use mod_portfoliobuilder\util\entry;
use mod_portfoliobuilder\util\group;
use mod_portfoliobuilder\util\reaction;
use mod_portfoliobuilder\util\layout;
use renderable;
use templatable;
use renderer_base;

/**
 * Index renderable class.
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
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

        $groupsmembers = [];
        if ($usercoursegroups) {
            $groupsmembers = $groupsutil->get_groups_members($usercoursegroups, true, $this->context);
        }

        if ($groupsmembers) {
            $reactionutil = new reaction();
            $commentutil = new comment();
            $entryutil = new entry();
            $layoututil = new layout();

            foreach ($groupsmembers as $groupsmember) {
                $groupsmember->totallikes = $reactionutil->get_total_course_reactions($this->course->id, $groupsmember->id);
                $groupsmember->totalcomments = $commentutil->get_total_course_comments($this->course->id, $groupsmember->id);
                $groupsmember->totalentries = $entryutil->get_total_course_entries($this->course->id, $groupsmember->id);
                $groupsmember->layout = $layoututil->get_user_layout($this->course->id, $groupsmember->id);
                $groupsmember->lastentry = $entryutil->get_last_course_entry($this->course->id, $groupsmember->id);
            }
        }

        if (!empty($groupsmembers)) {
            shuffle($groupsmembers);
        }

        return [
            'courseid' => $this->course->id,
            'groupsmembers' => array_values($groupsmembers),
            'hasgroupsmembers' => !empty($groupsmembers),
            'hasgroup' => !empty($usercoursegroups)
        ];
    }
}
