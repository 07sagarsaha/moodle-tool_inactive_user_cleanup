# Inactive User Cleanup - Moodle Admin Tool

[![Moodle Plugin](https://img.shields.io/badge/Moodle-5.0-orange.svg)](https://moodle.org)
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/copyleft/gpl.html)

## Description

The Inactive User Cleanup plugin automatically manages inactive user accounts in your Moodle site. It identifies users who haven't logged in for a specified period, sends them warning notifications through Moodle's messaging system, and optionally deletes their accounts after a grace period.

**Key Features:**
- Automatic detection of inactive users based on configurable inactivity period
- Warning notifications sent via Moodle messaging system (respects user preferences)
- Configurable grace period before account deletion
- Exclude specific users by role (e.g., teachers, managers)
- Exclude users in specific cohorts
- GDPR compliant with privacy API implementation
- Scheduled task runs automatically via Moodle cron

## Requirements

- Moodle 5.0 or higher
- PHP 8.1 or higher

## Installation

### Method 1: Via Moodle Plugin Installer
1. Download the plugin ZIP file
2. Go to **Site administration > Plugins > Install plugins**
3. Upload or drag & drop the downloaded ZIP file
4. Click "Install plugin from the ZIP file"
5. Follow the on-screen instructions

### Method 2: Manual Installation
1. Extract the ZIP file
2. Copy the `inactive_user_cleanup` folder to `/admin/tool/` directory
3. Visit **Site administration > Notifications** to complete the installation

## Configuration

### Access Settings
Navigate to: **Site administration > Plugins > Admin tools > Inactive User Cleanup > Settings**

Or: **Site administration > Reports > Inactive User Cleanup**

### Settings Options

#### General Settings
- **Days of inactivity**: Number of days after which a user is considered inactive (default: 365)
  - Set to "0" to disable the cleanup process
  - Users who haven't logged in for this many days will receive a warning notification

- **Days before deletion**: Grace period after notification before account deletion (default: 10)
  - Set to "0" to disable automatic deletion (only send warnings)
  - After this period, inactive accounts will be permanently deleted

#### Exclusion Settings
- **Exclude users with roles**: Select roles to exclude from cleanup (e.g., Manager, Teacher)
  - Users with any of the selected roles will never be cleaned up
  - Recommended: Exclude administrative and teaching roles

- **Exclude users in cohorts**: Select cohorts to exclude from cleanup
  - Users in any of the selected cohorts will be protected from cleanup
  - Useful for special user groups

#### Email Settings
- **Subject**: Customize the notification email subject
- **Body**: Customize the notification email body (HTML supported)
  - Use the editor to format your message
  - Include information about the inactivity period and deletion date

## How It Works

1. **Detection**: The scheduled task runs daily (by default at 23:59)
2. **Identification**: Identifies users inactive for the configured period
3. **Exclusion Check**: Skips users who are:
   - Guest users
   - Site administrators
   - Have excluded roles
   - Are in excluded cohorts
4. **Notification**: Sends warning message via Moodle messaging system
5. **Grace Period**: Waits for the configured "days before deletion"
6. **Deletion**: If user remains inactive, account is deleted

## Messaging System Integration

This plugin uses Moodle's built-in messaging system, which means:
- Users can control how they receive notifications (email, popup, mobile, etc.)
- Respects user messaging preferences at `/admin/message.php`
- Complies with site-wide messaging settings
- Messages are logged in Moodle's message history

## Privacy and GDPR Compliance

The plugin implements Moodle's Privacy API:
- Stores minimal user data (user ID, notification date, email sent status)
- Provides data export functionality
- Supports data deletion requests
- Complies with GDPR requirements

## Scheduled Task

The cleanup task runs automatically via Moodle cron:
- **Default schedule**: Daily at 23:59
- **Task name**: Inactive user cleanup task
- **Configuration**: Site administration > Server > Scheduled tasks

To modify the schedule:
1. Go to **Site administration > Server > Scheduled tasks**
2. Find "Inactive user cleanup task"
3. Click the edit icon
4. Adjust the schedule as needed

## Troubleshooting

### Users not receiving notifications
- Check Moodle messaging is enabled: **Site administration > Messaging > Notification settings**
- Verify user messaging preferences
- Check cron is running regularly
- Review scheduled task logs

### Settings not saving
- Ensure you have `moodle/site:config` capability
- Check for JavaScript errors in browser console
- Verify file permissions on Moodle data directory

### Unexpected deletions
- Review excluded roles and cohorts settings
- Check the inactivity period configuration
- Review scheduled task execution logs

## Support

For issues, questions, or feature requests:
- GitHub Issues: [Create an issue](https://github.com/dualcube/moodle-tool_inactive_user_cleanup/issues)
- Email: admin@dualcube.com

## License

This plugin is licensed under the GNU GPL v3 or later.

## Credits

Developed by DualCube (https://dualcube.com)

## Changelog

### Version 3.0.0 (2025-10-28)
- **Moodle 5.0 compatibility**
- Integrated Moodle messaging system (replaces direct email)
- Added role-based exclusions
- Added cohort-based exclusions
- Fixed privacy provider export_user_data bug
- Fixed settings form editor parameters
- Fixed settings not saving issue
- Improved code structure and documentation
- Added proper admin settings page
- Enhanced GDPR compliance

### Version 2.7.6 (2025-05-19)
- Moodle 4.4 compatibility
- Bug fixes

### Version 2.7.5 (2025-01-09)
- Various improvements
- Bug fixes

- If settings are required for this cleanup process.
- Days of Inactivity is set by the admin user.
- Days Before Deletion is set to zero when the admin just wants to notify the inactive user to access the site in the first step. After that, when the user wants to run the cleanup process, Days Before Deletion will be set by the admin user.

## Email Setting
- Admin user must set the subject and body text of the email, which the notified inactive user can receive the mail with correct words.

## Cron Process
- Admin user runs the cron job manually after setting the password for manual cron on **Administration > Security > Site security settings > Cron password for remote access** from [https://site.example.com/admin/cron.php?password=opensesame](https://site.example.com/admin/cron.php?password=opensesame) (replace "opensesame" with your cron password)

## Uninstall
- Admin can uninstall this admin tool from **Site administration > Plugins > Admin tools > Manage Admin Tools [Inactive User Cleanup]**
