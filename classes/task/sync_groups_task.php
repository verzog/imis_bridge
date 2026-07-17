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
 * Scheduled task: sync iMIS groups (incremental, based on last run time).
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imisbridge\task;

/**
 * Syncs iMIS groups, incrementally based on the last run timestamp.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_groups_task extends \core\task\scheduled_task {
    /**
     * Returns the task name shown in the Moodle admin UI.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_sync_groups', 'local_imisbridge');
    }

    /**
     * Executes the group sync, passing the last run time for incremental updates.
     *
     * @return void
     */
    public function execute(): void {
        mtrace('iMIS Bridge: Starting group sync...');
        try {
            $client = new \local_imisbridge\imis_client();

            // Only sync groups changed since the last time this task ran.
            // get_last_run_time() returns a Unix timestamp; convert to UTC string.
            // Null means sync everything (first run).
            $lastrun     = $this->get_last_run_time();
            $lastupdated = $lastrun ? gmdate('Y-m-d\TH:i:s', $lastrun) : null;

            $result = $client->update_groups(null, null, $lastupdated);
            mtrace('iMIS Bridge: Group sync complete. Result: ' . var_export($result, true));
        } catch (\Exception $e) {
            mtrace('iMIS Bridge: Group sync FAILED: ' . $e->getMessage());
            throw $e;
        }
    }
}
