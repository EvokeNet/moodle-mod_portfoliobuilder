<?php

namespace mod_portfoliobuilder\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;

/**
 * Portfolios table filterset.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class portfolios_filterset extends filterset {
    /**
     * Get the required filters.
     *
     * The only required filter is the courseid filter.
     *
     * @return array.
     */
    public function get_required_filters(): array {
        return [
            'courseid' => integer_filter::class,
        ];
    }

    /**
     * Get the optional filters.
     *
     * These are:
     * - group
     *
     * @return array
     */
    public function get_optional_filters(): array {
        return [
            'group' => integer_filter::class,
        ];
    }
}
