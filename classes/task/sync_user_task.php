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
 * Adhoc task: synchronise a single iMIS user off the request path.
 *
 * Queued on login so the external SOAP round-trips do not block the user.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imisbridge\task;

/**
 * Synchronises one iMIS user's enrolments, cancellations and groups.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_user_task extends \core\task\adhoc_task {
    /**
     * Returns the task name shown in the Moodle admin UI.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_sync_user', 'local_imisbridge');
    }

    /**
     * Executes the per-user sync. Exceptions propagate so Moodle retries with backoff.
     *
     * @return void
     */
    public function execute(): void {
        $data   = $this->get_custom_data();
        $imisid = $data->imisid ?? '';

        if (empty($imisid)) {
            return;
        }

        mtrace('iMIS Bridge: syncing user ' . $imisid . '...');

        $client = new \local_imisbridge\imis_client();
        $client->sync_orders($imisid);
        $client->sync_cancelled_orders($imisid);
        $client->update_groups($imisid);

        mtrace('iMIS Bridge: user sync complete for ' . $imisid . '.');
    }
}
