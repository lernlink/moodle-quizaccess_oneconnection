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
 * Event triggered when a student is blocked from continuing an attempt
 * because it was started in another session.
 *
 * @package     quizaccess_onesession
 * @category    event
 * @copyright   2016 Vadim Dvorovenko <Vadimon@mail.ru>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_onesession\event;

use coding_exception;
use core\event\base;
use moodle_url;

/**
 * Attempt blocked event.
 */
class attempt_blocked extends base
{

    /**
     * Initialises the event data.
     *
     * @return void
     */
    protected function init(): void
    {
        $this->data['objecttable'] = 'quiz_attempts';
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string
    {
        return get_string('eventattemptblocked', 'quizaccess_onesession');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description(): string
    {
        return "Attempt of user with id '{$this->userid}' to continue attempt with id '{$this->objectid}' for the quiz "
            . "with course module id '{$this->contextinstanceid}' using another device was blocked.";
    }

    /**
     * Get URL related to the action.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url
    {
        return new moodle_url('/mod/quiz/review.php', ['attempt' => $this->objectid]);
    }

    /**
     * Custom validation.
     *
     * @throws coding_exception
     * @return void
     */
    protected function validate_data(): void
    {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new coding_exception('The \'relateduserid\' must be set.');
        }

        if (!isset($this->other['quizid'])) {
            throw new coding_exception('The \'quizid\' value must be set in other.');
        }
    }

    /**
     * Mapping for objectid during restore.
     *
     * @return array|string
     */
    public static function get_objectid_mapping()
    {
        return ['db' => 'quiz_attempts', 'restore' => 'quiz_attempt'];
    }

    /**
     * Mapping for additional data during restore.
     *
     * @return array
     */
    public static function get_other_mapping(): array
    {
        $othermapped = [];
        $othermapped['quizid'] = ['db' => 'quiz', 'restore' => 'quiz'];

        return $othermapped;
    }
}
