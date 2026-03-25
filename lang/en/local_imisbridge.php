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

defined('MOODLE_INTERNAL') || die();

// Strings must be in alphabetical order.
$string['imis_api_auth_token']      = 'ATS API AuthToken';
$string['imis_api_auth_token_desc'] = 'The ATS-issued AuthToken (format: MO-xxxxxx) required on all secured API methods. Store this securely - do not share or commit to version control. Contact ATS if the token needs to be reissued.';
$string['imis_auth_token']          = 'Admin iMIS ID';
$string['imis_auth_token_desc']     = 'The iMIS ID used to generate a service-level SSO session token for API calls.';
$string['imis_wsdl_url']            = 'iMIS WSDL URL';
$string['imis_wsdl_url_desc']       = 'The WSDL endpoint for the iMIS Moodle web service.';
$string['last_sync']                = 'Last sync time';
$string['noadminimisid']            = 'No admin iMIS ID configured for session token generation.';
$string['noauthtoken']              = 'No ATS API AuthToken configured. Please add the AuthToken in plugin settings.';
$string['nowsdlconfigured']         = 'No iMIS WSDL URL configured. Check plugin settings.';
$string['pluginname']               = 'iMIS Bridge';
$string['sync_all']                 = 'Run Full iMIS Sync';
$string['sync_cancellations']       = 'Sync iMIS Cancellations';
$string['sync_enrollments']         = 'Sync iMIS Enrollments';
$string['sync_error']               = 'Sync encountered errors. Check the logs.';
$string['sync_groups']              = 'Sync iMIS Groups';
$string['sync_success']             = 'Sync completed successfully.';
