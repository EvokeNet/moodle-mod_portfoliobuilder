<?php

namespace mod_portfoliobuilder\form;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir. '/formslib.php');

/**
 * Submit form.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class submit extends \moodleform {
    protected $context;
    private $editordata = null;

    public function __construct($url, $formdata, $context)
    {
        $this->context = $context;

        parent::__construct($url, $formdata);
    }

    protected function definition() {
        global $CFG;

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

        $data = new \stdClass();
        if ($this->_customdata['entryid']) {
            $data = $this->get_entry_content($this->_customdata['entryid']);
        }

        $data = file_prepare_standard_editor($data,
            'content',
            $this->get_editor_options(),
            $this->context,
            'mod_portfoliobuilder',
            'entries_content',
            $data->id ?? null);

        $this->editordata = $data->content_editor;

        $mform->addElement('editor', 'content_editor', get_string('content', 'mod_portfoliobuilder'), null, $this->get_editor_options());

        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'mod_portfoliobuilder'), null,
            ['subdirs' => 0, 'maxfiles' => 10, 'accepted_types' => ['document', 'presentation', 'optimised_image'], 'return_types'=> FILE_INTERNAL | FILE_EXTERNAL]);

        $this->add_action_buttons(true);
    }

    public function definition_after_data() {
        global $DB;

        $mform = $this->_form;

        if (isset($this->_customdata['entryid'])) {
            $cm = get_coursemodule_from_instance('portfoliobuilder', $this->_customdata['portfolioid']);

            $context = \context_module::instance($cm->id);

            $entry = $DB->get_record('portfoliobuilder_entries', ['id' => $this->_customdata['entryid']], '*', MUST_EXIST);

            $mform->getElement('title')->setValue($entry->title);

            if (isset($entry->content)) {
                $mform->getElement('content_editor')->setValue([
                    'text' => $this->editordata['text'],
                    'format' => $entry->contentformat
                ]);
            }

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

        if (empty($files) && ($data['content_editor'] && empty($data['content_editor']['text']))) {
            $errors['attachments'] = get_string('validation:contentachmentsrequired', 'mod_portfoliobuilder');
            $errors['content'] = get_string('validation:contentachmentsrequired', 'mod_portfoliobuilder');
        }

        if ($data['content_editor'] && !empty($data['content_editor']['text']) && mb_strlen(strip_tags($data['content_editor']['text'])) < 10) {
            $errors['content'] = get_string('validation:contentlen', 'mod_portfoliobuilder');
        }

        return $errors;
    }

    private function get_editor_options() {
        global $CFG;

        return [
            'noclean' => false,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $CFG->maxbytes,
            'context' => $this->context,
            'return_types' => (FILE_INTERNAL | FILE_EXTERNAL | FILE_CONTROLLED_LINK),
            'removeorphaneddrafts' => true // Whether or not to remove any draft files which aren't referenced in the text.
        ];
    }

    private function get_entry_content($id) {
        global $DB;

        return $DB->get_record('portfoliobuilder_entries', ['id' => $id], 'id, content, contentformat', MUST_EXIST);
    }
}
