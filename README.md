# Block Concurrent Sessions Quiz Access Rule

[![Moodle Plugin CI](https://github.com/vadimonus/moodle-quizaccess_onesession/workflows/Moodle%20Plugin%20CI/badge.svg?branch=master)](https://github.com/vadimonus/moodle-quizaccess_onesession/actions?query=workflow%3A%22Moodle+Plugin+CI%22+branch%3Amaster)

## Requirements

*   **Moodle 5.0 (build 2025041400) or later.**

## Installation

1.  Copy the `onesession` folder into your Moodle's `mod/quiz/accessrule/` directory.
2.  Log in to your Moodle site as an administrator and visit the **Site administration > Notifications** page to complete the installation.

## Usage

This plugin prevents a student from continuing a single quiz attempt across multiple sessions. The first time a student accesses their quiz attempt, session information (Moodle session, user-agent, IP address) is recorded. Any subsequent attempt to access that same quiz attempt from another computer, device, or browser will be blocked.

This is useful for preventing situations where someone helps a student by accessing the quiz with the student's credentials from another location.

### For Course Creators (Editing Teachers)

1.  In your quiz settings, expand the **"Extra restrictions on attempts"** section.
2.  Check the box for **"Block concurrent connections"**. The rule is now active.

### For Exam Supervisors / Teachers

If a student's computer breaks or they are accidentally blocked, a teacher can allow them to continue on a new device.

1.  Navigate to the quiz.
2.  Click the **"Results"** tab.
3.  From the dropdown menu, select **"Allow connection changes"**.
4.  On this page, you can:
    *   **Unlock a single student:** Click the "Allow change" link in the student's row.
    *   **Unlock multiple students:** Use the checkboxes and the "Allow change in connection for selected attempts" button at the bottom.

Every time a change is allowed, it is permanently logged and displayed on this page for auditing purposes.

## Upgrade from 1.x

Due to changes in the hashing algorithm, when upgrading from version 1.x, all active quiz sessions will be unlocked. This is to ensure that students can safely continue their attempts immediately after the update. There is a small risk that someone could use the site update window to cheat on a quiz that was started before the update. If this is a concern, ensure all quiz attempts are completed before starting the site upgrade.

## Author

*   **Vadim Dvorovenko** (Vadimon@mail.ru)

## ETH Zürich Enhancements (Version 4.0+)

New features including bulk actions, persistent audit logging, and role-based permissions were developed based on the specification by Marco Lehre, ID Educational IT Services, ETH Zürich.

## Links

*   **Plugin Page:** https://moodle.org/plugins/view.php?plugin=quizaccess_onesession
*   **Latest Code:** https://github.com/vadimonus/moodle-quizaccess_onesession

## Changes

**Release 4.0.3 (build 2025092603):**
*   **MAJOR:** Added a "Allow connection changes" management page for teachers.
*   **MAJOR:** Teachers can now unlock multiple students in a single bulk action.
*   **MAJOR:** All unlock actions are now permanently logged for a clear audit trail.
*   **FEATURE:** Added a new capability `quizaccess/onesession:editenabled` to restrict who can change the "Block concurrent connections" setting.
*   **FEATURE:** Added German and Russian language translations.
*   **IMPROVEMENT:** Moved the link to the "Results" tab dropdown for better usability.
*   **FIX:** Updated the Privacy API and Backup/Restore API for Moodle 5.0 compatibility.
*   **Requirement:** Moodle 5.0 is now the minimum required version.

**Release 2.0.1 (build 2024032400):**
*   Changed unlock block title.

**Release 2.0.0 (build 2024010802):**
*   Removed support for versions prior to 4.2.
*   Changed hash algorithm from md5 to sha256 with random salt.

**Release 1.2.1 (build 2022020600):**
*   String fixes. Thanks to Luca Bösch.

**Release 1.2 (build 2021010301):**
*   Setting to exclude some networks from IP check. Thanks to Roberto Pinna.

**Release 1.1 (build 2021010300):**
*   Privacy provider implementation.

**Release 1.0 (build 2016042800):**
*   First stable version.

**Release 0.9 (build 2016042100):**
*   Initial release.