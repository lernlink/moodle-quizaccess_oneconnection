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
 * Rule that blocks attempt to open same quiz attempt in other session.
 *
 * @package    quizaccess_onesession
 * @copyright  2016 Vadim Dvorovenko
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php'); // For quiz_attempt.

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;
use quizaccess_onesession\event\attempt_blocked;

/**
 * Rule class.
 *
 * @package    quizaccess_onesession
 */
class quizaccess_onesession extends access_rule_base
{
    /**
     * Return an appropriately configured instance of this rule, if it is applicable
     * to the given quiz, otherwise return null.
     *
     * @param quiz_settings $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from time limits.
     * @return self|null
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits)
    {
        if (!empty($quizobj->get_quiz()->onesessionenabled)) {
            return new self($quizobj, $timenow);
        }
        return null;
    }

    /**
     * Build a per-attempt session hash (secret + HMAC of current session string).
     *
     * @return string
     */
    private function get_session_hash(): string
    {
        $sessionstring = $this->get_session_string();
        $secret = random_bytes(16);
        return bin2hex($secret) . '|' . hash_hmac('sha256', $sessionstring, $secret);
    }

    /**
     * Build a stable string describing the current session/device.
     *
     * @return string
     */
    private function get_session_string(): string
    {
        $sessionstring = [];
        // Moodle session.
        $sessionstring[] = sesskey();

        // IP (unless whitelisted).
        $whitelist = (string) get_config('quizaccess_onesession', 'whitelist');
        $ipaddress = getremoteaddr();
        if ($ipaddress) {
            $inwhitelist = false;
            if ($whitelist !== '') {
                $inwhitelist = address_in_subnet($ipaddress, $whitelist);
            }
            if (!$inwhitelist) {
                $sessionstring[] = $ipaddress;
            }
        }

        // User agent.
        $sessionstring[] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return implode('', $sessionstring);
    }

    /**
     * Validate a stored "secret|hash" against the current request.
     *
     * @param string $secretandhash
     * @return bool
     */
    private function validate_session_hash($secretandhash): bool
    {
        if (empty($secretandhash) || strpos($secretandhash, '|') === false) {
            return false;
        }

        [$secrethex, $storedhash] = explode('|', $secretandhash, 2);
        if ($secrethex === '' || $storedhash === '') {
            return false;
        }

        $secret = @hex2bin($secrethex);
        if ($secret === false) {
            return false;
        }

        $currenthash = hash_hmac('sha256', $this->get_session_string(), $secret);
        return hash_equals($storedhash, $currenthash);
    }

    /**
     * Check if the attempt is blocked (i.e. already tied to another session/device).
     *
     * @param int $attemptid
     * @return bool
     */
    private function is_attempt_blocked(int $attemptid): bool
    {
        global $DB;

        if (!$attemptid) {
            return false;
        }

        $session = $DB->get_record('quizaccess_onesession_sess', ['attemptid' => $attemptid]);
        if (empty($session)) {
            return false;
        }

        if ($this->validate_session_hash($session->sessionhash)) {
            return false;
        }

        return true;
    }

