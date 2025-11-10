<?php
// This file is part of Moodle - http://moodle.org/.
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Filter / settings form for the allowconnections page.
 *
 * @package     quizaccess_oneconnection
 * @category    form
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_oneconnection\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form used on the "Allow connection changes" report-like page.
 */
class allowconnections_settings_form extends \moodleform
{

    /**
     * Build the form definition.
     *
     * Custom data:
     *  - cmid
     *  - attemptsfrom
     *  - attemptstate (array of states to preselect)
     *  - pagesize
     *
     * @return void
     */
    public function definition(): void
    {
        $mform = $this->_form;

        // Custom data from the page.
        $cmid = $this->_customdata['cmid'] ?? 0;
        $attemptsfrom = $this->_customdata['attemptsfrom'] ?? 'enrolledattempts';
        $attemptstate = $this->_customdata['attemptstate'] ?? [];
        $pagesize = $this->_customdata['pagesize'] ?? 30;

        // Keep cmid on POST.
        $mform->addElement('hidden', 'id', $cmid);
        $mform->setType('id', PARAM_INT);

        // Section 1 – what to include.
        $mform->addElement('header', 'preferencespage', get_string('whattoincludeinreport', 'quizaccess_oneconnection'));
        $mform->setExpanded('preferencespage', true);

        $attemptsopts = [
            'enrolledattempts' => get_string('attemptsfrom_enrolledattempts', 'quizaccess_oneconnection'),
            'enrollednoattempts' => get_string('attemptsfrom_enrollednoattempts', 'quizaccess_oneconnection'),
            'enrolledall' => get_string('attemptsfrom_enrolledall', 'quizaccess_oneconnection'),
            'allattempts' => get_string('attemptsfrom_allattempts', 'quizaccess_oneconnection'),
        ];
        $mform->addElement('select', 'attemptsfrom', get_string('attemptsfrom', 'quizaccess_oneconnection'), $attemptsopts);
        $mform->setDefault('attemptsfrom', $attemptsfrom);

        // Attempt state group.
        $stategroup = [];
        $states = [
            'notstarted' => 'state_notstarted',
            'inprogress' => 'state_inprogress',
            'overdue' => 'state_overdue',
            'submitted' => 'state_submitted',
            'finished' => 'state_finished',
            'abandoned' => 'state_abandoned',
        ];
        foreach ($states as $key => $stringid) {
            $el = $mform->createElement(
                'advcheckbox',
                "attemptstate[$key]",
                '',
                get_string($stringid, 'quizaccess_oneconnection'),
                ['group' => 1],
                [0, 1]
            );
            $stategroup[] = $el;
            $mform->setDefault("attemptstate[$key]", empty($attemptstate[$key]) ? 0 : 1);
        }
        $mform->addGroup(
            $stategroup,
            'stateoptions',
            get_string('attemptsthat', 'quizaccess_oneconnection'),
            [' '],
            false
        );

        // Section 2 – display options.
        $mform->addElement('header', 'preferencesuser', get_string('displayoptions', 'quizaccess_oneconnection'));
        $mform->setExpanded('preferencesuser', true);

        $mform->addElement('text', 'pagesize', get_string('pagesize', 'quizaccess_oneconnection'));
        $mform->setType('pagesize', PARAM_INT);
        $mform->setDefault('pagesize', $pagesize);

        // Button.
        $this->add_action_buttons(false, get_string('showreport', 'quizaccess_oneconnection'));
    }
}
