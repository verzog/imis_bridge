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
 * Language strings for local_imisbridge.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allusers'] = 'all users';
$string['autosynccontrols'] = 'Automatic sync controls';
$string['autosynccontrols_desc'] = 'Enable or disable each automatic write to iMIS. Manual syncs on the sync admin page are not affected. Turn writes off when testing against a production iMIS.';
$string['contactid'] = 'iMIS contact ID';
$string['contactidhelp'] = 'Leave this blank to synchronise all users.';
$string['contactidplaceholder'] = 'e.g. 12345';
$string['credit_field'] = 'Credit value course field';
$string['credit_field_desc'] = 'The short name of the course custom field that holds the credit value awarded on completion. Leave blank to send a credit value of 0.';
$string['credit_type'] = 'Default credit type';
$string['credit_type_desc'] = 'The credit type recorded against iMIS activity records (for example, CEU).';
$string['filtercontactid'] = 'Optional: filter by iMIS contact ID';
$string['forcontact'] = 'contact {$a}';
$string['imis_api_auth_token'] = 'ATS API AuthToken';
$string['imis_api_auth_token_desc'] = 'The ATS-issued AuthToken (format: MO-xxxxxx) required on all secured API methods. Store this securely - do not share or commit to version control. Contact ATS if the token needs to be reissued.';
$string['imis_auth_token'] = 'Admin iMIS ID';
$string['imis_auth_token_desc'] = 'The iMIS ID used to generate a service-level SSO session token for API calls.';
$string['imis_wsdl_url'] = 'iMIS WSDL URL';
$string['imis_wsdl_url_desc'] = 'The WSDL endpoint for the iMIS Moodle web service.';
$string['last_sync'] = 'Last sync time';
$string['manualsynccontrols'] = 'Manual sync controls';
$string['noadminimisid'] = 'No admin iMIS ID configured for session token generation.';
$string['noauthtoken'] = 'No ATS API AuthToken configured. Please add the AuthToken in plugin settings.';
$string['nowsdlconfigured'] = 'No iMIS WSDL URL configured. Check plugin settings.';
$string['opensyncadmin'] = 'Open sync admin';
$string['pluginname'] = 'iMIS Bridge';
$string['privacy:metadata:imis'] = 'The iMIS Bridge plugin transmits personal data to an external iMIS association management system in order to synchronise memberships, enrolments and learning records.';
$string['privacy:metadata:imis:completiondate'] = 'The dates a course was started, completed and credit granted are sent to iMIS.';
$string['privacy:metadata:imis:courseid'] = 'The identifier of the course an activity record relates to is sent to iMIS.';
$string['privacy:metadata:imis:score'] = 'Your assessment score is sent to iMIS.';
$string['privacy:metadata:imis:status'] = 'Your completion status (pass or fail) is sent to iMIS.';
$string['privacy:metadata:imis:userid'] = 'Your iMIS contact ID (your Moodle username) is sent to identify your record in iMIS.';
$string['push_completions'] = 'Push course completions to iMIS';
$string['push_completions_desc'] = 'When enabled, completing a course sends the learner\'s result to iMIS. Turn off to stop all completion writes.';
$string['push_quiz_scores'] = 'Push quiz scores to iMIS';
$string['push_quiz_scores_desc'] = 'When enabled, submitting a graded quiz sends the score to iMIS. Turn off to stop all quiz writes.';
$string['schedule_cancellations'] = '02:15 UTC - cancellation sync (all users)';
$string['schedule_enrollments'] = '02:00 UTC - enrolment sync (all users)';
$string['schedule_groups'] = '02:30 UTC - group sync (incremental, based on last run)';
$string['scheduledtaskcontrols'] = 'Scheduled tasks';
$string['scheduledtaskcontrols_desc'] = 'Enable or disable each nightly all-user task. These can also be managed from Site administration > Server > Scheduled tasks.';
$string['scheduledtaskintro'] = 'Scheduled nightly tasks run automatically via Moodle cron:';
$string['scheduledtaskstatus'] = 'Scheduled task status';
$string['sync_all'] = 'Run full iMIS sync';
$string['sync_cancellations'] = 'Sync iMIS cancellations';
$string['sync_cancellations_on_login'] = 'Sync cancellations on login';
$string['sync_cancellations_on_login_desc'] = 'When enabled, a user logging in triggers a cancellation sync for that user.';
$string['sync_enrollments'] = 'Sync iMIS enrolments';
$string['sync_enrolments_on_login'] = 'Sync enrolments on login';
$string['sync_enrolments_on_login_desc'] = 'When enabled, a user logging in triggers an enrolment sync for that user.';
$string['sync_error'] = 'Sync encountered errors. Check the logs.';
$string['sync_groups'] = 'Sync iMIS groups';
$string['sync_groups_on_login'] = 'Sync groups on login';
$string['sync_groups_on_login_desc'] = 'When enabled, a user logging in triggers a group sync for that user.';
$string['sync_success'] = 'Sync completed successfully.';
$string['syncdone'] = 'Sync completed for {$a}.';
$string['syncfailed'] = 'Sync failed: {$a}';
$string['task_cancellations_enabled'] = 'Nightly cancellation sync task';
$string['task_cancellations_enabled_desc'] = 'Run the nightly cancellation sync for all users.';
$string['task_enrolments_enabled'] = 'Nightly enrolment sync task';
$string['task_enrolments_enabled_desc'] = 'Run the nightly enrolment sync for all users.';
$string['task_groups_enabled'] = 'Nightly group sync task';
$string['task_groups_enabled_desc'] = 'Run the nightly incremental group sync.';
$string['task_push_activity'] = 'iMIS Bridge: push activity record';
$string['task_sync_cancellations'] = 'iMIS Bridge: sync cancellations';
$string['task_sync_enrollments'] = 'iMIS Bridge: sync enrolments';
$string['task_sync_groups'] = 'iMIS Bridge: sync groups';
$string['task_sync_user'] = 'iMIS Bridge: sync user on login';
$string['testconnection'] = 'Test connection';
$string['testconnection_help'] = 'Runs read-only calls (GetBridgeSettings, plus a profile lookup when a contact ID is entered) to verify connectivity and credentials without writing any data.';
$string['testcredentialsnote'] = 'Connectivity and WSDL verified. Credentials (AuthToken) were not tested; enter an iMIS contact ID and run again to exercise an authenticated call.';
$string['testcredentialsok'] = 'Connectivity and credentials (AuthToken) verified.';
$string['viewtasklogs'] = 'View task logs';
$string['ws_timeout'] = 'Web service timeout (seconds)';
$string['ws_timeout_desc'] = 'Maximum time in seconds to wait when connecting to or reading from the iMIS web service before giving up.';
