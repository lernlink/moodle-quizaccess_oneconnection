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
 * Provides the information to backup the onesession quiz access plugin.
 *
 * @package    quizaccess_onesession
 * @copyright  2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/backup_mod_quiz_access_subplugin.class.php');

/**
 * Provides the information to backup the onesession quiz access plugin.
 *
 * If this plugin is required, a <quizaccess_onesession> tag
 * will be added to the XML in the appropriate place. Otherwise nothing will be
 * added.
 *
 * @package    quizaccess_onesession
 * @copyright  2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_quizaccess_onesession_subplugin extends backup_mod_quiz_access_subplugin
{

    protected function define_quiz_subplugin_structure()
    {

        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $subplugin->add_child($wrapper);

        // Backup the main setting table.
        $settings = new backup_nested_element('quizaccess_onesession', null, ['enabled']);
        $wrapper->add_child($settings);
        $settings->set_source_table('quizaccess_onesession', ['quizid' => backup::VAR_ACTIVITYID]);

        // Backup the log table.
        $logs = new backup_nested_element('logs');
        $wrapper->add_child($logs);

        $log = new backup_nested_element('log', ['id'], ['attemptid', 'unlockedby', 'timeunlocked']);
        $logs->add_child($log);
        $log->set_source_table('quizaccess_onesession_log', ['quizid' => backup::VAR_ACTIVITYID]);

        // CORRECTED: The annotate_ids() method is called on the parent element ($log)
        // that contains the fields to be annotated. The second parameter is the field name.
        $log->annotate_ids('quiz_attempt', 'attemptid');
        $log->annotate_ids('user', 'unlockedby');

        return $subplugin;
    }
}
