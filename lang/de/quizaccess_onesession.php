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
 * German language strings for quizaccess_onesession.
 *
 * @package    quizaccess_onesession
 * @copyright  2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['anothersession'] = 'Sie versuchen, auf diesen Testversuch von einem anderen Gerät oder Browser aus zuzugreifen als dem, mit dem Sie begonnen haben. Wenn Sie das Gerät wechseln müssen, wenden Sie sich bitte an die Prüfungsaufsicht.';
$string['eventattemptblocked'] = 'Der Versuch eines Studierenden, den Testversuch mit einem anderen Gerät fortzusetzen, wurde blockiert';
$string['eventattemptunlocked'] = 'Dem Studierenden wurde erlaubt, den Testversuch mit einem anderen Gerät fortzusetzen';
$string['onesession'] = 'Gleichzeitige Verbindungen blockieren';
$string['onesession:allowchange'] = 'Einen Verbindungswechsel für einen Testversuch erlauben';
$string['onesession:editenabled'] = 'Steuern, ob „Gleichzeitige Verbindungen blockieren“ gesetzt werden kann';
$string['onesession_help'] = 'Wenn aktiviert, können Nutzer/innen einen Testversuch nur in derselben Browser-Sitzung fortsetzen. Jeder Versuch, denselben Testversuch mit einem anderen Computer, Gerät oder Browser zu öffnen, wird blockiert.';
$string['pluginname'] = 'Testzugriffsregel: Gleichzeitige Sitzungen blockieren';
$string['privacy:metadata'] = 'Das Plugin speichert den Hash eines Strings zur Identifizierung der Client-Gerätesitzung. Es protokolliert außerdem, wenn eine Lehrperson einen Verbindungswechsel für den Versuch eines Studierenden erlaubt.';
$string['studentinfo'] = 'Achtung! Es ist verboten, das Gerät während dieses Tests zu wechseln. Nach Beginn des Testversuchs werden alle Verbindungen zu diesem Test mit anderen Computern, Geräten und Browsern blockiert. Schließen Sie das Browserfenster nicht vor dem Ende des Versuchs.';
$string['whitelist'] = 'Netzwerke ohne IP-Prüfung';
$string['whitelist_desc'] = 'Diese Option soll Fehlalarme reduzieren, wenn Tests über Mobilfunknetze durchgeführt werden, bei denen sich die IP-Adresse ändern kann. In den meisten Situationen ist keine Angabe erforderlich.';

// New strings for the report page.
$string['allowconnections'] = 'Verbindungswechsel erlauben';
$string['allowchange'] = 'Wechsel erlauben';
$string['allowchangeinconnection'] = 'Verbindungswechsel für ausgewählte Versuche erlauben';
$string['changeallowed'] = 'Wechsel erlaubt';
$string['changeinconnection'] = 'Verbindungswechsel';
$string['notpossible'] = 'Nicht möglich';
$string['statusattempt'] = 'Versuchsstatus';
$string['unlocksuccess'] = 'Verbindungswechsel für {$a} Versuch(e) erlaubt.';
$string['unlockedbyon'] = 'Erlaubt von {$a->teacher} am {$a->time}';

// Privacy provider strings.
$string['privacy:metadata:log'] = 'Speichert, welche Person einen Verbindungswechsel für einen Testversuch erlaubt hat und wann.';
$string['privacy:metadata:log:unlockedby'] = 'Die ID der Person (typisch Lehrperson oder Aufsicht), die den Verbindungswechsel erlaubt hat.';

// Extra strings for the mockup-like report UI.
$string['filterheading'] = 'Was soll im Bericht enthalten sein';
$string['filterattemptsfrom'] = 'Versuche von';
$string['filterattemptsthat'] = 'Versuche mit Status';
$string['filterenrolledwithattempts'] = 'Eingeschriebene Nutzer/innen mit Testversuch';
$string['displayoptions'] = 'Anzeigeoptionen';
$string['pagesize'] = 'Seitengröße';
$string['showreport'] = 'Bericht anzeigen';

// Additional for the form.
$string['whattoincludeinreport'] = 'Was soll im Bericht enthalten sein';
$string['attemptsfrom'] = 'Versuche von';
$string['attemptsfrom_enrolledattempts'] = 'Eingeschriebene Nutzer/innen mit Testversuch';
$string['attemptsfrom_enrollednoattempts'] = 'Eingeschriebene Nutzer/innen ohne Testversuch';
$string['attemptsfrom_enrolledall'] = 'Eingeschriebene Nutzer/innen mit oder ohne Testversuch';
$string['attemptsfrom_allattempts'] = 'Alle Nutzer/innen mit Testversuch';
$string['attemptsthat'] = 'Versuche mit Status';
$string['state_notstarted'] = 'Nicht gestartet';
$string['state_inprogress'] = 'Läuft';
$string['state_overdue'] = 'Überfällig';
$string['state_submitted'] = 'Abgegeben';
$string['state_finished'] = 'Beendet';
$string['state_abandoned'] = 'Nie abgegeben';
$string['allowedbyon'] = 'Erlaubt von {$a->fullname} am {$a->time}';

// Admin settings intro.
$string['settingsintro'] = 'Legen Sie das Standardverhalten für die Regel „Gleichzeitige Verbindungen blockieren“ fest. Sie können sie für neue Tests vorab aktivieren und IP-Netze angeben, die bei der Sitzungsprüfung ignoriert werden sollen.';
