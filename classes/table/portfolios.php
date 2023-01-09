<?php

namespace mod_portfoliobuilder\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

use mod_portfoliobuilder\util\entry;
use mod_portfoliobuilder\util\grade;
use mod_portfoliobuilder\util\group;
use table_sql;
use moodle_url;
use html_writer;

/**
 * Entries table class
 *
 * @copyright   2021 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class portfolios extends table_sql {

    protected $context;
    protected $course;
    protected $portfoliowithevaluation;

    public function __construct($uniqueid, $context, $course) {
        parent::__construct($uniqueid);

        $this->context = $context;
        $this->course = $course;

        $gradeutil = new grade();

        $this->portfoliowithevaluation = $gradeutil->get_portfolio_with_evaluation($this->course->id);

        $this->define_columns(['id', 'fullname', 'email', 'group', 'status']);

        $this->define_headers(['ID', get_string('fullname'), 'E-mail', get_string('group'), get_string('status', 'mod_portfoliobuilder')]);

        $this->no_sorting('status');

        $this->no_sorting('group');

        $this->define_baseurl(new moodle_url('/mod/portfoliobuilder/indextable.php', ['id' => $course->id]));

        $this->base_sql();

        $this->set_attribute('class', 'table table-bordered table-portfolios');
    }

    public function base_sql() {
        $fields = 'DISTINCT u.id, u.firstname, u.lastname, u.email';

        $capjoin = get_enrolled_with_capabilities_join($this->context, '', 'mod/portfoliobuilder:submit');

        $from = ' {user} u ' . $capjoin->joins;

        $params = $capjoin->params;

        $this->set_sql($fields, $from, $capjoin->wheres, $params);
    }

    public function col_fullname($user) {
        return $user->firstname . ' ' . $user->lastname;
    }

    public function col_group($data) {
        $grouputil = new group();

        return $grouputil->get_user_groups_names($this->course->id, $data->id);
    }

    public function col_status($data) {
        $gradeutil = new grade();
        $entryutil = new entry();

        $url = new moodle_url('/mod/portfoliobuilder/portfolio.php', ['id' => $this->course->id, 'u' => $data->id]);

        $statuscontent = html_writer::link($url, get_string('viewportfolio', 'mod_portfoliobuilder'), ['class' => 'btn btn-primary btn-sm']);

        if ($entryutil->get_total_course_entries($this->course->id, $data->id)) {
            $statuscontent .= html_writer::span(get_string('submitted', 'mod_portfoliobuilder'), 'badge badge-info ml-2 p-2');
        }

        if ($this->portfoliowithevaluation && $gradeutil->user_has_grade($this->portfoliowithevaluation, $data->id)) {
            $statuscontent .= html_writer::span(get_string('evaluated', 'mod_portfoliobuilder'), 'badge badge-success ml-2 p-2');
        }

        return $statuscontent;
    }
}
