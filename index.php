<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/mazeword/lib.php');

global $DB;

$courseid = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_course_login($course, true);

$context = context_course::instance($course->id);
require_capability('mod/mazeword:view', $context);

// Get all mazeword instances.
$mazewords = $DB->get_records('mazeword', ['course' => $courseid], 'name ASC');

$PAGE->set_url('/mod/mazeword/index.php', ['id' => $courseid]);
$PAGE->set_title($course->shortname . ': ' . get_string('modulenameplural', 'mod_mazeword'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'mod_mazeword'));

if ($mazewords) {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable modindex';
    $table->head = [
        get_string('name'),
        get_string('solutionpath', 'mod_mazeword'),
        get_string('mazesize', 'mod_mazeword'),
    ];
    $table->colclasses = ['left', 'center', 'center'];

    foreach ($mazewords as $mazeword) {
        $cm = get_coursemodule_from_instance('mazeword', $mazeword->id, $courseid, false, MUST_EXIST);
        $link = new moodle_url('/mod/mazeword/view.php', ['id' => $cm->id]);

        $row = new html_table_row();
        $row->cells = [
            html_writer::link($link, format_string($mazeword->name)),
            format_string($mazeword->solutionword),
            $mazeword->mazesize . 'x' . $mazeword->mazesize,
        ];
        $table->data[] = $row;
    }

    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('thereareno', 'mod_mazeword', get_string('modulenameplural', 'mod_mazeword')));
}

echo $OUTPUT->footer();
