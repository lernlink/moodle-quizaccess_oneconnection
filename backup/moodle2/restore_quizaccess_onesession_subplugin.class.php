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
 * Restore handler for the onesession quiz access plugin.
 *
 * @package     quizaccess_onesession
 * @category    backup
 * @copyright   2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/restore_mod_quiz_access_subplugin.class.php');

/**
 * Provides the information to restore the onesession quiz access plugin.
 */
class restore_quizaccess_onesession_subplugin extends restore_mod_quiz_access_subplugin
{

    /**
     * Describe the XML paths that store the subplugin's data.
     *
     * @return array
     */
    protected function define_quiz_subplugin_structure()
    {
        $paths = [];
        $paths[] = new restore_path_element('quizaccess_onesession', $this->get_pathfor('/quizaccess_onesession'));
        $paths[] = new restore_path_element('log', $this->get_pathfor('/logs/log'));
        return $paths;
    }

    /**
     * Restore the main plugin setting.
     *
     * @param array $data Data read from the XML file.
     * @return void
     */
    public function process_quizaccess_onesession($data): void
    {
        global $DB;

        $data = (object) $data;
        $data->quizid = $this->get_new_parentid('quiz');
        $DB->insert_record('quizaccess_onesession', $data);
    }

    /**
     * Restore a single audit log entry.
     *
     * @param array $data Data read from the XML file.
     * @return void
     */
    public function process_log($data): void
    {
        global $DB;
        $data = (object) $data;
        $data->quizid = $this->get_new_parentid('quiz');
        $data->attemptid = $this->get_mappingid('quiz_attempt', $data->attemptid);
        $data->unlockedby = $this->get_mappingid('user', $data->unlockedby);
        $DB->insert_record('quizaccess_onesession_log', $data);
    }
}
