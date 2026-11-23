<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade step for mod_mazeword.
 *
 * @param int $oldversion The version we are upgrading from.
 * @param core_progress $progress Progress bar.
 * @return bool
 */
function mod_mazeword_upgrade($oldversion, $progress) {
    global $DB;

    if ($oldversion < 2026080911) {
        // The maze is generated on an odd sized grid: rooms sit on odd
        // coordinates and row/column 0 and size-1 are border walls. Even sizes
        // (10/20/30) could not form a symmetric border and put the goal on a
        // wall, so they are raised to the next odd size.
        $records = $DB->get_records('mazeword', [], '', 'id, mazesize');
        foreach ($records as $record) {
            if (((int) $record->mazesize) % 2 === 0) {
                $record->mazesize = ((int) $record->mazesize) + 1;
                $DB->update_record('mazeword', $record);
            }
        }

        // The start cell moved from (0,0) to (1,1) and the border is now closed,
        // therefore all saved attempts are reset.
        $DB->delete_records('mazeword_attempts');

        upgrade_mod_savepoint(true, 2026080911, 'mazeword');
    }

    return true;
}
