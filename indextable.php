<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Display information about all the mod_portfoliobuilder modules in the requested course.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 Willian Mano <willianmanoaraujo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

define('DEFAULT_PAGE_SIZE', 20);
define('SHOW_ALL_PAGE_SIZE', 5000);

$id = required_param('id', PARAM_INT);
$perpage = optional_param('perpage', DEFAULT_PAGE_SIZE, PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_course_login($course);

$context = context_course::instance($course->id);

$pagetitle = format_string($course->fullname);

$PAGE->set_url('/mod/portfoliobuilder/indextable.php', ['id' => $id]);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->set_context($context);

$PAGE->navbar->add($pagetitle);

$renderer = $PAGE->get_renderer('mod_portfoliobuilder');

$filterset = new \mod_portfoliobuilder\table\portfolios_filterset();
$filterset->add_filter(new \core_table\local\filter\integer_filter('courseid', \core_table\local\filter\filter::JOINTYPE_DEFAULT, [(int)$course->id]));

$portfoliostable = new \mod_portfoliobuilder\table\portfolios("user-index-portfolios-{$course->id}", $context, $course);

$portfoliofilter = new \mod_portfoliobuilder\output\portfolios_filter($context, $portfoliostable->uniqueid);

echo $OUTPUT->header();

echo $renderer->render($portfoliofilter);

echo '<div class="portfoliolist">';

// Do this so we can get the total number of rows.
ob_start();
$portfoliostable->set_filterset($filterset);
$portfoliostable->out(20, true);
$portfoliostablehtml = ob_get_contents();
ob_end_clean();

echo html_writer::start_tag('form', [
    'action' => 'action_redir.php',
    'method' => 'post',
    'id' => 'portfoliosform',
    'data-course-id' => $course->id,
    'data-table-unique-id' => $portfoliostable->uniqueid,
    'data-table-default-per-page' => ($perpage < DEFAULT_PAGE_SIZE) ? $perpage : DEFAULT_PAGE_SIZE,
]);
echo '<div>';
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
echo '<input type="hidden" name="returnto" value="'.s($PAGE->url->out(false)).'" />';

echo html_writer::tag(
    'p',
    get_string('countparticipantsfound', 'core_user', $portfoliostable->totalrows),
    [
        'data-region' => 'portfolios-count',
    ]
);

echo $portfoliostablehtml;

echo '</div>';

$perpageurl = new moodle_url('/mod/portfoliobuilder/indextable.php', [
    'id' => $course->id,
]);
$perpagesize = DEFAULT_PAGE_SIZE;
$perpagevisible = false;
$perpagestring = '';

if ($perpage == SHOW_ALL_PAGE_SIZE && $portfoliostable->totalrows > DEFAULT_PAGE_SIZE) {
    $perpageurl->param('perpage', $portfoliostable->totalrows);
    $perpagesize = SHOW_ALL_PAGE_SIZE;
    $perpagevisible = true;
    $perpagestring = get_string('showperpage', '', DEFAULT_PAGE_SIZE);
} else if ($portfoliostable->get_page_size() < $portfoliostable->totalrows) {
    $perpageurl->param('perpage', SHOW_ALL_PAGE_SIZE);
    $perpagesize = SHOW_ALL_PAGE_SIZE;
    $perpagevisible = true;
    $perpagestring = get_string('showall', '', $portfoliostable->totalrows);
}

$perpageclasses = '';
if (!$perpagevisible) {
    $perpageclasses = 'hidden';
}
echo $OUTPUT->container(html_writer::link(
    $perpageurl,
    $perpagestring,
    [
        'data-action' => 'showcount',
        'data-target-page-size' => $perpagesize,
        'class' => $perpageclasses,
    ]
), [], 'showall');

$bulkoptions = (object) [
    'uniqueid' => $portfoliostable->uniqueid,
];

echo '<br /><div class="buttons"><div class="form-inline">';

echo html_writer::start_tag('div', array('class' => 'btn-group'));

if ($portfoliostable->get_page_size() < $portfoliostable->totalrows) {
    // Select all users, refresh table showing all users and mark them all selected.
    $label = get_string('selectalluserswithcount', 'moodle', $portfoliostable->totalrows);
    echo html_writer::empty_tag('input', [
        'type' => 'button',
        'id' => 'checkall',
        'class' => 'btn btn-secondary',
        'value' => $label,
        'data-target-page-size' => $portfoliostable->totalrows,
    ]);
}
echo html_writer::end_tag('div');

$displaylist = array();

$params = ['operation' => 'download_portfolios'];

$downloadoptions = [];
$formats = core_plugin_manager::instance()->get_plugins_of_type('dataformat');
foreach ($formats as $format) {
    if ($format->is_enabled()) {
        $params = ['operation' => 'download_portfolios', 'dataformat' => $format->name];
        $url = new moodle_url('bulkchange.php', $params);
        $downloadoptions[$url->out(false)] = get_string('dataformat', $format->component);
    }
}

if (!empty($downloadoptions)) {
    $displaylist[] = [get_string('downloadas', 'table') => $downloadoptions];
}

$selectactionparams = array(
    'id' => 'formactionid',
    'class' => 'ml-2',
    'data-action' => 'toggle',
    'data-togglegroup' => 'portfolios-table',
    'data-toggle' => 'action',
    'disabled' => 'disabled'
);

$label = html_writer::tag('label', get_string("withselectedusers"),
    ['for' => 'formactionid', 'class' => 'col-form-label d-inline']);
$select = html_writer::select($displaylist, 'formaction', '', ['' => 'choosedots'], $selectactionparams);

echo html_writer::tag('div', $label . $select);

echo '<input type="hidden" name="id" value="' . $course->id . '" />';
echo '</div></div></div>';

$bulkoptions->noteStateNames = note_get_state_names();

echo '</form>';

$PAGE->requires->js_call_amd('mod_portfoliobuilder/portfolios', 'init', [$bulkoptions]);

echo $OUTPUT->footer();
