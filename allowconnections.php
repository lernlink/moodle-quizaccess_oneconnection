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
 * Teacher/invigilator page to allow connection changes for quiz attempts.
 *
 * This page behaves similarly to a quiz report:
 *  - has filters for enrolled users / attempts;
 *  - supports pagination, sorting and initials bars;
 *  - allows unlocking a single attempt or multiple selected attempts;
 *  - logs every unlock action;
 *  - supports CSV/Excel export of the current table view.
 *
 * @package     quizaccess_oneconnection
 * @copyright   2025
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../config.php');

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/oneconnection/classes/form/allowconnections_settings_form.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/oneconnection/lib.php');

use core_user;

/**
 * Export the Allow connections table as CSV or Excel.
 *
 * @param string   $download 'csv' or 'excel'.
 * @param string   $sql      Full SQL (SELECT ... FROM ... ORDER BY ...).
 * @param array    $params   SQL params.
 * @param stdClass $quiz     Quiz record (for filename).
 * @return void
 */
function quizaccess_oneconnection_export_table(string $download, string $sql, array $params, stdClass $quiz): void
{
    global $DB;

    // No extension here – core\dataformat will add it for Excel,
    // and we add it manually for CSV.
    $filename = clean_filename($quiz->name . '_allowconnections_' . userdate(time(), '%Y%m%d-%H%M'));

    // -----------------------------------------------------------------
    // Excel export: real .xlsx via core\dataformat.
    // -----------------------------------------------------------------
    if ($download === 'excel') {
        $columns = [
            'fullname' => get_string('firstname') . ' ' . get_string('lastname'),
            'email' => get_string('email'),
            'status' => get_string('statusattempt', 'quizaccess_oneconnection'),
            'changeinconnection' => get_string('changeinconnection', 'quizaccess_oneconnection'),
            'changeallowed' => get_string('changeallowed', 'quizaccess_oneconnection'),
        ];

        // Build an in-memory iterable of rows with the exact column keys.
        $data = [];

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $r) {
            $canunlocknow = !empty($r->attemptid) && ($r->state === 'inprogress' || $r->state === 'overdue');

            // Status text.
            switch ($r->state) {
                case 'notstarted':
                    $statetext = get_string('state_notstarted', 'quizaccess_oneconnection');
                    break;
                case 'inprogress':
                    $statetext = get_string('state_inprogress', 'quizaccess_oneconnection');
                    break;
                case 'overdue':
                    $statetext = get_string('state_overdue', 'quizaccess_oneconnection');
                    break;
                case 'submitted':
                    $statetext = get_string('state_submitted', 'quizaccess_oneconnection');
                    break;
                case 'finished':
                    $statetext = get_string('state_finished', 'quizaccess_oneconnection');
                    break;
                case 'abandoned':
                    $statetext = get_string('state_abandoned', 'quizaccess_oneconnection');
                    break;
                default:
                    $statetext = $r->state ?? '';
            }

            // "Change in connection" column (plain text).
            if ($canunlocknow) {
                $changeinconnection = get_string('allowchange', 'quizaccess_oneconnection');
            } else if (!empty($r->attemptid)) {
                $changeinconnection = get_string('notpossible', 'quizaccess_oneconnection');
            } else {
                $changeinconnection = get_string('notpossible', 'quizaccess_oneconnection');
            }

            // "Change allowed" column from log table (if any).
            $changeallowed = '';
            if (!empty($r->unlockedby) && !empty($r->timeunlocked)) {
                $unlockuser = core_user::get_user(
                    $r->unlockedby,
                    'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename'
                );
                $a = (object) [
                    'time' => userdate($r->timeunlocked),
                    'fullname' => fullname($unlockuser),
                ];
                $changeallowed = get_string('allowedbyon', 'quizaccess_oneconnection', $a);
            }

            // Full name (respecting name fields).
            $userforfullname = (object) [
                'id' => $r->userid,
                'firstname' => $r->firstname,
                'lastname' => $r->lastname,
                'firstnamephonetic' => $r->firstnamephonetic,
                'lastnamephonetic' => $r->lastnamephonetic,
                'middlename' => $r->middlename,
                'alternatename' => $r->alternatename,
            ];
            $fullname = fullname($userforfullname);

            $row = new stdClass();
            $row->fullname = $fullname;
            $row->email = $r->email;
            $row->status = $statetext;
            $row->changeinconnection = $changeinconnection;
            $row->changeallowed = $changeallowed;

            $data[] = $row;
        }
        $rs->close();

        // Correct argument order:
        // (filename, dataformat, columns, iterable-of-records).
        \core\dataformat::download_data($filename, 'excel', $columns, $data);
        exit;
    }

    // -----------------------------------------------------------------
    // CSV export: same as before (streamed text CSV).
    // -----------------------------------------------------------------
    $mimetype = 'text/csv';
    $extension = '.csv';
    $delimiter = ',';

    header('Content-Type: ' . $mimetype . '; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . $extension . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');

    // Header row (no selection column).
    $headers = [
        get_string('firstname') . ' ' . get_string('lastname'),
        get_string('email'),
        get_string('statusattempt', 'quizaccess_oneconnection'),
        get_string('changeinconnection', 'quizaccess_oneconnection'),
        get_string('changeallowed', 'quizaccess_oneconnection'),
    ];
    fputcsv($out, $headers, $delimiter);

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $r) {
        $canunlocknow = !empty($r->attemptid) && ($r->state === 'inprogress' || $r->state === 'overdue');

        // Status text.
        switch ($r->state) {
            case 'notstarted':
                $statetext = get_string('state_notstarted', 'quizaccess_oneconnection');
                break;
            case 'inprogress':
                $statetext = get_string('state_inprogress', 'quizaccess_oneconnection');
                break;
            case 'overdue':
                $statetext = get_string('state_overdue', 'quizaccess_oneconnection');
                break;
            case 'submitted':
                $statetext = get_string('state_submitted', 'quizaccess_oneconnection');
                break;
            case 'finished':
                $statetext = get_string('state_finished', 'quizaccess_oneconnection');
                break;
            case 'abandoned':
                $statetext = get_string('state_abandoned', 'quizaccess_oneconnection');
                break;
            default:
                $statetext = $r->state ?? '';
        }

        // "Change in connection" column.
        if ($canunlocknow) {
            $changeinconnection = get_string('allowchange', 'quizaccess_oneconnection');
        } else if (!empty($r->attemptid)) {
            $changeinconnection = get_string('notpossible', 'quizaccess_oneconnection');
        } else {
            $changeinconnection = get_string('notpossible', 'quizaccess_oneconnection');
        }

        // "Change allowed" column from log table (if any).
        $changeallowed = '';
        if (!empty($r->unlockedby) && !empty($r->timeunlocked)) {
            $unlockuser = core_user::get_user(
                $r->unlockedby,
                'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename'
            );
            $a = (object) [
                'time' => userdate($r->timeunlocked),
                'fullname' => fullname($unlockuser),
            ];
            $changeallowed = get_string('allowedbyon', 'quizaccess_oneconnection', $a);
        }

        // Full name for CSV.
        $userforfullname = (object) [
            'id' => $r->userid,
            'firstname' => $r->firstname,
            'lastname' => $r->lastname,
            'firstnamephonetic' => $r->firstnamephonetic,
            'lastnamephonetic' => $r->lastnamephonetic,
            'middlename' => $r->middlename,
            'alternatename' => $r->alternatename,
        ];
        $fullname = fullname($userforfullname);

        $row = [
            $fullname,
            $r->email,
            $statetext,
            $changeinconnection,
            $changeallowed,
        ];

        fputcsv($out, $row, $delimiter);
    }
    $rs->close();
    fclose($out);
    exit;
}

