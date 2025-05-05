<?php

/**
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace assignfeedback_smartfeedback\event;

use assignfeedback_smartfeedback\api;

defined('MOODLE_INTERNAL') || die();


class submission_observer
{
    /**
     * Observer for submission_created event
     *
     * @param \mod_assign\event\submission_created $event The event
     */
    public static function submission_created(\mod_assign\event\submission_created $event)
    {
        self::process_submission($event);
    }

    /**
     * Observer for submission_updated event
     *
     * @param \mod_assign\event\submission_updated $event The event
     */
    public static function submission_updated(\mod_assign\event\submission_updated $event)
    {
        self::process_submission($event);
    }

    private static function process_submission(\core\event\base $event)
    {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $submission = self::get_submission_from_event($event);
        $context = $event->get_context();
        $cm = get_coursemodule_from_id('assign', $context->instanceid);
        $assignment = self::get_assignment_from_cm($cm);
        $assign = new \assign($context, $cm, $assignment->course);

        if (!self::is_plugin_enabled($assign, 'smartfeedback')) {
            return;
        }

        $feedback = self::generate_feedback($assign, $submission);
        self::save_feedback($assign, $submission, $feedback);
    }

    private static function get_submission_from_event(\core\event\base $event)
    {
        $submissionid = $event->other['submissionid'];
        return $event->get_record_snapshot('assign_submission', $submissionid);
    }

    private static function get_assignment_from_cm($cm)
    {
        global $DB;
        return $DB->get_record('assign', ['id' => $cm->instance], '*', MUST_EXIST);
    }

    private static function is_plugin_enabled(\assign $assign, string $pluginname): bool
    {
        $plugin = $assign->get_feedback_plugin_by_type($pluginname);
        return $plugin && $plugin->is_enabled();
    }

    private static function generate_feedback($assign, $submission)
    {
        $plugin = $assign->get_plugin_by_type('assignfeedback', 'smartfeedback');
        $config = $plugin->get_config_data();

        // 1. Retrieve submission content
        $plugins = $assign->get_submission_plugins();
        $submissiontext = '';

        foreach ($plugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                // For text submissions
                if ($plugin->get_type() === 'onlinetext' && !$plugin->is_empty($submission)) {
                    $submissiontext .= $plugin->get_editor_text('onlinetext', $submission->id);
                }

                // You can add handling for other submission types here
            }
        }

        // 2. Process the submission text
        $referencematerial = "";
        $instructions = $config->instructions;
        $assign_intro = $assign->get_instance()->intro ?? ''; // Solve this! (returns null)
        $ai = new api();
        $feedback = $ai->generate_feedback($assign_intro, $submissiontext, $referencematerial, $instructions);
        return $feedback;
    }

    function process_online_text_submission($submission)
    {
        if ($submission->plugin === 'onlinetext') {
            $text = $submission->data2;
            $filename = '/tmp/submission_' . $submission->userid . '.txt';
            file_put_contents($filename, $text);
            return $filename;
        }
        return null;
    }

    /**
     * Save the generated feedback
     * 
     * @param \assign $assign
     * @param stdClass $submission
     * @param string $feedback
     */
    private static function save_feedback($assign, $submission, $feedback)
    {
        global $DB;

        // Get the grade instance or create one if it doesn't exist
        if ($submission) {
            // Try to get existing grade
            $grade = $assign->get_user_grade($submission->userid, false);

            if (!$grade) {
                // Create a new grade object
                $grade = $assign->get_user_grade($submission->userid, true);
                $grade->grade = -1; // Not graded yet
                $grade->grader = 0; // System grader (0)
                $DB->update_record('assign_grades', $grade);
            }
        }

        // Now save our feedback
        $feedbackrecord = $DB->get_record('assignfeedback_smartfeedback', ['grade' => $grade->id]);

        if ($feedbackrecord) {
            $feedbackrecord->feedbacktext = $feedback;
            $DB->update_record('assignfeedback_smartfeedback', $feedbackrecord);
        } else {
            $feedbackrecord = new \stdClass();
            $feedbackrecord->grade = $grade->id;
            $feedbackrecord->assignment = $assign->get_instance()->id;
            $feedbackrecord->feedbacktext = $feedback;
            $feedbackrecord->feedbackformat = FORMAT_HTML;
            $DB->insert_record('assignfeedback_smartfeedback', $feedbackrecord);
        }
    }
}
