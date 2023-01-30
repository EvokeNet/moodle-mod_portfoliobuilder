<?php

namespace mod_portfoliobuilder\notification;

defined('MOODLE_INTERNAL') || die();

use core\message\message;
use moodle_url;

/**
 * Comment mention notification class
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class commentmention {
    /** @var \context Course context. */
    public $context;
    /** @var int The course module ID. */
    public $cmid;
    /** @var int The course ID. */
    public $courseid;
    /** @var string The course name. */
    public $portfolioname;
    /** @var string The user id. */
    public $userid;

    /**
     * Constructor.
     *
     * @param int $courseid
     * @param string $portfolioname
     * @param int $postid
     * @param \context $context
     */
    public function __construct($context, $cmid, $courseid, $portfolioname, $userid) {
        $this->context = $context;
        $this->cmid = $cmid;
        $this->courseid = $courseid;
        $this->portfolioname = $portfolioname;
        $this->userid = $userid;
    }

    /**
     * Send the message
     *
     * @param array $users A list of users ids to be notifiable
     *
     * @return bool
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function send_mentions_notifications(array $users) {
        $messagedata = $this->get_mention_message_data();

        foreach ($users as $user) {
            $messagedata->userto = $user;

            message_send($messagedata);
        }

        return true;
    }

    /**
     * Get the notification message data
     *
     * @return message
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function get_mention_message_data() {
        global $USER;

        $youwerementioned = get_string('message_mentioned', 'mod_portfoliobuilder');
        $youwerementionedinaportfolio = get_string('message_mentionedinaportfolio', 'mod_portfoliobuilder', $this->portfolioname);
        $clicktoaccessportfolio = get_string('message_clicktoaccessportfolio', 'mod_portfoliobuilder');

        $urlparams = [
            'id' => $this->cmid,
            'userid' => $this->userid
        ];

        $url = new moodle_url("/mod/portfoliobuilder/viewsubmission.php", $urlparams);

        $message = new message();
        $message->component = 'mod_portfoliobuilder';
        $message->name = 'commentmention';
        $message->userfrom = $USER;
        $message->subject = $youwerementioned;
        $message->fullmessage = "{$youwerementioned}: {$this->portfolioname}";
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '<p>'.$youwerementionedinaportfolio.'</p>';
        $message->fullmessagehtml .= '<p><a class="btn btn-primary" href="'.$url.'">'.$clicktoaccessportfolio.'</a></p>';
        $message->smallmessage = $youwerementioned;
        $message->contexturl = $url;
        $message->contexturlname = get_string('message_mentioncontextname', 'mod_portfoliobuilder');
        $message->courseid = $this->courseid;
        $message->notification = 1;

        return $message;
    }
}
