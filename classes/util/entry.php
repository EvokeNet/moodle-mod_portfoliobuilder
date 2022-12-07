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
    public function get_user_course_entries($courseid, $userid = null) {
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
            $entryfiles = $this->get_attachments($record->id, \context_module::instance($record->cmid));

            $data[] = [
                'id' => $record->id,
                'title' => $record->title,
                'content' => format_text($record->content, $record->contentformat),
                'timecreated' => userdate($record->timecreated),
                'images' => !empty($entryfiles) ? $this->get_images($entryfiles) : false,
                'files' => !empty($entryfiles) ? $this->get_files($entryfiles) : false,
                'position' => ($i % 2 == 0) ? 'right' : 'left',
            ];

            $i++;
        }

        return $data;
    }

    public function get_images($files = null) {
        if (!$files) {
            return false;
        }

        return array_filter($files, function($file) {
            return $file['isimage'] === true;
        });
    }

    public function get_files($files = null) {
        if (!$files) {
            return false;
        }

        return array_filter($files, function($file) {
            return $file['isimage'] === false;
        });
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

    public function get_mock_entries($total = 10) {
        $data = [];

        for ($i = 1; $i <= $total; $i++) {
            $hasimage = (bool)random_int(0, 1);

            $data[] = [
                'id' => $i,
                'title' => $this->titles[rand(0,3)],
                'content' => $this->paragraphs[rand(0,3)],
                'position' => ($i % 2 == 0) ? 'right' : 'left',
                'image' => $hasimage ? random_int(1, 4) : null
            ];
        }

        return $data;
    }

    protected $titles = [
        'Random title',
        'Big random title',
        'Another random title',
        'More one random title'
    ];

    protected $paragraphs = [
        'Lorem ipsum, dolor sit amet consectetur adipisicing elit. Rem perspiciatis est fuga quas dolorem doloribus itaque omnis repudiandae animi accusamus hic maxime corporis doloremque cumque vel numquam, molestias, consequatur officiis.',
        'Architecto enim, reiciendis repudiandae voluptatem iure sequi quo reprehenderit, temporibus sint minima voluptates quibusdam consectetur libero suscipit illum exercitationem odio obcaecati itaque inventore rerum molestias, doloribus quos cum aut? Ratione!',
        'Fuga enim et impedit distinctio, similique sunt repellat voluptas eligendi modi, doloremque corporis, soluta dicta quod aut aliquam nam. Obcaecati fugit aspernatur id nobis officiis, dolorem delectus amet. Eaque, quibusdam!',
        'In perferendis, eos, ipsum labore quibusdam laboriosam fugit adipisci accusantium molestias non! Sunt, dignissimos, optio. Incidunt consequatur, sed saepe quidem quis facilis aliquam corporis exercitationem aspernatur vel neque quas necessitatibus!'
    ];
}