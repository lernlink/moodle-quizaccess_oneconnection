<?php
// This file is part of Moodle - http://moodle.org/
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
 * Rule that blocks attempt to open same quiz attempt in other session
 *
 * @package    quizaccess_onesession
 * @copyright  2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;
use quizaccess_onesession\event\attempt_blocked;

/**
 * Rule class.
 *
 * @package    quizaccess_onesession
 * @copyright  2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_onesession extends access_rule_base
{

    /**
     * Return an appropriately configured instance of this rule, if it is applicable
     * to the given quiz, otherwise return null.
     * @param quiz_settings $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from
     *      time limits by the mod/quiz:ignoretimelimits capability.
     * @return self|null the rule, if applicable, else null.
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits)
    {
        if (!empty($quizobj->get_quiz()->onesessionenabled)) {
            return new self($quizobj, $timenow);
        } else {
            return null;
        }
    }

    /**
     * Returns session hash based on moodle session, IP and browser info
     *
     * @return string
     */
    private function get_session_hash()
    {
        $sessionstring = $this->get_session_string();
        $secret = random_bytes(16);
        return bin2hex($secret) . '|' . hash_hmac('sha256', $sessionstring, $secret);
    }

    /**
     * Returns session hash based on moodle session, IP and browser info
     *
     * @return string
     */
    private function get_session_string()
    {
        $sessionstring = [];
        $sessionstring[] = sesskey();

        $whitelist = get_config('quizaccess_onesession', 'whitelist');
        $ipaddress = getremoteaddr();
        if (!address_in_subnet($ipaddress, $whitelist)) {
            $sessionstring[] = $ipaddress;
        }

        $sessionstring[] = $_SERVER['HTTP_USER_AGENT'];

        return implode('', $sessionstring);
    }

    /**
     * Returns session hash based on moodle session, IP and browser info
     *
     * @param string $secretandhash
     * @return bool
     */
    private function validate_session_hash($secretandhash)
    {
        [$secrethex, $storedhash] = explode('|', $secretandhash);
        $secret = hex2bin($secrethex);
        $currenthash = hash_hmac('sha256', $this->get_session_string(), $secret);
        return hash_equals($storedhash, $currenthash);
    }

    /**
     * Is check before attempt start is required.
     *
     * @param int|null $attemptid the id of the current attempt, if there is one,
     *      otherwise null.
     * @return bool whether a check is required before the user starts/continues
     *      their attempt.
     */
    public function is_preflight_check_required($attemptid)
    {
        global $DB;

        if (is_null($attemptid)) {
            return false;
        }
        $attemptobj = quiz_attempt::create($attemptid);
        if ($attemptobj->is_preview()) {
            return false;
        }
        $session = $DB->get_record('quizaccess_onesession_sess', ['attemptid' => $attemptid]);
        if (empty($session)) {
            $session = new stdClass();
            $session->quizid = $this->quiz->id;
            $session->attemptid = $attemptid;
            $session->sessionhash = $this->get_session_hash();
            $DB->insert_record('quizaccess_onesession_sess', $session);
            return false;
        } else if ($this->validate_session_hash($session->sessionhash)) {
            return false;
        } else {
            $params = [
                'objectid' => $attemptobj->get_attemptid(),
                'relateduserid' => $attemptobj->get_userid(),
                'courseid' => $attemptobj->get_courseid(),
                'context' => $attemptobj->get_quizobj()->get_context(),
                'other' => [
                    'quizid' => $attemptobj->get_quizid(),
                ],
            ];
            $event = attempt_blocked::create($params);
            $event->trigger();
            throw new moodle_exception('anothersession', 'quizaccess_onesession', $this->quizobj->view_url());
        }
    }

    /**
     * Information, such as might be shown on the quiz view page, relating to this restriction.
     * @return mixed a message, or array of messages, explaining the restriction
     */
    public function description()
    {
        return get_string('studentinfo', 'quizaccess_onesession');
    }

    /**
     * Sets up the attempt (review or summary) page.
     * Old unlock block is deprecated and removed in favor of the new report page.
     *
     * @param moodle_page $page the page object to initialise.
     */
    public function setup_attempt_page($page)
    {
        // This functionality has been moved to the "Allow connections" report page.
        return;
    }

    /**
     * Add any fields that this rule requires to the quiz settings form.
     * @param mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param MoodleQuickForm $mform the wrapped MoodleQuickForm.
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform)
    {
        if (!has_capability('quizaccess/onesession:editenabled', $quizform->get_context())) {
            return;
        }

        $pluginconfig = get_config('quizaccess_onesession');

        $mform->addElement('checkbox', 'onesessionenabled', get_string('onesession', 'quizaccess_onesession'));
        $mform->setDefault('onesessionenabled', $pluginconfig->defaultenabled);
        $mform->setAdvanced('onesessionenabled', $pluginconfig->defaultenabled_adv);
        $mform->addHelpButton('onesessionenabled', 'onesession', 'quizaccess_onesession');
    }

    /**
     * Save any submitted settings when the quiz settings form is submitted.
     * @param object $quiz the data from the quiz form.
     */
    public static function save_settings($quiz)
    {
        global $DB;

        // If the checkbox was not displayed due to capabilities, 'onesessionenabled' won't be in the form data.
        if (!property_exists($quiz, 'onesessionenabled')) {
            return;
        }

        if (empty($quiz->onesessionenabled)) {
            $DB->delete_records('quizaccess_onesession', ['quizid' => $quiz->id]);
            $DB->delete_records('quizaccess_onesession_sess', ['quizid' => $quiz->id]);
        } else {
            if (!$DB->record_exists('quizaccess_onesession', ['quizid' => $quiz->id])) {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                $record->enabled = 1;
                $DB->insert_record('quizaccess_onesession', $record);
            }
        }
    }

    /**
     * Delete any rule-specific settings when the quiz is deleted.
     * @param object $quiz the data from the database.
     */
    public static function delete_settings($quiz)
    {
        global $DB;

        $DB->delete_records('quizaccess_onesession', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_onesession_sess', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_onesession_log', ['quizid' => $quiz->id]);
    }

    /**
     * Return the bits of SQL needed to load all the settings from all the access
     * plugins in one DB query.
     * @param int $quizid the id of the quiz we are loading settings for.
     * @return array with three elements
     */
    public static function get_settings_sql($quizid)
    {
        return [
            'quizaccess_onesession.enabled onesessionenabled',
            'LEFT JOIN {quizaccess_onesession} quizaccess_onesession ON quizaccess_onesession.quizid = quiz.id',
            []
        ];
    }
}