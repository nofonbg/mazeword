<?php
defined('MOODLE_INTERNAL') || die();

class backup_mazeword_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        $mazeword = new backup_nested_element('mazeword', ['id'], [
            'course', 'name', 'intro', 'introformat',
            'solutionword', 'wrongword1', 'wrongword2', 'wrongword3',
            'mazesize', 'timecreated', 'timemodified',
        ]);

        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', ['id'], [
            'userid', 'playerx', 'playery',
            'visitedcells', 'solutionvisited', 'clearedcells',
            'collectedletters', 'movetrail',
            'completed', 'completionsource',
            'timecreated', 'timemodified', 'timecompleted',
        ]);

        $mazeword->add_child($attempts);
        $attempts->add_child($attempt);

        $mazeword->set_source_table('mazeword', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $attempt->set_source_table('mazeword_attempts', ['mazewordid' => backup::VAR_PARENTID]);
        }

        $attempt->annotate_ids('user', 'userid');
        $mazeword->annotate_files('mod_mazeword', 'intro', null);

        return $this->prepare_activity_structure($mazeword);
    }
}
