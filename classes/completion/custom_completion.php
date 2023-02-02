<?php

declare(strict_types=1);

namespace mod_portfoliobuilder\completion;

use core_completion\activity_custom_completion;

/**
 * Activity custom completion subclass for the Assign Tutor activity.
 *
 * Class for defining mod_portfoliobuilder's custom completion rules and fetching the completion statuses
 * of the custom completion rules for a given peerreview instance and a user.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $userid = $this->userid;
        $portfoliobuilderid = $this->cm->instance;

        if (!$portfoliobuilder = $DB->get_record('portfoliobuilder', ['id' => $portfoliobuilderid])) {
            throw new \moodle_exception('Unable to find portfoliobuilder with id ' . $portfoliobuilderid);
        }

        if ($rule == 'completionrequiresubmit') {
            $submissionutil = new \mod_portfoliobuilder\util\entry();

            if ($submissionutil->get_user_course_entries($portfoliobuilder->course, $userid)) {
                return COMPLETION_COMPLETE;
            }
        }

        return COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionrequiresubmit'];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionrequiresubmit' => get_string('completionrequiresubmit', 'mod_portfoliobuilder')
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionrequiresubmit',
            'completionusegrade'
        ];
    }
}
