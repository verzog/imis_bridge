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
 * Shared helpers for local_imisbridge.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_imisbridge;

/**
 * Small utility helpers.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class util {
    /**
     * Whether a sync toggle is enabled.
     *
     * Every toggle defaults to on: an unset value (e.g. immediately after an
     * upgrade, before defaults are written) is treated as enabled so existing
     * behaviour is preserved. Only an explicit '0' disables the operation.
     *
     * @param string $name The plugin config name of the checkbox setting.
     * @return bool
     */
    public static function is_enabled(string $name): bool {
        $value = get_config('local_imisbridge', $name);
        return ($value === false) ? true : (bool)$value;
    }
}
