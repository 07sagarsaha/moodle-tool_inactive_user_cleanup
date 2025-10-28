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
 * tool_inactive_user_cleanup setting form
 *
 * @package    tool_inactive_user_cleanup
 * @copyright  DualCube (https://dualcube.com)
 * @author     DualCube <admin@dualcube.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');

/**
 * settings form for tool_inactive_user_cleanup
 *
 * @copyright DualCube (https://dualcube.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_inactive_user_cleanup_config_form extends moodleform {

    /**
     * Definition.
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        // General settings header.
        $mform->addElement('header', 'configheader', get_string('setting', 'tool_inactive_user_cleanup'));

        // Days of inactivity.
        $mform->addElement('text', 'config_daysofinactivity', get_string('daysofinactivity', 'tool_inactive_user_cleanup'));
        $mform->setType('config_daysofinactivity', PARAM_INT);
        $mform->setDefault('config_daysofinactivity', 365);
        $mform->addRule('config_daysofinactivity', null, 'required', null, 'client');
        $mform->addRule('config_daysofinactivity', null, 'numeric', null, 'client');

        // Days before deletion.
        $mform->addElement('text', 'config_daysbeforedeletion', get_string('daysbeforedeletion', 'tool_inactive_user_cleanup'));
        $mform->setType('config_daysbeforedeletion', PARAM_INT);
        $mform->setDefault('config_daysbeforedeletion', 10);
        $mform->addRule('config_daysbeforedeletion', null, 'required', null, 'client');
        $mform->addRule('config_daysbeforedeletion', null, 'numeric', null, 'client');
        $mform->addElement('static', 'description', '', get_string('deletiondescription', 'tool_inactive_user_cleanup'));

        // Exclusion settings.
        $mform->addElement('header', 'exclusionheader', get_string('exclusionsettings', 'tool_inactive_user_cleanup'));

        // Excluded roles.
        $roles = role_get_names(\context_system::instance());
        $roleoptions = [];
        foreach ($roles as $role) {
            $roleoptions[$role->id] = $role->localname;
        }
        $select = $mform->addElement('select', 'config_excludedroles',
                                      get_string('excludedroles', 'tool_inactive_user_cleanup'),
                                      $roleoptions);
        $select->setMultiple(true);
        $mform->addHelpButton('config_excludedroles', 'excludedroles', 'tool_inactive_user_cleanup');

        // Excluded cohorts.
        $cohorts = $DB->get_records_menu('cohort', null, 'name', 'id, name');
        if (!empty($cohorts)) {
            $select = $mform->addElement('select', 'config_excludecohorts',
                                          get_string('excludecohorts', 'tool_inactive_user_cleanup'),
                                          $cohorts);
            $select->setMultiple(true);
            $mform->addHelpButton('config_excludecohorts', 'excludecohorts', 'tool_inactive_user_cleanup');
        }

        // Email settings header.
        $mform->addElement('header', 'config_headeremail', get_string('emailsetting', 'tool_inactive_user_cleanup'));

        // Email subject.
        $mform->addElement('text', 'config_subjectemail', get_string('emailsubject', 'tool_inactive_user_cleanup'));
        $mform->setType('config_subjectemail', PARAM_TEXT);
        $mform->setDefault('config_subjectemail', get_string('emailsubject_default', 'tool_inactive_user_cleanup'));
        $mform->addRule('config_subjectemail', null, 'required', null, 'client');

        // Email body - Fixed editor options.
        $editoroptions = [
            'maxfiles' => 0,
            'maxbytes' => 0,
            'context' => \context_system::instance(),
        ];
        $mform->addElement('editor', 'config_bodyemail',
                          get_string('emailbody', 'tool_inactive_user_cleanup'),
                          null,
                          $editoroptions);
        $mform->setType('config_bodyemail', PARAM_RAW);
        $mform->addRule('config_bodyemail', null, 'required', null, 'client');

        $this->add_action_buttons();
    }
}