$id = required_param('id', PARAM_INT); // Course module ID.

// Filters (like quiz report).
$attemptsfrom = optional_param('attemptsfrom', 'enrolledattempts', PARAM_ALPHA); // enrolledattempts | enrollednoattempts | enrolledall | allattempts.
$pagesize = optional_param('pagesize', 30, PARAM_INT);
$firstnameinitial = optional_param('tifirst', '', PARAM_ALPHA);
$lastnameinitial = optional_param('tilast', '', PARAM_ALPHA);

// If the user clicked "Reset table preferences", also clear initials.
if (optional_param('treset', 0, PARAM_BOOL)) {
    $firstnameinitial = '';
    $lastnameinitial = '';
}

// Sorting (preserve and honour flexible_table 'sort' & 'dir' params).
$sort = optional_param('sort', 'firstname', PARAM_ALPHA); // 'firstname' default.
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);

// Download type (empty string = normal HTML output).
$download = optional_param('download', '', PARAM_ALPHA);

// Attempt state checkboxes. If none are present in the URL we use the default (all on).
$attemptstate = optional_param_array('attemptstate', null, PARAM_BOOL);
if ($attemptstate === null) {
    $attemptstate = [
        'notstarted' => 1,
        'inprogress' => 1,
        'overdue' => 1,
        'submitted' => 1,
        'finished' => 1,
        'abandoned' => 1,
    ];
}

