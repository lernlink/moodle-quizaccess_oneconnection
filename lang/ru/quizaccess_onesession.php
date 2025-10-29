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

$string['anothersession'] = 'Вы пытаетесь получить доступ к этой попытке теста с другого устройства или браузера, а не с того, с которого начали. Если вам необходимо сменить устройство, обратитесь к экзаменатору.';
$string['eventattemptblocked'] = 'Попытка студента продолжить тестирование используя другое устройство была заблокирована';
$string['eventattemptunlocked'] = 'Студенту было разрешено продолжить попытку тестирования используя другое устройство';
$string['onesession'] = 'Блокировать одновременные подключения';
$string['onesession:allowchange'] = 'Разрешить смену подключения для попытки теста';
$string['onesession:editenabled'] = 'Управлять возможностью установки "Блокировать одновременные подключения"';
$string['onesession_help'] = 'Если включено, пользователи могут продолжать попытку теста только в том же сеансе браузера. Любые попытки открыть ту же самую попытку теста с другого компьютера, устройства или браузера будут заблокированы.';
$string['pluginname'] = 'Правило доступа к тесту: блокировка одновременных подключений';
$string['privacy:metadata'] = 'Плагин сохраняет хеш строки для идентификации сессии клиентского устройства. Он также регистрирует, когда преподаватель разрешает смену подключения для попытки студента.';
$string['studentinfo'] = 'Внимание! Запрещено менять устройство во время прохождения этого теста. После начала тестирования любые попытки подключиться к этому тесту с другого компьютера, устройства или браузера будут блокироваться. Не закрывайте окно браузера до окончания тестирования, иначе вы не сможете завершить тест.';
$string['whitelist'] = 'Сети без проверки IP';
$string['whitelist_desc'] = 'Этот параметр предназначен для уменьшения количества ложных срабатываний, когда студенты подключаются через мобильные сети, где IP-адрес может измениться в процессе тестирования. В большинстве ситуаций заполнять этот параметр не требуется.';

// New strings for the report page.
$string['allowconnections'] = 'Разрешить смену подключений';
$string['allowchange'] = 'Разрешить смену';
$string['allowchangeinconnection'] = 'Разрешить смену подключения для выбранных попыток';
$string['changeallowed'] = 'Смена разрешена';
$string['changeinconnection'] = 'Смена подключения';
$string['notpossible'] = 'Невозможно';
$string['statusattempt'] = 'Статус попытки';
$string['unlocksuccess'] = 'Смена подключения разрешена для {$a} попыток.';
$string['unlockedbyon'] = 'Разрешено {$a->teacher} в {$a->time}';

// Privacy provider strings.
$string['privacy:metadata:log'] = 'Сохраняет запись о том, какой пользователь разрешил смену подключения для попытки теста и когда это произошло.';
$string['privacy:metadata:log:unlockedby'] = 'ID пользователя (обычно преподавателя или экзаменатора), который разрешил смену подключения.';