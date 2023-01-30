<?php

/**
 * Prints an instance of mod_portfoliobuilder.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

require(__DIR__.'/../../config.php');

global $DB;

// Course module id.
$id = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'portfoliobuilder');
$portfoliobuilder = $DB->get_record('portfoliobuilder', ['id' => $cm->instance], '*', MUST_EXIST);

require_course_login($course, true, $cm);

$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/portfoliobuilder/layout.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($portfoliobuilder->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

if (!$action) {
    echo $OUTPUT->header();

    $renderer = $PAGE->get_renderer('mod_portfoliobuilder');

    $contentrenderable = new \mod_portfoliobuilder\output\layout($portfoliobuilder, $context);

    echo $renderer->render($contentrenderable);

    echo $OUTPUT->footer();

    exit;
}

$courseid = required_param('courseid', PARAM_INT);
$layout = required_param('layout', PARAM_ALPHA);

$layoututil = new \mod_portfoliobuilder\util\layout();

if ($layoututil->set_user_layout($courseid, $layout)) {
    redirect(new moodle_url('/mod/portfoliobuilder/view.php', ['id' => $id]), 'Preferences successfuly saved.');
}

redirect(new moodle_url('/mod/portfoliobuilder/layout.php', ['id' => $id]), 'Error attempting to save your proferences.');