    /**
     * Is check before attempt start is required.
     *
     * @param int|null $attemptid the id of the current attempt, if there is one.
     * @return bool
     */
    public function is_preflight_check_required($attemptid): bool
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
        }

        if ($this->validate_session_hash($session->sessionhash)) {
            return false;
        }

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

        return true;
    }

    /**
     * Add fields to the preflight form.
     *
     * @param \mod_quiz\form\preflight_check_form|\mod_quiz\preflight_check_form $quizform
     * @param \MoodleQuickForm $mform
     * @param int|null $attemptid
     * @return void
     */
    public function add_preflight_check_form_fields($quizform, $mform, $attemptid)
    {
        if (!$attemptid || !$this->is_attempt_blocked((int) $attemptid)) {
            return;
        }

        $mform->addElement(
            'static',
            'onesessionblocked',
            '',
            get_string('anothersession', 'quizaccess_onesession')
        );

        $mform->addElement(
            'static',
            'onesessioncss',
            '',
            '<style>
                #fgroup_id_buttonar,
                #id_submitbutton,
                #id_cancel {
                    display: none !important;
                }
            </style>'
        );

        $context = $this->quizobj->get_context();
        if (has_capability('quizaccess/onesession:allowchange', $context)) {
            $url = new \moodle_url(
                '/mod/quiz/accessrule/onesession/allowconnections.php',
                ['id' => $this->quizobj->get_cmid()]
            );
            $link = \html_writer::link($url, get_string('allowconnections', 'quizaccess_onesession'));
            $mform->addElement('static', 'onesessionmanage', '', $link);
        }

        $js = <<<JS
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var g = document.getElementById('fgroup_id_buttonar');
                if (g) { g.style.display = 'none'; }
                var s = document.getElementById('id_submitbutton');
                if (s) { s.style.display = 'none'; }
                var c = document.getElementById('id_cancel');
                if (c) { c.style.display = 'none'; }
            });
            </script>
        JS;

        $mform->addElement('static', 'onesessionjs', '', $js);
    }

    /**
     * Validate preflight submission.
     *
     * @param array $data
     * @param array $files
     * @param array $errors
     * @param int|null $attemptid
     * @return array
     */
    public function validate_preflight_check($data, $files, $errors, $attemptid): array
    {
        if ($attemptid && $this->is_attempt_blocked((int) $attemptid)) {
            $errors['onesessionblocked'] = get_string('anothersession', 'quizaccess_onesession');
        }
        return $errors;
    }

    /**
     * Return null so Moodle does NOT throw an exception screen.
     *
     * @return string|null
     */
    public function prevent_access()
    {
        return null;
    }

    /**
     * Information shown on quiz view page.
     *
     * @return array|string
     */
    public function description()
    {
        $messages = [get_string('studentinfo', 'quizaccess_onesession')];

        if (!empty($this->quiz->onesessionenabled)) {
            $context = $this->quizobj->get_context();
            if (has_capability('quizaccess/onesession:allowchange', $context)) {
                $url = new \moodle_url(
                    '/mod/quiz/accessrule/onesession/allowconnections.php',
                    ['id' => $this->quizobj->get_cmid()]
                );
                $link = \html_writer::link(
                    $url,
                    get_string('allowconnections', 'quizaccess_onesession'),
                    ['class' => 'btn btn-secondary mt-2']
                );
                $messages[] = $link;
            }
        }

        return $messages;
    }

    /**
     * Setup attempt page – no-op.
     *
     * @param moodle_page $page
     */
    public function setup_attempt_page($page)
    {
        return;
    }

    /**
     * Add fields to quiz settings form.
     *
     * @param mod_quiz_mod_form $quizform
     * @param MoodleQuickForm $mform
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform)
    {
        if (!has_capability('quizaccess/onesession:editenabled', $quizform->get_context())) {
            return;
        }

        $pluginconfig = get_config('quizaccess_onesession') ?: (object) [];

        $defaultenabled = isset($pluginconfig->defaultenabled) ? (int) $pluginconfig->defaultenabled : 0;

        $mform->addElement('checkbox', 'onesessionenabled', get_string('onesession', 'quizaccess_onesession'));
        $mform->setDefault('onesessionenabled', $defaultenabled);

        $mform->addHelpButton('onesessionenabled', 'onesession', 'quizaccess_onesession');
    }

    /**
     * Save settings.
     *
     * @param object $quiz
     */
    public static function save_settings($quiz)
    {
        global $DB;

        // Sometimes this is called in contexts where no CM/context is available (restore, CLI).
        // In that case we fall back to the old behavior.
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, IGNORE_MISSING);
        if ($cm) {
            $context = \context_module::instance($cm->id, IGNORE_MISSING);
            if ($context && !has_capability('quizaccess/onesession:editenabled', $context)) {
                // User is not allowed to change this plugin's quiz-level flag.
                return;
            }
        }

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
     * Delete settings when quiz is deleted.
     *
     * @param object $quiz
     */
    public static function delete_settings($quiz)
    {
        global $DB;

        $DB->delete_records('quizaccess_onesession', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_onesession_sess', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_onesession_log', ['quizid' => $quiz->id]);
    }

    /**
     * Return SQL needed to load settings.
     *
     * @param int $quizid
     * @return array
     */
    public static function get_settings_sql($quizid): array
    {
        return [
            'quizaccess_onesession.enabled onesessionenabled',
            'LEFT JOIN {quizaccess_onesession} quizaccess_onesession ON quizaccess_onesession.quizid = quiz.id',
            []
        ];
    }
}
