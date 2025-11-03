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
 * Inactive user cleanup library
 *
 * @package    tool_inactive_user_cleanup
 * @copyright  DualCube (https://dualcube.com)
 * @author     DualCube <admin@dualcube.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_inactive_user_cleanup\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

/**
 * Privacy provider for tool_inactive_user_cleanup
 *
 * @package   tool_inactive_user_cleanup
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe data stored in the plugin's database tables.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_inactive_user_cleanup',
            [
                'userid' => 'privacy:metadata:tool_inactive_user_cleanup:userid',
                'emailsent' => 'privacy:metadata:tool_inactive_user_cleanup:emailsent',
                'date' => 'privacy:metadata:tool_inactive_user_cleanup:date',
            ],
            'privacy:metadata:tool_inactive_user_cleanup'
        );
        return $collection;
    }

    /**
     * Delete all user data for all users in a given context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }
        $DB->delete_records('tool_inactive_user_cleanup', ['userid' => $context->instanceid]);
    }

    /**
     * Delete all user data for a specific user across approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $DB->delete_records('tool_inactive_user_cleanup', ['userid' => $userid]);
    }

    /**
     * Delete user data for multiple users.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        [$sql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $DB->delete_records_select('tool_inactive_user_cleanup', "userid {$sql}", $params);
    }

    /**
     * Return the contexts that contain user information for a given user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {tool_inactive_user_cleanup} t ON t.userid = c.instanceid
                 WHERE c.contextlevel = :contextlevel
                   AND t.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_USER,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export user data for a specific user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $records = $DB->get_records('tool_inactive_user_cleanup', ['userid' => $userid]);

        foreach ($contextlist->get_contexts() as $context) {
            $subcontext = [get_string('pluginname', 'tool_inactive_user_cleanup')];
            foreach ($records as $record) {
                $data = (object)[
                    'emailsent' => $record->emailsent,
                    'date' => $record->date ? transform::datetime($record->date) : null,
                ];
                writer::with_context($context)->export_data($subcontext, $data);
            }
        }
    }

    /**
     * List users who have data in this context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }

        $sql = "SELECT userid
                  FROM {tool_inactive_user_cleanup}
                 WHERE userid = :userid";
        $params = ['userid' => $context->instanceid];

        $userlist->add_from_sql('userid', $sql, $params);
    }
}
