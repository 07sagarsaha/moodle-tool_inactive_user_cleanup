<?php
namespace tool_inactive_user_cleanup\task;

class tool_inactive_user_cleanup_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('pluginname', 'tool_inactive_user_cleanup');
    }

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

        $excludecohorts = get_config('tool_inactive_user_cleanup', 'excludecohorts');
        $excludecohorts = $excludecohorts ? unserialize($excludecohorts) : [];

        $users = $DB->get_records('user', ['deleted' => 0]);

        foreach ($users as $user) {
            if (isguestuser($user->id) || is_siteadmin($user->id)) {
                continue;
            }
        
            if ($this->is_user_excluded($user->id, $excludedroles, $excludecohorts)) {
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

    private function is_user_excluded($userid, $excludedroles, $excludecohorts) {
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

        // Check excluded cohorts.
        if (!empty($excludecohorts)) {
            list($cohortsql, $cohortparams) = $DB->get_in_or_equal($excludecohorts, SQL_PARAMS_NAMED);
            $cohortparams['userid'] = $userid;
            $sql = "SELECT 1 FROM {cohort_members} WHERE userid = :userid AND cohortid $cohortsql";
            if ($DB->record_exists_sql($sql, $cohortparams)) {
                return true;
            }
        }

        return false;
    }
}
