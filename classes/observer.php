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
 * Event observer — hooks into Moodle events and calls iMIS accordingly.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imisbridge;

/**
 * Event observer for local_imisbridge.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    // USER LOGIN — sync enrollments + cancellations + groups for the logged-in user.

    /**
     * Triggered on user login. Syncs enrollments, cancellations, and groups for the user.
     *
     * @param \core\event\user_loggedin $event The login event.
     * @return void
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        global $DB;

        $userid = $event->userid;
        $user   = $DB->get_record('user', ['id' => $userid], 'id, username');
        $imisid = $user->username; // Username == iMIS ID via SAML2.

        if (empty($imisid)) {
            return;
        }

        try {
            $client = new imis_client();

            // Sync active enrollments for this user.
            $client->sync_orders($imisid);

            // Sync any cancellations for this user.
            $client->sync_cancelled_orders($imisid);

            // Update group memberships for this user.
            $client->update_groups($imisid);
        } catch (\Exception $e) {
            debugging('iMIS Bridge login sync error for user ' . $imisid . ': ' . $e->getMessage(), DEBUG_NORMAL);
        }
    }

    // COURSE COMPLETED — push completion record to iMIS.

    /**
     * Triggered on course completion. Pushes the completion record to iMIS.
     *
     * NOTE: credit_value is currently hardcoded to 0. This should be read from a
     * course custom field before this plugin goes live.
     *
     * @param \core\event\course_completed $event The course completed event.
     * @return void
     */
    public static function course_completed(\core\event\course_completed $event): void {
        global $DB;

        $data   = $event->get_data();
        $userid = $data['relateduserid'];
        $user   = $DB->get_record('user', ['id' => $userid], 'id, username');

        if (empty($user)) {
            return;
        }

        $imisid   = $user->username;
        $courseid = $data['courseid'];
        $course   = $DB->get_record('course', ['id' => $courseid], 'id, idnumber, shortname');

        // Use course idnumber as the Moodle course number sent to iMIS.
        // Fall back to shortname if idnumber is blank.
        $coursenum = !empty($course->idnumber) ? $course->idnumber : $course->shortname;

        // TODO MDL-0: Read credit value from a course custom field instead of hardcoding 0.
        $creditvalue = 0;

        try {
            $client = new imis_client();
            $client->update_activity_record(
                imisid:         $imisid,
                moodlecoursenum: $coursenum,
                credittype:     'CEU',
                creditvalue:    $creditvalue,
                startdate:      date('Y-m-d'),
                completiondate: date('Y-m-d'),
                grantdate:      date('Y-m-d'),
                status:         'Pass',
                score:          0
            );
        } catch (\Exception $e) {
            debugging('iMIS Bridge course completion error for user ' . $imisid . ': ' . $e->getMessage(), DEBUG_NORMAL);
        }
    }

    // QUIZ ATTEMPT SUBMITTED — push quiz score to iMIS.

    /**
     * Triggered on quiz attempt submission. Pushes the quiz score to iMIS.
     *
     * @param \mod_quiz\event\attempt_submitted $event The attempt submitted event.
     * @return void
     */
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event): void {
        global $DB;

        $data      = $event->get_data();
        $userid    = $data['userid'];
        $attemptid = $data['objectid'];

        $user = $DB->get_record('user', ['id' => $userid], 'id, username');
        if (empty($user)) {
            return;
        }

        $imisid = $user->username;

        // Load attempt and quiz details.
        $attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid]);
        $quiz    = !empty($attempt) ? $DB->get_record('quiz', ['id' => $attempt->quiz]) : null;
        $course  = !empty($quiz) ? $DB->get_record('course', ['id' => $quiz->course], 'id, idnumber, shortname') : null;

        if (!$attempt || !$quiz || !$course) {
            debugging('iMIS Bridge quiz sync: could not load attempt/quiz/course for attempt ' . $attemptid, DEBUG_NORMAL);
            return;
        }

        // Calculate percentage score.
        $score = 0;
        if (!empty($attempt->sumgrades) && !empty($quiz->sumgrades)) {
            $score = round(($attempt->sumgrades / $quiz->sumgrades) * 100, 2);
        }

        // Use gradepass as the pass threshold, falling back to 50 if not set.
        $passmark  = !empty($quiz->gradepass) ? $quiz->gradepass : 50;
        $status    = ($score >= $passmark) ? 'Pass' : 'Fail';
        $coursenum = !empty($course->idnumber) ? $course->idnumber : $course->shortname;
        $finishtime = !empty($attempt->timefinish) ? date('Y-m-d', $attempt->timefinish) : date('Y-m-d');
        $starttime  = !empty($attempt->timestart) ? date('Y-m-d', $attempt->timestart) : date('Y-m-d');

        try {
            $client = new imis_client();
            $client->update_activity_record(
                imisid:         $imisid,
                moodlecoursenum: $coursenum,
                credittype:     'CEU',
                creditvalue:    0,
                startdate:      $starttime,
                completiondate: $finishtime,
                grantdate:      $finishtime,
                status:         $status,
                score:          $score
            );
        } catch (\Exception $e) {
            debugging('iMIS Bridge quiz sync error for user ' . $imisid . ': ' . $e->getMessage(), DEBUG_NORMAL);
        }
    }
}
