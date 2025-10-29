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
 * Extends the quiz results navigation with a link to the "Allow connections" page.
 *
 * This function is a hook that is called by the quiz module. The function name must
 * follow the pattern {plugintype}_{pluginname}_extend_navigation_{navgroup}.
 *
 * @param navigation_node $resultsnode The navigation node for quiz results.
 * @param stdClass $quiz The quiz object.
 * @param stdClass $cm The course module object.
 * @return void
 */
function quizaccess_onesession_extend_navigation_quiz_results(navigation_node $resultsnode, stdClass $quiz, stdClass $cm): void
{
    $context = context_module::instance($cm->id);
    if (has_capability('quizaccess/onesession:allowchange', $context)) {
        $url = new moodle_url('/mod/quiz/accessrule/onesession/allowconnections.php', ['id' => $cm->id]);
        $resultsnode->add(
            get_string('allowconnections', 'quizaccess_onesession'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'allowconnections',
            new pix_icon('i/unlock', '')
        );
    }
}