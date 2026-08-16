<?php
namespace mod_mazeword\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for the mazeword module.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the types of data stored or transmitted by this plugin.
     *
     * @param collection $collection - The collection to add metadata to.
     * @return collection - The updated collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'mazeword_attempts',
            [
                'userid' => 'privacy:metadata:mazeword_attempts:userid',
                'completed' => 'privacy:metadata:mazeword_attempts:completed',
                'playerx' => 'privacy:metadata:mazeword_attempts:playerx',
                'playery' => 'privacy:metadata:mazeword_attempts:playery',
                'visitedcells' => 'privacy:metadata:mazeword_attempts:visitedcells',
                'solutionvisited' => 'privacy:metadata:mazeword_attempts:solutionvisited',
            ],
            'privacy:metadata:mazeword_attempts'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid - The user to search.
     * @return contextlist - The contextlist containing contexts with user data.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {mazeword} y ON y.id = cm.instance
            INNER JOIN {mazeword_attempts} ya ON ya.mazewordid = y.id
                 WHERE ya.userid = :userid";

        $params = [
            'modname' => 'mazeword',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist.
     *
     * @param approved_contextlist $contextlist - The approved contexts to export for.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $cmid = $context->instanceid;
            $cm = $DB->get_record('course_modules', ['id' => $cmid]);
            if (!$cm) {
                continue;
            }

            $instance = $DB->get_record('mazeword', ['id' => $cm->instance]);
            if (!$instance) {
                continue;
            }

            $attempts = $DB->get_records('mazeword_attempts', [
                'mazewordid' => $instance->id,
                'userid' => $userid,
            ]);

            $data = [];
            foreach ($attempts as $attempt) {
                $data[] = [
                    'completed' => $attempt->completed,
                    'timecreated' => transform::datetime($attempt->timecreated),
                    'timemodified' => transform::datetime($attempt->timemodified),
                    'timecompleted' => $attempt->timecompleted
                        ? transform::datetime($attempt->timecompleted) : null,
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('modulename', 'mod_mazeword')],
                (object) ['attempts' => $data]
            );
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context - The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
        if (!$cm) {
            return;
        }

        $instance = $DB->get_record('mazeword', ['id' => $cm->instance]);
        if (!$instance) {
            return;
        }

        $DB->delete_records('mazeword_attempts', ['mazewordid' => $instance->id]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist - The approved contexts and user to delete for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);
            if (!$cm) {
                continue;
            }

            $instance = $DB->get_record('mazeword', ['id' => $cm->instance]);
            if (!$instance) {
                continue;
            }

            $DB->delete_records('mazeword_attempts', [
                'mazewordid' => $instance->id,
                'userid' => $userid,
            ]);
        }
    }
}
