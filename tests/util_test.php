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
 * Tests for the local_imisbridge util helpers.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imisbridge;

/**
 * Unit tests for {@see util}.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_imisbridge\util
 */
final class util_test extends \advanced_testcase {
    /**
     * Toggles default to on when unset, and only an explicit '0' disables them.
     *
     * @return void
     */
    public function test_is_enabled(): void {
        $this->resetAfterTest();

        // Unset is treated as enabled so upgrades keep existing behaviour.
        $this->assertTrue(util::is_enabled('push_completions'));

        set_config('push_completions', '0', 'local_imisbridge');
        $this->assertFalse(util::is_enabled('push_completions'));

        set_config('push_completions', '1', 'local_imisbridge');
        $this->assertTrue(util::is_enabled('push_completions'));
    }
}
