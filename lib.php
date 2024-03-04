<?php

/**
 * Library of interface functions and constants.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function portfoliobuilder_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_ASSIGNMENT;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COLLABORATION;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_portfoliobuilder into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_portfoliobuilder_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function portfoliobuilder_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('portfoliobuilder', $moduleinstance);

    $moduleinstance->id = $id;

    portfoliobuilder_grade_item_update($moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_portfoliobuilder in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_portfoliobuilder_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function portfoliobuilder_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    portfoliobuilder_grade_item_update($moduleinstance);

    return $DB->update_record('portfoliobuilder', $moduleinstance);
}

/**
 * Removes an instance of the mod_portfoliobuilder from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function portfoliobuilder_delete_instance($id) {
    global $DB;

    $portfolio = $DB->get_record('portfoliobuilder', ['id' => $id]);
    if (!$portfolio) {
        return false;
    }

    $entries = $DB->get_records('portfoliobuilder_entries', ['portfolioid' => $id]);

    foreach ($entries as $entry) {
        $DB->delete_records('portfoliobuilder_comments', ['entryid' => $entry->id]);

        $DB->delete_records('portfoliobuilder_reactions', ['entryid' => $entry->id]);
    }

    $DB->delete_records('portfoliobuilder_entries', ['portfolioid' => $id]);

    $DB->delete_records('portfoliobuilder_grades', ['portfolioid' => $id]);

    $DB->delete_records('portfoliobuilder', ['id' => $id]);

    portfoliobuilder_grade_item_delete($portfolio);

    return true;
}

/**
 * Is a given scale used by the instance of mod_portfoliobuilder?
 *
 * This function returns if a scale is being used by one mod_portfoliobuilder
 * if it has support for grading and scales.
 *
 * @param int $moduleinstanceid ID of an instance of this module.
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by the given mod_portfoliobuilder instance.
 */
function portfoliobuilder_scale_used($moduleinstanceid, $scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('portfoliobuilder', ['id' => $moduleinstanceid, 'grade' => -$scaleid])) {
        return true;
    }

    return false;
}

/**
 * Checks if scale is being used by any instance of mod_portfoliobuilder.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by any mod_portfoliobuilder instance.
 */
function portfoliobuilder_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('portfoliobuilder', array('grade' => -$scaleid))) {
        return true;
    }

    return false;
}

/**
 * Creates or updates grade item for the given mod_portfoliobuilder instance.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param bool $reset Reset grades in the gradebook.
 * @return void.
 */
function portfoliobuilder_grade_item_update($moduleinstance, $reset=false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($moduleinstance->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($moduleinstance->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax']  = $moduleinstance->grade;
        $item['grademin']  = 0;
    } else if ($moduleinstance->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid']   = -$moduleinstance->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }
    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/portfoliobuilder', $moduleinstance->course, 'mod', 'portfoliobuilder', $moduleinstance->id, 0, null, $item);
}

/**
 * Delete grade item for given mod_portfoliobuilder instance.
 *
 * @param stdClass $moduleinstance Instance object.
 * @return grade_item.
 */
function portfoliobuilder_grade_item_delete($moduleinstance) {
    global $CFG;

    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/portfoliobuilder', $moduleinstance->course, 'mod', 'portfoliobuilder',
                        $moduleinstance->id, 0, null, array('deleted' => 1));
}

/**
 * Update mod_portfoliobuilder grades in the gradebook.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid Update grade of specific user only, 0 means all participants.
 */
function portfoliobuilder_update_grades($moduleinstance, $userid = 0) {
    global $CFG;

    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = [];

    grade_update('mod/portfoliobuilder', $moduleinstance->course, 'mod', 'portfoliobuilder', $moduleinstance->id, 0, $grades);
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@see file_browser::get_file_info_context_module()}.
 *
 * @package     mod_portfoliobuilder
 * @category    files
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return string[].
 */
function portfoliobuilder_get_file_areas($course, $cm, $context) {
    return [
        'entries_content' => get_string('content', 'mod_portfoliobuilder')
    ];
}

/**
 * File browsing support for mod_portfoliobuilder file areas.
 *
 * @package     mod_portfoliobuilder
 * @category    files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info Instance or null if not found.
 */
function portfoliobuilder_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the mod_portfoliobuilder file areas.
 *
 * @package     mod_portfoliobuilder
 * @category    files
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $context The mod_portfoliobuilder's context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 */
function portfoliobuilder_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

//    require_login($course, false, $cm);

    $itemid = (int)array_shift($args);
    if ($itemid == 0) {
        return false;
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/mod_portfoliobuilder/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function mod_portfoliobuilder_output_fragment_grade_form($args) {
    $args = (object) $args;
    $o = '';

    $formdata = [];
    if (!empty($args->jsonformdata)) {
        $serialiseddata = json_decode($args->jsonformdata);
        $formdata = (array)$serialiseddata;
    }

    $mform = new \mod_portfoliobuilder\form\grade($formdata, [
        'userid' => $serialiseddata->userid,
        'instanceid' => $serialiseddata->instanceid
    ]);

    if (!empty($args->jsonformdata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    ob_start();
    $mform->display();
    $o .= ob_get_contents();
    ob_end_clean();

    return $o;
}

/**
 * Add a get_coursemodule_info function in case any survey type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function portfoliobuilder_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionrequiresubmit';
    if (!$portfoliobuilder = $DB->get_record('portfoliobuilder', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $portfoliobuilder->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('portfoliobuilder', $portfoliobuilder, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionrequiresubmit'] = $portfoliobuilder->completionrequiresubmit;
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_portfoliobuilder_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules']) || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionrequiresubmit':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionrequiresubmit', 'mod_portfoliobuilder');
                }
                break;
            default:
                break;
        }
    }

    return $descriptions;
}
