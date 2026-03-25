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
 * iMIS SOAP client wrapper.
 *
 * All public methods map directly to wsmoodle.asmx operations.
 *
 * Updated 2025: ATS now requires an AuthToken parameter on the following methods:
 *   MoodleGetUserProfile, MoodleUpdate, createActivity,
 *   getActivityByIDAndType, getIQARows.
 * The AuthToken is stored in plugin settings (local_imisbridge/auth_token)
 * and injected automatically by get_api_auth_token().
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imisbridge;

/**
 * iMIS SOAP client wrapper class.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class imis_client {
    /** @var \SoapClient */
    private $soap;

    /** @var string Cached iMIS session token (for SSO, not the API AuthToken). */
    private $token;

    /** @var string Cached ATS API AuthToken (MO-xxxxxx). */
    private $apiauthtoken;

    /**
     * Constructor — initialises the SOAP client from plugin settings.
     *
     * @throws \moodle_exception If no WSDL URL is configured.
     */
    public function __construct() {
        $wsdl = get_config('local_imisbridge', 'wsdl_url');

        if (empty($wsdl)) {
            throw new \moodle_exception('nowsdlconfigured', 'local_imisbridge');
        }

        $this->soap = new \SoapClient($wsdl, [
            'trace'        => true,
            'exceptions'   => true,
            'cache_wsdl'   => WSDL_CACHE_DISK,
            'soap_version' => SOAP_1_1,
        ]);
    }

    // AUTH.

    /**
     * Returns the ATS API AuthToken (MO-xxxxxx) from plugin settings.
     *
     * This token is required by ATS from 2025 onward on methods:
     * MoodleGetUserProfile, MoodleUpdate, createActivity,
     * getActivityByIDAndType, and getIQARows.
     *
     * @return string The API AuthToken.
     * @throws \moodle_exception If no AuthToken is configured.
     */
    private function get_api_auth_token(): string {
        if (empty($this->apiauthtoken)) {
            $token = get_config('local_imisbridge', 'auth_token');
            if (empty($token)) {
                throw new \moodle_exception('noauthtoken', 'local_imisbridge');
            }
            $this->apiauthtoken = $token;
        }
        return $this->apiauthtoken;
    }

    /**
     * Generate an iMIS session token for the given iMIS ID.
     *
     * @param string $imisid The iMIS contact ID.
     * @return string The generated session token.
     */
    public function get_token(string $imisid): string {
        $result = $this->soap->getToken(['iMISID' => $imisid]);
        return $result->getTokenResult ?? '';
    }

    /**
     * Get or lazily create a service-level session token using the configured admin iMIS ID.
     *
     * @return string The service-level session token.
     * @throws \moodle_exception If no admin iMIS ID is configured.
     */
    public function get_service_token(): string {
        if (empty($this->token)) {
            $adminid = get_config('local_imisbridge', 'admin_imis_id');
            if (empty($adminid)) {
                throw new \moodle_exception('noadminimisid', 'local_imisbridge');
            }
            $this->token = $this->get_token($adminid);
        }
        return $this->token;
    }

    /**
     * Check if a given iMIS user is authorised to log in.
     *
     * @param string $imisid The iMIS contact ID.
     * @return bool True if the user is authorised.
     */
    public function check_authorization(string $imisid): bool {
        $result = $this->soap->checkAuthorization(['iMISID' => $imisid]);
        return (bool)($result->checkAuthorizationResult ?? false);
    }

    // CONTACT LOOKUP.

    /**
     * Return iMIS contact info by iMIS ID (MoodleGetUserProfile).
     *
     * Requires AuthToken as of 2025 ATS security update.
     *
     * @param string $imisid The iMIS contact ID.
     * @return object|null The contact record, or null if not found.
     */
    public function get_contact_by_id(string $imisid): ?object {
        $result = $this->soap->MoodleGetUserProfile([
            'ID'        => $imisid,
            'AuthToken' => $this->get_api_auth_token(),
        ]);
        return $result->MoodleGetUserProfileResult ?? null;
    }

    /**
     * Return iMIS contact info by session token (MoodleGetUserProfile).
     *
     * Requires AuthToken as of 2025 ATS security update.
     *
     * @param string $token The SSO session token.
     * @return object|null The contact record, or null if not found.
     */
    public function get_contact_by_token(string $token): ?object {
        $result = $this->soap->MoodleGetUserProfileByToken([
            'Token'     => $token,
            'AuthToken' => $this->get_api_auth_token(),
        ]);
        return $result->MoodleGetUserProfileByTokenResult ?? null;
    }

    // ENROLLMENT SYNC.

    /**
     * Find orders missing activity records and create them, then notify Moodle.
     *
     * Pass null $contactid to sync ALL users.
     *
     * @param string|null $contactid iMIS contact ID, or null for all users.
     * @return mixed The SOAP result.
     */
    public function sync_orders(?string $contactid = null): mixed {
        $params = ['contactId' => $contactid ?? ''];
        $result = $this->soap->SendNewOrdersToMoodle($params);
        return $result->SendNewOrdersToMoodleResult ?? null;
    }

    /**
     * Find cancelled orders and send unsubscribe notice to Moodle.
     *
     * Pass null $contactid to sync ALL users.
     *
     * @param string|null $contactid iMIS contact ID, or null for all users.
     * @return mixed The SOAP result.
     */
    public function sync_cancelled_orders(?string $contactid = null): mixed {
        $params = ['contactId' => $contactid ?? ''];
        $result = $this->soap->SendCancelledOrdersToMoodle($params);
        return $result->SendCancelledOrdersToMoodleResult ?? null;
    }

    // GROUP SYNC.

    /**
     * Update Moodle groups based on iMIS group membership.
     *
     * @param string|null $contactid   iMIS contact ID (optional).
     * @param string|null $courseid    Moodle course ID (optional).
     * @param string|null $lastupdated UTC datetime string e.g. '2024-03-04T00:00:00' (optional).
     * @return mixed The SOAP result.
     */
    public function update_groups(
        ?string $contactid = null,
        ?string $courseid = null,
        ?string $lastupdated = null
    ): mixed {
        $params = [
            'ContactID'   => $contactid ?? '',
            'CourseID'    => $courseid ?? '',
            'lastUpdated' => $lastupdated ?? '',
        ];
        $result = $this->soap->UpdateMoodleGroups($params);
        return $result->UpdateMoodleGroupsResult ?? null;
    }

    // ACTIVITY RECORDS.

    /**
     * Retrieve activity records for a contact and activity type (getActivityByIDAndType).
     *
     * Requires AuthToken as of 2025 ATS security update.
     *
     * @param string $contactid    The iMIS contact ID.
     * @param string $activitytype The activity type to filter by.
     * @param string $productcode  Optional product code filter.
     * @return mixed The SOAP result.
     */
    public function get_activities(string $contactid, string $activitytype, string $productcode = ''): mixed {
        $params = [
            'ContactID'    => $contactid,
            'ActivityType' => $activitytype,
            'ProductCode'  => $productcode,
            'AuthToken'    => $this->get_api_auth_token(),
        ];
        $result = $this->soap->getActivityByIDAndType($params);
        return $result->getActivityByIDAndTypeResult ?? null;
    }

    /**
     * Create a new activity record in iMIS (createActivity).
     *
     * Requires AuthToken as of 2025 ATS security update.
     *
     * @param array $data The activity data (AuthToken is injected automatically).
     * @return mixed The SOAP result.
     */
    public function create_activity(array $data): mixed {
        $data['AuthToken'] = $this->get_api_auth_token();
        $result = $this->soap->createActivity($data);
        return $result->createActivityResult ?? null;
    }

    /**
     * Update an iMIS activity record with Moodle course completion data (MoodleUpdate).
     *
     * Requires AuthToken as of 2025 ATS security update.
     *
     * @param string $imisid          iMIS user ID (= Moodle username).
     * @param string $moodlecoursenum Moodle course number (maps to iMIS product code).
     * @param string $credittype      Type of credit hour.
     * @param float  $creditvalue     Number of credit hours awarded.
     * @param string $startdate       Course start date (Y-m-d).
     * @param string $completiondate  Course completion date (Y-m-d).
     * @param string $grantdate       Date credit hours were granted (Y-m-d).
     * @param string $status          Pass or Fail.
     * @param float  $score           Last test score.
     * @return mixed The SOAP result.
     */
    public function update_activity_record(
        string $imisid,
        string $moodlecoursenum,
        string $credittype,
        float $creditvalue,
        string $startdate,
        string $completiondate,
        string $grantdate,
        string $status,
        float $score
    ): mixed {
        $params = [
            'UserID'         => $imisid,
            'productID'      => $moodlecoursenum,
            'type'           => $credittype,
            'value'          => $creditvalue,
            'startDate'      => $startdate,
            'completionDate' => $completiondate,
            'grantDate'      => $grantdate,
            'status'         => $status,
            'score'          => $score,
            'AuthToken'      => $this->get_api_auth_token(),
        ];
        $result = $this->soap->MoodleUpdate($params);
        return $result->MoodleUpdateResult ?? null;
    }

    // BRIDGE SETTINGS.

    /**
     * Return the Moodle bridge settings as configured in iMIS.
     *
     * @return mixed The SOAP result.
     */
    public function get_bridge_settings(): mixed {
        $result = $this->soap->getBridgeSettings([]);
        return $result->getBridgeSettingsResult ?? null;
    }

    // IQA.

    /**
     * Get rows from an iMIS IQA query (getIQARows).
     *
     * AuthToken has always been required for this method.
     *
     * @param string $iqapath   The IQA path to query.
     * @param array  $params    Optional additional parameters.
     * @return mixed The SOAP result.
     */
    public function get_iqa_rows(string $iqapath, array $params = []): mixed {
        $params['IQAPath']   = $iqapath;
        $params['AuthToken'] = $this->get_api_auth_token();
        $result = $this->soap->getIQARows($params);
        return $result->getIQARowsResult ?? null;
    }

    // ENCRYPTION HELPERS.

    /**
     * Encrypt a value via iMIS.
     *
     * @param string $value The value to encrypt.
     * @return string The encrypted value.
     */
    public function encrypt(string $value): string {
        $result = $this->soap->asiEncrypt(['value' => $value]);
        return $result->asiEncryptResult ?? '';
    }

    /**
     * Decrypt a value via iMIS.
     *
     * @param string $value The value to decrypt.
     * @return string The decrypted value.
     */
    public function decrypt(string $value): string {
        $result = $this->soap->asiDecrypt(['value' => $value]);
        return $result->asiDecryptResult ?? '';
    }
}
