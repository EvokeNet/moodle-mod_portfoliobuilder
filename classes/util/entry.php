<?php

namespace mod_portfoliobuilder\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Layout utility class helper
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class entry {
    private $portfoliocontexts = [];

    public function get_entry_context($portfolioid) {
        if (isset($this->portfoliocontexts[$portfolioid])) {
            return $this->portfoliocontexts[$portfolioid];
        }

        $coursemodule = get_coursemodule_from_instance('portfoliobuilder', $portfolioid);

        $this->portfoliocontexts[$portfolioid] = \context_module::instance($coursemodule->id);

        return $this->portfoliocontexts[$portfolioid];
    }

    public function get_user_portfolio_entries($portfolioid, $userid = null) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $records = $DB->get_records('portfoliobuilder_entries', ['portfolioid' => $portfolioid, 'userid' => $userid]);

        if (!$records) {
            return false;
        }

        return $this->fill_entries_with_additional_data($records, $userid);
    }

    public function get_user_course_entries($courseid, $userid = null) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $records = $DB->get_records('portfoliobuilder_entries', ['courseid' => $courseid, 'userid' => $userid]);

        if (!$records) {
            return false;
        }

        return $this->fill_entries_with_additional_data($records, $userid);
    }

    public function fill_entries_with_additional_data($records, $userid) {
        global $USER;

        $data = [];
        $i = 1;
        foreach ($records as $record) {
            $context = $this->get_entry_context($record->portfolioid);

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
                'isowner' => $USER->id == $userid,
                'cmid' => $context->instanceid
            ];

            $data[] = array_merge($entry, $this->get_entry_reactions($record->id), $this->get_entry_comments($record->id));

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
        $files = $this->get_entry_attachments($entryid, $context->id);

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

            if (!$file->is_valid_image() || $file->get_filepath() == '/thumb/') {
                $entryfiles[] = [
                    'filename' => $file->get_filename(),
                    'isimage' => $file->is_valid_image(),
                    'fileurl' => $fileurl->out()
                ];
            }
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

    public function get_entry_comments($entryid) {
        global $DB, $USER;


        $sql = 'SELECT c.id as commentid, c.text, c.timecreated as ctimecreated, c.timemodified as ctimemodified, u.id as userid, u.*
            FROM {portfoliobuilder_comments} c
            INNER JOIN {user} u ON u.id = c.userid
            WHERE c.entryid = :entryid';

        $comments = $DB->get_records_sql($sql, ['entryid' => $entryid]);

        if (!$comments) {
            return [
                'comments' => false,
                'totalcomments' => 0
            ];
        }

        $userutil = new user();

        $commentsdata = [];
        foreach ($comments as $comment) {
            $userpicture = $userutil->get_user_image_or_avatar($comment);

            $commentsdata[] = [
                'commentid' => $comment->commentid,
                'text' => $comment->text,
                'commentuserpicture' => $userpicture,
                'commentuserfullname' => fullname($comment),
                'isowner' => $USER->id == $comment->userid,
                'edited' => $comment->ctimecreated != $comment->ctimemodified,
                'humantimecreated' => userdate($comment->ctimecreated)
            ];
        }

        return [
            'comments' => $commentsdata,
            'totalcomments' => count($commentsdata)
        ];
    }

    public function get_total_course_entries($courseid, $userid, $chapter = null) {
        global $DB;

        $sql = 'SELECT count(*)
                FROM {portfoliobuilder_entries} e
                INNER JOIN {portfoliobuilder} p ON p.id = e.portfolioid
                WHERE e.courseid = :courseid AND e.userid = :userid';

        $parameters = ['userid' => $userid, 'courseid' => $courseid];

        if (!is_null($chapter)) {
            $sql .= ' AND p.chapter = :chapter';

            $parameters['chapter'] = $chapter;
        }

        return $DB->count_records_sql($sql, $parameters);
    }

    public function get_last_course_entry($courseid, $userid, $chapter = null) {
        global $DB;

        $sql = 'SELECT e.id, e.title, e.timecreated
                FROM {portfoliobuilder_entries} e
                INNER JOIN {portfoliobuilder} p ON p.id = e.portfolioid
                WHERE e.courseid = :courseid AND e.userid = :userid';

        $parameters = ['courseid' => $courseid, 'userid' => $userid];

        if (!is_null($chapter)) {
            $sql .= ' AND p.chapter = :chapter';

            $parameters['chapter'] = $chapter;
        }

        $sql .= ' ORDER BY id DESC LIMIT 1';

        $record = $DB->get_record_sql($sql, $parameters);

        if (!$record) {
            return false;
        }

        $record->humantimecreated = userdate($record->timecreated);

        return $record;
    }

    public function user_has_entry_in_portfolio_instance($portfolioid, $userid = null) {
        global $DB, $USER;

        if (!$userid) {
            $userid = $USER->id;
        }

        $count = $DB->count_records('portfoliobuilder_entries', ['portfolioid' => $portfolioid, 'userid' => $userid]);

        if (!$count) {
            return false;
        }

        return true;
    }

    public function delete_entry($entryid) {
        global $DB, $USER;

        $entry = $DB->get_record('portfoliobuilder_entries', ['id' => $entryid, 'userid' => $USER->id], '*', MUST_EXIST);

        $DB->delete_records('portfoliobuilder_comments', ['entryid' => $entry->id]);

        $DB->delete_records('portfoliobuilder_reactions', ['entryid' => $entry->id]);

        $DB->delete_records('portfoliobuilder_entries', ['id' => $entry->id]);

        $coursemodule = get_coursemodule_from_instance('portfoliobuilder', $entry->portfolioid, $entry->courseid);

        $context = \context_module::instance($coursemodule->id);

        if ($files = $this->get_entry_attachments($entryid, $context->id)) {
            foreach ($files as $file) {
                $file->delete();
            }
        }
    }

    public function get_entry_attachments($entryid, $contextid, $filearea = 'attachments') {
        $fs = get_file_storage();

        $files = $fs->get_area_files($contextid,
            'mod_portfoliobuilder',
            $filearea,
            $entryid,
            'timemodified',
            false);

        if (!$files) {
            return false;
        }

        return $files;
    }

    public function create_entry_thumbs($entry) {
        $context = $this->get_entry_context($entry->portfolioid);

        if (!$files = $this->get_entry_attachments($entry->id, $context->id)) {
            return;
        }

        $fs = get_file_storage();

        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                $filerecord = [
                    'userid' => $entry->userid,
                    'filename' => $file->get_filename(),
                    'contextid' => $file->get_contextid(),
                    'component' => $file->get_component(),
                    'filearea' => $file->get_filearea(),
                    'itemid' => $entry->id,
                    'filepath' => '/thumb/'
                ];

                $fileinfo = $file->get_imageinfo();

                $width = $fileinfo['width'];
                if ($width > 600) {
                    $width = 600;
                }

                $fs->convert_image($filerecord, $file, $width);
            }
        }
    }
}
