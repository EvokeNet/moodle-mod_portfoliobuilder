<?php

namespace mod_portfoliobuilder\external;

use external_api;
use external_value;
use external_single_structure;
use external_function_parameters;
use mod_portfoliobuilder\notification\commentmention;
use mod_portfoliobuilder\util\user;
use moodle_url;
use html_writer;
use context_course;
use context_module;

/**
 * Comment external api class.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class comment extends external_api {
    /**
     * Create comment parameters
     *
     * @return external_function_parameters
     */
    public static function add_parameters() {
        return new external_function_parameters([
            'comment' => new external_single_structure([
                'entryid' => new external_value(PARAM_INT, 'The entry id', VALUE_REQUIRED),
                'message' => new external_value(PARAM_RAW, 'The post message', VALUE_REQUIRED)
            ])
        ]);
    }

    /**
     * Create comment method
     *
     * @param array $comment
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function add($comment) {
        global $DB, $USER;

        self::validate_parameters(self::add_parameters(), ['comment' => $comment]);

        $comment = (object)$comment;

        $sql = 'SELECT e.id, e.userid, p.id as portfolioid, p.course, p.name as portfolioname
                FROM {portfoliobuilder_entries} e
                INNER JOIN {portfoliobuilder} p ON p.id = e.portfolioid
                WHERE e.id = :entryid';

        $utildata = $DB->get_record_sql($sql, ['entryid' => $comment->entryid], MUST_EXIST);
        $cm = get_coursemodule_from_instance('portfoliobuilder', $utildata->portfolioid);

        $contextcourse = context_course::instance($utildata->course);
        $contextmodule = context_module::instance($cm->id);

        $usercomment = new \stdClass();
        $usercomment->entryid = $comment->entryid;
        $usercomment->userid = $USER->id;
        $usercomment->timecreated = time();
        $usercomment->timemodified = time();

        // Handle the mentions.
        $matches = [];
        preg_match_all('/<span(.*?)<\/span>/s', $comment->message, $matches);
        $replaces = [];
        $userstonotifymention = [];
        if (!empty($matches[0])) {
            $userutil = new user();

            for ($i = 0; $i < count($matches[0]); $i++) {
                $mention = $matches[0][$i];

                $useridmatches = null;
                preg_match( '@data-uid="([^"]+)"@' , $mention, $useridmatches);
                $userid = array_pop($useridmatches);

                if (!$userid) {
                    continue;
                }

                $user = $userutil->get_by_id($userid, $contextcourse);

                if (!$user) {
                    continue;
                }

                $userprofilelink = new moodle_url('/user/view.php',  ['id' => $user->id, 'course' => $utildata->course]);
                $userprofilelink = html_writer::link($userprofilelink->out(false), fullname($user));

                $replaces['replace' . $i] = $userprofilelink;

                $userstonotifymention[] = $user->id;
            }
        }

        $usercomment->text = $comment->message;

        foreach ($replaces as $key => $replace) {
            $usercomment->text = str_replace("[$key]", $replace, $usercomment->text);
        }

        $insertedid = $DB->insert_record('portfoliobuilder_comments', $usercomment);
        $usercomment->id = $insertedid;

        $params = array(
            'context' => $contextmodule,
            'objectid' => $insertedid,
            'courseid' => $utildata->course,
            'relateduserid' => $utildata->userid
        );
        $event = \mod_portfoliobuilder\event\comment_added::create($params);
        $event->add_record_snapshot('portfoliobuilder_comments', $usercomment);
        $event->trigger();

        $notification = new commentmention($contextmodule, $cm->id, $utildata->course, $utildata->portfolioname, $utildata->userid);

        if (!empty($userstonotifymention)) {
            $notification->send_mentions_notifications($userstonotifymention);
        }

        return [
            'status' => 'ok',
            'comment' => [
                'id' => $usercomment->id,
                'message' => $usercomment->text,
                'humantimecreated' => userdate($usercomment->timecreated)
            ]
        ];
    }

    /**
     * Create comment return fields
     *
     * @return external_single_structure
     */
    public static function add_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'comment' => new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Comment id', VALUE_REQUIRED),
                    'message' => new external_value(PARAM_RAW, 'Comment message', VALUE_REQUIRED),
                    'humantimecreated' => new external_value(PARAM_TEXT, 'Human readable time created')
                ])
            )
        );
    }

    /**
     * Edit comment parameters
     *
     * @return external_function_parameters
     */
    public static function edit_parameters() {
        return new external_function_parameters([
            'comment' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'The message id', VALUE_REQUIRED),
                'message' => new external_value(PARAM_RAW, 'The post message', VALUE_REQUIRED)
            ])
        ]);
    }

    /**
     * Edit comment method
     *
     * @param array $comment
     *
     * @return array
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \moodle_exception
     */
    public static function edit($comment) {
        global $DB, $USER;

        self::validate_parameters(self::edit_parameters(), ['comment' => $comment]);

        $comment = (object)$comment;

        $sql = 'SELECT c.*, e.id as entryid, p.id as portfolioid, p.course
                FROM {portfoliobuilder_comments} c
                INNER JOIN {portfoliobuilder_entries} e ON e.id = c.entryid
                INNER JOIN {portfoliobuilder} p ON p.id = e.portfolioid
                WHERE c.id = :commentid AND c.userid = :userid';

        $utildata = $DB->get_record_sql($sql, ['commentid' => $comment->id, 'userid' => $USER->id], MUST_EXIST);

        $cm = get_coursemodule_from_instance('portfoliobuilder', $utildata->portfolioid);

        $contextcourse = context_course::instance($utildata->course);
        $contextmodule = context_module::instance($cm->id);

        $usercomment = new \stdClass();
        $usercomment->id = $utildata->id;
        $usercomment->timemodified = time();

        // Handle the mentions.
        $matches = [];
        preg_match_all('/<span(.*?)<\/span>/s', $comment->message, $matches);
        $replaces = [];
        $userstonotifymention = [];
        if (!empty($matches[0])) {
            $userutil = new user();

            for ($i = 0; $i < count($matches[0]); $i++) {
                $mention = $matches[0][$i];

                $useridmatches = null;
                preg_match( '@data-uid="([^"]+)"@' , $mention, $useridmatches);
                $userid = array_pop($useridmatches);

                if (!$userid) {
                    continue;
                }

                $user = $userutil->get_by_id($userid, $contextcourse);

                if (!$user) {
                    continue;
                }

                $userprofilelink = new moodle_url('/user/view.php',  ['id' => $user->id, 'course' => $utildata->course]);
                $userprofilelink = html_writer::link($userprofilelink->out(false), fullname($user));

                $replaces['replace' . $i] = $userprofilelink;

                $userstonotifymention[] = $user->id;
            }
        }

        $usercomment->text = $comment->message;

        foreach ($replaces as $key => $replace) {
            $usercomment->text = str_replace("[$key]", $replace, $usercomment->text);
        }

        $DB->update_record('portfoliobuilder_comments', $usercomment);

        $notification = new commentmention($contextmodule, $cm->id, $utildata->course, $utildata->portfolioname, $utildata->userid);

        if (!empty($userstonotifymention)) {
            $notification->send_mentions_notifications($userstonotifymention);
        }

        return [
            'status' => 'ok',
            'message' => get_string('editcomment_success', 'mod_portfoliobuilder')
        ];
    }

    /**
     * Edit comment return fields
     *
     * @return external_single_structure
     */
    public static function edit_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'Operation status'),
                'message' => new external_value(PARAM_RAW, 'Return message'),
            )
        );
    }
}