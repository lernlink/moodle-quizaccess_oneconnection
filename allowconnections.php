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
 * Page for managing and allowing connection changes for quiz attempts.
 *
 * @package    quizaccess_onesession
 * @copyright  2024 onwards, Adrian
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use mod_quiz\quiz_attempt;

require_once('../../../../config.php');

$cmid = required_param('id', PARAM_INT);

// Standard Moodle page setup.
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('quizaccess/onesession:allowchange', $context);

// Page setup.
$PAGE->set_url('/mod/quiz/accessrule/onesession/allowconnections.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($quiz->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

/**
 * Get the human-readable name for a quiz attempt state.
 *
 * This function correctly maps the state constants to their language strings.
 *
 * @param string $state The state constant from the quiz_attempt object (e.g., 'inprogress').
 * @return string The localized state name.
 */
function quizaccess_onesession_get_attempt_state_string(string $state): string
{
    $stringkey = 'state' . $state;
    // Check if a specific string like 'stateinprogress' exists.
    if (get_string_manager()->string_exists($stringkey, 'quiz')) {
        return get_string($stringkey, 'quiz');
    }
    // Fallback for any other or future states.
    return $state;
}

/**
 * Unlocks a specific attempt and logs the action.
 *
 * @param int $attemptid The attempt ID to unlock.
 * @param int $quizid The quiz ID for logging purposes.
 * @return void
 */
function quizaccess_onesession_unlock_and_log(int $attemptid, int $quizid): void
{
    global $DB, $USER;
    $DB->delete_records('quizaccess_onesession_sess', ['attemptid' => $attemptid]);
    $log = new stdClass();
    $log->quizid = $quizid;
    $log->attemptid = $attemptid;
    $log->unlockedby = $USER->id;
    $log->timeunlocked = time();
    $DB->insert_record('quizaccess_onesession_log', $log);
}

// --- Action Handling ---

$unlockid = optional_param('unlock', 0, PARAM_INT);
if ($unlockid && confirm_sesskey()) {
    quizaccess_onesession_unlock_and_log($unlockid, $quiz->id);
    redirect($PAGE->url);
}

if (($data = data_submitted()) && !empty($data->unlockselected) && confirm_sesskey()) {
    $attemptids = optional_param_array('attemptid', [], PARAM_INT);
    if (!empty($attemptids)) {
        foreach ($attemptids as $attemptid) {
            quizaccess_onesession_unlock_and_log($attemptid, $quiz->id);
        }
        notification::add(get_string('unlocksuccess', 'quizaccess_onesession', count($attemptids)), 'success');
    }
}

// --- Page Output ---

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('allowconnections', 'quizaccess_onesession'));

$table = new html_table();
$table->head = [
    '',
    get_string('user'),
    get_string('email'),
    get_string('statusattempt', 'quizaccess_onesession'),
    get_string('changeinconnection', 'quizaccess_onesession'),
    get_string('changeallowed', 'quizaccess_onesession'),
];

$sql = "SELECT qa.id, qa.userid, qa.attempt, qa.state, qa.quiz,
               u.firstname, u.lastname, u.email, u.picture, u.imagealt,
               qoss.id AS locked,
               qol.timeunlocked, ul.firstname AS teacher_firstname, ul.lastname AS teacher_lastname
          FROM {quiz_attempts} qa
          JOIN {user} u ON u.id = qa.userid
     LEFT JOIN {quizaccess_onesession_sess} qoss ON qoss.attemptid = qa.id
     LEFT JOIN {quizaccess_onesession_log} qol ON qol.attemptid = qa.id
     LEFT JOIN {user} ul ON ul.id = qol.unlockedby
         WHERE qa.quiz = :quizid
      ORDER BY u.lastname, u.firstname, qa.attempt";

$params = ['quizid' => $quiz->id];
$attempts = $DB->get_records_sql($sql, $params);

$unlockurl = new moodle_url($PAGE->url);

foreach ($attempts as $attempt) {
    $row = new html_table_row();
    $user = (object) [
        'id' => $attempt->userid,
        'firstname' => $attempt->firstname,
        'lastname' => $attempt->lastname,
        'email' => $attempt->email,
        'picture' => $attempt->picture,
        'imagealt' => $attempt->imagealt,
    ];

    $checkbox = '';
    if ($attempt->state == quiz_attempt::IN_PROGRESS) {
        $checkbox = html_writer::checkbox('attemptid[]', $attempt->id, false, '', ['id' => 'attempt-' . $attempt->id]);
    }
    $row->cells[] = $checkbox;

    $row->cells[] = $OUTPUT->user_picture($user, ['size' => 24, 'courseid' => $course->id]) . ' ' . fullname($user);
    $row->cells[] = $attempt->email;

    // CORRECTED: Use the new, correct helper function here.
    $row->cells[] = quizaccess_onesession_get_attempt_state_string($attempt->state);

    if ($attempt->state == quiz_attempt::IN_PROGRESS) {
        $singleunlockurl = new moodle_url($unlockurl, ['unlock' => $attempt->id, 'sesskey' => sesskey()]);
        $action = html_writer::link($singleunlockurl, get_string('allowchange', 'quizaccess_onesession'));
    } else {
        $action = get_string('notpossible', 'quizaccess_onesession');
    }
    $row->cells[] = $action;

    $logtext = '';
    if (!empty($attempt->timeunlocked)) {
        $teacher = (object) ['firstname' => $attempt->teacher_firstname, 'lastname' => $attempt->teacher_lastname];
        $logdata = ['teacher' => fullname($teacher), 'time' => userdate($attempt->timeunlocked)];
        $logtext = get_string('unlockedbyon', 'quizaccess_onesession', $logdata);
    }
    $row->cells[] = $logtext;

    $table->data[] = $row;
}

echo html_writer::start_tag('form', ['action' => $PAGE->url, 'method' => 'post']);
echo html_writer::table($table);
echo html_writer::start_tag('div', ['class' => 'buttons']);
echo html_writer::tag('button', get_string('allowchangeinconnection', 'quizaccess_onesession'), ['type' => 'submit', 'name' => 'unlockselected', 'value' => '1', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('div');
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();