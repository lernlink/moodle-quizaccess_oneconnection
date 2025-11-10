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
 * Russian language strings for quizaccess_oneconnection.
 *
 * @package    quizaccess_oneconnection
 * @copyright  2016 Vadim Dvorovenko
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allowchange'] = 'Разрешить смену';
$string['allowchangeinconnection'] = 'Разрешить смену подключения для выбранных попыток';
$string['allowconnections'] = 'Разрешить смену подключений';
$string['allowedbyon'] = 'Разрешено {$a->fullname} в {$a->time}';
$string['anothersession'] = 'Вы пытаетесь получить доступ к этой попытке теста с другого устройства или браузера, а не с того, с которого начали. Если вам необходимо сменить устройство, обратитесь к экзаменатору.';
$string['attemptsfrom'] = 'Попытки от';
$string['attemptsfrom_allattempts'] = 'Все пользователи, у которых есть попытка теста';
$string['attemptsfrom_enrolledall'] = 'Зачисленные пользователи, у которых есть или нет попытки теста';
$string['attemptsfrom_enrolledattempts'] = 'Зачисленные пользователи, у которых есть попытка теста';
$string['attemptsfrom_enrollednoattempts'] = 'Зачисленные пользователи, у которых нет попытки теста';
$string['attemptsthat'] = 'Попытки со статусом';
$string['changeallowed'] = 'Смена разрешена';
$string['changeinconnection'] = 'Смена подключения';
$string['displayoptions'] = 'Параметры отображения';
$string['eventattemptblocked'] = 'Попытка студента продолжить тестирование, используя другое устройство, была заблокирована';
$string['eventattemptunlocked'] = 'Студенту было разрешено продолжить попытку тестирования, используя другое устройство';
$string['filterattemptsfrom'] = 'Попытки от';
$string['filterattemptsthat'] = 'Попытки со статусом';
$string['filterenrolledwithattempts'] = 'Зачисленные пользователи, у которых есть попытка теста';
$string['filterheading'] = 'Что включить в отчёт';
$string['notpossible'] = 'Невозможно';
$string['oneconnection'] = 'Блокировать одновременные подключения';
$string['oneconnection:allowchange'] = 'Разрешить смену подключения для попытки теста';
$string['oneconnection:editenabled'] = 'Управлять возможностью установки «Блокировать одновременные подключения»';
$string['oneconnection_help'] = 'Если включено, пользователи могут продолжать попытку теста только в том же сеансе браузера. Любые попытки открыть ту же самую попытку теста с другого компьютера, устройства или браузера будут заблокированы.';
$string['pagesize'] = 'Размер страницы';
$string['pluginname'] = 'Правило доступа к тесту: блокировка одновременных подключений';
$string['privacy:metadata'] = 'Плагин сохраняет хеш строки для идентификации сессии клиентского устройства. Он также регистрирует, когда преподаватель разрешает смену подключения для попытки студента.';
$string['privacy:metadata:log'] = 'Сохраняет запись о том, какой пользователь разрешил смену подключения для попытки теста и когда это произошло.';
$string['privacy:metadata:log:unlockedby'] = 'ID пользователя (обычно преподавателя или экзаменатора), который разрешил смену подключения.';
$string['settingsintro'] = 'Настройте поведение правила «Блокировать одновременные подключения»: можно включить его по умолчанию для новых тестов и указать IP-сети, которые не нужно учитывать при проверке сессии.';
$string['showreport'] = 'Показать отчёт';
$string['state_abandoned'] = 'Не отправлена';
$string['state_finished'] = 'Завершена';
$string['state_inprogress'] = 'В процессе';
$string['state_notstarted'] = 'Не начата';
$string['state_overdue'] = 'Просрочена';
$string['state_submitted'] = 'Отправлена';
$string['statusattempt'] = 'Статус попытки';
$string['studentinfo'] = 'Внимание! Запрещено менять устройство во время прохождения этого теста. После начала тестирования любые попытки подключиться к этому тесту с другого компьютера, устройства или браузера будут блокироваться. Не закрывайте окно браузера до окончания тестирования, иначе вы не сможете завершить тест.';
$string['unlockedbyon'] = 'Разрешено {$a->teacher} в {$a->time}';
$string['unlocksuccess'] = 'Смена подключения разрешена для {$a} попыток.';
$string['whattoincludeinreport'] = 'Что включить в отчёт';
$string['whitelist'] = 'Сети без проверки IP';
$string['whitelist_desc'] = 'Этот параметр предназначен для уменьшения количества ложных срабатываний, когда студенты подключаются через мобильные сети, где IP-адрес может измениться в процессе тестирования. В большинстве ситуаций заполнять этот параметр не требуется.';
