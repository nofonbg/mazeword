<?php
namespace mod_mazeword\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event for when a maze attempt is completed.
 */
class attempt_completed extends \core\event\base {

    /**
     * Init the event.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'mazeword_attempts';
    }

    /**
     * Get the name of the event.
     *
     * @return string - Event name.
     */
    public static function get_name(): string {
        return get_string('eventattemptcompleted', 'mod_mazeword');
    }

    /**
     * Get a description of the event.
     *
     * @return string - Event description.
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' completed the maze activity with " .
               "course module id '{$this->contextinstanceid}'.";
    }

    /**
     * Get the URL for the event.
     *
     * @return \moodle_url - Event URL.
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/mazeword/view.php', ['id' => $this->contextinstanceid]);
    }
}
