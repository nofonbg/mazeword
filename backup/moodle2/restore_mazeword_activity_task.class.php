<?php
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/mazeword/backup/moodle2/restore_mazeword_stepslib.php');

class restore_mazeword_activity_task extends restore_activity_task {

    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new restore_mazeword_activity_structure_step('mazeword_structure', 'mazeword.xml'));
    }

    public static function define_decode_contents(): array {
        $contents = [];
        $contents[] = new restore_decode_content('mazeword', ['intro'], 'mazeword');
        return $contents;
    }

    public static function define_decode_rules(): array {
        $rules = [];
        $rules[] = new restore_decode_rule('MAZEWORDINDEX', '/mod/mazeword/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('MAZEWORDVIEWBYID', '/mod/mazeword/view.php?id=$1', 'course_module');
        return $rules;
    }

    public static function define_restore_log_rules(): array {
        $rules = [];
        $rules[] = new restore_log_rule('mazeword', 'view', 'view.php?id={course_module}', '{folder}');
        return $rules;
    }

    public static function define_restore_log_rules_for_course(): array {
        $rules = [];
        $rules[] = new restore_log_rule('mazeword', 'view all', 'index.php?id={course}', null);
        return $rules;
    }
}
