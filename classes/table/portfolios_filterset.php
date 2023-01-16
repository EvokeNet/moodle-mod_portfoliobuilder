<?php

/**
 * Portfolios table filterset.
 *
 * @package    mod_portfoliobuilder
 * @copyright  2023 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_portfoliobuilder\table;

use core_table\local\filter\filterset;
use core_table\local\filter\integer_filter;

/**
 * Portfolios table filterset.
 *
 * @package    mod_portfoliobuilder
 * @copyright  2023 Willian Mano - http://conecti.me
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
