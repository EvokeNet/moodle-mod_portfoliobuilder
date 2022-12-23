<?php

/**
 * Prints user's public portfolio page.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

require(__DIR__.'/../../config.php');

global $DB;

// Course id.
$id = required_param('id', PARAM_INT);
$userid = required_param('u', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

if ($user->id == $USER->id) {
    $instances = get_all_instances_in_course('portfoliobuilder', $course);

    if ($instances) {
        $current = current($instances);

        redirect(new moodle_url('/mod/portfoliobuilder/view.php', ['id' => $current->coursemodule]));
    }
}

$context = context_course::instance($course->id);

$PAGE->set_context($context);
$PAGE->set_url('/mod/portfoliobuilder/portfolio.php', ['id' => $id, 'u' => $userid]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

$PAGE->add_body_class('path-mod-portfoliobuilder');

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_portfoliobuilder');

$contentrenderable = new \mod_portfoliobuilder\output\portfolio($context, $course, $user);

echo $renderer->render($contentrenderable);

echo $OUTPUT->footer();
