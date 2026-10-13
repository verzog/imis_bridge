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
 * Tests for the local_imisbridge audit logger.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imisbridge;

/**
 * Unit tests for {@see logger}.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_imisbridge\logger
 */
final class logger_test extends \advanced_testcase {
    /**
     * A SOAP client stub returning fixed request/response XML.
     *
     * @param string $request  The request XML to return.
     * @param string $response The response XML to return.
     * @return \SoapClient
     */
    private function soap_stub(string $request, string $response): \SoapClient {
        return new class($request, $response) extends \SoapClient {
            /** @var string Request XML. */
            private string $req;
            /** @var string Response XML. */
            private string $res;

            /**
             * Construct without touching a WSDL.
             *
             * @param string $req Request XML.
             * @param string $res Response XML.
             */
            public function __construct(string $req, string $res) {
                $this->req = $req;
                $this->res = $res;
            }

            /**
             * Return the canned request.
             *
             * @return string|null
             */
            public function __getLastRequest(): ?string {
                return $this->req;
            }

            /**
             * Return the canned response.
             *
             * @return string|null
             */
            public function __getLastResponse(): ?string {
                return $this->res;
            }
        };
    }

    /**
     * Credentials are stripped from stored XML.
     *
     * @return void
     */
    public function test_redact(): void {
        $xml = '<e><AuthToken>MO-abc123</AuthToken>'
            . '<getTokenResult>SESSIONSECRET</getTokenResult>'
            . '<note>token MO-xyz789 here</note></e>';
        $out = logger::redact($xml);

        $this->assertStringNotContainsString('MO-abc123', $out);
        $this->assertStringNotContainsString('MO-xyz789', $out);
        $this->assertStringNotContainsString('SESSIONSECRET', $out);
        $this->assertStringContainsString('[redacted]', $out);
    }

    /**
     * By default only metadata is stored; payloads are not.
     *
     * @return void
     */
    public function test_record_metadata_only_by_default(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['username' => 'member123']);
        $soap = $this->soap_stub('<req/>', '<res/>');

        logger::record('MoodleUpdate', 'member123', 'PROD1', logger::STATUS_SUCCESS, $soap, null);

        $row = $DB->get_record('local_imisbridge_log', ['operation' => 'MoodleUpdate'], '*', MUST_EXIST);
        $this->assertSame((int)$user->id, (int)$row->userid);
        $this->assertSame('member123', $row->imisid);
        $this->assertSame('PROD1', $row->productcode);
        $this->assertSame(logger::STATUS_SUCCESS, $row->status);
        $this->assertNull($row->request);
        $this->assertNull($row->response);
    }

    /**
     * With payload capture enabled, the redacted request/response are stored.
     *
     * @return void
     */
    public function test_record_captures_redacted_payloads_when_enabled(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('capture_payloads', 1, 'local_imisbridge');

        $soap = $this->soap_stub('<req><AuthToken>MO-secret</AuthToken></req>', '<res>ok</res>');
        logger::record('MoodleUpdate', '12345', null, logger::STATUS_SUCCESS, $soap, null);

        $row = $DB->get_record('local_imisbridge_log', ['operation' => 'MoodleUpdate'], '*', MUST_EXIST);
        $this->assertStringContainsString('[redacted]', $row->request);
        $this->assertStringNotContainsString('MO-secret', $row->request);
        $this->assertSame('<res>ok</res>', $row->response);
    }

    /**
     * Disabling logging stops rows being written.
     *
     * @return void
     */
    public function test_record_respects_disabled(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('log_enabled', '0', 'local_imisbridge');
        logger::record('MoodleUpdate', '12345', null, logger::STATUS_SUCCESS, null, null);

        $this->assertEquals(0, $DB->count_records('local_imisbridge_log'));
    }

    /**
     * Purge deletes rows older than the retention window and nothing when disabled.
     *
     * @return void
     */
    public function test_purge(): void {
        global $DB;
        $this->resetAfterTest();

        $old = (object)['userid' => 0, 'operation' => 'old', 'status' => 'success',
            'timecreated' => time() - (100 * DAYSECS)];
        $new = (object)['userid' => 0, 'operation' => 'new', 'status' => 'success', 'timecreated' => time()];
        $DB->insert_record('local_imisbridge_log', $old);
        $DB->insert_record('local_imisbridge_log', $new);

        $this->assertSame(0, logger::purge(0));
        $this->assertEquals(2, $DB->count_records('local_imisbridge_log'));

        $this->assertSame(1, logger::purge(90));
        $this->assertEquals(1, $DB->count_records('local_imisbridge_log'));
        $this->assertTrue($DB->record_exists('local_imisbridge_log', ['operation' => 'new']));
    }

    /**
     * A fault message is redacted before storage, even with payload capture off.
     *
     * @return void
     */
    public function test_error_is_redacted(): void {
        global $DB;
        $this->resetAfterTest();

        logger::record('MoodleUpdate', '12345', null, logger::STATUS_ERROR, null,
            'Auth failed for token MO-badtoken');

        $row = $DB->get_record('local_imisbridge_log', ['operation' => 'MoodleUpdate'], '*', MUST_EXIST);
        $this->assertStringNotContainsString('MO-badtoken', $row->error);
        $this->assertStringContainsString('MO-[redacted]', $row->error);
    }

    /**
     * An iMIS ID that matches more than one user is left unattributed (userid 0).
     *
     * @return void
     */
    public function test_ambiguous_imisid_is_unattributed(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('imis_id_source', 'idnumber', 'local_imisbridge');
        $this->getDataGenerator()->create_user(['idnumber' => 'DUP1']);
        $this->getDataGenerator()->create_user(['idnumber' => 'DUP1']);

        logger::record('MoodleUpdate', 'DUP1', null, logger::STATUS_SUCCESS, null, null);

        $row = $DB->get_record('local_imisbridge_log', ['operation' => 'MoodleUpdate'], '*', MUST_EXIST);
        $this->assertSame(0, (int)$row->userid);
    }
}
