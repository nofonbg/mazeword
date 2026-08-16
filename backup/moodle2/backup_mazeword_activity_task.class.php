<?php
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/mazeword/backup/moodle2/backup_mazeword_stepslib.php');
require_once($CFG->dirroot . '/mod/mazeword/backup/moodle2/backup_mazeword_settingslib.php');

class backup_mazeword_activity_task extends backup_activity_task {

    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
        $this->add_step(new backup_mazeword_activity_structure_step('mazeword_structure', 'mazeword.xml'));
    }

    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        $search = "/(" . $base . "\/mod\/mazeword\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MAZEWORDINDEX*$2@$', $content);

        $search = "/(" . $base . "\/mod\/mazeword\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@MAZEWORDVIEWBYID*$2@$', $content);

        return $content;
    }
}
