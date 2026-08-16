<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/mazeword/lib.php');
require_once($CFG->dirroot . '/mod/mazeword/locallib.php');

global $DB;

// Get parameters.
$id = optional_param('id', 0, PARAM_INT); // Course module ID.
$m = optional_param('m', 0, PARAM_INT);  // Mazeword instance ID.

if ($id) {
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'mazeword');
    $mazewordrecord = $DB->get_record('mazeword', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $mazewordrecord = $DB->get_record('mazeword', ['id' => $m], '*', MUST_EXIST);
    list($course, $cm) = get_course_and_cm_from_instance($mazewordrecord->id, 'mazeword');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/mazeword:view', $context);

// Create the mazeword instance.
$mazeword = new \mod_mazeword\mazeword($cm, $course, $mazewordrecord);

// Check if trying to save state via AJAX POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $cmid = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($cmid !== (int) $cm->id) {
        echo json_encode(['success' => false, 'error' => 'Mismatched CM ID']);
        die();
    }

    if ($action === 'save_state') {
        require_capability('mod/mazeword:attempt', $context);

        $playerx = isset($_POST['playerx']) ? (int) $_POST['playerx'] : 0;
        $playery = isset($_POST['playery']) ? (int) $_POST['playery'] : 0;
        $visitedcells = isset($_POST['visitedcells']) ? $_POST['visitedcells'] : '[]';
        $solutionvisited = isset($_POST['solutionvisited']) ? $_POST['solutionvisited'] : '[]';
        $completed = isset($_POST['completed']) ? (int) $_POST['completed'] : 0;
        $clearedcells = isset($_POST['clearedcells']) ? $_POST['clearedcells'] : '[]';
        $collectedletters = isset($_POST['collectedletters']) ? $_POST['collectedletters'] : '[]';
        $movetrail = isset($_POST['movetrail']) ? $_POST['movetrail'] : '[]';

        $visitedcellsarr = json_decode($visitedcells, true) ?? [];
        $solutionvisitedarr = json_decode($solutionvisited, true) ?? [];
        $clearedcellsarr = json_decode($clearedcells, true) ?? [];
        $collectedlettersarr = json_decode($collectedletters, true) ?? [];
        $movetrailarr = json_decode($movetrail, true) ?? [];

        $mazeword->save_state(
            $playerx,
            $playery,
            $visitedcellsarr,
            $solutionvisitedarr,
            (bool) $completed,
            $clearedcellsarr,
            $collectedlettersarr,
            $movetrailarr
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        die();
    }

    if ($action === 'reset') {
        require_capability('mod/mazeword:attempt', $context);

        $mazeword->reset_attempt();

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        die();
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    die();
}

// Trigger module viewed event.
$event = \mod_mazeword\event\course_module_viewed::create([
    'objectid' => $mazewordrecord->id,
    'context' => $context,
    'userid' => $USER->id,
]);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('mazeword', $mazewordrecord);
$event->trigger();

// Mark module as viewed for completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Set up page.
$PAGE->set_url('/mod/mazeword/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($mazewordrecord->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->add_body_class('mod_mazeword');

// Check if user is teacher.
$isteacher = has_capability('mod/mazeword:configure', $context);

// Get or create attempt.
$attempt = $mazeword->get_or_create_attempt();

// Display page.
echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_mazeword');
echo $renderer->render(new \mod_mazeword\output\game_view($mazeword, $attempt, $isteacher));

echo $OUTPUT->footer();