// Basic quiz/course setup.
$cm = get_coursemodule_from_id('quiz', $id, 0, false, MUST_EXIST);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('quizaccess/oneconnection:allowchange', $context);

// We need the course context to detect roles that can preview (not students).
$coursecontext = context_course::instance($course->id);

$PAGE->set_url('/mod/quiz/accessrule/oneconnection/allowconnections.php', [
    'id' => $id,
]);
$PAGE->set_title($quiz->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');

// Handle single-row unlock.
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'unlock' && confirm_sesskey()) {
    $attemptid = required_param('attemptid', PARAM_INT);
    $unlocked = false;

    // Only unlock attempts that are in progress or overdue.
    $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], 'id,userid,state', IGNORE_MISSING);
    if ($attempt && ($attempt->state === 'inprogress' || $attempt->state === 'overdue')) {
        // Delete session binding.
        $DB->delete_records('quizaccess_oneconnection_sess', ['attemptid' => $attemptid]);

        // Log the action (if the table exists).
        $log = (object) [
            'quizid' => $quiz->id,
            'attemptid' => $attemptid,
            'unlockedby' => $USER->id,
            'timeunlocked' => time(),
        ];
        try {
            $DB->insert_record('quizaccess_oneconnection_log', $log);
        } catch (dml_exception $e) {
            // For older sites where the log table is not yet present, we simply continue.
        }

        // Fire the event so logstores receive the action.
        $event = \quizaccess_oneconnection\event\attempt_unlocked::create([
            'objectid' => $attemptid,
            'relateduserid' => $attempt->userid ?? 0,
            'context' => $context,
            'other' => ['quizid' => $quiz->id],
        ]);
        $event->trigger();

        $unlocked = true;
    }

    // Redirect back (keep filters).
    $backurl = new moodle_url($PAGE->url, [
        'id' => $id,
        'attemptsfrom' => $attemptsfrom,
        'pagesize' => $pagesize,
        'tifirst' => $firstnameinitial,
        'tilast' => $lastnameinitial,
        'sort' => $sort,
        'dir' => $dir,
    ]);
    foreach (($attemptstate ?? []) as $k => $v) {
        if ($v) {
            $backurl->param("attemptstate[$k]", 1);
        }
    }

    if ($unlocked) {
        // Nice green success alert after redirect.
        redirect(
            $backurl,
            get_string('unlocksuccess', 'quizaccess_oneconnection', 1),
            0,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect($backurl);
    }
}

// Build the filter form.
$customdata = [
    'cmid' => $cm->id,
    'attemptsfrom' => $attemptsfrom,
    'attemptstate' => $attemptstate,
    'pagesize' => $pagesize,
];
$mform = new \quizaccess_oneconnection\form\allowconnections_settings_form(null, $customdata);

// If the form is submitted, redirect to the same page with clean params (GET).
if (!$download) {
    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/quiz/view.php', ['id' => $cm->id]));
    }
    if ($data = $mform->get_data()) {
        $params = [
            'id' => $cm->id,
            'attemptsfrom' => $data->attemptsfrom,
            'pagesize' => $data->pagesize,
            'sort' => $sort,
            'dir' => $dir,
        ];
        if (!empty($data->attemptstate) && is_array($data->attemptstate)) {
            foreach ($data->attemptstate as $k => $v) {
                if ($v) {
                    $params["attemptstate[$k]"] = 1;
                }
            }
        }
        redirect(new moodle_url($PAGE->url, $params));
    }
}

// Normalize attemptstate for filtering later (1/0).
$normalizedattemptstate = [
    'notstarted' => empty($attemptstate['notstarted']) ? 0 : 1,
    'inprogress' => empty($attemptstate['inprogress']) ? 0 : 1,
    'overdue' => empty($attemptstate['overdue']) ? 0 : 1,
    'submitted' => empty($attemptstate['submitted']) ? 0 : 1,
    'finished' => empty($attemptstate['finished']) ? 0 : 1,
    'abandoned' => empty($attemptstate['abandoned']) ? 0 : 1,
];

