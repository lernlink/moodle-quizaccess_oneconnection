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
 * English language strings for quizaccess_onesession.
 *
 * @package    quizaccess_onesession
 * @copyright  2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['anothersession'] = 'You are trying to access this quiz attempt from a different device or browser than the one you started with. If you need to switch devices, please contact the invigilator.';
$string['eventattemptblocked'] = 'Student\'s attempt to continue quiz attempt using other device was blocked';
$string['eventattemptunlocked'] = 'Student was allowed to continue quiz attempt using other device';
$string['onesession'] = 'Block concurrent connections';
$string['onesession:allowchange'] = 'Allow a change in connection for a quiz attempt';
$string['onesession:editenabled'] = 'Control whether "Block concurrent connections" can be set';
$string['onesession_help'] = 'If enabled, users can continue a quiz attempt only in the same browser session. Any attempts to open the same quiz attempt using another computer, device or browser will be blocked. This may be useful to be sure that no one helps a student by opening the same quiz attempt on other computer.';
$string['pluginname'] = 'Block concurrent sessions quiz access rule';
$string['privacy:metadata'] = 'The plugin stores the hash of the string used to identify the client device session. Although the original string contains the client\'s IP address and the User-Agent header sent by the client\'s browser, the hash does not allow to extract this information. The hash is automatically deleted immediately after the end of the quiz session. It also logs when a teacher allows a connection change for a student attempt.';
$string['studentinfo'] = 'Attention! It is prohibited to change device while attempting this quiz. Please note that after beginning of quiz attempt any connections to this quiz using other computers, devices and browsers will be blocked. Do not close the browser window until the end of attempt, otherwise you will not be able to complete this quiz.';
$string['whitelist'] = 'Networks without IP check';
$string['whitelist_desc'] = 'This option is intended to lower false positives when users takes quizzes over mobile networks, where IP can be changed during quiz. It is not needed in most of situations. You can provide a comma separated list of subnets (e.g. 88.0.0.0/8, 77.77.0.0/16). If an IP address is in such a network, it\'s not checked. To totally disable the IP check, you can set the value to 0.0.0.0/0.';

// New strings for the report page.
$string['allowconnections'] = 'Allow connection changes';
$string['allowchange'] = 'Allow change';
$string['allowchangeinconnection'] = 'Allow change in connection for selected attempts';
$string['changeallowed'] = 'Change allowed';
$string['changeinconnection'] = 'Change in connection';
$string['notpossible'] = 'Not possible';
$string['statusattempt'] = 'Attempt status';
$string['unlocksuccess'] = 'Connection change allowed for {$a} attempt(s).';
$string['unlockedbyon'] = 'Allowed by {$a->teacher} on {$a->time}';

// This one is used by the new table renderer I gave you.
$string['allowedbyon'] = 'Allowed by {$a->fullname} on {$a->time}';

// Privacy provider strings.
$string['privacy:metadata:log'] = 'Stores a record of which user allowed a connection change for a quiz attempt and when it occurred.';
$string['privacy:metadata:log:unlockedby'] = 'The ID of the user (typically a teacher or invigilator) who allowed the connection change.';

// Extra strings for the mockup-like report UI.
$string['filterheading'] = 'What to include in the report';
$string['filterattemptsfrom'] = 'Attempts from';
$string['filterattemptsthat'] = 'Attempts that are';
$string['filterenrolledwithattempts'] = 'Enrolled users who have a quiz attempt';
$string['displayoptions'] = 'Display options';
$string['pagesize'] = 'Page size';
$string['showreport'] = 'Show report';

// Additional strings needed by the form.
$string['whattoincludeinreport'] = 'What to include in the report';
$string['attemptsfrom'] = 'Attempts from';
$string['attemptsfrom_enrolledattempts'] = 'Enrolled users who have a quiz attempt';
$string['attemptsfrom_enrollednoattempts'] = 'Enrolled users who do not have a quiz attempt';
$string['attemptsfrom_enrolledall'] = 'Enrolled users who have, or do not have, a quiz attempt';
$string['attemptsfrom_allattempts'] = 'All users who have a quiz attempt';
$string['attemptsthat'] = 'Attempts that are';
$string['state_notstarted'] = 'Not started';
$string['state_inprogress'] = 'In progress';
$string['state_overdue'] = 'Overdue';
$string['state_submitted'] = 'Submitted';
$string['state_finished'] = 'Finished';
$string['state_abandoned'] = 'Never submitted';

// Admin settings intro.
$string['settingsintro'] = 'Configure the default behaviour for the “Block concurrent sessions” quiz access rule. You can pre-enable it for new quizzes and list IP subnets that should be ignored when building the session fingerprint.';
