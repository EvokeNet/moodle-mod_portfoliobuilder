<?php

namespace mod_portfoliobuilder\output;

defined('MOODLE_INTERNAL') || die();

use context_course;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Portfolio renderable class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class portfolios_filter implements renderable, templatable {
    /** @var context_course $context The context where the filters are being rendered. */
    protected $context;

    /** @var string $tableregionid The table to be updated by this filter */
    protected $tableregionid;

    /** @var stdClass $course The course shown */
    protected $course;

    /**
     * Students filter constructor.
     *
     * @param context_course $context The context where the filters are being rendered.
     * @param string $tableregionid The table to be updated by this filter
     */
    public function __construct(context_course $context, string $tableregionid) {
        $this->context = $context;
        $this->tableregionid = $tableregionid;

        $this->course = get_course($context->instanceid);
    }

    /**
     * Get data for all filter types.
     *
     * @return array
     */
    protected function get_filtertypes(): array {
        $filtertypes = [];

        if ($filtertype = $this->get_group_filter()) {
            $filtertypes[] = $filtertype;
        }

        return $filtertypes;
    }

    /**
     * Get data for the competencies filter.
     *
     * @return stdClass|null
     */
    protected function get_group_filter(): ?stdClass {
        global $DB;

        $params = ['courseid' => $this->course->id];

        $groups = $DB->get_records_sql('SELECT id, name FROM {groups} WHERE courseid = :courseid', $params);

        return $this->get_filter_object(
            'group',
            get_string('groups', 'mod_portfoliobuilder'),
            false,
            false,
            null,
            array_map(function($group) {
                return (object) [
                    'value' => $group->id,
                    'title' => $group->name,
                ];
            }, array_values($groups))
        );
    }

    /**
     * Export the renderer data in a mustache template friendly format.
     *
     * @param renderer_base $output Unused.
     * @return stdClass Data in a format compatible with a mustache template.
     */
    public function export_for_template(renderer_base $output): stdClass {
        return (object) [
            'tableregionid' => $this->tableregionid,
            'courseid' => $this->context->instanceid,
            'filtertypes' => $this->get_filtertypes(),
            'rownumber' => 1,
        ];
    }

    /**
     * Get a standardised filter object.
     *
     * @param string $name
     * @param string $title
     * @param bool $custom
     * @param bool $multiple
     * @param string|null $filterclass
     * @param array $values
     * @param bool $allowempty
     * @return stdClass|null
     */
    protected function get_filter_object(
        string $name,
        string $title,
        bool $custom,
        bool $multiple,
        ?string $filterclass,
        array $values,
        bool $allowempty = false
    ): ?stdClass {

        if (!$allowempty && empty($values)) {
            // Do not show empty filters.
            return null;
        }

        return (object) [
            'name' => $name,
            'title' => $title,
            'allowcustom' => $custom,
            'allowmultiple' => $multiple,
            'filtertypeclass' => $filterclass,
            'values' => $values,
        ];
    }
}
