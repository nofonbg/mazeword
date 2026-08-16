<?php
namespace mod_mazeword\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable for the mazeword game view.
 */
class game_view implements \renderable {

    /**
     * @var \mod_mazeword\mazeword The mazeword instance.
     */
    public $instance;

    /**
     * @var \stdClass The attempt data.
     */
    public $attempt;

    /**
     * @var bool Whether the user is a teacher.
     */
    public $isteacher;

    /**
     * Constructor.
     *
     * @param \mod_mazeword\mazeword $instance - The mazeword instance.
     * @param \stdClass $attempt - The attempt data.
     * @param bool $isteacher - Whether the user is a teacher.
     */
    public function __construct(\mod_mazeword\mazeword $instance, \stdClass $attempt, bool $isteacher = false) {
        $this->instance = $instance;
        $this->attempt = $attempt;
        $this->isteacher = $isteacher;
    }
}
