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
 * German language strings for quizaccess_oneconnection.
 *
 * @package    quizaccess_oneconnection
 * @copyright  2016 Vadim Dvorovenko
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowchange'] = 'Wechsel erlauben';
$string['allowchangeinconnection'] = 'Verbindungswechsel für ausgewählte Versuche erlauben';
$string['allowconnections'] = 'Verbindungswechsel erlauben';
$string['allowedbyon'] = '{$a->time} (Erlaubt von {$a->fullname})';
$string['allowchangesinconnection'] = 'Verbindungswechsel zulassen';
$string['anothersession'] = 'Sie versuchen, von einem anderen Gerät oder Browser aus auf diesen Testversuch zuzugreifen als dem, mit dem Sie begonnen haben. Wenden Sie sich an die Prüfungsaufsicht, wenn Sie die Verbindung erneuern müssen.';
$string['attemptsfrom'] = 'Versuche von';
$string['attemptsfrom_allattempts'] = 'Alle Nutzer/innen mit Testversuch';
$string['attemptsfrom_enrolledall'] = 'Eingeschriebene Nutzer/innen mit oder ohne Testversuch';
$string['attemptsfrom_enrolledattempts'] = 'Eingeschriebene Nutzer/innen mit Testversuch';
$string['attemptsfrom_enrollednoattempts'] = 'Eingeschriebene Nutzer/innen ohne Testversuch';
$string['attemptsthat'] = 'Versuche mit Status';
$string['changeallowed'] = 'Wechsel erlaubt';
$string['changeinconnection'] = 'Verbindungswechsel';
$string['defaultenabled_desc'] = 'Wenn aktiviert, ist die Einstellung "Gleichzeitige Verbindungen blockieren" beim Erzeugen eines neuen Tests standardmässig aktiviert.';
$string['displayoptions'] = 'Anzeigeoptionen';
$string['downloadcsv'] = 'Tabelle als CSV exportieren';
$string['downloadexcel'] = 'Tabelle als Excel-Datei exportieren';
$string['eventattemptblocked'] = 'Versuch eines Studierenden, mit einer neuen Verbindung auf den Test zuzugreifen, wurde blockiert';
$string['eventattemptunlocked'] = 'Dem Studierenden wurde erlaubt, den Testversuch mit einem anderen Gerät fortzusetzen';
$string['filterattemptsfrom'] = 'Versuche von';
$string['filterattemptsthat'] = 'Versuche mit Status';
$string['filterenrolledwithattempts'] = 'Eingeschriebene Nutzer/innen mit Testversuch';
$string['filterheading'] = 'Was in Bericht einbezogen wird';
$string['isadvanced'] = 'Erweiterte Einstellung';
$string['isadvanced_desc'] = 'Wenn aktiviert, wird die Einstellung nur angezeigt, wenn man auf "Mehr anzeigen …" klickt';
$string['notpossible'] = 'Nicht möglich';
$string['oneconnection'] = 'Gleichzeitige Verbindungen blockieren';
$string['oneconnection:allowchange'] = 'Einen Verbindungswechsel für einen Testversuch erlauben';
$string['oneconnection:editenabled'] = 'Steuern, ob „Gleichzeitige Verbindungen blockieren“ gesetzt werden kann';
$string['oneconnection_help'] = 'Wenn diese Option aktiviert ist, können Nutzer/innen einen Testversuch nur in einer einzigen Browsersitzung durchführen. Alle Versuche, denselben Testversuch mit einem anderen Computer oder Browser zu öffnen, werden blockiert. Dies kann nützlich sein, da keine andere Person denselben Testversuch auf einem anderen Computer öffnen kann.';
$string['pagesize'] = 'Seitengrösse';
$string['pluginname'] = 'Testzugriffsregel: Gleichzeitige Verbindungen blockieren';
$string['privacy:metadata'] = 'Das Plugin speichert den Hash eines Strings zur Identifizierung der Client-Gerätesitzung. Es protokolliert außerdem, wenn eine Lehrperson einen Verbindungswechsel für den Versuch eines Studierenden erlaubt.';
$string['privacy:metadata:log'] = 'Speichert, welche Person einen Verbindungswechsel für einen Testversuch erlaubt hat und wann.';
$string['privacy:metadata:log:unlockedby'] = 'Die ID der Person (typisch Lehrperson oder Aufsicht), die den Verbindungswechsel erlaubt hat.';
$string['settingsintro'] = 'Legen Sie das Standardverhalten für die Regel „Gleichzeitige Verbindungen blockieren“ fest. Sie können sie für neue Tests vorab aktivieren und IP-Netze angeben, die bei der Sitzungsprüfung ignoriert werden sollen.';
$string['showreport'] = 'Bericht anzeigen';
$string['state_abandoned'] = 'Nie abgegeben';
$string['state_finished'] = 'Beendet';
$string['state_inprogress'] = 'In Bearbeitung';
$string['state_notstarted'] = 'Nicht begonnen';
$string['state_overdue'] = 'Überfällig';
$string['state_submitted'] = 'Abgegeben';
$string['statusattempt'] = 'Versuchsstatus';
$string['studentinfo'] = 'Bitte beachten Sie, dass nach Beginn des Testversuchs alle Verbindungen zu diesem Test über andere Computer oder Browsersitzungen blockiert werden. Schliessen Sie das Browserfenster nicht vor Ende des Testversuchs, da sonst die Prüfungsaufsicht einen Verbindungswechsel erlauben muss.';
$string['unlockedbyon'] = 'Erlaubt von {$a->teacher} am {$a->time}';
$string['unlocksuccess'] = 'Verbindungswechsel für {$a} Versuch(e) erlaubt.';
$string['whattoincludeinreport'] = 'Was in Bericht einbezogen wird';
$string['whitelist'] = 'Netzwerke ohne IP-Prüfung';
$string['whitelist_desc'] = 'Diese Option ist dazu gedacht, Probleme zu verringern, wenn Nutzer/innen Tests in mobilen Netzwerken durchführen, in denen die IP-Adressen während des Tests ändern können. In den meisten Situationen ist das nicht notwendig. Sie können eine kommagetrennte Liste von Subnetzen angeben (z. B. 88.0.0.0/8, 77.77.0.0/16). Befindet sich die IP-Adresse in solchen Netzwerken, wird sie nicht überprüft. Um die Überprüfung der IP-Adresse vollständig zu deaktivieren, können Sie den Wert 0.0.0.0/0 angeben.';