// Handle POST (unlock selected)
if (optional_param('unlockselected', 0, PARAM_BOOL) && confirm_sesskey()) {
    $selected = optional_param_array('attemptid', [], PARAM_INT);
    $unlockedcount = 0;

    if (!empty($selected)) {
        foreach ($selected as $attemptid) {
            // Only unlock valid states.
            $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid], 'id,userid,state', IGNORE_MISSING);
            if (!$attempt || ($attempt->state !== 'inprogress' && $attempt->state !== 'overdue')) {
                continue;
            }

            $DB->delete_records('quizaccess_oneconnection_sess', ['attemptid' => $attemptid]);

            $log = (object) [
                'quizid' => $quiz->id,
                'attemptid' => $attemptid,
                'unlockedby' => $USER->id,
                'timeunlocked' => time(),
            ];
            try {
                $DB->insert_record('quizaccess_oneconnection_log', $log);
            } catch (dml_exception $e) {
                // Silently continue if the log table is not available.
            }

            // Fire event per attempt.
            $event = \quizaccess_oneconnection\event\attempt_unlocked::create([
                'objectid' => $attemptid,
                'relateduserid' => $attempt->userid ?? 0,
                'context' => $context,
                'other' => ['quizid' => $quiz->id],
            ]);
            $event->trigger();

            $unlockedcount++;
        }
    }

    // Preserve filters / initials / attemptstate on bulk unlock too.
    $backurl = new moodle_url($PAGE->url, [
        'id' => $id,
        'attemptsfrom' => $attemptsfrom,
        'pagesize' => $pagesize,
        'tifirst' => $firstnameinitial,
        'tilast' => $lastnameinitial,
        'sort' => $sort,
        'dir' => $dir,
    ]);
    foreach ($normalizedattemptstate as $k => $v) {
        if ($v) {
            $backurl->param("attemptstate[$k]", 1);
        }
    }

    if ($unlockedcount > 0) {
        redirect(
            $backurl,
            get_string('unlocksuccess', 'quizaccess_oneconnection', $unlockedcount),
            0,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        redirect($backurl);
    }
}

// Build base URL for table links (sort, initials, paging).
$baseurl = new moodle_url($PAGE->url, [
    'id' => $id,
    'attemptsfrom' => $attemptsfrom,
    'pagesize' => $pagesize,
    'tifirst' => $firstnameinitial,
    'tilast' => $lastnameinitial,
    'sort' => $sort,
    'dir' => $dir,
]);
foreach ($normalizedattemptstate as $k => $v) {
    if ($v) {
        $baseurl->param("attemptstate[$k]", 1);
    }
}

// Sorting direction sanitization.
$dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

// Build state filter SQL (for attempts).
$statelikes = [];
if ($normalizedattemptstate['notstarted']) {
    $statelikes[] = "qa.state = 'notstarted'";
}
if ($normalizedattemptstate['inprogress']) {
    $statelikes[] = "qa.state = 'inprogress'";
}
if ($normalizedattemptstate['overdue']) {
    $statelikes[] = "qa.state = 'overdue'";
}
if ($normalizedattemptstate['submitted']) {
    $statelikes[] = "qa.state = 'submitted'";
}
if ($normalizedattemptstate['finished']) {
    $statelikes[] = "qa.state = 'finished'";
}
if ($normalizedattemptstate['abandoned']) {
    $statelikes[] = "qa.state = 'abandoned'";
}
$statewhere = '';
if (!empty($statelikes)) {
    $statewhere = '(' . implode(' OR ', $statelikes) . ')';
}

// Latest unlock per attempt – used for the "change allowed" column.
$latestunlockjoin = "
    LEFT JOIN (
        SELECT ql1.*
          FROM {quizaccess_oneconnection_log} ql1
          JOIN (
                SELECT attemptid, MAX(timeunlocked) AS maxtime
                  FROM {quizaccess_oneconnection_log}
                 GROUP BY attemptid
               ) ql2
            ON ql1.attemptid = ql2.attemptid AND ql1.timeunlocked = ql2.maxtime
    ) qlog ON qlog.attemptid = qa.id
";

// Start WHERE list.
$wheres = ["u.deleted = 0"];

// Build params per branch.
$params = [
    'quizid' => $quiz->id,
];

