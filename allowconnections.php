<?php
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
 * @param string $state
 * @return string
 */
function quizaccess_onesession_get_attempt_state_string(string $state): string
{
    $stringkey = 'state' . $state;
    if (get_string_manager()->string_exists($stringkey, 'quiz')) {
        return get_string($stringkey, 'quiz');
    }
    return $state;
}

/**
 * Unlocks a specific attempt and logs the action.
 *
 * @param int $attemptid
 * @param int $quizid
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

$sql = "SELECT qa.id,
               qa.userid,
               qa.attempt,
               qa.state,
               qa.quiz,
               u.firstname,
               u.lastname,
               u.email,
               u.picture,
               u.imagealt,
               qoss.id AS locked,
               l.timeunlocked,
               ul.firstname AS teacher_firstname,
               ul.lastname AS teacher_lastname
          FROM {quiz_attempts} qa
          JOIN {user} u ON u.id = qa.userid
     LEFT JOIN {quizaccess_onesession_sess} qoss ON qoss.attemptid = qa.id
     /* Get only the latest unlock per attempt */
     LEFT JOIN (
               SELECT attemptid, MAX(timeunlocked) AS timeunlocked
                 FROM {quizaccess_onesession_log}
             GROUP BY attemptid
               ) l ON l.attemptid = qa.id
     LEFT JOIN {quizaccess_onesession_log} qol
               ON qol.attemptid = qa.id AND qol.timeunlocked = l.timeunlocked
     LEFT JOIN {user} ul ON ul.id = qol.unlockedby
         WHERE qa.quiz = :quizid
      ORDER BY u.lastname, u.firstname, qa.attempt";

$params = ['quizid' => $quiz->id];

$attempts = $DB->get_records_sql($sql, $params);

$unlockurl = new moodle_url($PAGE->url);

echo html_writer::start_tag('form', ['action' => $PAGE->url, 'method' => 'post']);

foreach ($attempts as $attempt) {
    $row = new html_table_row();

    $checkbox = '';
    if ($attempt->state == quiz_attempt::IN_PROGRESS) {
        $checkbox = html_writer::checkbox('attemptid[]', $attempt->id, false, '', ['id' => 'attempt-' . $attempt->id]);
    }
    $row->cells[] = $checkbox;

    // Student name – build manually to avoid fullname() debug warning.
    $row->cells[] = trim($attempt->firstname . ' ' . $attempt->lastname);

    $row->cells[] = $attempt->email;

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
        // Teacher name – also build manually.
        $teachername = trim(($attempt->teacher_firstname ?? '') . ' ' . ($attempt->teacher_lastname ?? ''));
        $logdata = ['teacher' => $teachername, 'time' => userdate($attempt->timeunlocked)];
        $logtext = get_string('unlockedbyon', 'quizaccess_onesession', $logdata);
    }
    $row->cells[] = $logtext;

    $table->data[] = $row;
}

echo html_writer::table($table);

echo html_writer::start_tag('div', ['class' => 'buttons']);
echo html_writer::tag('button', get_string('allowchangeinconnection', 'quizaccess_onesession'), [
    'type' => 'submit',
    'name' => 'unlockselected',
    'value' => '1',
    'class' => 'btn btn-primary mt-2'
]);
echo html_writer::end_tag('div');
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
