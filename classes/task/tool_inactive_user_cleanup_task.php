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
 * The Inactive user cleanup
 *
 * @package    tool_inactive_user_cleanup
 * @copyright  DualCube (https://dualcube.com)
 * @author     DualCube <admin@dualcube.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_inactive_user_cleanup\task;
/**
 * Scheduled task for Inactive user cleanup.
 *
 * @copyright DualCube (https://dualcube.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_inactive_user_cleanup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'tool_inactive_user_cleanup');
    }

     /**
      * Execute.
      */
    public function execute() {
        global $DB;

        mtrace(get_string('taskstart', 'tool_inactive_user_cleanup'));

        $beforedelete = get_config('tool_inactive_user_cleanup', 'daysbeforedeletion');
        $inactivity = get_config('tool_inactive_user_cleanup', 'daysofinactivity');

        if (empty($inactivity)) {
            mtrace(get_string('invalaliddayofinactivity', 'tool_inactive_user_cleanup'));
            return;
        }

        $subject = get_config('tool_inactive_user_cleanup', 'emailsubject');
        $body = get_config('tool_inactive_user_cleanup', 'emailbody');
        $messagetext = html_to_text($body);

        // Decode excluded roles and cohorts (serialized arrays).
        $excludedroles = get_config('tool_inactive_user_cleanup', 'excludedroles');
        $excludedroles = $excludedroles ? unserialize($excludedroles) : [];

        $users = $DB->get_records('user', ['deleted' => 0]);

        foreach ($users as $user) {
            if (isguestuser($user->id) || is_siteadmin($user->id)) {
                continue;
            }

            if ($this->is_user_excluded($user->id, $excludedroles)) {
                continue;
            }

            $daysinactive = round((time() - $user->lastaccess) / 86400);
            $record = $DB->get_record('tool_inactive_user_cleanup', ['userid' => $user->id]);

            if ($record && $user->lastaccess > $record->date) {
                $DB->delete_records('tool_inactive_user_cleanup', ['userid' => $user->id]);
                mtrace("User {$user->id} reactivated after warning, cleanup record removed.");
                continue; // Don't delete or warn them again now.
            }

            // Send notification if user is inactive and not yet warned.
            if ($daysinactive > $inactivity && !$record && $user->lastaccess != 0) {
                if ($this->send_inactivity_notification($user, $subject, $messagetext, $body)) {
                    $newrecord = new \stdClass();
                    $newrecord->userid = $user->id;
                    $newrecord->emailsent = 1;
                    $newrecord->date = time();
                    $DB->insert_record('tool_inactive_user_cleanup', $newrecord, false);
                    mtrace("Notified user {$user->id} ({$user->email}) after {$daysinactive} days of inactivity.");
                }
            }

            // Delete after grace period (only if user hasn’t logged in again).
            if ($beforedelete && $record) {
                $dayssinceemail = round((time() - $record->date) / 86400);
                if ($dayssinceemail > $beforedelete && $user->lastaccess < $record->date) {
                    delete_user($user);
                    mtrace("Deleted user {$user->id} after {$dayssinceemail} days since warning (no login detected).");
                }
            }
            mtrace(get_string('taskend', 'tool_inactive_user_cleanup'));
        }
    }

    private function send_inactivity_notification($user, $subject, $messagetext, $messagehtml) {
        $message = new \core\message\message();
        $message->component = 'tool_inactive_user_cleanup';
        $message->name = 'inactivitywarning';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $messagetext;
        $message->fullmessageformat = FORMAT_HTML;
        $message->fullmessagehtml = $messagehtml;
        $message->smallmessage = $subject;
        $message->notification = 1;

        return message_send($message);
    }

    private function is_user_excluded($userid, $excludedroles) {
        global $DB;

        // Check excluded roles.
        if (!empty($excludedroles)) {
            list($rolesql, $roleparams) = $DB->get_in_or_equal($excludedroles, SQL_PARAMS_NAMED);
            $roleparams['userid'] = $userid;
            $sql = "SELECT 1 FROM {role_assignments} WHERE userid = :userid AND roleid $rolesql";
            if ($DB->record_exists_sql($sql, $roleparams)) {
                return true;
            }
        }

        return false;
    }
}
