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
 * @copyright   2025 lern.link GmbH <team@lernlink.de>, Adrian Sarmas, Vadym Nersesov
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\pix_icon;
use core\plugin_manager;

/**
 * Extends the quiz "More" / secondary menu with the 'Allow connection changes' link.
 *
 * This is the standard navigation hook for quiz sub-plugins.
 *
 * @param navigation_node $morenode The parent "more" node where items are added.
 * @param stdClass $quiz The quiz activity record.
 * @param stdClass $cm The course module record for the quiz.
 * @param stdClass $course The course record.
 * @return void
 */
function quizaccess_oneconnection_extend_navigation(navigation_node $morenode, stdClass $quiz, stdClass $cm, stdClass $course): void {
    $context = context_module::instance($cm->id);
    if (!has_capability('quizaccess/oneconnection:allowchange', $context)) {
        return;
    }

    // Only show the link if the rule is enabled for this specific quiz.
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


/**
 * Returns a dataformat selection and download form.
 *
 * This function renders a simple form with a dropdown for selecting a data format
 * (like CSV or Excel) and a download button.
 *
 * @param string $label A text label for the form (e.g., "Download as").
 * @param moodle_url|string $base The URL of the page that will handle the download request.
 * @param string $name The name of the query parameter that will hold the selected data format type.
 * @param array $params Extra hidden parameters to include in the form submission.
 * @return string The rendered HTML for the form.
 */
function quizaccess_oneconnection_download_dataformat_selector($label, $base, $name = 'dataformat', $params = []) {
    global $OUTPUT;

    $formats = plugin_manager::instance()->get_plugins_of_type('dataformat');
    $options = [];

    // Only offer CSV and Excel as download options.
    foreach ($formats as $format) {
        if ($format->is_enabled() && in_array($format->name, ['csv', 'excel'])) {
            $options[] = [
                'value' => $format->name,
                'label' => get_string('dataformat', $format->component),
            ];
        }
    }
    $hiddenparams = [];
    foreach ($params as $key => $value) {
        $hiddenparams[] = [
            'name' => $key,
            'value' => $value,
        ];
    }

    // Data to be passed to the template.
    $data = [
        'label' => $label,
        'base' => $base,
        'name' => $name,
        'params' => $hiddenparams,
        'options' => $options,
        'sesskey' => sesskey(),
        'submit' => get_string('download'),
    ];

    return $OUTPUT->render_from_template('core/dataformat_selector', $data);
}
