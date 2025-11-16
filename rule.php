<?php

use function PHPUnit\Framework\throwException;
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
 * Quiz access rule that binds a quiz attempt to a single browser session / device.
 *
 * @package     quizaccess_oneconnection
 * @copyright   2016 Vadim Dvorovenko
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (file_exists($CFG->dirroot . '/mod/quiz/locallib.php')) {
    require_once($CFG->dirroot . '/mod/quiz/locallib.php'); // For quiz_attempt.
} else if (file_exists($CFG->dirroot . '/mod/quiz/classes/quiz_attempt.php')) {
    require_once($CFG->dirroot . '/mod/quiz/classes/quiz_attempt.php'); // For quiz_attempt.
}

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;
use quizaccess_oneconnection\event\attempt_blocked;
use mod_quiz\quiz_attempt;

/**
 * Access rule that disallows continuing the same attempt from a different session/device.
 *
 * The plugin works by:
 * - Creating a per-attempt session record the first time the attempt is accessed.
 * - Storing a signed "session fingerprint" that describes the current client.
 * - Validating the stored fingerprint on every subsequent access.
 * - Blocking the attempt (via the preflight form) when the fingerprint changes.
 */
class quizaccess_oneconnection extends access_rule_base
{

    /**
     * Create an instance of this rule if applicable to the given quiz.
     *
     * @param quiz_settings $quizobj Information about the quiz.
     * @param int $timenow Current timestamp.
     * @param bool $canignoretimelimits Whether the current user is exempt from time limits (ignored).
     * @return self|null Returns a rule instance if enabled, otherwise null.
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits)
    {
        if (!empty($quizobj->get_quiz()->oneconnectionenabled)) {
            return new self($quizobj, $timenow);
        }
        return null;
    }

    /**
     * Build a per-attempt session hash (secret + HMAC of current session string).
     *
     * The format is: "<hex-secret>|<hmac>", where:
     * - the secret is a random binary string (hex-encoded);
     * - the HMAC is calculated over the current session fingerprint.
     *
     * @return string Session hash in the format "secret|hmac".
     * @throws Exception If random_bytes() fails.
     */
    private function get_session_hash(): string
    {
        $sessionstring = $this->get_session_string();
        $secret = random_bytes(16);
        return bin2hex($secret) . '|' . hash_hmac('sha256', $sessionstring, $secret);
    }

    /**
     * Build a stable string describing the current browser session and device.
     *
     * Components:
     * 1. The current Moodle session key.
     * 2. The client IP address (unless it belongs to a whitelisted subnet).
     * 3. The HTTP user agent.
     *
     * @return string Fingerprint string for the current request.
     */
    private function get_session_string(): string
    {
        $sessionstring = [];

        // Moodle session.
        $sessionstring[] = sesskey();

        // IP (unless whitelisted).
        $whitelist = (string) get_config('quizaccess_oneconnection', 'whitelist');
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
     * Validate a stored "secret|hash" string against the current request.
     *
     * @param string $secretandhash The value stored in DB.
     * @return bool True if the current request matches the stored fingerprint, false otherwise.
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
     * Check whether the given attempt is currently blocked because it is tied to another session.
     *
     * @param int $attemptid Quiz attempt ID.
     * @return bool True if the attempt is blocked, false if it is either unbound or valid for this session.
     */
    private function is_attempt_blocked(int $attemptid): bool
    {
        global $DB, $PAGE;

        if (!$attemptid) {
            return false;
        }

        $session = $DB->get_record('quizaccess_oneconnection_sess', ['attemptid' => $attemptid]);
        if (empty($session)) {
            return false;
        }

        if ($this->validate_session_hash($session->sessionhash)) {
            return false;
        }

        throw new moodle_exception('anothersession', 'quizaccess_oneconnection', $PAGE->url);
    }

    /**
     * Whether a preflight check is required for this attempt.
     *
     * This method is called before opening the attempt. If we detect that the attempt
     * was started elsewhere (i.e. another device/session), we will show the preflight
     * form that only contains a static error message.
     *
     * @param int|null $attemptid The current attempt ID, if any.
     * @return bool True if the preflight form must be shown.
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

        $session = $DB->get_record('quizaccess_oneconnection_sess', ['attemptid' => $attemptid]);
        if (empty($session)) {
            // First access to this attempt – create a session binding.
            $session = new stdClass();
            $session->quizid = $this->quiz->id;
            $session->attemptid = $attemptid;
            $session->sessionhash = $this->get_session_hash();
            $DB->insert_record('quizaccess_oneconnection_sess', $session);
            return false;
        }

        if ($this->validate_session_hash($session->sessionhash)) {
            return false;
        }

        // Log the blocked attempt for teachers / invigilators.
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
     * Add fields to the preflight form when we have to block the attempt.
     *
     * @param \mod_quiz\form\preflight_check_form|\mod_quiz\preflight_check_form $quizform Current preflight form wrapper.
     * @param \MoodleQuickForm $mform The actual form.
     * @param int|null $attemptid Current attempt ID.
     * @return void
     */
    public function add_preflight_check_form_fields($quizform, $mform, $attemptid)
    {
        if (!$attemptid || !$this->is_attempt_blocked((int) $attemptid)) {
            return;
        }

        // Human-readable message for the student.
        $mform->addElement(
            'static',
            'oneconnectionblocked',
            '',
            get_string('anothersession', 'quizaccess_oneconnection')
        );

        // Hide submit/cancel buttons to prevent continuing.
        $mform->addElement(
            'static',
            'oneconnectioncss',
            '',
            '<style>
                #fgroup_id_buttonar,
                #id_submitbutton,
                #id_cancel {
                    display: none !important;
                }
            </style>'
        );

        // Provide teachers/invigilators with a link to unblock.
        $context = $this->quizobj->get_context();
        if (has_capability('quizaccess/oneconnection:allowchange', $context)) {
            $url = new \moodle_url(
                '/mod/quiz/accessrule/oneconnection/allowconnections.php',
                ['id' => $this->quizobj->get_cmid()]
            );
            $link = \html_writer::link($url, get_string('allowconnections', 'quizaccess_oneconnection'));
            $mform->addElement('static', 'oneconnectionmanage', '', $link);
        }

        // Extra safety for any themes that still display the buttons.
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

        $mform->addElement('static', 'oneconnectionjs', '', $js);
    }

