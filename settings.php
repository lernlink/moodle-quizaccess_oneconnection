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
 * Admin settings for the "Block concurrent sessions" quiz access rule.
 *
 * @package     quizaccess_onesession
 * @category    admin
 * @copyright  2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // General section.
    $settings->add(
        new admin_setting_heading(
            'quizaccess_onesession/heading',
            get_string('generalsettings', 'admin'),
            get_string('settingsintro', 'quizaccess_onesession')
        )
    );

    // Whether the rule should be pre-enabled for new quizzes.
    $settings->add(
        new admin_setting_configcheckbox(
            'quizaccess_onesession/defaultenabled',
            get_string('onesession', 'quizaccess_onesession'),
            '',
            0
        )
    );

    // Advanced section.
    $settings->add(
        new admin_setting_heading(
            'quizaccess_onesession/headingadvanced',
            get_string('advancedsettings', 'moodle'),
            ''
        )
    );

    // List of subnets that should not be used when calculating the session fingerprint.
    $settings->add(
        new admin_setting_configtextarea(
            'quizaccess_onesession/whitelist',
            get_string('whitelist', 'quizaccess_onesession'),
            get_string('whitelist_desc', 'quizaccess_onesession'),
            '',
            PARAM_RAW,
            60,
            5
        )
    );
}
