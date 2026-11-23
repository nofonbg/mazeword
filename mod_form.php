<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_mazeword_mod_form extends moodleform_mod {

    public function definition(): void {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('activityname', 'mod_mazeword'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'mazewordsection', get_string('mazewordsettings', 'mod_mazeword'));

        $mform->addElement('text', 'solutionword', get_string('solutionword', 'mod_mazeword'), ['size' => '40']);
        $mform->setType('solutionword', PARAM_TEXT);
        $mform->addRule('solutionword', null, 'required', null, 'client');
        $mform->addHelpButton('solutionword', 'solutionword', 'mod_mazeword');

        $mform->addElement('text', 'wrongword1', get_string('wrongword1', 'mod_mazeword'), ['size' => '40']);
        $mform->setType('wrongword1', PARAM_TEXT);
        $mform->addRule('wrongword1', null, 'required', null, 'client');
        $mform->addHelpButton('wrongword1', 'wrongword1', 'mod_mazeword');

        $mform->addElement('text', 'wrongword2', get_string('wrongword2', 'mod_mazeword'), ['size' => '40']);
        $mform->setType('wrongword2', PARAM_TEXT);
        $mform->addRule('wrongword2', null, 'required', null, 'client');

        $mform->addElement('text', 'wrongword3', get_string('wrongword3', 'mod_mazeword'), ['size' => '40']);
        $mform->setType('wrongword3', PARAM_TEXT);
        $mform->addRule('wrongword3', null, 'required', null, 'client');

        // Only odd sizes: they allow a symmetric border wall around the maze.
        $mform->addElement('select', 'mazesize', get_string('mazesize', 'mod_mazeword'), [
            11 => '11x11',
            15 => '15x15',
            21 => '21x21',
            25 => '25x25',
            31 => '31x31',
        ]);
        $mform->setDefault('mazesize', 21);
        $mform->addHelpButton('mazesize', 'mazesize', 'mod_mazeword');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $sol = strtoupper(str_replace('ß', 'SS', trim($data['solutionword'] ?? '')));
        $data['wrongword1'] = strtoupper(str_replace('ß', 'SS', trim($data['wrongword1'] ?? '')));
        $data['wrongword2'] = strtoupper(str_replace('ß', 'SS', trim($data['wrongword2'] ?? '')));
        $data['wrongword3'] = strtoupper(str_replace('ß', 'SS', trim($data['wrongword3'] ?? '')));

        if (!empty($sol)) {
            if (mb_strlen($sol) < 3) {
                $errors['solutionword'] = get_string('wordtooshort', 'mod_mazeword');
            }
            if (mb_strlen($sol) > 30) {
                $errors['solutionword'] = get_string('wordtoolong', 'mod_mazeword');
            }
            if (!preg_match('/^[A-ZÄÖÜ]+$/', $sol)) {
                $errors['solutionword'] = get_string('wordonlyletters', 'mod_mazeword');
            }
        }

        foreach (['wrongword1', 'wrongword2', 'wrongword3'] as $field) {
            if (!empty($data[$field])) {
                if (mb_strlen($data[$field]) < 3) {
                    $errors[$field] = get_string('wordtooshort', 'mod_mazeword');
                }
                if (!preg_match('/^[A-ZÄÖÜ]+$/', $data[$field])) {
                    $errors[$field] = get_string('wordonlyletters', 'mod_mazeword');
                }
            }
        }

        if (!empty($sol)) {
            foreach (['wrongword1', 'wrongword2', 'wrongword3'] as $field) {
                if (!empty($data[$field]) && $data[$field] === $sol) {
                    $errors[$field] = get_string('wordmustdiffer', 'mod_mazeword');
                }
            }
        }

        return $errors;
    }
}
