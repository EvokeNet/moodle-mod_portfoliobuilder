<?php

/**
 * Backup steps for mod_portfoliobuilder are defined here.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

// More information about the backup process: {@link https://docs.moodle.org/dev/Backup_API}.
// More information about the restore process: {@link https://docs.moodle.org/dev/Restore_API}.

/**
 * Define the complete structure for backup, with file and id annotations.
 */
class backup_portfoliobuilder_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the resulting xml file.
     *
     * @return backup_nested_element The structure wrapped by the common 'activity' element.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        // Build the tree with these elements with $portfoliobuilder as the root of the backup tree.
        $portfoliobuilder = new backup_nested_element('portfoliobuilder', ['id'], [
            'course', 'name', 'intro', 'introformat', 'grade', 'completionrequiresubmit', 'timecreated', 'timemodified']);

        $entries = new backup_nested_element('entries');
        $entry = new backup_nested_element('entry', ['id'], [
            'courseid', 'userid', 'title', 'content', 'contentformat', 'timecreated', 'timemodified']);

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', ['id'], [
            'userid', 'grader', 'grade', 'timecreated', 'timemodified']);

        $portfoliobuilder->add_child($entries);
        $entries->add_child($entry);
        $portfoliobuilder->add_child($grades);
        $grades->add_child($grade);

        // Define the source tables for the elements.
        $portfoliobuilder->set_source_table('portfoliobuilder', ['id' => backup::VAR_ACTIVITYID]);

        // User entries and grades are included only if we are including user info.
        if ($userinfo) {
            // Define sources.
            $entry->set_source_table('portfoliobuilder_entries', ['portfolioid' => backup::VAR_ACTIVITYID, 'courseid' => backup::VAR_COURSEID]);
            $grade->set_source_table('portfoliobuilder_grades', ['portfolioid' => backup::VAR_ACTIVITYID]);
        }

        $entry->annotate_ids('user', 'userid');

        $grade->annotate_ids('user', 'userid');
        $grade->annotate_ids('user', 'grader');

        // Define file annotations.
        $entry->annotate_files('mod_portfoliobuilder', 'attachments', 'id');

        $portfoliobuilder->annotate_files('mod_portfoliobuilder', 'intro', null);

        return $this->prepare_activity_structure($portfoliobuilder);
    }
}