// Branch: build joins + where + params.
switch ($attemptsfrom) {
    case 'enrollednoattempts':
        // Enrolled students only, with NO attempts.
        $joins = "JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
                  JOIN {role_assignments} ra ON ra.userid = u.id AND ra.contextid = :coursectxid
                  LEFT JOIN {role_capabilities} rc ON rc.roleid = ra.roleid
                       AND rc.capability = :capquizpreview
                       AND rc.permission = " . (int) CAP_ALLOW . "
                  LEFT JOIN {quiz_attempts} qa ON qa.userid = u.id AND qa.quiz = :quizid";
        $wheres[] = "rc.id IS NULL";     // Exclude teachers / managers that can preview.
        $wheres[] = "qa.id IS NULL";     // Keep only users without attempts.
        $params['courseid'] = $course->id;
        $params['coursectxid'] = $coursecontext->id;
        $params['capquizpreview'] = 'mod/quiz:preview';
        break;

    case 'enrolledall':
        // Enrolled students only, attempts or not.
        $joins = "JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
                  JOIN {role_assignments} ra ON ra.userid = u.id AND ra.contextid = :coursectxid
                  LEFT JOIN {role_capabilities} rc ON rc.roleid = ra.roleid
                       AND rc.capability = :capquizpreview
                       AND rc.permission = " . (int) CAP_ALLOW . "
                  LEFT JOIN {quiz_attempts} qa ON qa.userid = u.id AND qa.quiz = :quizid";
        $wheres[] = "rc.id IS NULL";     // Students only.
        if ($statewhere !== '') {
            $wheres[] = "(qa.id IS NULL OR $statewhere)";
        }
        $params['courseid'] = $course->id;
        $params['coursectxid'] = $coursecontext->id;
        $params['capquizpreview'] = 'mod/quiz:preview';
        break;

    case 'allattempts':
        // All attempts for this quiz (do not restrict by role).
        $joins = "JOIN {quiz_attempts} qa ON qa.userid = u.id AND qa.quiz = :quizid";
        if ($statewhere !== '') {
            $wheres[] = $statewhere;
        }
        break;

    case 'enrolledattempts':
    default:
        // Enrolled students only, with attempts.
        $joins = "JOIN {user_enrolments} ue ON ue.userid = u.id
                  JOIN {enrol} e ON e.id = ue.enrolid AND e.courseid = :courseid
                  JOIN {role_assignments} ra ON ra.userid = u.id AND ra.contextid = :coursectxid
                  LEFT JOIN {role_capabilities} rc ON rc.roleid = ra.roleid
                       AND rc.capability = :capquizpreview
                       AND rc.permission = " . (int) CAP_ALLOW . "
                  JOIN {quiz_attempts} qa ON qa.userid = u.id AND qa.quiz = :quizid";
        $wheres[] = "rc.id IS NULL";     // Students only.
        if ($statewhere !== '') {
            $wheres[] = $statewhere;
        }
        $params['courseid'] = $course->id;
        $params['coursectxid'] = $coursecontext->id;
        $params['capquizpreview'] = 'mod/quiz:preview';
        break;
}

// Initials filtering.
if ($firstnameinitial !== '') {
    $wheres[] = $DB->sql_like('u.firstname', ':tifirst', false, false);
    $params['tifirst'] = $firstnameinitial . '%';
}
if ($lastnameinitial !== '') {
    $wheres[] = $DB->sql_like('u.lastname', ':tilast', false, false);
    $params['tilast'] = $lastnameinitial . '%';
}

// Build WHERE SQL.
$wheresql = '';
if (!empty($wheres)) {
    $wheresql = 'WHERE ' . implode(' AND ', $wheres);
}

$basefromsql = "FROM {user} u
                $joins
                $latestunlockjoin
                $wheresql";

// Common SELECT fields used both for HTML table and downloads.
$selectfields = "SELECT
                    COALESCE(qa.id, -u.id) AS uniqueid,
                    u.id AS userid,
                    u.firstname,
                    u.lastname,
                    u.email,
                    u.firstnamephonetic,
                    u.lastnamephonetic,
                    u.middlename,
                    u.alternatename,
                    qa.id AS attemptid,
                    qa.state,
                    qa.timestart,
                    qa.timefinish,
                    qlog.unlockedby,
                    qlog.timeunlocked
               ";

