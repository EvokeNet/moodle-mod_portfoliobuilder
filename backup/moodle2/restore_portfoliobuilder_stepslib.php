<?php

/**
 * All the steps to restore mod_portfoliobuilder are defined here.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

// More information about the backup process: {@link https://docs.moodle.org/dev/Backup_API}.
// More information about the restore process: {@link https://docs.moodle.org/dev/Restore_API}.

/**
 * Defines the structure step to restore one mod_portfoliobuilder activity.
 */
class restore_portfoliobuilder_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the structure to be restored.
     *
     * @return restore_path_element[].
     */
    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('portfoliobuilder', '/activity/portfoliobuilder');
        if ($userinfo) {
            $paths[] = new restore_path_element('portfoliobuilder_entry', '/activity/portfoliobuilder/entries/entry');
            $paths[] = new restore_path_element('portfoliobuilder_grade', '/activity/portfoliobuilder/grades/grade');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Processes the elt restore data.
     *
     * @param array $data Parsed element data.
     */
    protected function process_portfoliobuilder($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('portfoliobuilder', $data);

        $this->apply_activity_instance($newitemid);
    }

    protected function process_portfoliobuilder_entry($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->portfolioid = $this->get_new_parentid('portfoliobuilder');
        $data->courseid = $this->get_courseid();

        $newitemid = $DB->insert_record('portfoliobuilder_entries', $data);

        $this->set_mapping('portfoliobuilder_entry', $oldid, $newitemid, true);

        $this->add_related_files('mod_portfoliobuilder', 'attachments', 'portfoliobuilder_entry', null, $oldid);
    }

    protected function process_portfoliobuilder_grade($data) {
        global $DB;

        $data = (object)$data;

        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->grader = $this->get_mappingid('user', $data->grader);
        $data->portfolioid = $this->get_new_parentid('portfoliobuilder');

        $DB->insert_record('portfoliobuilder_grades', $data);
    }

    protected function after_execute() {
        $this->add_related_files('mod_portfoliobuilder', 'intro', null);
    }
}
