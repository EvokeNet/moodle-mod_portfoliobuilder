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
$id = optional_param('id', null, PARAM_INT);
$portfolioid = optional_param('portfolioid', null, PARAM_INT);

if (!$id && !$portfolioid) {
    throw new Exception('Illegal access');
}

if ($id) {
    list ($course, $cm) = get_course_and_cm_from_cmid($id, 'portfoliobuilder');
    $portfoliobuilder = $DB->get_record('portfoliobuilder', ['id' => $cm->instance], '*', MUST_EXIST);
} else if ($portfolioid) {
    list ($course, $cm) = get_course_and_cm_from_instance($portfolioid, 'portfoliobuilder');
    $portfoliobuilder = $DB->get_record('portfoliobuilder', ['id' => $cm->instance], '*', MUST_EXIST);
}

$context = context_module::instance($cm->id);

if (has_capability('mod/portfoliobuilder:grade', $context)) {
    redirect(new moodle_url('/mod/portfoliobuilder/index.php', ['id' => $course->id]));
}

$layoututil = new \mod_portfoliobuilder\util\layout();
if (!$layoututil->user_chose_layout($course->id)) {
    redirect(new moodle_url('/mod/portfoliobuilder/layout.php', ['id' => $id]));
}

require_course_login($course, true, $cm);

$event = \mod_portfoliobuilder\event\course_module_viewed::create(array(
    'context' => $context,
    'objectid' => $portfoliobuilder->id,
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('portfoliobuilder', $portfoliobuilder);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/portfoliobuilder/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($portfoliobuilder->name));
$PAGE->set_heading(format_string($portfoliobuilder->name));
$PAGE->set_context($context);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_portfoliobuilder');

$contentrenderable = new \mod_portfoliobuilder\output\view($portfoliobuilder, $context);

echo $renderer->render($contentrenderable);

echo $OUTPUT->footer();
