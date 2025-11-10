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
 *  - logs every unlock action.
 *
 * @package     quizaccess_oneconnection
 * @copyright   2025
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../../config.php');

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/oneconnection/classes/form/allowconnections_settings_form.php');

use core_user;

$id = required_param('id', PARAM_INT); // Course module ID.

// Filters (like quiz report).
$attemptsfrom = optional_param('attemptsfrom', 'enrolledattempts', PARAM_ALPHA); // enrolledattempts | enrollednoattempts | enrolledall | allattempts.
$pagesize = optional_param('pagesize', 30, PARAM_INT);
$firstnameinitial = optional_param('tifirst', '', PARAM_ALPHA);
$lastnameinitial = optional_param('tilast', '', PARAM_ALPHA);

// Sorting (preserve and honour flexible_table 'sort' & 'dir' params).
$sort = optional_param('sort', 'firstname', PARAM_ALPHA); // 'firstname' default.
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);

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
    redirect($backurl);
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
    redirect($backurl);
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

// Output starts.
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

// Custom initials bars (because core initials_bar() needs a baseurl).
$letters = range('A', 'Z');

// Firstname initials bar.
echo html_writer::start_div('initialsbar');
echo html_writer::tag('strong', get_string('firstname') . ' ');
$allurl = clone $baseurl;
$allurl->param('tifirst', '');
if ($firstnameinitial === '') {
    echo html_writer::tag('strong', get_string('all'));
} else {
    echo html_writer::link($allurl, get_string('all'));
}
foreach ($letters as $letter) {
    $letterurl = clone $baseurl;
    $letterurl->param('tifirst', $letter);
    if ($firstnameinitial === $letter) {
        echo ' ' . html_writer::tag('strong', $letter);
    } else {
        echo ' ' . html_writer::link($letterurl, $letter);
    }
}
echo html_writer::end_div();

// Lastname initials bar.
echo html_writer::start_div('initialsbar');
echo html_writer::tag('strong', get_string('lastname') . ' ');
$allurl = clone $baseurl;
$allurl->param('tilast', '');
if ($lastnameinitial === '') {
    echo html_writer::tag('strong', get_string('all'));
} else {
    echo html_writer::link($allurl, get_string('all'));
}
foreach ($letters as $letter) {
    $letterurl = clone $baseurl;
    $letterurl->param('tilast', $letter);
    if ($lastnameinitial === $letter) {
        echo ' ' . html_writer::tag('strong', $letter);
    } else {
        echo ' ' . html_writer::link($letterurl, $letter);
    }
}
echo html_writer::end_div();

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

// Prepare table.
$table = new flexible_table('quizaccess-oneconnection-allowconnections-' . $cm->id);

$table->define_baseurl($baseurl);
$table->define_columns([
    'select',
    'firstname',
    'lastname',
    'email',
    'status',
    'changeinconnection',
    'changeallowed',
]);
$table->define_headers([
    '',
    get_string('firstname'),
    get_string('lastname'),
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

$selectsql = "SELECT
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
               $basefromsql
           ORDER BY " . implode(', ', $orderby);

$records = $DB->get_records_sql($selectsql, $params, $offset, $perpage);

// 6) Render table rows.
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

    // User profile link (link first name; show last name separately).
    $profileurl = new moodle_url('/user/view.php', ['id' => $r->userid, 'course' => $course->id]);
    $firstnamecell = html_writer::link($profileurl, s($r->firstname));
    $lastnamecell = s($r->lastname);

    $table->add_data([
        $selectbox,
        $firstnamecell,
        $lastnamecell,
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
