<?php
namespace mod_mazeword;

defined('MOODLE_INTERNAL') || die();

class mazeword {

    public $cm;
    public $course;
    public $mazeword;
    public $context;

    public function __construct($cm, $course, $mazeword) {
        $this->cm = $cm;
        $this->course = $course;
        $this->mazeword = $mazeword;
        $this->context = \context_module::instance($cm->id);
    }

    public function get_or_create_attempt(?int $userid = null) {
        global $DB, $USER;

        if ($userid === null) {
            $userid = $USER->id;
        }

        $attempt = $DB->get_record('mazeword_attempts', [
            'mazewordid' => $this->mazeword->id,
            'userid' => $userid,
        ]);

        if (!$attempt) {
            $attempt = new \stdClass();
            $attempt->mazewordid = $this->mazeword->id;
            $attempt->userid = $userid;
            $attempt->playerx = 0;
            $attempt->playery = 0;
            $attempt->visitedcells = json_encode([]);
            $attempt->solutionvisited = json_encode([]);
            $attempt->clearedcells = json_encode([]);
            $attempt->collectedletters = json_encode([]);
            $attempt->movetrail = json_encode([]);
            $attempt->completed = 0;
            $attempt->completionsource = 0;
            $attempt->timecreated = time();
            $attempt->timemodified = time();
            $attempt->id = $DB->insert_record('mazeword_attempts', $attempt);
        }

        return $attempt;
    }

    public function save_state(
        int $playerx,
        int $playery,
        array $visitedcells,
        array $solutionvisited,
        bool $completed = false,
        array $clearedcells = [],
        array $collectedletters = [],
        array $movetrail = []
    ): bool {
        global $DB, $USER;

        $attempt = $DB->get_record('mazeword_attempts', [
            'mazewordid' => $this->mazeword->id,
            'userid' => $USER->id,
        ]);

        if (!$attempt) {
            return false;
        }

        $attempt->playerx = $playerx;
        $attempt->playery = $playery;
        $attempt->visitedcells = json_encode($visitedcells);
        $attempt->solutionvisited = json_encode($solutionvisited);
        $attempt->clearedcells = json_encode($clearedcells);
        $attempt->collectedletters = json_encode($collectedletters);
        $attempt->movetrail = json_encode($movetrail);
        $attempt->timemodified = time();

        if ($completed && !$attempt->completed) {
            $attempt->completed = 1;
            $attempt->timecompleted = time();

            $event = \mod_mazeword\event\attempt_completed::create([
                'objectid' => $this->mazeword->id,
                'context' => $this->context,
                'userid' => $USER->id,
                'other' => [
                    'mazewordid' => $this->mazeword->id,
                ],
            ]);
            $event->add_record_snapshot('mazeword', $this->mazeword);
            $event->trigger();

            $completion = new \completion_info($this->course);
            $completion->update_state($this->cm, \COMPLETION_COMPLETE, $USER->id);
        }

        return $DB->update_record('mazeword_attempts', $attempt);
    }

    public function reset_attempt(): bool {
        global $DB, $USER;

        $attempt = $DB->get_record('mazeword_attempts', [
            'mazewordid' => $this->mazeword->id,
            'userid' => $USER->id,
        ]);

        if (!$attempt) {
            return false;
        }

        $attempt->playerx = 0;
        $attempt->playery = 0;
        $attempt->visitedcells = json_encode([]);
        $attempt->solutionvisited = json_encode([]);
        $attempt->clearedcells = json_encode([]);
        $attempt->collectedletters = json_encode([]);
        $attempt->movetrail = json_encode([]);
        $attempt->completed = 0;
        $attempt->timecompleted = 0;
        $attempt->timemodified = time();

        return $DB->update_record('mazeword_attempts', $attempt);
    }

    public function get_statistics(): \stdClass {
        global $DB;

        $stats = new \stdClass();

        $stats->total_attempts = $DB->count_records('mazeword_attempts', [
            'mazewordid' => $this->mazeword->id,
        ]);

        $stats->completed_attempts = $DB->count_records('mazeword_attempts', [
            'mazewordid' => $this->mazeword->id,
            'completed' => 1,
        ]);

        $stats->total_enrolled = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT ue.userid) FROM {user_enrolments} ue
             JOIN {enrol} e ON e.id = ue.enrolid
             WHERE e.courseid = ?",
            [$this->course->id]
        );

        return $stats;
    }

    public function get_game_config(): array {
        return [
            'solutionword' => strtoupper($this->mazeword->solutionword),
            'wrongword1' => strtoupper($this->mazeword->wrongword1),
            'wrongword2' => strtoupper($this->mazeword->wrongword2),
            'wrongword3' => strtoupper($this->mazeword->wrongword3),
            'mazesize' => (int) $this->mazeword->mazesize,
            'instanceid' => $this->mazeword->id,
            'cmid' => $this->cm->id,
            'sesskey' => sesskey(),
        ];
    }
}
