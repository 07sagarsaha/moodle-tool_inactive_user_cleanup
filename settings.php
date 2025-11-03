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
 * tool_inactive_user_cleanup setting file
 *
 * @package    tool_inactive_user_cleanup
 * @copyright  DualCube (https://dualcube.com)
 * @author     DualCube <admin@dualcube.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'tool_inactive_user_cleanup',
        get_string('pluginname', 'tool_inactive_user_cleanup')
    );

    // Days of inactivity.
    $settings->add(new admin_setting_configtext(
        'tool_inactive_user_cleanup/daysofinactivity',
        get_string('daysofinactivity', 'tool_inactive_user_cleanup'),
        '',
        365,
        PARAM_INT
    ));

    // Days before deletion.
    $settings->add(new admin_setting_configtext(
        'tool_inactive_user_cleanup/daysbeforedeletion',
        get_string('daysbeforedeletion', 'tool_inactive_user_cleanup'),
        '',
        10,
        PARAM_INT
    ));

    // Excluded roles.
    $roles = role_get_names(\context_system::instance());
    $roleoptions = [];
    foreach ($roles as $role) {
        $roleoptions[$role->id] = $role->localname;
    }

    $settings->add(new admin_setting_configmulticheckbox(
        'tool_inactive_user_cleanup/excludedroles',
        get_string('excludedroles', 'tool_inactive_user_cleanup'),
        get_string('excludedroles_desc', 'tool_inactive_user_cleanup'),
        [],
        $roleoptions
    ));


    // Email subject.
    $settings->add(new admin_setting_configtext(
        'tool_inactive_user_cleanup/emailsubject',
        get_string('emailsubject', 'tool_inactive_user_cleanup'),
        '',
        get_string('emailsubject_default', 'tool_inactive_user_cleanup'),
        PARAM_TEXT
    ));

    // Email body.
    $settings->add(new admin_setting_confightmleditor(
        'tool_inactive_user_cleanup/emailbody',
        get_string('emailbody', 'tool_inactive_user_cleanup'),
        '',
        ''
    ));

    $ADMIN->add('tools', $settings);
}
