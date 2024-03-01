<?php

/**
 * Submit portfolio entry.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot . '/repository/lib.php');

// Course module id.
$id = required_param('id', PARAM_INT);
$entryid = optional_param('entryid', null, PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'portfoliobuilder');

require_course_login($course, true);

$portfoliobuilder = $DB->get_record('portfoliobuilder', ['id' => $cm->instance], '*', MUST_EXIST);

if ($entryid) {
    $entry = $DB->get_record('portfoliobuilder_entries', ['id' => $entryid, 'userid' => $USER->id], '*', MUST_EXIST);
}

$context = context_module::instance($id);

$url = new moodle_url('/mod/portfoliobuilder/submit.php', ['id' => $id]);

$PAGE->set_url($url);
$PAGE->set_title(format_string($portfoliobuilder->name));
$PAGE->set_heading(format_string($portfoliobuilder->name));
$PAGE->set_context($context);

$formdata = [
    'courseid' => $course->id,
    'portfolioid' => $portfoliobuilder->id
];

if ($entryid) {
    $formdata['entryid'] = $entryid;
}

$form = new \mod_portfoliobuilder\form\submit($url, $formdata, $context);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/portfoliobuilder/view.php', ['id' => $id]));
} else if ($formdata = $form->get_data()) {
    try {
        unset($formdata->submitbutton);

        if (isset($formdata->entryid)) {
            $entry = $DB->get_record('portfoliobuilder_entries', ['id' => $formdata->entryid, 'userid' => $USER->id], '*', MUST_EXIST);

            $entry->title = $formdata->title;
            $entry->timemodified = time();

            $DB->update_record('portfoliobuilder_entries', $entry);

            // Process event.
            $params = array(
                'context' => $context,
                'objectid' => $entry->id,
                'relateduserid' => $entry->userid
            );
            $event = \mod_portfoliobuilder\event\entry_updated::create($params);
            $event->add_record_snapshot('portfoliobuilder_entries', $entry);
            $event->trigger();

            $redirectstring = get_string('entry:update_success', 'mod_portfoliobuilder');
        } else {
            $entry = new \stdClass();
            $entry->courseid = $course->id;
            $entry->portfolioid = $portfoliobuilder->id;
            $entry->userid = $USER->id;
            $entry->title = $formdata->title;
            $entry->content = null;
            $entry->contentformat = null;
            $entry->timecreated = time();
            $entry->timemodified = time();

            $entryid = $DB->insert_record('portfoliobuilder_entries', $entry);
            $entry->id = $entryid;

            // Processe event.
            $params = array(
                'context' => $context,
                'objectid' => $entryid,
                'relateduserid' => $entry->userid
            );
            $event = \mod_portfoliobuilder\event\entry_added::create($params);
            $event->add_record_snapshot('portfoliobuilder_entries', $entry);
            $event->trigger();

            // Completion progress.
            $completion = new completion_info($course);
            $completion->update_state($cm, COMPLETION_COMPLETE);

            $redirectstring = get_string('entry:add_success', 'mod_portfoliobuilder');
        }

        // Process content text with audio and video.
        $editoroptions = array(
            'noclean' => false,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'context' => $context,
            'return_types' => (FILE_INTERNAL | FILE_EXTERNAL | FILE_CONTROLLED_LINK),
            'removeorphaneddrafts' => true // Whether or not to remove any draft files which aren't referenced in the text.
        );

        $formdata = file_postupdate_standard_editor($formdata,
            'content',
            $editoroptions,
            $context,
            'mod_portfoliobuilder',
            'entries_content',
            $entry->id);

        if (isset($formdata->content)) {
            $entry->content = $formdata->content;
            $entry->contentformat = $formdata->contentformat;

            $DB->update_record('portfoliobuilder_entries', $entry);
        }

        // Process attachments.
        $draftitemid = file_get_submitted_draft_itemid('attachments');

        file_save_draft_area_files($draftitemid, $context->id, 'mod_portfoliobuilder', 'attachments', $entry->id, ['subdirs' => 0, 'maxfiles' => 10]);

        $entryutil = new \mod_portfoliobuilder\util\entry();
        $entryutil->create_entry_thumbs($entry);

        $url = new moodle_url('/mod/portfoliobuilder/view.php', ['id' => $cm->id]);

        redirect($url, $redirectstring, null, \core\output\notification::NOTIFY_SUCCESS);
    } catch (\Exception $e) {
        redirect($url, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
} else {
    echo $OUTPUT->header();

    $form->display();

    echo $OUTPUT->footer();
}
