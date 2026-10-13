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
 * External web service definitions for local_imisbridge.
 *
 * @package    local_imisbridge
 * @copyright  2024 Vernon Spain
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_imisbridge_get_completions_since' => [
        'classname'   => 'local_imisbridge\\external\\get_completions_since',
        'methodname'  => 'execute',
        'description' => 'Return course completions and final course grades changed since a given time, '
            . 'for bulk pull synchronisation to iMIS.',
        'type'        => 'read',
        'capabilities' => 'local/imisbridge:readcompletions',
        'ajax'        => false,
    ],
];

// A pre-built, restricted service so iMIS can be issued a token for just this
// function. Enabled, but reaches nothing until the readcompletions capability
// is granted to the token's user.
$services = [
    'iMIS bridge' => [
        'functions'       => ['local_imisbridge_get_completions_since'],
        'restrictedusers' => 1,
        'enabled'         => 1,
        'shortname'       => 'local_imisbridge',
        'downloadfiles'   => 0,
        'uploadfiles'     => 0,
    ],
];
