<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Portfolio utility class helper
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class portfolio {
    public function get_user_course_groups_portfolios($context, $courseid) {
        global $USER;

        $groupsutil = new group();

        $usercoursegroups = $groupsutil->get_user_groups($courseid);

        if (!$usercoursegroups) {
            return [];
        }

        $groupsmembers = $groupsutil->get_groups_members($usercoursegroups, true, $context);

        if (empty($groupsmembers)) {
            return [];
        }

        $reactionutil = new reaction();
        $commentutil = new comment();
        $entryutil = new entry();
        $layoututil = new layout();

        foreach ($groupsmembers as $groupsmember) {
            $groupsmember->totallikes = $reactionutil->get_total_course_reactions($courseid, $groupsmember->id);
            $groupsmember->totalcomments = $commentutil->get_total_course_comments($courseid, $groupsmember->id);
            $groupsmember->totalentries = $entryutil->get_total_course_entries($courseid, $groupsmember->id);
            $groupsmember->layout = $layoututil->get_user_layout($courseid, $groupsmember->id);
            $groupsmember->lastentry = $entryutil->get_last_course_entry($courseid, $groupsmember->id);
        }

        shuffle($groupsmembers);

        return array_values($groupsmembers);
    }

    public function get_course_portfolios($context, $courseid) {
        global $DB;

        $fields = 'DISTINCT u.*';

        $capjoin = get_enrolled_with_capabilities_join($context, '', 'mod/portfoliobuilder:submit');

        $from = ' {user} u ' . $capjoin->joins;

        $sql = "SELECT {$fields} FROM {$from} WHERE {$capjoin->wheres}";

        $params = $capjoin->params;

        $users = $DB->get_records_sql($sql, $params);

        if (!$users) {
            return [];
        }

        $userutil = new user();
        $reactionutil = new reaction();
        $commentutil = new comment();
        $entryutil = new entry();
        $layoututil = new layout();

        foreach ($users as $user) {
            $user->totallikes = $reactionutil->get_total_course_reactions($courseid, $user->id);
            $user->totalcomments = $commentutil->get_total_course_comments($courseid, $user->id);
            $user->totalentries = $entryutil->get_total_course_entries($courseid, $user->id);
            $user->layout = $layoututil->get_user_layout($courseid, $user->id);
            $user->lastentry = $entryutil->get_last_course_entry($courseid, $user->id);

            $user->userpicture = $userutil->get_user_image_or_avatar($user);
            $user->fullname = fullname($user);
        }

        shuffle($users);

        return array_values($users);
    }
}