// If a download is requested, build ORDER BY based on 'sort' & 'dir',
// export and exit *before* rendering the HTML table.
if (!empty($download)) {
    $orderby = [];
    $sortdirection = (strtoupper($dir) === 'DESC') ? 'DESC' : 'ASC';

    switch ($sort) {
        case 'lastname':
            $orderby[] = "u.lastname $sortdirection, u.firstname $sortdirection";
            break;
        case 'email':
            $orderby[] = "u.email $sortdirection";
            break;
        case 'status':
            // Alphabetical by state (attempts first).
            $orderby[] = "COALESCE(qa.state, 'zzzz') $sortdirection";
            $orderby[] = "u.firstname ASC, u.lastname ASC";
            break;
        case 'changeinconnection':
            // Sort by possibility first.
            $orderby[] = "CASE
                WHEN qa.id IS NOT NULL AND (qa.state = 'inprogress' OR qa.state = 'overdue') THEN 0
                WHEN qa.id IS NOT NULL THEN 1
                ELSE 2
            END $sortdirection";
            $orderby[] = "u.firstname ASC, u.lastname ASC";
            break;
        case 'changeallowed':
            $orderby[] = "CASE WHEN qlog.timeunlocked IS NULL THEN 1 ELSE 0 END $sortdirection";
            $orderby[] = "qlog.timeunlocked $sortdirection";
            $orderby[] = "u.firstname ASC, u.lastname ASC";
            break;
        case 'fullname': // Backwards compatibility.
        case 'firstname':
        default:
            $orderby[] = "u.firstname $sortdirection, u.lastname $sortdirection";
            break;
    }

    $exportsql = $selectfields . "
                 $basefromsql
             ORDER BY " . implode(', ', $orderby);

    quizaccess_oneconnection_export_table($download, $exportsql, $params, $quiz);
    // Never returns.
}

// -----------------------------
// Normal HTML table continues.
// -----------------------------

// Output starts (HTML only, not for downloads).
echo $OUTPUT->header();

// 1) Tertiary navigation like quiz Results.
$stringman = get_string_manager();
$reportoptions = [];

// Add real quiz reports if present.
if ($stringman->string_exists('overview', 'quiz')) {
    $reportoptions[(new moodle_url('/mod/quiz/report.php', [
        'id' => $cm->id,
        'mode' => 'overview',
    ]))->out(false)] = get_string('overview', 'quiz');
}
if ($stringman->string_exists('responses', 'quiz')) {
    $reportoptions[(new moodle_url('/mod/quiz/report.php', [
        'id' => $cm->id,
        'mode' => 'responses',
    ]))->out(false)] = get_string('responses', 'quiz');
}
if ($stringman->string_exists('statistics', 'quiz')) {
    $reportoptions[(new moodle_url('/mod/quiz/report.php', [
        'id' => $cm->id,
        'mode' => 'statistics',
    ]))->out(false)] = get_string('statistics', 'quiz');
}

// Filter form.
$mform->display();

echo html_writer::tag('h3', get_string('allowchangesinconnection', 'quizaccess_oneconnection'), ['class' => 'mb-3']);

// --- START OF INITIALS BAR SECTION ---

$letters = range('A', 'Z');

// First name initials bar.
echo html_writer::start_div('initialbar firstinitial d-flex flex-wrap justify-content-center justify-content-md-start mb-2');
echo html_writer::tag('span', get_string('firstname'), ['class' => 'initialbarlabel me-2']);
echo html_writer::start_tag('nav', ['class' => 'initialbargroups d-flex flex-wrap justify-content-center justify-content-md-start']);
echo html_writer::start_tag('ul', ['class' => 'pagination pagination-sm']);

// "All" button for first name.
$allurl = clone $baseurl;
$allurl->param('tifirst', '');
$isactive = ($firstnameinitial === '');
$liclasses = 'initialbarall page-item' . ($isactive ? ' active' : '');
$link = html_writer::link($allurl, get_string('all'), ['class' => 'page-link']);
echo html_writer::tag('li', $link, ['class' => $liclasses]);
echo html_writer::end_tag('ul');

// Letter buttons for first name.
echo html_writer::start_tag('ul', ['class' => 'pagination pagination-sm']);
foreach ($letters as $letter) {
    $letterurl = clone $baseurl;
    $letterurl->param('tifirst', $letter);
    $isactive = ($firstnameinitial === $letter);
    $liclasses = 'page-item' . ($isactive ? ' active' : '');
    $link = html_writer::link($letterurl, $letter, ['class' => 'page-link']);
    echo html_writer::tag('li', $link, ['class' => $liclasses]);
}
echo html_writer::end_tag('ul');
echo html_writer::end_tag('nav');
echo html_writer::end_div();

