<?php

/**
 * Display information about all the mod_portfoliobuilder modules in the requested course.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

require(__DIR__.'/../../config.php');

require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_course_login($course);

$context = context_course::instance($course->id);

$event = \mod_portfoliobuilder\event\course_module_instance_list_viewed::create([
    'context' => $context
]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$pagetitle = format_string($course->fullname);

$PAGE->set_url('/mod/portfoliobuilder/index.php', ['id' => $id]);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->set_context($context);

$PAGE->navbar->add($pagetitle);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_portfoliobuilder');

$contentrenderable = new \mod_portfoliobuilder\output\index($context, $course);

echo $renderer->render($contentrenderable);

echo $OUTPUT->footer();
