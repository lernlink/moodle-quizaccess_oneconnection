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
 * Privacy provider for the onesession quiz access rule.
 *
 * @package    quizaccess_onesession
 * @copyright  2021 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_onesession\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as privacy_provider;
use core_privacy\local\request\context;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_user;

/**
 * Privacy provider for the onesession quiz access rule.
 *
 * @package    quizaccess_onesession
 */
class provider implements privacy_provider
{

    /**
     * Returns metadata about the data stored by this plugin.
     *
     * @param collection $collection The collection to add metadata to.
     * @return collection The collection with metadata added.
     */
    public static function get_metadata(collection $collection): collection
    {
        $collection->add_database_table(
            'quizaccess_onesession_log',
            [
                'unlockedby' => 'privacy:metadata:log:unlockedby',
            ],
            'privacy:metadata:log'
        );
        return $collection;
    }

    /**
     * Get the list of contexts that contain user data for the specified user.
     *
     * @param   int           $userid The user to search.
     * @return  approved_contextlist  A list of contexts that may contain user data.
     */
    public static function get_contexts_for_user(int $userid): approved_contextlist
    {
        global $DB;

        // The constructor requires the full user object.
        $user = core_user::get_user($userid, '*', MUST_EXIST);

        // This query now directly fetches the context IDs, not the course module IDs.
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {quiz} q ON q.id = cm.instance
                  JOIN {quizaccess_onesession_log} qol ON qol.quizid = q.id
                 WHERE ctx.contextlevel = :contextlevel
                   AND qol.unlockedby = :userid";

        $params = [
            'modname' => 'quiz',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        // The result is now an array of integer IDs, as required by the constructor.
        $contextids = array_values($DB->get_fieldset_sql($sql, $params));

        // CORRECTED: The constructor requires 3 arguments: user object, component name, and an array of context IDs.
        return new approved_contextlist($user, 'mod_quiz', $contextids);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The list of contexts to export data from.
     */
    public static function export_user_data(approved_contextlist $contextlist)
    {
        // Not implemented, as this data is not considered exportable user content.
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context $context    The context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(context $context)
    {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id(null, $context->instanceid);
        if (!$cm || $cm->modname !== 'quiz') {
            return;
        }

        $quizid = $cm->instance;
        $DB->delete_records('quizaccess_onesession_log', ['quizid' => $quizid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_userlist $userlist The approved list of users and contexts.
     */
    public static function delete_data_for_user(approved_userlist $userlist)
    {
        global $DB;

        $userids = $userlist->get_userids();
        foreach ($userids as $userid) {
            $DB->delete_records('quizaccess_onesession_log', ['unlockedby' => $userid]);
        }
    }
}