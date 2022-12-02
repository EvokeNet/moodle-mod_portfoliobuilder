<?php

/**
 * Prints an instance of mod_portfoliobuilder.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

require(__DIR__.'/../../config.php');

global $DB;

// Course module id.
$id = required_param('id', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'portfoliobuilder');
$portfoliobuilder = $DB->get_record('portfoliobuilder', ['id' => $cm->instance], '*', MUST_EXIST);

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/portfoliobuilder/layout.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($portfoliobuilder->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_portfoliobuilder');

$contentrenderable = new \mod_portfoliobuilder\output\layout($portfoliobuilder, $context);

echo $renderer->render($contentrenderable);

echo $OUTPUT->footer();
