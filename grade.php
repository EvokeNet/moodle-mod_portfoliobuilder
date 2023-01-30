<?php

/**
 * Redirect the user to the appropiate submission related page.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

require(__DIR__.'/../../config.php');

// Course module ID.
$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('portfoliobuilder', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moduleinstance = $DB->get_record('portfoliobuilder', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

// Item number may be != 0 for activities that allow more than one grade per user.
$itemnumber = optional_param('itemnumber', 0, PARAM_INT);

// Graded user ID (optional).
$userid = optional_param('userid', 0, PARAM_INT);

// In the simplest case just redirect to the view page.
redirect('view.php?id='.$id);