// Last name initials bar.
echo html_writer::start_div('initialbar lastinitial d-flex flex-wrap justify-content-center justify-content-md-start');
echo html_writer::tag('span', get_string('lastname'), ['class' => 'initialbarlabel me-2']);
echo html_writer::start_tag('nav', ['class' => 'initialbargroups d-flex flex-wrap justify-content-center justify-content-md-start']);
echo html_writer::start_tag('ul', ['class' => 'pagination pagination-sm']);

// "All" button for last name.
$allurl = clone $baseurl;
$allurl->param('tilast', '');
$isactive = ($lastnameinitial === '');
$liclasses = 'initialbarall page-item' . ($isactive ? ' active' : '');
$link = html_writer::link($allurl, get_string('all'), ['class' => 'page-link']);
echo html_writer::tag('li', $link, ['class' => $liclasses]);
echo html_writer::end_tag('ul');

// Letter buttons for last name.
echo html_writer::start_tag('ul', ['class' => 'pagination pagination-sm']);
foreach ($letters as $letter) {
    $letterurl = clone $baseurl;
    $letterurl->param('tilast', $letter);
    $isactive = ($lastnameinitial === $letter);
    $liclasses = 'page-item' . ($isactive ? ' active' : '');
    $link = html_writer::link($letterurl, $letter, ['class' => 'page-link']);
    echo html_writer::tag('li', $link, ['class' => $liclasses]);
}
echo html_writer::end_tag('ul');
echo html_writer::end_tag('nav');
echo html_writer::end_div();

// --- END OF INITIALS BAR SECTION ---

// Export buttons.
echo html_writer::start_div('initialbar-export mb-3 mt-2');
echo quizaccess_oneconnection_download_dataformat_selector(
    get_string('downloadas', 'table'),
    $PAGE->url,
    'download',
    $baseurl->params()
);
echo html_writer::end_div();

// Prepare table.
$table = new flexible_table('quizaccess-oneconnection-allowconnections-' . $cm->id);

$table->define_baseurl($baseurl);
$table->define_columns([
    'select',
    'fullname',
    'email',
    'status',
    'changeinconnection',
    'changeallowed',
]);
$table->define_headers([
    '',
    get_string('firstname') . ' ' . get_string('lastname'),
    get_string('email'),
    get_string('statusattempt', 'quizaccess_oneconnection'),
    get_string('changeinconnection', 'quizaccess_oneconnection'),
    get_string('changeallowed', 'quizaccess_oneconnection'),
]);
$table->sortable(true, 'firstname', SORT_ASC);
$table->no_sorting('select');
$table->set_attribute('class', 'flexible table table-striped table-hover generaltable quizaccess-oneconnection-table');

// Count total with subquery (to avoid duplicate rows from joins).
$countsql = "SELECT COUNT(1)
               FROM (
                    SELECT DISTINCT COALESCE(qa.id, -u.id) AS uniqueid
                      $basefromsql
                    ) sq";
$total = $DB->count_records_sql($countsql, $params);

$table->pagesize($pagesize, $total);
$table->setup();

// Fetch data for current page.
$offset = $table->get_page_start();
$perpage = $table->get_page_size();

// Sorting from the table (we must honour flexible_table choices).
$sortcolumns = $table->get_sort_columns();
$orderby = [];
foreach ($sortcolumns as $column => $sortdirection) {
    if ($sortdirection === SORT_DESC || $sortdirection === 'DESC' || $sortdirection === 'desc') {
        $sortdirection = 'DESC';
    } else {
        $sortdirection = 'ASC';
    }

    switch ($column) {
        case 'firstname':
            $orderby[] = "u.firstname $sortdirection, u.lastname $sortdirection";
            break;
        case 'lastname':
            $orderby[] = "u.lastname $sortdirection, u.firstname $sortdirection";
            break;
        case 'fullname': // Backwards compatibility if some links still pass 'fullname'.
            $orderby[] = "u.firstname $sortdirection, u.lastname $sortdirection";
            break;
        case 'email':
            $orderby[] = "u.email $sortdirection";
            break;
        case 'status':
            // Alphabetical by state (attempts first).
            $orderby[] = "COALESCE(qa.state, 'zzzz') $sortdirection";
            $orderby[] = "u.firstname ASC, u.lastname ASC";
            break;
        case 'changeinconnection':
            // Sort by possibility first.
            $orderby[] = "CASE
                WHEN qa.id IS NOT NULL AND (qa.state = 'inprogress' OR qa.state = 'overdue') THEN 0
                WHEN qa.id IS NOT NULL THEN 1
                ELSE 2
            END $sortdirection";
            $orderby[] = "u.firstname ASC, u.lastname ASC";
            break;
        case 'changeallowed':
            $orderby[] = "CASE WHEN qlog.timeunlocked IS NULL THEN 1 ELSE 0 END $sortdirection";
            $orderby[] = "qlog.timeunlocked $sortdirection";
            $orderby[] = "u.firstname ASC, u.lastname ASC";
            break;
        default:
            $orderby[] = "u.firstname ASC, u.lastname ASC";
            break;
    }
}
if (empty($orderby)) {
    $orderby[] = "u.firstname ASC, u.lastname ASC";
}

