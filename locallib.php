<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Save the maze game state via AJAX.
 *
 * @param int $cmid - Course module ID.
 * @param int $playerx - Player X position.
 * @param int $playery - Player Y position.
 * @param string $visitedcells - JSON string of visited cells.
 * @param string $solutionvisited - JSON string of visited solution cells.
 * @param bool $completed - Whether the game is completed.
 * @return object - Response object with success flag.
 */
function mazeword_save_state($cmid, $playerx, $playery, $visitedcells, $solutionvisited, $completed = false) {
    global $DB, $USER;

    $cm = get_coursemodule_from_id('mazeword', $cmid);
    if (!$cm) {
        return (object) ['success' => false, 'error' => 'Invalid course module'];
    }

    $context = context_module::instance($cm->id);
    require_capability('mod/mazeword:attempt', $context);

    $mazewordrecord = $DB->get_record('mazeword', ['id' => $cm->instance]);
    if (!$mazewordrecord) {
        return (object) ['success' => false, 'error' => 'Invalid activity'];
    }

    $mazeword = new \mod_mazeword\mazeword($cm, $DB->get_record('course', ['id' => $cm->course]), $mazewordrecord);

    $visitedcellsarr = json_decode($visitedcells, true) ?? [];
    $solutionvisitedarr = json_decode($solutionvisited, true) ?? [];

    $result = $mazeword->save_state(
        $playerx,
        $playery,
        $visitedcellsarr,
        $solutionvisitedarr,
        $completed
    );

    return (object) ['success' => $result];
}

/**
 * Reset the maze game state via AJAX.
 *
 * @param int $cmid - Course module ID.
 * @return object - Response object with success flag.
 */
function mazeword_reset_state($cmid) {
    global $DB, $USER;

    $cm = get_coursemodule_from_id('mazeword', $cmid);
    if (!$cm) {
        return (object) ['success' => false, 'error' => 'Invalid course module'];
    }

    $context = context_module::instance($cm->id);
    require_capability('mod/mazeword:attempt', $context);

    $mazewordrecord = $DB->get_record('mazeword', ['id' => $cm->instance]);
    if (!$mazewordrecord) {
        return (object) ['success' => false, 'error' => 'Invalid activity'];
    }

    $mazeword = new \mod_mazeword\mazeword($cm, $DB->get_record('course', ['id' => $cm->course]), $mazewordrecord);

    $result = $mazeword->reset_attempt();

    return (object) ['success' => $result];
}
