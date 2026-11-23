<?php
defined('MOODLE_INTERNAL') || die();

if (!defined('MOD_ARCHETYPE_ACTIVITY')) {
    define('MOD_ARCHETYPE_ACTIVITY', 0);
}

/**
 * List of features supported in this module.
 *
 * @param string $feature FEATURE_xx constant for requested feature.
 * @return mixed True if module supports feature, false if not, null if doesn't know.
 */
function mazeword_supports($feature): mixed {
    return match ($feature) {
        FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_ACTIVITY,
        FEATURE_GROUPS => true,
        FEATURE_GROUPINGS => true,
        FEATURE_MOD_INTRO => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_GRADE_HAS_GRADE => false,
        FEATURE_GRADE_OUTCOMES => false,
        FEATURE_BACKUP_MOODLE2 => true,
        default => null,
    };
}

/**
 * Upgrade function.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Always returns true.
 */
function xmldb_mazeword_upgrade($oldversion) {
    return true;
}

/**
 * Add activity instance to database.
 *
 * @param object $data - Form data.
 * @param object|null $mform - Form instance (may be null).
 * @return int - The instance id of the new activity.
 */
function mazeword_add_instance(stdClass $data, ?object $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    // Convert words to uppercase, handle ß → SS.
    $data->solutionword = strtoupper(str_replace('ß', 'SS', trim($data->solutionword ?? '')));
    $data->wrongword1 = strtoupper(str_replace('ß', 'SS', trim($data->wrongword1 ?? '')));
    $data->wrongword2 = strtoupper(str_replace('ß', 'SS', trim($data->wrongword2 ?? '')));
    $data->wrongword3 = strtoupper(str_replace('ß', 'SS', trim($data->wrongword3 ?? '')));
    if (!isset($data->mazesize) || empty($data->mazesize)) {
        $data->mazesize = 21;
    }

    $data->id = $DB->insert_record('mazeword', $data);

    return $data->id;
}

/**
 * Update activity instance in database.
 *
 * @param object $data - Form data.
 * @param object|null $mform - Form instance (may be null).
 * @return bool - True on success.
 */
function mazeword_update_instance(stdClass $data, ?object $mform = null): bool {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    // Convert words to uppercase, handle ß → SS.
    $data->solutionword = strtoupper(str_replace('ß', 'SS', trim($data->solutionword ?? '')));
    $data->wrongword1 = strtoupper(str_replace('ß', 'SS', trim($data->wrongword1 ?? '')));
    $data->wrongword2 = strtoupper(str_replace('ß', 'SS', trim($data->wrongword2 ?? '')));
    $data->wrongword3 = strtoupper(str_replace('ß', 'SS', trim($data->wrongword3 ?? '')));

    $result = $DB->update_record('mazeword', $data);

    return $result;
}

/**
 * Delete activity instance from database.
 *
 * @param int $id - Instance id.
 * @return bool - True on success.
 */
function mazeword_delete_instance($id): bool {
    global $DB;

    if (!$mazeword = $DB->get_record('mazeword', ['id' => $id])) {
        return false;
    }

    // Delete completion data.
    $DB->delete_records('mazeword_attempts', ['mazewordid' => $id]);

    // Delete files.
    $cm = get_coursemodule_from_instance('mazeword', $id);
    if ($cm) {
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_mazeword');
    }

    $DB->delete_records('mazeword', ['id' => $id]);

    return true;
}

/**
 * Returns the scales used in the module.
 *
 * @param int $courseid - Course id.
 * @return array - Array of scale ids => scale names.
 */
function mazeword_get_scales($courseid): array {
    return [];
}

/**
 * User outline for grade report.
 *
 * @param object $course - Course object.
 * @param object $user - User object.
 * @param object $mod - Course module object.
 * @param object $mazeword - Module instance object.
 * @return object|null - Summary object or null.
 */
function mazeword_user_outline($course, $user, $mod, $mazeword): ?object {
    global $DB;

    $attempt = $DB->get_record('mazeword_attempts', [
        'mazewordid' => $mazeword->id,
        'userid' => $user->id,
    ]);

    if ($attempt) {
        $result = new stdClass();
        if ($attempt->completed) {
            $result->info = get_string('completed', 'mod_mazeword');
        } else {
            $result->info = get_string('inprogress', 'mod_mazeword');
        }
        $result->time = $attempt->timemodified;
        return $result;
    }

    return null;
}

/**
 * User complete information for grade report.
 *
 * @param object $course - Course object.
 * @param object $user - User object.
 * @param object $mod - Course module object.
 * @param object $mazeword - Module instance object.
 */
function mazeword_user_complete($course, $user, $mod, $mazeword): void {
    $outline = mazeword_user_outline($course, $user, $mod, $mazeword);
    if ($outline) {
        echo $outline->info . ' (' . userdate($outline->time) . ')';
    } else {
        echo get_string('notattempted', 'mod_mazeword');
    }
}

/**
 * Mark the activity viewed for completion tracking.
 *
 * @param object $course - Course object.
 * @param object $user - User object.
 * @param object $mod - Course module object.
 * @param object $mazeword - Module instance object.
 */
function mazeword_view($course, $user, $mod, $mazeword): void {
    global $CFG;
    require_once($CFG->libdir . '/completionlib.php');

    $completion = new completion_info($course);
    $completion->set_module_viewed($mod, $user->id);
}

/**
 * Reset user data for a course reset.
 *
 * @param object $data - Reset data.
 * @return array - Status messages.
 */
function mazeword_reset_userdata(object $data): array {
    global $DB;

    $status = [];
    $component = get_string('modulenameplural', 'mod_mazeword');

    if (!empty($data->reset_mazeword_attempts)) {
        $DB->delete_records_select('mazeword_attempts',
            'mazewordid IN (SELECT id FROM {mazeword} WHERE course = ?)',
            [$data->courseid]);

        $status[] = [
            'component' => $component,
            'item' => get_string('deleteallattempts', 'mod_mazeword'),
            'error' => false,
        ];
    }

    return $status;
}

/**
 * Pluginfile function for serving files.
 *
 * @param object $course - Course object.
 * @param object $cm - Course module object.
 * @param object $context - Context object.
 * @param string $filearea - File area name.
 * @param array $args - Path arguments.
 * @param bool $forcedownload - Force download flag.
 * @param array $options - Additional options.
 * @return bool - False if file not found.
 */
function mazeword_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []): bool {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    if (!has_capability('mod/mazeword:view', $context)) {
        return false;
    }

    if ($filearea !== 'intro' && $filearea !== 'attachment') {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_mazeword', $filearea, $itemid, $filepath, $filename);

    if (!$file) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
