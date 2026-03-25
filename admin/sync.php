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
 * Manual sync admin page for local_imisbridge.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url(new moodle_url('/local/imisbridge/admin/sync.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('iMIS Bridge - Manual Sync');
$PAGE->set_heading('iMIS Bridge - Manual Sync');

// Use POST to avoid action/contactid appearing in browser history and server logs.
$action    = optional_param('action', '', PARAM_ALPHANUMEXT);
$contactid = optional_param('contactid', '', PARAM_TEXT);
$message   = '';
$error     = '';

if ($action && confirm_sesskey()) {
    try {
        $client = new \local_imisbridge\imis_client();
        $cid    = !empty($contactid) ? $contactid : null;

        switch ($action) {
            case 'enrollments':
                $client->sync_orders($cid);
                $message = 'Enrollment sync completed' . ($cid ? ' for contact ' . s($cid) : ' for all users') . '.';
                break;

            case 'cancellations':
                $client->sync_cancelled_orders($cid);
                $message = 'Cancellation sync completed' . ($cid ? ' for contact ' . s($cid) : ' for all users') . '.';
                break;

            case 'groups':
                $client->update_groups($cid);
                $message = 'Group sync completed' . ($cid ? ' for contact ' . s($cid) : ' for all users') . '.';
                break;

            case 'all':
                $client->sync_orders($cid);
                $client->sync_cancelled_orders($cid);
                $client->update_groups($cid);
                $message = 'Full sync completed' . ($cid ? ' for contact ' . s($cid) : ' for all users') . '.';
                break;
        }
    } catch (\Exception $e) {
        $error = 'Sync failed: ' . $e->getMessage();
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading('iMIS Bridge - Manual Sync Controls');

if ($message) {
    echo $OUTPUT->notification($message, 'notifysuccess');
}
if ($error) {
    echo $OUTPUT->notification($error, 'notifyproblem');
}

$syncurl  = new moodle_url('/local/imisbridge/admin/sync.php');
$logsurl  = new moodle_url('/admin/tasklogs.php');
$sesskey  = sesskey();
$cidvalue = s($contactid);

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', 'Optional: Filter by iMIS Contact ID', ['class' => 'card-title']);
echo '<form method="post" action="">';
echo '<input type="hidden" name="sesskey" value="' . $sesskey . '">';
echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'iMIS Contact ID (leave blank for all users)', ['for' => 'contactid']);
echo '<input type="text" id="contactid" name="contactid" class="form-control"'
    . ' value="' . $cidvalue . '" placeholder="e.g. 12345">';
echo html_writer::end_div();
echo html_writer::start_div('mt-3 d-flex gap-2 flex-wrap');
echo '<button type="submit" name="action" value="enrollments" class="btn btn-primary">Sync Enrollments</button>';
echo '<button type="submit" name="action" value="cancellations" class="btn btn-warning">Sync Cancellations</button>';
echo '<button type="submit" name="action" value="groups" class="btn btn-info">Sync Groups</button>';
echo '<button type="submit" name="action" value="all" class="btn btn-success">Run Full Sync</button>';
echo html_writer::end_div();
echo '</form>';
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', 'Scheduled Task Status', ['class' => 'card-title']);
echo html_writer::tag('p', 'Scheduled nightly tasks run automatically via Moodle cron:');
echo '<ul>'
    . '<li><strong>02:00 UTC</strong> - Enrollment sync (all users)</li>'
    . '<li><strong>02:15 UTC</strong> - Cancellation sync (all users)</li>'
    . '<li><strong>02:30 UTC</strong> - Group sync (incremental, based on last run)</li>'
    . '</ul>';
echo html_writer::link($logsurl, 'View Task Logs', ['class' => 'btn btn-secondary btn-sm']);
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
