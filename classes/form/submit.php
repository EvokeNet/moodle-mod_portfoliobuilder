<?php

namespace mod_portfoliobuilder\form;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir. '/formslib.php');

class submit extends \moodleform {
    protected function definition() {
        $mform = $this->_form;

        if (isset($this->_customdata['entryid'])) {
            $mform->addElement('hidden', 'entryid', $this->_customdata['entryid']);
            $mform->setType('entryid', PARAM_INT);
        }

        $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'portfolioid', $this->_customdata['portfolioid']);
        $mform->setType('portfolioid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('title', 'mod_portfoliobuilder'), ['style' => 'width: 100%;']);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->setType('title', PARAM_TEXT);

        $options = [
            'subdirs'=> 0,
            'maxbytes'=> 0,
            'maxfiles'=> 0,
            'changeformat'=> 0,
            'context'=> null,
            'noclean'=> 0,
            'trusttext'=> 0,
            'enable_filemanagement' => false
        ];

        $mform->addElement('editor', 'content', get_string('content', 'mod_portfoliobuilder', $options));
        $mform->setType('content', PARAM_CLEANHTML);

        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'mod_portfoliobuilder'), null,
            ['subdirs' => 0, 'maxfiles' => 10, 'accepted_types' => ['document', 'presentation', 'optimised_image'], 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL]);

        $this->add_action_buttons(true);
    }

    public function definition_after_data() {
        global $DB;

        $mform = $this->_form;

        if (isset($this->_customdata['entryid'])) {
            $entry = $DB->get_record('portfoliobuilder_entries', ['id' => $this->_customdata['entryid']], '*', MUST_EXIST);

            $mform->getElement('content')->setValue([
                'text' => $entry->content,
                'format' => $entry->contentformat
            ]);

            $cm = get_coursemodule_from_instance('portfoliobuilder', $this->_customdata['portfolioid']);

            $context = \context_module::instance($cm->id);
            $draftitemid = file_get_submitted_draft_itemid('attachments');

            file_prepare_draft_area($draftitemid, $context->id, 'mod_portfoliobuilder', 'attachments', $entry->id, ['subdirs' => 0, 'maxfiles' => 10]);

            $mform->getElement('attachments')->setValue($draftitemid);
        }
    }

    public function validation($data, $files) {
        global $USER;

        $errors = parent::validation($data, $files);

        $usercontext = \context_user::instance($USER->id);

        $files = array();
        if(isset($data['attachments'])) {
            $fs = get_file_storage();
            $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data['attachments']);
        }

        if (empty($data['title']) || mb_strlen(strip_tags($data['title'])) < 3) {
            $errors['title'] = get_string('validation:titlerequirelen', 'mod_portfoliobuilder');
        }

        if (empty($files) && ($data['content'] && empty($data['content']['text']))) {
            $errors['attachments'] = get_string('validation:contentachmentsrequired', 'mod_portfoliobuilder');
            $errors['content'] = get_string('validation:contentachmentsrequired', 'mod_portfoliobuilder');
        }

        if ($data['content'] && !empty($data['content']['text']) && mb_strlen(strip_tags($data['content']['text'])) < 10) {
            $errors['content'] = get_string('validation:contentlen', 'mod_portfoliobuilder');
        }

        return $errors;
    }
}
