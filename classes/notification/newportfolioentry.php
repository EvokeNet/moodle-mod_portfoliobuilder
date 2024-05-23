<?php

namespace mod_portfoliobuilder\notification;

defined('MOODLE_INTERNAL') || die();

use core\message\message;
use mod_portfoliobuilder\util\group;
use moodle_url;

/**
 * New portfolio entry notification class
 *
 * @package     mod_portfoliobuilder
 * @copyright   2024 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class newportfolioentry {
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
    public function send_notifications() {
        $groupsutil = new group();

        $usercoursegroups = $groupsutil->get_user_groups($this->courseid, $this->userid);

        if (!$usercoursegroups) {
            return false;
        }

        $groupsmembers = $groupsutil->get_groups_mentors($usercoursegroups, $this->context, false);

        if (empty($groupsmembers)) {
            return false;
        }

        $messagedata = $this->get_message_data();

        foreach ($groupsmembers as $user) {
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
    protected function get_message_data() {
        global $USER;

        $messagesubject = get_string('message_newportfolioentry', 'mod_portfoliobuilder', fullname($USER));
        $clicktoaccessportfolio = get_string('message_clicktoaccessportfolio', 'mod_portfoliobuilder');

        $urlparams = [
            'id' => $this->courseid,
            'u' => $this->userid
        ];

        $url = new moodle_url("/mod/portfoliobuilder/portfolio.php", $urlparams);

        $message = new message();
        $message->component = 'mod_portfoliobuilder';
        $message->name = 'commentmention';
        $message->userfrom = $USER;
        $message->subject = $messagesubject;
        $message->fullmessage = $messagesubject;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '<p>'.$messagesubject.'</p>';
        $message->fullmessagehtml .= '<p><a class="btn btn-primary" href="'.$url.'">'.$clicktoaccessportfolio.'</a></p>';
        $message->smallmessage = $messagesubject;
        $message->contexturl = $url;
        $message->contexturlname = get_string('message_newportfolioentrycontextname', 'mod_portfoliobuilder');
        $message->courseid = $this->courseid;
        $message->notification = 1;

        return $message;
    }
}
