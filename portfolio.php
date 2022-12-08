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

// Course module id.
$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'portfoliobuilder');
$portfoliobuilder = $DB->get_record('portfoliobuilder', ['id' => $cm->instance], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

$context = context_course::instance($course->id);

is_guest($context);

require_course_login($course->id);

$PAGE->set_url('/mod/portfoliobuilder/portfolio.php', ['id' => $cm->id, 'userid' => $userid]);
$PAGE->set_title(format_string($portfoliobuilder->name));
$PAGE->set_heading(format_string($portfoliobuilder->name));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->activityheader->disable();

$event = \mod_portfoliobuilder\event\portfolio_viewed::create(array(
    'context' => $context,
    'objectid' => $portfoliobuilder->id,
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('portfoliobuilder', $portfoliobuilder);
$event->trigger();

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_portfoliobuilder');

$contentrenderable = new \mod_portfoliobuilder\output\portfolio($context, $portfoliobuilder, $user);

echo $renderer->render($contentrenderable);

echo $OUTPUT->footer();
