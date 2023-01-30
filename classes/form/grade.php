<?php

namespace mod_portfoliobuilder\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir. '/formslib.php');

use mod_portfoliobuilder\util\grade as gradeutil;
use mod_portfoliobuilder\util\portfolio;

/**
 * Grade form.
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */
class grade extends \moodleform {

    private $userid = null;

    public function __construct($formdata, $customdata = null) {
        parent::__construct(null, $customdata, 'post',  '', ['class' => 'portfoliobuilder-grade-form'], true, $formdata);

        $this->set_display_vertical();
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        if (isset($this->_customdata['userid'])) {
            $this->userid = $this->_customdata['userid'];

            $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
            $mform->setType('userid', PARAM_INT);
        }

        if (isset($this->_customdata['courseid'])) {
            $mform->addElement('hidden', 'courseid', $this->_customdata['courseid']);
            $mform->setType('courseid', PARAM_INT);
        }

        $gradeutil = new gradeutil();
        $portfolio = $gradeutil->get_portfolio_with_evaluation($this->_customdata['courseid']);

        if ($portfolio) {
            $this->fill_form_with_grade_fields($mform, $portfolio);
        }

        $this->add_action_buttons(true);
    }

    private function fill_form_with_grade_fields($mform, $portfolio) {
        $usergradegrade = $this->get_user_grade($portfolio, $this->userid);

        if ($portfolio->grade > 0) {
            $mform->addElement('text', 'grade', get_string('grade', 'mod_portfoliobuilder'));
            $mform->addHelpButton('grade', 'grade', 'mod_portfoliobuilder');
            $mform->addRule('grade', get_string('onlynumbers', 'mod_portfoliobuilder'), 'numeric', null, 'client');
            $mform->addRule('grade', get_string('required'), 'required', null, 'client');
            $mform->setType('grade', PARAM_RAW);

            if ($usergradegrade) {
                $mform->setDefault('grade', $usergradegrade);
            }
        }

        if ($portfolio->grade < 0) {
            $grademenu = array(-1 => get_string("nograde")) + make_grades_menu($portfolio->grade);

            $mform->addElement('select', 'grade', get_string('gradenoun') . ':', $grademenu);
            $mform->setType('grade', PARAM_INT);
            $mform->addRule('grade', get_string('required'), 'required', null, 'client');

            if ($usergradegrade) {
                $mform->setDefault('grade', $usergradegrade);
            }
        }
    }

    private function get_user_grade($portfolio, $userid) {
        $gradeutil = new gradeutil();
        $usergrade = $gradeutil->get_user_grade($portfolio, $userid);

        return $this->process_grade($portfolio->grade, $usergrade);
    }

    private function process_grade($portfoliograde, $grade = null) {
        // Grade in decimals.
        if ($grade && $portfoliograde > 0) {
            return number_format($grade, 1, '.', '');
        }

        // Grade in scale.
        if ($grade && $portfoliograde < 0) {
            return (int) $grade;
        }

        return false;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['grade'])) {
            $errors['grade'] = get_string('required');
        }

        return $errors;
    }
}
