<?php

namespace mod_portfoliobuilder\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

use mod_portfoliobuilder\util\entry;
use mod_portfoliobuilder\util\grade;
use mod_portfoliobuilder\util\group;
use moodle_url;
use html_writer;
use table_sql;
use core_table\dynamic as dynamic_table;
use core_table\local\filter\filterset;
use context;

/**
 * Portfolios table class
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class portfolios extends table_sql implements dynamic_table {

    protected $context;

    protected $portfoliobuilder;

    private $portfoliosheaders;

    private $portfolioscolumns;

    public function __construct($uniqueid, $context, $portfoliobuilder) {
        global $OUTPUT, $PAGE;

        parent::__construct($uniqueid);

        $PAGE->set_context($context);

        $this->context = $context;

        $this->portfoliobuilder = $portfoliobuilder;

        $mastercheckbox = new \core\output\checkbox_toggleall('portfolios-table', true, [
            'id' => 'select-all-portfolios',
            'name' => 'select-all-portfolios',
            'label' => get_string('selectall'),
            'labelclasses' => 'sr-only',
            'classes' => 'm-1',
            'checked' => false,
        ]);
        $this->portfoliosheaders = [$OUTPUT->render($mastercheckbox), 'ID', get_string('fullname'), 'E-mail', get_string('group'), get_string('status', 'mod_portfoliobuilder')];
        $this->portfolioscolumns = ['select', 'id', 'fullname', 'email', 'group', 'status'];
    }

    /**
     * Render the portfolios table.
     *
     * @param int $pagesize Size of page for paginated displayed table.
     * @param bool $useinitialsbar Whether to use the initials bar which will only be used if there is a fullname column defined.
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        $this->base_sql();

        $this->define_headers($this->portfoliosheaders);

        $this->define_columns($this->portfolioscolumns);

        $this->no_sorting('select');

        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Set filters and build table structure.
     *
     * @param filterset $filterset The filterset object to get the filters from.
     */
    public function set_filterset(filterset $filterset): void {
        // Get the context.
        $this->courseid = $filterset->get_filter('courseid')->current();

        // Process the filterset.
        parent::set_filterset($filterset);
    }

    /**
     * Guess the base url for the portfolios table.
     */
    public function guess_base_url(): void {
        $this->baseurl = new moodle_url('/mod/portfoliobuilder/indextable.php', ['id' => $this->context->instanceid]);
    }

    /**
     * Get the context of the current table.
     *
     * Note: This function should not be called until after the filterset has been provided.
     *
     * @return context
     */
    public function get_context(): context {
        return $this->context;
    }

    public function base_sql() {
        $fields = 'DISTINCT u.id, u.firstname, u.lastname, u.email';

        $capjoin = get_enrolled_with_capabilities_join($this->context, '', 'mod/portfoliobuilder:submit');

        $from = ' {user} u ' . $capjoin->joins;

        $params = $capjoin->params;

        if ($this->filterset->has_filter('group')) {
            $from .= ' JOIN {groups_members} wm_gm ON (wm_gm.userid = u.id AND wm_gm.groupid = :groupid)';
            $params['groupid'] = $this->filterset->get_filter('group')->current();
        }

        $this->set_sql($fields, $from, $capjoin->wheres, $params);
    }

    /**
     * Generate the select column.
     *
     * @param \stdClass $data
     * @return string
     */
    public function col_select($data) {
        global $OUTPUT;

        $checkbox = new \core\output\checkbox_toggleall('portfolios-table', false, [
            'classes' => 'portfoliocheckbox m-1',
            'id' => 'portfolio' . $data->id,
            'name' => 'portfolio' . $data->id,
            'checked' => false,
            'label' => get_string('selectitem', 'moodle', $data->firstname . ' ' . $data->lastname),
            'labelclasses' => 'accesshide',
        ]);

        return $OUTPUT->render($checkbox);
    }

    public function col_fullname($user) {
        return $user->firstname . ' ' . $user->lastname;
    }

    public function col_group($data) {
        $grouputil = new group();

        return $grouputil->get_user_groups_names($this->portfoliobuilder->course, $data->id);
    }

    public function col_status($data) {
        $gradeutil = new grade();
        $entryutil = new entry();

        $url = new moodle_url('/mod/portfoliobuilder/portfolio.php', ['id' => $this->portfoliobuilder->course, 'u' => $data->id]);

        $statuscontent = html_writer::link($url, get_string('viewportfolio', 'mod_portfoliobuilder'), ['class' => 'btn btn-primary btn-sm']);

        if ($entryutil->get_total_course_entries($this->courseid, $data->id)) {
            $statuscontent .= html_writer::span(get_string('submitted', 'mod_portfoliobuilder'), 'badge badge-info ml-2 p-2');
        }

        if ($gradeutil->user_has_grade($this->portfoliobuilder, $data->id)) {
            $statuscontent .= html_writer::span(get_string('evaluated', 'mod_portfoliobuilder'), 'badge badge-success ml-2 p-2');
        }

        return $statuscontent;
    }
}
