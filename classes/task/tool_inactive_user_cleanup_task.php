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
        global $DB, $CFG;

        mtrace(get_string('taskstart', 'tool_inactive_user_cleanup'));

        $beforedelete = get_config('tool_inactive_user_cleanup', 'daysbeforedeletion');
        $inactivity = get_config('tool_inactive_user_cleanup', 'daysofinactivity');

        if ($inactivity == 0) {
            mtrace(get_string('invalaliddayofinactivity', 'tool_inactive_user_cleanup'));
            return;
        }

        $subject = get_config('tool_inactive_user_cleanup', 'emailsubject');
        $body = get_config('tool_inactive_user_cleanup', 'emailbody');
        $excludedroles = get_config('tool_inactive_user_cleanup', 'excludedroles');
        $excludecohorts = get_config('tool_inactive_user_cleanup', 'excludecohorts');

        // Get all non-deleted users.
        $users = $DB->get_records('user', ['deleted' => '0']);
        $messagetext = html_to_text($body);
        $mainadminuser = get_admin();

        foreach ($users as $usersdetails) {
            // Skip guest user and admin.
            if (isguestuser($usersdetails->id) || is_siteadmin($usersdetails->id)) {
                continue;
            }

            // Check if user should be excluded by role.
            if ($this->is_user_excluded($usersdetails->id, $excludedroles, $excludecohorts)) {
                continue;
            }

            $minus = round((time() - $usersdetails->lastaccess) / 60 / 60 / 24);
            $ischeck = $DB->get_record('tool_inactive_user_cleanup', ['userid' => $usersdetails->id]);
            $record = new \stdClass();
            $record->userid = $usersdetails->id;

            // Send notification if user is inactive and hasn't been notified yet.
            if ($minus > $inactivity && !$ischeck && $usersdetails->lastaccess != 0) {
                // Use messaging system instead of direct email.
                if ($this->send_inactivity_notification($usersdetails, $subject, $messagetext, $body)) {
                    mtrace(get_string('userid', 'tool_inactive_user_cleanup'));
                    mtrace($usersdetails->id . '---' . $usersdetails->email);
                    mtrace(get_string('userinactivtime', 'tool_inactive_user_cleanup') . $minus);
                    mtrace('');
                    $record->emailsent = 1;
                    $record->date = time();
                    $DB->insert_record('tool_inactive_user_cleanup', $record, false);
                }
            }

            // Delete user if grace period has passed.
            if ($beforedelete != 0 && $usersdetails->lastaccess != 0) {
                $deleteuserafternotify = $DB->get_record('tool_inactive_user_cleanup', ['userid' => $usersdetails->id]);
                if ($deleteuserafternotify) {
                    $beforedelete = get_config('tool_inactive_user_cleanup', 'daysbeforedeletion');
                    $mailssent = $deleteuserafternotify->date;
                    $diff = round((time() - $mailssent) / 60 / 60 / 24);
                    if (!empty($deleteuserafternotify) && $diff > $beforedelete) {
                        delete_user($usersdetails);
                        mtrace(get_string('deleteduser', 'tool_inactive_user_cleanup') . $usersdetails->id);
                        mtrace(get_string('detetsuccess', 'tool_inactive_user_cleanup'));
                    }
                }
            }
        }

        mtrace(get_string('taskend', 'tool_inactive_user_cleanup'));
    }

    /**
     * Send inactivity notification using Moodle messaging system.
     *
     * @param \stdClass $user User object
     * @param string $subject Message subject
     * @param string $messagetext Plain text message
     * @param string $messagehtml HTML message
     * @return bool True if message was sent successfully
     */
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

    /**
     * Check if user should be excluded from cleanup.
     *
     * @param int $userid User ID
     * @param string $excludedroles Comma-separated list of role IDs to exclude
     * @param string $excludecohorts Comma-separated list of cohort IDs to exclude
     * @return bool True if user should be excluded
     */
    private function is_user_excluded($userid, $excludedroles, $excludecohorts) {
        global $DB;

        // Check excluded roles.
        if (!empty($excludedroles)) {
            $roleids = explode(',', $excludedroles);
            $roleids = array_filter(array_map('trim', $roleids));

            if (!empty($roleids)) {
                list($rolesql, $roleparams) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
                $sql = "SELECT DISTINCT ra.userid
                          FROM {role_assignments} ra
                         WHERE ra.userid = :userid
                           AND ra.roleid $rolesql";
                $roleparams['userid'] = $userid;

                if ($DB->record_exists_sql($sql, $roleparams)) {
                    return true;
                }
            }
        }

        // Check excluded cohorts.
        if (!empty($excludecohorts)) {
            $cohortids = explode(',', $excludecohorts);
            $cohortids = array_filter(array_map('trim', $cohortids));

            if (!empty($cohortids)) {
                list($cohortsql, $cohortparams) = $DB->get_in_or_equal($cohortids, SQL_PARAMS_NAMED);
                $sql = "SELECT DISTINCT cm.userid
                          FROM {cohort_members} cm
                         WHERE cm.userid = :userid
                           AND cm.cohortid $cohortsql";
                $cohortparams['userid'] = $userid;

                if ($DB->record_exists_sql($sql, $cohortparams)) {
                    return true;
                }
            }
        }

        return false;
    }
}
