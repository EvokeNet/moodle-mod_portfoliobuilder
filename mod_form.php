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
 * The main mod_portfoliobuilder configuration form.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 Willian Mano <willianmanoaraujo@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

        if ($this->gradedorrated && $this->gradedorrated !== 'graded') {
            return;
        }

        if ($this->_features->rating) {
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
}
