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
 * setting form display and set config variables
 *
 * @package    tool_inactive_user_cleanup
 * @copyright  DualCube (https://dualcube.com)
 * @author     DualCube <admin@dualcube.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/inactive_user_cleanup/settings_form.php');

require_login();

admin_externalpage_setup('toolinactive_user_cleanup');

$settingsform = new tool_inactive_user_cleanup_config_form();

// Handle form submission.
if ($fromdata = $settingsform->get_data()) {
    set_config('daysbeforedeletion', $fromdata->config_daysbeforedeletion, 'tool_inactive_user_cleanup');
    set_config('daysofinactivity', $fromdata->config_daysofinactivity, 'tool_inactive_user_cleanup');
    set_config('emailsubject', $fromdata->config_subjectemail, 'tool_inactive_user_cleanup');
    set_config('emailbody', $fromdata->config_bodyemail['text'], 'tool_inactive_user_cleanup');

    // Save excluded roles and cohorts if provided.
    if (isset($fromdata->config_excludedroles)) {
        $excludedroles = is_array($fromdata->config_excludedroles)
            ? implode(',', $fromdata->config_excludedroles)
            : $fromdata->config_excludedroles;
        set_config('excludedroles', $excludedroles, 'tool_inactive_user_cleanup');
    }

    if (isset($fromdata->config_excludecohorts)) {
        $excludecohorts = is_array($fromdata->config_excludecohorts)
            ? implode(',', $fromdata->config_excludecohorts)
            : $fromdata->config_excludecohorts;
        set_config('excludecohorts', $excludecohorts, 'tool_inactive_user_cleanup');
    }

    redirect(new moodle_url('/admin/tool/inactive_user_cleanup/index.php'),
             get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Load existing configuration.
$configdata = get_config('tool_inactive_user_cleanup');
$data = new stdClass();
$data->config_daysbeforedeletion = $configdata->daysbeforedeletion ?? 10;
$data->config_daysofinactivity = $configdata->daysofinactivity ?? 365;
$data->config_subjectemail = $configdata->emailsubject ?? '';
$data->config_bodyemail['text'] = $configdata->emailbody ?? '';

if (!empty($configdata->excludedroles)) {
    $data->config_excludedroles = explode(',', $configdata->excludedroles);
}

if (!empty($configdata->excludecohorts)) {
    $data->config_excludecohorts = explode(',', $configdata->excludecohorts);
}

$settingsform->set_data($data);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_inactive_user_cleanup'));
$settingsform->display();
echo $OUTPUT->footer();
