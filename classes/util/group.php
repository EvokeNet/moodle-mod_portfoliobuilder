<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Groups utility class helper
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class group {
    public function get_user_groups($courseid, $userid = null) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $sql = "SELECT g.id, g.name, g.picture
                FROM {groups} g
                JOIN {groups_members} gm ON gm.groupid = g.id
                WHERE gm.userid = :userid AND g.courseid = :courseid";

        $groups = $DB->get_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);

        if (!$groups) {
            return false;
        }

        return $groups;
    }

    public function get_user_groups_names($courseid, $userid = null) {
        global $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $usergroups = $this->get_user_groups($courseid, $userid);

        if (!$usergroups) {
            return '';
        }

        $groupsnames = [];
        foreach ($usergroups as $usergroup) {
            $groupsnames[] = $usergroup->name;
        }

        return implode(', ', $groupsnames);
    }

    public function get_user_groups_ids($courseid, $userid = null) {
        global $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $usergroups = $this->get_user_groups($courseid, $userid);

        if (!$usergroups) {
            return false;
        }

        $groupsids = [];
        foreach ($usergroups as $usergroup) {
            $groupsids[] = $usergroup->id;
        }

        return $groupsids;
    }

    public function get_groups_members($groups, $withfulluserinfo = true, $contexttofilter = false) {
        global $DB;

        $ids = [];
        foreach ($groups as $group) {
            $ids[] = $group->id;
        }

        list($groupsids, $groupsparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'group');

        $sql = "SELECT u.*
                FROM {groups_members} gm
                INNER JOIN {user} u ON u.id = gm.userid
                WHERE gm.groupid " . $groupsids;

        $groupsmembers = $DB->get_records_sql($sql, $groupsparams);

        if (!$groupsmembers) {
            return false;
        }

        // Remove any person who have access to grade students. Teachers, mentors...
        if ($contexttofilter) {
            foreach ($groupsmembers as $key => $groupmember) {
                if (has_capability('mod/portfoliobuilder:grade', $contexttofilter, $groupmember->id)) {
                    unset($groupsmembers[$key]);
                }
            }
        }

        if ($withfulluserinfo) {
            $userutil = new user();
            foreach ($groupsmembers as $key => $groupmember) {
                $userpicture = $userutil->get_user_image_or_avatar($groupmember);

                $groupsmembers[$key]->userpicture = $userpicture;

                $groupsmembers[$key]->fullname = fullname($groupmember);
            }
        }

        return array_values($groupsmembers);
    }

    public function get_group_members($groupid, $withfulluserinfo = true) {
        global $DB;

        $sql = "SELECT u.*
                FROM {groups_members} gm
                INNER JOIN {user} u ON u.id = gm.userid
                WHERE gm.groupid = :groupid";

        $groupmembers = $DB->get_records_sql($sql, ['groupid' => $groupid]);

        if (!$groupmembers) {
            return false;
        }

        if ($withfulluserinfo) {
            foreach ($groupmembers as $key => $groupmember) {
                $userpicture = user::get_user_image_or_avatar($groupmember);

                $groupmembers[$key]->userpicture = $userpicture;

                $groupmembers[$key]->fullname = fullname($groupmember);
            }
        }

        return array_values($groupmembers);
    }

    public function get_total_groups_in_course($courseid) {
        global $DB;

        return $DB->count_records('groups', ['courseid' => $courseid]);
    }

    public function is_group_member($groupid, $userid) {
        global $DB;

        return $DB->count_records('groups_members', ['groupid' => $groupid, 'userid' => $userid]);
    }

    public function get_course_groups($course, $withimage = true) {
        global $DB;

        $groups = $DB->get_records('groups', ['courseid' => $course->id]);

        if (!$groups) {
            return false;
        }

        if ($withimage) {
            foreach ($groups as $group) {
                $group->groupimage = $this->get_group_image($group);
            }
        }

        return array_values($groups);
    }

    public function get_group_image($group) {
        global $CFG;

        $pictureurl = get_group_picture_url($group, $group->courseid, true);

        if ($pictureurl) {
            return $pictureurl->out();
        }

        return $CFG->wwwroot . '/blocks/evokehq/pix/defaultgroupimg.png';
    }
}
