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
class commentonyourportfolio {
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
     * @param $usercomment
     *
     * @return bool
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function send_notification() {
        global $USER;

        $messagedata = $this->get_message_data(fullname($USER));

        $messagedata->userto = $this->userid;

        message_send($messagedata);

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
    protected function get_message_data($usercomment) {
        global $USER;

        $someonecommentedyourportfolio = get_string('message_someonecommentedyourportfolio', 'mod_portfoliobuilder', $usercomment);
        $clicktoaccessportfolio = get_string('message_clicktoaccessportfolio', 'mod_portfoliobuilder');

        $urlparams = [
            'id' => $this->courseid,
            'u' => $this->userid
        ];

        $url = new moodle_url("/mod/portfoliobuilder/portfolio.php", $urlparams);

        $message = new message();
        $message->component = 'mod_portfoliobuilder';
        $message->name = 'commentonyourportfolio';
        $message->userfrom = $USER;
        $message->subject = $someonecommentedyourportfolio;
        $message->fullmessage = $someonecommentedyourportfolio;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '<p>'.$someonecommentedyourportfolio.'</p>';
        $message->fullmessagehtml .= '<p><a class="btn btn-primary" href="'.$url.'">'.$clicktoaccessportfolio.'</a></p>';
        $message->smallmessage = $someonecommentedyourportfolio;
        $message->contexturl = $url;
        $message->contexturlname = get_string('message_commentportfoliocontextname', 'mod_portfoliobuilder');
        $message->courseid = $this->courseid;
        $message->notification = 1;

        return $message;
    }
}
