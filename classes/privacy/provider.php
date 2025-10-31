<?php
// This file is part of Moodle - http://moodle.org/.
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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_onesession\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\userlist;
use core_privacy\local\request\core_userlist_provider;

/**
 * Privacy provider.
 */
class provider implements metadata_provider, plugin_provider, core_userlist_provider
{
    /**
     * Describe stored data.
     *
     * @param collection $collection
     * @return collection
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
     * Return contexts containing user data for this plugin.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist
    {
        global $DB;

        $contextlist = new contextlist();

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

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export user data (none – audit data only).
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist)
    {
        // Intentionally nothing – this is teacher/invigilator audit.
    }

    /**
     * Delete all data for all users in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context)
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
     * Delete data for specific users in a context list.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist)
    {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id(null, $context->instanceid);
            if (!$cm || $cm->modname !== 'quiz') {
                continue;
            }

            $quizid = $cm->instance;
            $DB->delete_records('quizaccess_onesession_log', [
                'quizid' => $quizid,
                'unlockedby' => $userid,
            ]);
        }
    }

    /**
     * Add users who have data in this context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist)
    {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id(null, $context->instanceid);
        if (!$cm || $cm->modname !== 'quiz') {
            return;
        }

        $quizid = $cm->instance;

        $sql = "SELECT DISTINCT unlockedby
                  FROM {quizaccess_onesession_log}
                 WHERE quizid = :quizid";
        $params = ['quizid' => $quizid];

        $userlist->add_from_sql('unlockedby', $sql, $params);
    }

    /**
     * Delete data for users in userlist.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist)
    {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id(null, $context->instanceid);
        if (!$cm || $cm->modname !== 'quiz') {
            return;
        }

        $quizid = $cm->instance;
        $userids = $userlist->get_userids();

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = ['quizid' => $quizid] + $inparams;

        $DB->delete_records_select(
            'quizaccess_onesession_log',
            "quizid = :quizid AND unlockedby $insql",
            $params
        );
    }
}
