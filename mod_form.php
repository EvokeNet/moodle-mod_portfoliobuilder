<?php

/**
 * The main mod_portfoliobuilder configuration form.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

use core_grades\component_gradeitems;

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 Willian Mano <willianmanoaraujo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_portfoliobuilder_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name', 'mod_portfoliobuilder'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;

            if (!$autocompletion || empty($data->completionrequiresubmit)) {
                $data->completionrequiresubmit = 0;
            }
        }

        if (!isset($data->grade)) {
            $data->grade = 0;
        }
    }

    /**
     * Add elements for setting the custom completion rules.
     *
     * @category completion
     * @return array List of added element names, or names of wrapping group elements.
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $mform->addElement('checkbox', 'completionrequiresubmit', get_string('completionrequiresubmit', 'mod_portfoliobuilder'), get_string('completionrequiresubmit_help', 'mod_portfoliobuilder'));
        $mform->setDefault('completionrequiresubmit', 1);
        $mform->setType('completionrequiresubmit', PARAM_INT);

        return ['completionrequiresubmit'];
    }

    /**
     * Called during validation to see whether some module-specific completion rules are selected.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionrequiresubmit']);
    }

    /**
     * Adds all the standard elements to a form to edit the settings for an activity module.
     */
    protected function standard_coursemodule_elements() {
        global $COURSE, $CFG, $DB;
        $mform =& $this->_form;

        $this->_outcomesused = false;
        if ($this->_features->outcomes) {
            if ($outcomes = grade_outcome::fetch_all_available($COURSE->id)) {
                $this->_outcomesused = true;
                $mform->addElement('header', 'modoutcomes', get_string('outcomes', 'grades'));
                foreach($outcomes as $outcome) {
                    $mform->addElement('advcheckbox', 'outcome_'.$outcome->id, $outcome->get_name());
                }
            }
        }

        if ($this->_features->rating) {
            $this->add_rating_settings($mform, 0);
        }

        $mform->addElement('header', 'modstandardelshdr', get_string('modstandardels', 'form'));

        $section = get_fast_modinfo($COURSE)->get_section_info($this->_section);
        $allowstealth =
            !empty($CFG->allowstealth) &&
            $this->courseformat->allow_stealth_module_visibility($this->_cm, $section) &&
            !$this->_features->hasnoview;
        if ($allowstealth && $section->visible) {
            $modvisiblelabel = 'modvisiblewithstealth';
        } else if ($section->visible) {
            $modvisiblelabel = 'modvisible';
        } else {
            $modvisiblelabel = 'modvisiblehiddensection';
        }
        $mform->addElement('modvisible', 'visible', get_string($modvisiblelabel), null,
            array('allowstealth' => $allowstealth, 'sectionvisible' => $section->visible, 'cm' => $this->_cm));
        $mform->addHelpButton('visible', $modvisiblelabel);
        if (!empty($this->_cm)) {
            $context = context_module::instance($this->_cm->id);
            if (!has_capability('moodle/course:activityvisibility', $context)) {
                $mform->hardFreeze('visible');
            }
        }

        if ($this->_features->idnumber) {
            $mform->addElement('text', 'cmidnumber', get_string('idnumbermod'));
            $mform->setType('cmidnumber', PARAM_RAW);
            $mform->addHelpButton('cmidnumber', 'idnumbermod');
        }

        if ($CFG->downloadcoursecontentallowed) {
            $choices = [
                DOWNLOAD_COURSE_CONTENT_DISABLED => get_string('no'),
                DOWNLOAD_COURSE_CONTENT_ENABLED => get_string('yes'),
            ];
            $mform->addElement('select', 'downloadcontent', get_string('downloadcontent', 'course'), $choices);
            $downloadcontentdefault = $this->_cm->downloadcontent ?? DOWNLOAD_COURSE_CONTENT_ENABLED;
            $mform->addHelpButton('downloadcontent', 'downloadcontent', 'course');
            if (has_capability('moodle/course:configuredownloadcontent', $this->get_context())) {
                $mform->setDefault('downloadcontent', $downloadcontentdefault);
            } else {
                $mform->hardFreeze('downloadcontent');
                $mform->setConstant('downloadcontent', $downloadcontentdefault);
            }
        }

        if ($this->_features->groups) {
            $options = array(NOGROUPS       => get_string('groupsnone'),
                SEPARATEGROUPS => get_string('groupsseparate'),
                VISIBLEGROUPS  => get_string('groupsvisible'));
            $mform->addElement('select', 'groupmode', get_string('groupmode', 'group'), $options, NOGROUPS);
            $mform->addHelpButton('groupmode', 'groupmode', 'group');
        }

        if ($this->_features->groupings) {
            // Groupings selector - used to select grouping for groups in activity.
            $options = array();
            if ($groupings = $DB->get_records('groupings', array('courseid'=>$COURSE->id))) {
                foreach ($groupings as $grouping) {
                    $options[$grouping->id] = format_string($grouping->name);
                }
            }
            core_collator::asort($options);
            $options = array(0 => get_string('none')) + $options;
            $mform->addElement('select', 'groupingid', get_string('grouping', 'group'), $options);
            $mform->addHelpButton('groupingid', 'grouping', 'group');
        }

        if (!empty($CFG->enableavailability)) {
            // Add special button to end of previous section if groups/groupings
            // are enabled.

            $availabilityplugins = \core\plugininfo\availability::get_enabled_plugins();
            $groupavailability = $this->_features->groups && array_key_exists('group', $availabilityplugins);
            $groupingavailability = $this->_features->groupings && array_key_exists('grouping', $availabilityplugins);

            if ($groupavailability || $groupingavailability) {
                // When creating the button, we need to set type=button to prevent it behaving as a submit.
                $mform->addElement('static', 'restrictgroupbutton', '',
                    html_writer::tag('button', get_string('restrictbygroup', 'availability'), [
                        'id' => 'restrictbygroup',
                        'type' => 'button',
                        'disabled' => 'disabled',
                        'class' => 'btn btn-secondary',
                        'data-groupavailability' => $groupavailability,
                        'data-groupingavailability' => $groupingavailability
                    ])
                );
            }

            // Availability field. This is just a textarea; the user interface
            // interaction is all implemented in JavaScript.
            $mform->addElement('header', 'availabilityconditionsheader',
                get_string('restrictaccess', 'availability'));
            // Note: This field cannot be named 'availability' because that
            // conflicts with fields in existing modules (such as assign).
            // So it uses a long name that will not conflict.
            $mform->addElement('textarea', 'availabilityconditionsjson',
                get_string('accessrestrictions', 'availability'));
            // The _cm variable may not be a proper cm_info, so get one from modinfo.
            if ($this->_cm) {
                $modinfo = get_fast_modinfo($COURSE);
                $cm = $modinfo->get_cm($this->_cm->id);
            } else {
                $cm = null;
            }
            \core_availability\frontend::include_all_javascript($COURSE, $cm);
        }

        // Conditional activities: completion tracking section
        if(!isset($completion)) {
            $completion = new completion_info($COURSE);
        }

        $canviewactivitycompletioncriterias = $this->can_view_activity_completion_criterias();

        if ($completion->is_enabled() && $canviewactivitycompletioncriterias) {
            $mform->addElement('header', 'activitycompletionheader', get_string('activitycompletion', 'completion'));
            // Unlock button for if people have completed it (will
            // be removed in definition_after_data if they haven't)
            $mform->addElement('submit', 'unlockcompletion', get_string('unlockcompletion', 'completion'));
            $mform->registerNoSubmitButton('unlockcompletion');
            $mform->addElement('hidden', 'completionunlocked', 0);
            $mform->setType('completionunlocked', PARAM_INT);

            $trackingdefault = COMPLETION_TRACKING_NONE;
            // If system and activity default is on, set it.
            if ($CFG->completiondefault && $this->_features->defaultcompletion) {
                $hasrules = plugin_supports('mod', $this->_modname, FEATURE_COMPLETION_HAS_RULES, true);
                $tracksviews = plugin_supports('mod', $this->_modname, FEATURE_COMPLETION_TRACKS_VIEWS, true);
                if ($hasrules || $tracksviews) {
                    $trackingdefault = COMPLETION_TRACKING_AUTOMATIC;
                } else {
                    $trackingdefault = COMPLETION_TRACKING_MANUAL;
                }
            }

            $mform->addElement('select', 'completion', get_string('completion', 'completion'),
                array(COMPLETION_TRACKING_NONE=>get_string('completion_none', 'completion'),
                    COMPLETION_TRACKING_MANUAL=>get_string('completion_manual', 'completion')));
            $mform->setDefault('completion', $trackingdefault);
            $mform->addHelpButton('completion', 'completion', 'completion');

            // Automatic completion once you view it
            $gotcompletionoptions = false;
            if (plugin_supports('mod', $this->_modname, FEATURE_COMPLETION_TRACKS_VIEWS, false)) {
                $mform->addElement('checkbox', 'completionview', get_string('completionview', 'completion'),
                    get_string('completionview_desc', 'completion'));
                $mform->hideIf('completionview', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
                // Check by default if automatic completion tracking is set.
                if ($trackingdefault == COMPLETION_TRACKING_AUTOMATIC) {
                    $mform->setDefault('completionview', 1);
                }
                $gotcompletionoptions = true;
            }

            if (plugin_supports('mod', $this->_modname, FEATURE_GRADE_HAS_GRADE, false)) {
                // This activity supports grading.
                $gotcompletionoptions = true;

                $component = "mod_{$this->_modname}";
                $itemnames = component_gradeitems::get_itemname_mapping_for_component($component);

                if (count($itemnames) === 1) {
                    // Only one gradeitem in this activity.
                    // We use the completionusegrade field here.
                    $mform->addElement(
                        'checkbox',
                        'completionusegrade',
                        get_string('completionusegrade', 'completion'),
                        get_string('completionusegrade_desc', 'completion')
                    );
                    $mform->hideIf('completionusegrade', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
                    $mform->addHelpButton('completionusegrade', 'completionusegrade', 'completion');

                    // Complete if the user has reached the pass grade.
                    $mform->addElement(
                        'checkbox',
                        'completionpassgrade', null,
                        get_string('completionpassgrade_desc', 'completion')
                    );
                    $mform->hideIf('completionpassgrade', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
                    $mform->disabledIf('completionpassgrade', 'completionusegrade', 'notchecked');
                    $mform->addHelpButton('completionpassgrade', 'completionpassgrade', 'completion');

                    // The disabledIf logic differs between ratings and other grade items due to different field types.
                    if ($this->_features->rating) {
                        // If using the rating system, there is no grade unless ratings are enabled.
                        $mform->disabledIf('completionusegrade', 'assessed', 'eq', 0);
                        $mform->disabledIf('completionpassgrade', 'assessed', 'eq', 0);
                    } else {
                        // All other field types use the '$gradefieldname' field's modgrade_type.
                        $itemnumbers = array_keys($itemnames);
                        $itemnumber = array_shift($itemnumbers);
                        $gradefieldname = component_gradeitems::get_field_name_for_itemnumber($component, $itemnumber, 'grade');
                        $mform->disabledIf('completionusegrade', "{$gradefieldname}[modgrade_type]", 'eq', 'none');
                        $mform->disabledIf('completionpassgrade', "{$gradefieldname}[modgrade_type]", 'eq', 'none');
                    }
                } else if (count($itemnames) > 1) {
                    // There are multiple grade items in this activity.
                    // Show them all.
                    $options = [
                        '' => get_string('activitygradenotrequired', 'completion'),
                    ];
                    foreach ($itemnames as $itemnumber => $itemname) {
                        $options[$itemnumber] = get_string("grade_{$itemname}_name", $component);
                    }

                    $mform->addElement(
                        'select',
                        'completiongradeitemnumber',
                        get_string('completionusegrade', 'completion'),
                        $options
                    );
                    $mform->hideIf('completiongradeitemnumber', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);

                    // Complete if the user has reached the pass grade.
                    $mform->addElement(
                        'checkbox',
                        'completionpassgrade', null,
                        get_string('completionpassgrade_desc', 'completion')
                    );
                    $mform->hideIf('completionpassgrade', 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
                    $mform->disabledIf('completionpassgrade', 'completiongradeitemnumber', 'eq', '');
                    $mform->addHelpButton('completionpassgrade', 'completionpassgrade', 'completion');
                }
            }

            // Automatic completion according to module-specific rules
            $this->_customcompletionelements = $this->add_completion_rules();
            foreach ($this->_customcompletionelements as $element) {
                $mform->hideIf($element, 'completion', 'ne', COMPLETION_TRACKING_AUTOMATIC);
            }

            $gotcompletionoptions = $gotcompletionoptions ||
                count($this->_customcompletionelements)>0;

            // Automatic option only appears if possible
            if ($gotcompletionoptions) {
                $mform->getElement('completion')->addOption(
                    get_string('completion_automatic', 'completion'),
                    COMPLETION_TRACKING_AUTOMATIC);
            }

            // Completion expected at particular date? (For progress tracking)
            $mform->addElement('date_time_selector', 'completionexpected', get_string('completionexpected', 'completion'),
                array('optional' => true));
            $mform->addHelpButton('completionexpected', 'completionexpected', 'completion');
            $mform->hideIf('completionexpected', 'completion', 'eq', COMPLETION_TRACKING_NONE);
        }

        // Populate module tags.
        if (core_tag_tag::is_enabled('core', 'course_modules')) {
            $mform->addElement('header', 'tagshdr', get_string('tags', 'tag'));
            $mform->addElement('tags', 'tags', get_string('tags'), array('itemtype' => 'course_modules', 'component' => 'core'));
            if ($this->_cm) {
                $tags = core_tag_tag::get_item_tags_array('core', 'course_modules', $this->_cm->id);
                $mform->setDefault('tags', $tags);
            }
        }

        $this->standard_hidden_coursemodule_elements();

        if ($completion->is_enabled() && $canviewactivitycompletioncriterias) {
            $this->plugin_extend_coursemodule_standard_elements();
        }
    }

    /**
     * Each module which defines definition_after_data() must call this method using parent::definition_after_data();
     */
    function definition_after_data() {
        global $COURSE;

        $mform =& $this->_form;

        if ($id = $mform->getElementValue('update')) {
            $modulename = $mform->getElementValue('modulename');
            $instance   = $mform->getElementValue('instance');
            $component = "mod_{$modulename}";

            if ($this->_features->gradecat) {
                $hasgradeitems = false;
                $items = grade_item::fetch_all([
                    'itemtype' => 'mod',
                    'itemmodule' => $modulename,
                    'iteminstance' => $instance,
                    'courseid' => $COURSE->id,
                ]);

                $gradecategories = [];
                $removecategories = [];
                //will be no items if, for example, this activity supports ratings but rating aggregate type == no ratings
                if (!empty($items)) {
                    foreach ($items as $item) {
                        if (!empty($item->outcomeid)) {
                            $elname = 'outcome_'.$item->outcomeid;
                            if ($mform->elementExists($elname)) {
                                $mform->hardFreeze($elname); // prevent removing of existing outcomes
                            }
                        } else {
                            $hasgradeitems = true;
                        }
                    }

                    foreach ($items as $item) {
                        $gradecatfieldname = component_gradeitems::get_field_name_for_itemnumber(
                            $component,
                            $item->itemnumber,
                            'gradecat'
                        );

                        if (!isset($gradecategories[$gradecatfieldname])) {
                            $gradecategories[$gradecatfieldname] = $item->categoryid;
                        } else if ($gradecategories[$gradecatfieldname] != $item->categoryid) {
                            $removecategories[$gradecatfieldname] = true;
                        }
                    }
                }

                foreach ($removecategories as $toremove) {
                    if ($mform->elementExists($toremove)) {
                        $mform->removeElement($toremove);
                    }
                }
            }
        }

        if ($COURSE->groupmodeforce) {
            if ($mform->elementExists('groupmode')) {
                // The groupmode can not be changed if forced from course settings.
                $mform->hardFreeze('groupmode');
            }
        }

        // Don't disable/remove groupingid if it is currently set to something, otherwise you cannot turn it off at same
        // time as turning off other option (MDL-30764).
        if (empty($this->_cm) || !$this->_cm->groupingid) {
            if ($mform->elementExists('groupmode') && empty($COURSE->groupmodeforce)) {
                $mform->hideIf('groupingid', 'groupmode', 'eq', NOGROUPS);

            } else if (!$mform->elementExists('groupmode')) {
                // Groupings have no use without groupmode.
                if ($mform->elementExists('groupingid')) {
                    $mform->removeElement('groupingid');
                }
                // Nor does the group restrictions button.
                if ($mform->elementExists('restrictgroupbutton')) {
                    $mform->removeElement('restrictgroupbutton');
                }
            }
        }

        // Completion: If necessary, freeze fields
        $completion = new completion_info($COURSE);
        if ($completion->is_enabled() && $this->can_view_activity_completion_criterias()) {
            // If anybody has completed the activity, these options will be 'locked'
            $completedcount = empty($this->_cm)
                ? 0
                : $completion->count_user_data($this->_cm);

            $freeze = false;
            if (!$completedcount) {
                if ($mform->elementExists('unlockcompletion')) {
                    $mform->removeElement('unlockcompletion');
                }
                // Automatically set to unlocked (note: this is necessary
                // in order to make it recalculate completion once the option
                // is changed, maybe someone has completed it now)
                $mform->getElement('completionunlocked')->setValue(1);
            } else {
                // Has the element been unlocked, either by the button being pressed
                // in this request, or the field already being set from a previous one?
                if ($mform->exportValue('unlockcompletion') ||
                    $mform->exportValue('completionunlocked')) {
                    // Yes, add in warning text and set the hidden variable
                    $mform->insertElementBefore(
                        $mform->createElement('static', 'completedunlocked',
                            get_string('completedunlocked', 'completion'),
                            get_string('completedunlockedtext', 'completion')),
                        'unlockcompletion');
                    $mform->removeElement('unlockcompletion');
                    $mform->getElement('completionunlocked')->setValue(1);
                } else {
                    // No, add in the warning text with the count (now we know
                    // it) before the unlock button
                    $mform->insertElementBefore(
                        $mform->createElement('static', 'completedwarning',
                            get_string('completedwarning', 'completion'),
                            get_string('completedwarningtext', 'completion', $completedcount)),
                        'unlockcompletion');
                    $freeze = true;
                }
            }

            if ($freeze) {
                $mform->freeze('completion');
                if ($mform->elementExists('completionview')) {
                    $mform->freeze('completionview'); // don't use hardFreeze or checkbox value gets lost
                }
                if ($mform->elementExists('completionusegrade')) {
                    $mform->freeze('completionusegrade');
                }
                if ($mform->elementExists('completionpassgrade')) {
                    $mform->freeze('completionpassgrade');

                    // Has the completion pass grade completion criteria been set?
                    // If it has then we shouldn't change the gradepass field.
                    if ($mform->exportValue('completionpassgrade')) {
                        $mform->freeze('gradepass');
                    }
                }
                if ($mform->elementExists('completiongradeitemnumber')) {
                    $mform->freeze('completiongradeitemnumber');
                }
                $mform->freeze($this->_customcompletionelements);
            }
        }

        // Freeze admin defaults if required (and not different from default)
        $this->apply_admin_locked_flags();

        $this->plugin_extend_coursemodule_definition_after_data();
    }

    public function standard_grading_coursemodule_elements() {
        global $COURSE, $CFG, $DB;

        $isupdate = !empty($this->_cm);

        $sql = 'SELECT * FROM {portfoliobuilder} WHERE course = :course AND grade <> 0 LIMIT 1';

        $portfoliowithgrade = $DB->get_record_sql($sql, ['course' => $COURSE->id]);

        // 1. New portfolio.
        // 2. Course already have a portfolio with grade.
        // Action: Remove grade fields.
        if ($portfoliowithgrade && !$isupdate) {
            return;
        }

        // 1. Is update.
        // 2. Course already have a portfolio with grade.
        // 3. Portfolio with grade is not the current editing portfolio.
        // Action: Remove grade fields.
        if ($portfoliowithgrade && $isupdate && $portfoliowithgrade->id != $this->_cm->instance) {
            return;
        }

        if (isset($this->gradedorrated) && $this->gradedorrated !== 'graded') {
            return;
        }

        if (isset($this->_features->rating) && $this->_features->rating === true) {
            return;
        }

        $this->gradedorrated = 'graded';

        $itemnumber = 0;
        $component = "mod_{$this->_modname}";
        $gradefieldname = component_gradeitems::get_field_name_for_itemnumber($component, $itemnumber, 'grade');
        $gradecatfieldname = component_gradeitems::get_field_name_for_itemnumber($component, $itemnumber, 'gradecat');
        $gradepassfieldname = component_gradeitems::get_field_name_for_itemnumber($component, $itemnumber, 'gradepass');

        $mform =& $this->_form;
        $gradeoptions = array('isupdate' => $isupdate,
            'currentgrade' => false,
            'hasgrades' => false,
            'canrescale' => $this->_features->canrescale,
            'useratings' => $this->_features->rating);

        if ($this->_features->hasgrades) {
            if ($this->_features->gradecat) {
                $mform->addElement('header', 'modstandardgrade', get_string('gradenoun'));
            }

            //if supports grades and grades arent being handled via ratings
            if ($isupdate) {
                $gradeitem = grade_item::fetch(array('itemtype' => 'mod',
                    'itemmodule' => $this->_cm->modname,
                    'iteminstance' => $this->_cm->instance,
                    'itemnumber' => 0,
                    'courseid' => $COURSE->id));
                if ($gradeitem) {
                    $gradeoptions['currentgrade'] = $gradeitem->grademax;
                    $gradeoptions['currentgradetype'] = $gradeitem->gradetype;
                    $gradeoptions['currentscaleid'] = $gradeitem->scaleid;
                    $gradeoptions['hasgrades'] = $gradeitem->has_grades();
                }
            }
            $mform->addElement('modgrade', $gradefieldname, get_string('gradenoun'), $gradeoptions);
            $mform->addHelpButton($gradefieldname, 'modgrade', 'grades');
            $mform->setDefault($gradefieldname, $CFG->gradepointdefault);

            if ($this->_features->advancedgrading
                and !empty($this->current->_advancedgradingdata['methods'])
                and !empty($this->current->_advancedgradingdata['areas'])) {

                if (count($this->current->_advancedgradingdata['areas']) == 1) {
                    // if there is just one gradable area (most cases), display just the selector
                    // without its name to make UI simplier
                    $areadata = reset($this->current->_advancedgradingdata['areas']);
                    $areaname = key($this->current->_advancedgradingdata['areas']);
                    $mform->addElement('select', 'advancedgradingmethod_'.$areaname,
                        get_string('gradingmethod', 'core_grading'), $this->current->_advancedgradingdata['methods']);
                    $mform->addHelpButton('advancedgradingmethod_'.$areaname, 'gradingmethod', 'core_grading');
                    $mform->hideIf('advancedgradingmethod_'.$areaname, "{$gradefieldname}[modgrade_type]", 'eq', 'none');

                } else {
                    // the module defines multiple gradable areas, display a selector
                    // for each of them together with a name of the area
                    $areasgroup = array();
                    foreach ($this->current->_advancedgradingdata['areas'] as $areaname => $areadata) {
                        $areasgroup[] = $mform->createElement('select', 'advancedgradingmethod_'.$areaname,
                            $areadata['title'], $this->current->_advancedgradingdata['methods']);
                        $areasgroup[] = $mform->createElement('static', 'advancedgradingareaname_'.$areaname, '', $areadata['title']);
                    }
                    $mform->addGroup($areasgroup, 'advancedgradingmethodsgroup', get_string('gradingmethods', 'core_grading'),
                        array(' ', '<br />'), false);
                }
            }

            if ($this->_features->gradecat) {
                $mform->addElement('select', $gradecatfieldname,
                    get_string('gradecategoryonmodform', 'grades'),
                    grade_get_categories_menu($COURSE->id, $this->_outcomesused));
                $mform->addHelpButton($gradecatfieldname, 'gradecategoryonmodform', 'grades');
                $mform->hideIf($gradecatfieldname, "{$gradefieldname}[modgrade_type]", 'eq', 'none');
            }

            // Grade to pass.
            $mform->addElement('float', $gradepassfieldname, get_string($gradepassfieldname, 'grades'));
            $mform->addHelpButton($gradepassfieldname, $gradepassfieldname, 'grades');
            $mform->setDefault($gradepassfieldname, '');
            $mform->hideIf($gradepassfieldname, "{$gradefieldname}[modgrade_type]", 'eq', 'none');
        }
    }

    private function can_view_activity_completion_criterias() {
        global $DB, $COURSE;

        $isupdate = !empty($this->_cm);

        $sql = 'SELECT cm.*
                FROM {course_modules} cm
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = "portfoliobuilder"
                WHERE course = :course AND completion <> 0 LIMIT 1';

        $portfoliowithcompletion = $DB->get_record_sql($sql, ['course' => $COURSE->id]);

        // 1. New portfolio.
        // 2. Course already have a portfolio with completion.
        // Action: Remove completion fields.
        if (!$isupdate && $portfoliowithcompletion) {
            return false;
        }

        // 1. Is update.
        // 2. Course already have a portfolio with grade.
        // 3. Portfolio with grade is not the current editing portfolio.
        // Action: Remove completion fields.
        if ($isupdate && $portfoliowithcompletion && $portfoliowithcompletion->instance != $this->_cm->instance) {
            return false;
        }

        return true;
    }
}