$selectsql = $selectfields . "
               $basefromsql
           ORDER BY " . implode(', ', $orderby);

$records = $DB->get_records_sql($selectsql, $params, $offset, $perpage);

// Render table rows.
echo html_writer::start_tag('form', [
    'action' => $baseurl,
    'method' => 'post',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

foreach ($records as $r) {
    // Decide once per row.
    $canunlocknow = !empty($r->attemptid) && ($r->state === 'inprogress' || $r->state === 'overdue');

    // Checkbox only for allowed states.
    if ($canunlocknow) {
        $selectbox = html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'name' => 'attemptid[]',
            'value' => $r->attemptid,
        ]);
    } else {
        $selectbox = '';
    }

    // Status text.
    $statetext = '';
    switch ($r->state) {
        case 'notstarted':
            $statetext = get_string('state_notstarted', 'quizaccess_oneconnection');
            break;
        case 'inprogress':
            $statetext = get_string('state_inprogress', 'quizaccess_oneconnection');
            break;
        case 'overdue':
            $statetext = get_string('state_overdue', 'quizaccess_oneconnection');
            break;
        case 'submitted':
            $statetext = get_string('state_submitted', 'quizaccess_oneconnection');
            break;
        case 'finished':
            $statetext = get_string('state_finished', 'quizaccess_oneconnection');
            break;
        case 'abandoned':
            $statetext = get_string('state_abandoned', 'quizaccess_oneconnection');
            break;
        default:
            $statetext = $r->state ? s($r->state) : '';
    }

    // Change in connection column as link / text.
    if ($canunlocknow) {
        $unlockurl = new moodle_url($baseurl, [
            'action' => 'unlock',
            'attemptid' => $r->attemptid,
            'sesskey' => sesskey(),
        ]);
        $changeinconnection = html_writer::link($unlockurl, get_string('allowchange', 'quizaccess_oneconnection'));
    } else if (!empty($r->attemptid)) {
        $changeinconnection = get_string('notpossible', 'quizaccess_oneconnection');
    } else {
        $changeinconnection = get_string('notpossible', 'quizaccess_oneconnection');
    }

    // Change allowed column – use log table data if any.
    $changeallowed = '';
    if (!empty($r->unlockedby) && !empty($r->timeunlocked)) {
        $unlockuser = core_user::get_user(
            $r->unlockedby,
            'id,firstname,lastname,firstnamephonetic,lastnamephonetic,middlename,alternatename'
        );
        $a = (object) [
            'time' => userdate($r->timeunlocked),
            'fullname' => fullname($unlockuser),
        ];
        $changeallowed = get_string('allowedbyon', 'quizaccess_oneconnection', $a);
    }

    // User profile link.
    $profileurl = new moodle_url('/user/view.php', ['id' => $r->userid, 'course' => $course->id]);
    $fullnamecell = html_writer::link($profileurl, s($r->firstname . ' ' . $r->lastname));

    $table->add_data([
        $selectbox,
        $fullnamecell,
        s($r->email),
        $statetext,
        $changeinconnection,
        $changeallowed,
    ]);
}

$table->finish_output();

// Action buttons.
echo html_writer::start_div('buttons');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'name' => 'unlockselected',
    'value' => get_string('allowchangeinconnection', 'quizaccess_oneconnection'),
    'class' => 'btn btn-primary mt-2',
]);
echo html_writer::end_div();

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
