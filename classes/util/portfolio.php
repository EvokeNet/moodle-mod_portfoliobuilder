<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Portfolio utility class helper
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class portfolio {
    protected $context;
    protected $courseid;

    public function __construct($context, $courseid) {
        $this->context = $context;
        $this->courseid = $courseid;
    }

    public function get_user_course_groups_portfolios() {
        $groupsutil = new group();

        $usercoursegroups = $groupsutil->get_user_groups($this->courseid);

        if (!$usercoursegroups) {
            return [];
        }

        $groupsmembers = $groupsutil->get_groups_members($usercoursegroups, true, $this->context);

        if (empty($groupsmembers)) {
            return [];
        }

        $this->fill_user_portfolios_with_extra_data($groupsmembers);

        shuffle($groupsmembers);

        return array_values($groupsmembers);
    }

    public function get_course_group_portfolios($groupid) {
        $groupsutil = new group();

        $group = $groupsutil->get_group($groupid);

        $groupsmembers = $groupsutil->get_groups_members([$group], true, $this->context);

        if (empty($groupsmembers)) {
            return [];
        }

        $this->fill_user_portfolios_with_extra_data($groupsmembers);

        shuffle($groupsmembers);

        return array_values($groupsmembers);
    }

    public function get_course_portfolios() {
        global $DB;

        $fields = 'DISTINCT u.*';

        $capjoin = get_enrolled_with_capabilities_join($this->context, '', 'mod/portfoliobuilder:submit');

        $from = ' {user} u ' . $capjoin->joins;

        $sql = "SELECT {$fields} FROM {$from} WHERE {$capjoin->wheres}";

        $params = $capjoin->params;

        $userutil = new user();
        if ($userstoremove = $userutil->get_user_ids_with_grade_capability($this->context)) {
            list($sqlin, $paramsin) = $DB->get_in_or_equal($userstoremove, SQL_PARAMS_NAMED, 'notin_', false);

            $sql .= " AND u.id {$sqlin}";

            $params = array_merge($params, $paramsin);
        }

        $users = $DB->get_records_sql($sql, $params);

        if (!$users) {
            return [];
        }

        $this->fill_user_portfolios_with_extra_data($users);

        shuffle($users);

        return array_values($users);
    }

    private function fill_user_portfolios_with_extra_data($users) {
        $userutil = new user();
        $reactionutil = new reaction();
        $commentutil = new comment();
        $entryutil = new entry();
        $layoututil = new layout();
        $logutil = new log();

        $lastaccesstoportfolios = $logutil->get_last_time_accessed_portfolios($this->courseid);

        foreach ($users as $user) {
            $user->totallikes = $reactionutil->get_total_course_reactions($this->courseid, $user->id);
            $user->totalcomments = $commentutil->get_total_course_comments($this->courseid, $user->id);
            $user->totalentries = $entryutil->get_total_course_entries($this->courseid, $user->id);
            $user->layout = $layoututil->get_user_layout($this->courseid, $user->id, 'timeline');
            $user->lastentry = $entryutil->get_last_course_entry($this->courseid, $user->id);

            $user->userpicture = $userutil->get_user_image_or_avatar($user);
            $user->fullname = fullname($user);

            $user->hasnews = false;

            if ($user->lastentry && $user->lastentry->timecreated > $lastaccesstoportfolios) {
                $user->hasnews = true;
            }
        }
    }
}
