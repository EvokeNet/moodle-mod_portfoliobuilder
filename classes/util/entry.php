<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Layout utility class helper
 *
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class entry {
    public function get_user_course_entries($context, $courseid, $userid = null) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $records = $DB->get_records('portfoliobuilder_entries', ['courseid' => $courseid, 'userid' => $userid]);

        if (!$records) {
            return false;
        }

        $data = [];
        $i = 1;
        foreach ($records as $record) {
            $attachments = $this->get_attachments($record->id, $context);

            $images = $this->get_images($attachments);
            $files = $this->get_files($attachments);

            $entry = [
                'id' => $record->id,
                'title' => $record->title,
                'content' => format_text($record->content, $record->contentformat),
                'timecreated' => userdate($record->timecreated),
                'hasimages' => !empty($images),
                'images' => $images,
                'singleimage' => !empty($images) && count($images) === 1,
                'hasfiles' => !empty($files),
                'files' => $files,
                'position' => ($i % 2 == 0) ? 'right' : 'left',
            ];

            $data[] = array_merge($entry, $this->get_entry_reactions($record->id));

            $i++;
        }

        return $data;
    }

    public function get_images($files = null) {
        if (!$files) {
            return false;
        }

        $files = array_filter($files, function($file) {
            return $file['isimage'] === true;
        });

        $files = array_values($files);

        $files[0]['active'] = true;

        return $files;
    }

    public function get_files($files = null) {
        if (!$files) {
            return false;
        }

        $files = array_filter($files, function($file) {
            return $file['isimage'] === false;
        });

        return array_values($files);
    }

    public function get_attachments($entryid, $context) {
        $fs = get_file_storage();

        $files = $fs->get_area_files($context->id,
            'mod_portfoliobuilder',
            'attachments',
            $entryid,
            'timemodified',
            false);

        if (!$files) {
            return false;
        }

        $entryfiles = [];
        foreach ($files as $file) {
            $path = [
                '',
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $entryid . $file->get_filepath() . $file->get_filename()
            ];

            $fileurl = \moodle_url::make_file_url('/pluginfile.php', implode('/', $path), true);

            $entryfiles[] = [
                'filename' => $file->get_filename(),
                'isimage' => $file->is_valid_image(),
                'fileurl' => $fileurl->out()
            ];
        }

        return $entryfiles;
    }

    public function get_entry_reactions($entryid) {
        $reactionutil = new reaction();

        $totalreactions = $reactionutil->get_total_reactions($entryid, reaction::LIKE);
        $userreacted = $reactionutil->user_reacted($entryid, reaction::LIKE);

        return [
            'totalreactions' => $totalreactions,
            'userreacted' => $userreacted
        ];
    }
}