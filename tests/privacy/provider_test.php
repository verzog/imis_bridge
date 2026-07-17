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
 * Tests for the local_imisbridge privacy provider.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imisbridge\privacy;

use core_privacy\local\metadata\collection;

/**
 * Unit tests for {@see provider}.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_imisbridge\privacy\provider
 */
final class provider_test extends \advanced_testcase {
    /**
     * The provider declares the external iMIS data flow.
     *
     * @return void
     */
    public function test_get_metadata(): void {
        $this->resetAfterTest();

        $collection = new collection('local_imisbridge');
        $result     = provider::get_metadata($collection);

        $this->assertInstanceOf(collection::class, $result);

        $items = $result->get_collection();
        $this->assertCount(1, $items);

        $link = reset($items);
        $this->assertSame('imis', $link->get_name());
        $this->assertArrayHasKey('userid', $link->get_privacy_fields());
    }
}
