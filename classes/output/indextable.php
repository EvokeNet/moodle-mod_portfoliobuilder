<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use mod_portfoliobuilder\table\portfolios;
use renderable;
use templatable;
use renderer_base;
use mod_portfoliobuilder\table\portfolios_filterset;
use core_table\local\filter\integer_filter;
use core_table\local\filter\filter;

/**
 * Index renderable class.
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class indextable implements renderable, templatable {

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
        $table = new portfolios(
            'mod-evokeportfolio-portfolios-table',
            $this->context,
            $this->course
        );

        $filterset = new portfolios_filterset();
        $filterset->add_filter(new integer_filter('courseid', filter::JOINTYPE_DEFAULT, [(int)$this->course->id]));

        $portfoliofilter = new portfolios_filter($this->context, $table->uniqueid);

        $filter = $output->render($portfoliofilter);

        $table->set_filterset($filterset);

        ob_start();
        $table->out(20, true);
        $studentstablehtml = ob_get_contents();
        ob_end_clean();

        $data = [
            'portfolios' => $participantstable
        ];

        return $data;
    }
}
