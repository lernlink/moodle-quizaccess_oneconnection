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
 * Lib file for the quiz access rule 'onesession'.
 *
 * @package    quizaccess_onesession
 * @copyright  2024 onwards, Adrian
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\output\pix_icon;

/**
 * Extends the quiz "More" menu with the 'Allow connection changes' link.
 *
 * This is the correct and only navigation hook for quiz sub-plugins. The quiz module
 * specifically looks for a function named {component_name}_extend_navigation.
 *
 * @param navigation_node $morenode The navigation node for the "More" menu.
 * @param stdClass $quiz The quiz object.
 * @param stdClass $cm The course module object.
 * @param stdClass $course The course object.
 * @return void
 */
function quizaccess_onesession_extend_navigation(navigation_node $morenode, stdClass $quiz, stdClass $cm, stdClass $course): void
{
    $context = context_module::instance($cm->id);
    if (has_capability('quizaccess/onesession:allowchange', $context)) {
        $url = new moodle_url('/mod/quiz/accessrule/onesession/allowconnections.php', ['id' => $cm->id]);
        $morenode->add(
            get_string('allowconnections', 'quizaccess_onesession'),
            $url,
            navigation_node::TYPE_ACTION,
            null,
            null,
            new pix_icon('i/unlock', '')
        );
    }
}