    /**
     * Validate the preflight form submission.
     *
     * If the attempt is still blocked for the current session, we return an error.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @param array $errors Existing form errors.
     * @param int|null $attemptid Current attempt ID.
     * @return array Updated list of errors.
     */
    public function validate_preflight_check($data, $files, $errors, $attemptid): array
    {
        if ($attemptid && $this->is_attempt_blocked((int) $attemptid)) {
            $errors['oneconnectionblocked'] = get_string('anothersession', 'quizaccess_oneconnection');
        }
        return $errors;
    }

    /**
     * Return null so Moodle does NOT show a generic exception screen.
     *
     * We handle blocking via the preflight form instead.
     *
     * @return string|null Always null.
     */
    public function prevent_access()
    {
        return null;
    }

    /**
     * Information shown on the quiz view page.
     *
     * @return array|string One or more messages to display.
     */
    public function description()
    {
        $messages = [get_string('studentinfo', 'quizaccess_oneconnection')];

        if (!empty($this->quiz->oneconnectionenabled)) {
            $context = $this->quizobj->get_context();
            if (has_capability('quizaccess/oneconnection:allowchange', $context)) {
                $url = new \moodle_url(
                    '/mod/quiz/accessrule/oneconnection/allowconnections.php',
                    ['id' => $this->quizobj->get_cmid()]
                );
                $link = \html_writer::link(
                    $url,
                    get_string('allowconnections', 'quizaccess_oneconnection'),
                    ['class' => 'btn btn-secondary mt-2']
                );
                $messages[] = $link;
            }
        }

        return $messages;
    }

    /**
     * Setup attempt page – intentionally left empty.
     *
     * @param moodle_page $page Current page.
     * @return void
     */
    public function setup_attempt_page($page)
    {
        return;
    }

    /**
     * Add per-quiz settings to the quiz settings form.
     *
     * @param mod_quiz_mod_form $quizform The quiz form.
     * @param MoodleQuickForm $mform The MoodleQuickForm instance.
     * @return void
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform)
    {
        $context = $quizform->get_context();
        $canedit = has_capability('quizaccess/oneconnection:editenabled', $context);

        $pluginconfig = get_config('quizaccess_oneconnection') ?: (object) [];
        $defaultenabled = isset($pluginconfig->defaultenabled) ? (int) $pluginconfig->defaultenabled : 0;

        // Always show the option to teachers, but only allow changing it when permitted.
        $mform->addElement('checkbox', 'oneconnectionenabled', get_string('oneconnection', 'quizaccess_oneconnection'));
        $mform->setDefault('oneconnectionenabled', $defaultenabled);
        $mform->addHelpButton('oneconnectionenabled', 'oneconnection', 'quizaccess_oneconnection');

        if (!$canedit) {
            // Visible but read-only if the role doesn't have permission to change it.
            $mform->freeze('oneconnectionenabled');
        }
    }

    /**
     * Save the per-quiz setting when the quiz is saved.
     *
     * @param object $quiz Quiz data object.
     * @return void
     */
    public static function save_settings($quiz)
    {
        global $DB;

        // Sometimes this is called in contexts where no CM/context is available (restore, CLI).
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, IGNORE_MISSING);
        if ($cm) {
            $context = \context_module::instance($cm->id, IGNORE_MISSING);
            if ($context && !has_capability('quizaccess/oneconnection:editenabled', $context)) {
                // User is not allowed to change this plugin's quiz-level flag.
                return;
            }
        }

        if (!property_exists($quiz, 'oneconnectionenabled')) {
            return;
        }

        if (empty($quiz->oneconnectionenabled)) {
            // Rule disabled – remove all related records.
            $DB->delete_records('quizaccess_oneconnection', ['quizid' => $quiz->id]);
            $DB->delete_records('quizaccess_oneconnection_sess', ['quizid' => $quiz->id]);
        } else {
            // Rule enabled – ensure there is a record.
            if (!$DB->record_exists('quizaccess_oneconnection', ['quizid' => $quiz->id])) {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                $record->enabled = 1;
                $DB->insert_record('quizaccess_oneconnection', $record);
            }
        }
    }

    /**
     * Delete all data when the quiz itself is deleted.
     *
     * @param object $quiz Quiz data object.
     * @return void
     */
    public static function delete_settings($quiz)
    {
        global $DB;

        $DB->delete_records('quizaccess_oneconnection', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_oneconnection_sess', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_oneconnection_log', ['quizid' => $quiz->id]);
    }

    /**
     * Return SQL needed to load the per-quiz setting.
     *
     * @param int $quizid Quiz ID.
     * @return array Array with select, join, params.
     */
    public static function get_settings_sql($quizid): array
    {
        return [
            'quizaccess_oneconnection.enabled oneconnectionenabled',
            'LEFT JOIN {quizaccess_oneconnection} quizaccess_oneconnection ON quizaccess_oneconnection.quizid = quiz.id',
            []
        ];
    }
}
