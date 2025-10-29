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

$string['anothersession'] = 'Sie versuchen, auf diesen Testversuch von einem anderen Gerät oder Browser aus zuzugreifen, als dem, mit dem Sie begonnen haben. Wenn Sie das Gerät wechseln müssen, wenden Sie sich bitte an die Prüfungsaufsicht.';
$string['eventattemptblocked'] = 'Der Versuch eines Studierenden, den Testversuch mit einem anderen Gerät fortzusetzen, wurde blockiert';
$string['eventattemptunlocked'] = 'Dem Studierenden wurde erlaubt, den Testversuch mit einem anderen Gerät fortzusetzen';
$string['onesession'] = 'Gleichzeitige Verbindungen blockieren';
$string['onesession:allowchange'] = 'Einen Verbindungswechsel für einen Testversuch erlauben';
$string['onesession:editenabled'] = 'Kontrollieren, ob "Gleichzeitige Verbindungen blockieren" gesetzt werden kann';
$string['onesession_help'] = 'Wenn aktiviert, können Benutzer einen Testversuch nur in derselben Browser-Sitzung fortsetzen. Jeder Versuch, denselben Testversuch mit einem anderen Computer, Gerät oder Browser zu öffnen, wird blockiert.';
$string['pluginname'] = 'Testzugriffsregel: Gleichzeitige Sitzungen blockieren';
$string['privacy:metadata'] = 'Das Plugin speichert den Hash eines Strings zur Identifizierung der Client-Gerätesitzung. Es protokolliert auch, wenn eine Lehrperson einen Verbindungswechsel für den Versuch eines Studierenden erlaubt.';
$string['studentinfo'] = 'Achtung! Es ist verboten, das Gerät während dieses Tests zu wechseln. Bitte beachten Sie, dass nach Beginn des Testversuchs alle Verbindungen zu diesem Test mit anderen Computern, Geräten und Browsern blockiert werden. Schließen Sie das Browserfenster nicht vor dem Ende des Versuchs, da Sie den Test sonst nicht abschließen können.';
$string['whitelist'] = 'Netzwerke ohne IP-Prüfung';
$string['whitelist_desc'] = 'Diese Option soll Fehlalarme reduzieren, wenn Benutzer Tests über Mobilfunknetze durchführen, bei denen sich die IP-Adresse während des Tests ändern kann. In den meisten Situationen ist dies nicht erforderlich.';

// New strings for the report page.
$string['allowconnections'] = 'Verbindungswechsel erlauben';
$string['allowchange'] = 'Wechsel erlauben';
$string['allowchangeinconnection'] = 'Verbindungswechsel für ausgewählte Versuche erlauben';
$string['changeallowed'] = 'Wechsel erlaubt';
$string['changeinconnection'] = 'Verbindungswechsel';
$string['notpossible'] = 'Nicht möglich';
$string['statusattempt'] = 'Status des Versuchs';
$string['unlocksuccess'] = 'Verbindungswechsel für {$a} Versuch(e) erlaubt.';
$string['unlockedbyon'] = 'Erlaubt von {$a->teacher} am {$a->time}';

// Privacy provider strings.
$string['privacy:metadata:log'] = 'Speichert einen Datensatz darüber, welcher Benutzer einen Verbindungswechsel für einen Testversuch erlaubt hat und wann dies geschah.';
$string['privacy:metadata:log:unlockedby'] = 'Die ID des Benutzers (typischerweise eine Lehrperson oder Prüfungsaufsicht), der den Verbindungswechsel erlaubt hat.';