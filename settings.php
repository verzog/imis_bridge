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
 * Admin settings for local_imisbridge.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_imisbridge', get_string('pluginname', 'local_imisbridge'));

    $ADMIN->add('localplugins', $settings);

    // WSDL endpoint.
    $settings->add(new admin_setting_configtext(
        'local_imisbridge/wsdl_url',
        get_string('imis_wsdl_url', 'local_imisbridge'),
        get_string('imis_wsdl_url_desc', 'local_imisbridge'),
        'https://scca.atsservices.net/wsmoodle.asmx?WSDL',
        PARAM_URL
    ));

    // ATS API AuthToken (MO-xxxxxx) — required on all secured methods from 2025 onward.
    $settings->add(new admin_setting_configpasswordunmask(
        'local_imisbridge/auth_token',
        get_string('imis_api_auth_token', 'local_imisbridge'),
        get_string('imis_api_auth_token_desc', 'local_imisbridge'),
        '',
        PARAM_TEXT
    ));

    // Admin iMIS ID for SSO session token generation.
    $settings->add(new admin_setting_configtext(
        'local_imisbridge/admin_imis_id',
        get_string('imis_auth_token', 'local_imisbridge'),
        get_string('imis_auth_token_desc', 'local_imisbridge'),
        '',
        PARAM_TEXT
    ));

    // Web service timeout (seconds) for calls to iMIS.
    $settings->add(new admin_setting_configtext(
        'local_imisbridge/ws_timeout',
        get_string('ws_timeout', 'local_imisbridge'),
        get_string('ws_timeout_desc', 'local_imisbridge'),
        '30',
        PARAM_INT
    ));

    // Default credit type recorded against iMIS activity records.
    $settings->add(new admin_setting_configtext(
        'local_imisbridge/credit_type',
        get_string('credit_type', 'local_imisbridge'),
        get_string('credit_type_desc', 'local_imisbridge'),
        'CEU',
        PARAM_ALPHANUMEXT
    ));

    // Course custom field short name holding the credit value.
    $settings->add(new admin_setting_configtext(
        'local_imisbridge/credit_field',
        get_string('credit_field', 'local_imisbridge'),
        get_string('credit_field_desc', 'local_imisbridge'),
        '',
        PARAM_ALPHANUMEXT
    ));

    // Manual sync controls — link to admin tool page.
    $settings->add(new admin_setting_heading(
        'local_imisbridge/manual_sync_heading',
        get_string('manualsynccontrols', 'local_imisbridge'),
        html_writer::link(
            new moodle_url('/local/imisbridge/admin/sync.php'),
            get_string('opensyncadmin', 'local_imisbridge'),
            ['class' => 'btn btn-primary']
        )
    ));
}
