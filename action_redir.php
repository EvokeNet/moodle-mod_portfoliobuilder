<?php

/**
 * Wrapper script redirecting portfolios operations to correct destination.
 *
 * @package    mod_portfoliobuilder
 * @copyright  2023 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot . '/course/lib.php');

$formaction = required_param('formaction', PARAM_LOCALURL);
$id = required_param('id', PARAM_INT);

$PAGE->set_url('/mod/portfoliobuilder/action_redir.php', array('formaction' => $formaction, 'id' => $id));
list($formaction) = explode('?', $formaction, 2);

// This page now only handles the bulk enrolment change actions, other actions are done with ajax.
$actions = array('bulkchange.php');

if (array_search($formaction, $actions) === false) {
    print_error('unknownuseraction');
}

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad');
}

if ($formaction == 'bulkchange.php') {
    // Backwards compatibility for enrolment plugins bulk change functionality.
    // This awful code is adapting from the participant page with it's param names and values
    // to the values expected by the bulk enrolment changes forms.
    $formaction = required_param('formaction', PARAM_URL);

    $url = new moodle_url($formaction);
    // Get the enrolment plugin type and bulk action from the url.
    $plugin = $url->param('plugin');
    $operationname = $url->param('operation');
    $dataformat = $url->param('dataformat');

    $course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
    $context = context_course::instance($id);
    $PAGE->set_context($context);

    $userids = optional_param_array('userid', array(), PARAM_INT);
    $default = new moodle_url('/mod/portfoliobuilder/indextable.php', ['id' => $course->id]);
    $returnurl = new moodle_url(optional_param('returnto', $default, PARAM_URL));

    if (empty($userids)) {
        $userids = optional_param_array('bulkuser', array(), PARAM_INT);
    }

    if (empty($userids)) {
        // The first time list hack.
        if (empty($userids) and $post = data_submitted()) {
            foreach ($post as $k => $v) {
                if (preg_match('/^portfolio(\d+)$/', $k, $m)) {
                    $userids[] = $m[1];
                }
            }
        }
    }

    if (empty($plugin) AND $operationname == 'download_portfolios') {
        // Check permissions.
        $pagecontext = ($course->id == SITEID) ? context_system::instance() : $context;
        if (course_can_view_participants($pagecontext)) {
            $plugins = core_plugin_manager::instance()->get_plugins_of_type('dataformat');
            if (isset($plugins[$dataformat])) {
                if ($plugins[$dataformat]->is_enabled()) {
                    if (empty($userids)) {
                        redirect($returnurl, get_string('noselectedusers', 'bulkusers'));
                    }

                    $columnnames = array(
                        'firstname' => get_string('firstname'),
                        'lastname' => get_string('lastname'),
                    );

                    $fields = \core_user\fields::for_identity($context, false);
                    $identityfields = $fields->get_required_fields();
                    $identityfieldsselect = '';

                    foreach ($identityfields as $field) {
                        $columnnames[$field] = get_string($field);
                        $identityfieldsselect .= ', u.' . $field . ' ';
                    }

                    // Ensure users are enrolled in this course context, further limiting them by selected userids.
                    [$enrolledsql, $enrolledparams] = get_enrolled_sql($context);
                    [$useridsql, $useridparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'userid');

                    $params = array_merge($enrolledparams, $useridparams);

                    // If user can only view their own groups then they can only export users from those groups too.
                    $groupmode = groups_get_course_groupmode($course);
                    if ($groupmode == SEPARATEGROUPS && !has_capability('moodle/site:accessallgroups', $context)) {
                        $groups = groups_get_all_groups($course->id, $USER->id, 0, 'g.id');
                        $groupids = array_column($groups, 'id');

                        [$groupmembersql, $groupmemberparams] = groups_get_members_ids_sql($groupids, $context);
                        $params = array_merge($params, $groupmemberparams);

                        $groupmemberjoin = "JOIN ({$groupmembersql}) jg ON jg.id = u.id";
                    } else {
                        $groupmemberjoin = '';
                    }

                    $sql = "SELECT u.firstname, u.lastname" . $identityfieldsselect . "
                              FROM {user} u
                              JOIN ({$enrolledsql}) je ON je.id = u.id
                                   {$groupmemberjoin}
                             WHERE u.id {$useridsql}";

                    $rs = $DB->get_recordset_sql($sql, $params);

                    // Provide callback to pre-process all records ensuring user identity fields are escaped if HTML supported.
                    \core\dataformat::download_data(
                        'courseid_' . $course->id . '_portfolios',
                        $dataformat,
                        $columnnames,
                        $rs,
                        function(stdClass $record, bool $supportshtml) use ($identityfields): stdClass {
                            if ($supportshtml) {
                                foreach ($identityfields as $identityfield) {
                                    $record->{$identityfield} = s($record->{$identityfield});
                                }
                            }

                            return $record;
                        }
                    );
                    $rs->close();
                }
            }
        }
    }
} else {
    throw new coding_exception('invalidaction');
}
