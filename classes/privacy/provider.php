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
 * Privacy Subsystem implementation for local_imisbridge.
 *
 * The plugin stores no personal data in Moodle, but it transmits personal data
 * to the external iMIS association management system, which is declared here in
 * line with the Australian Privacy Principles.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imisbridge\privacy;

use core_privacy\local\metadata\collection;

/**
 * Privacy provider declaring the external iMIS data flow.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider {
    /**
     * Describe the personal data transmitted to iMIS.
     *
     * @param collection $collection The metadata collection to add to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
            'imis',
            [
                'userid'         => 'privacy:metadata:imis:userid',
                'courseid'       => 'privacy:metadata:imis:courseid',
                'status'         => 'privacy:metadata:imis:status',
                'score'          => 'privacy:metadata:imis:score',
                'completiondate' => 'privacy:metadata:imis:completiondate',
            ],
            'privacy:metadata:imis'
        );

        return $collection;
    }
}
