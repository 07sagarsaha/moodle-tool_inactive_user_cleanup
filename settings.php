<?php
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

    // Excluded roles (all checkboxes in one line).
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

    // Excluded cohorts (all checkboxes in one line).
    $cohorts = $DB->get_records_menu('cohort', null, 'name', 'id, name');
    if (!empty($cohorts)) {
        $settings->add(new admin_setting_configmulticheckbox(
            'tool_inactive_user_cleanup/excludecohorts',
            get_string('excludecohorts', 'tool_inactive_user_cleanup'),
            get_string('excludecohorts_desc', 'tool_inactive_user_cleanup'),
            [],
            $cohorts
        ));
    }

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
