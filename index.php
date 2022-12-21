<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Display information about all the mod_portfoliobuilder modules in the requested course.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 Willian Mano <willianmanoaraujo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
