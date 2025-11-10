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
 * Library for the quiz access rule 'oneconnection'.
 *
 * Contains navigation hooks and other integration points.
 *
 * @package     quizaccess_oneconnection
 * @category    lib
 * @copyright   2024 onwards
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\output\pix_icon;

/**
 * Extends the quiz "More" / secondary menu with the 'Allow connection changes' link.
 *
 * This is the standard navigation hook for quiz sub-plugins.
 *
 * @param navigation_node $morenode The parent "more" node.
 * @param stdClass $quiz The quiz activity record.
 * @param stdClass $cm The course module.
 * @param stdClass $course The course record.
 * @return void
 */
function quizaccess_oneconnection_extend_navigation(navigation_node $morenode, stdClass $quiz, stdClass $cm, stdClass $course): void
{
    $context = context_module::instance($cm->id);
    if (!has_capability('quizaccess/oneconnection:allowchange', $context)) {
        return;
    }

    // Only show the link if the rule is enabled on this quiz.
    if (empty($quiz->oneconnectionenabled)) {
        return;
    }

    $url = new moodle_url('/mod/quiz/accessrule/oneconnection/allowconnections.php', ['id' => $cm->id]);
    $morenode->add(
        get_string('allowconnections', 'quizaccess_oneconnection'),
        $url,
        navigation_node::TYPE_ACTION,
        null,
        null,
        new pix_icon('i/unlock', '')
    );
}
