<?php
defined('MOODLE_INTERNAL') || die();

class restore_mazeword_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure(): array {

        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('mazeword', '/activity/mazeword');
        if ($userinfo) {
            $paths[] = new restore_path_element('mazeword_attempt', '/activity/mazeword/attempts/attempt');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_mazeword($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newid = $DB->insert_record('mazeword', $data);
        $this->set_mapping('mazeword', $oldid, $newid);
        $this->apply_activity_instance($newid);
    }

    protected function process_mazeword_attempt($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->mazewordid = $this->get_new_parentid('mazeword');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        if (!empty($data->timecompleted)) {
            $data->timecompleted = $this->apply_date_offset($data->timecompleted);
        }

        $newid = $DB->insert_record('mazeword_attempts', $data);
        $this->set_mapping('mazeword_attempt', $oldid, $newid);
    }

    protected function after_execute(): void {
        $this->add_related_files('mod_mazeword', 'intro', null);
    }
}
