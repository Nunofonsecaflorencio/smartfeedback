<?php

/**
 * This file contains the definition for the library class for smart feedback plugin
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Library class for smart feedback plugin extending feedback plugin base class.
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_feedback_smartfeedback extends assign_feedback_plugin
{

    /**
     * Get the name of the smart feedback plugin.
     * @return string
     */
    public function get_name()
    {
        return get_string('pluginname', 'assignfeedback_smartfeedback');
    }

    /**
     * Get the feedback from the database.
     *
     * @param int $gradeid
     * @return mixed The feedback record or false if not found
     */
    public function get_feedback($gradeid)
    {
        global $DB;
        return $DB->get_record('assignfeedback_smartfeedback', ['grade' => $gradeid]);
    }

    /**
     * Get the default setting for smart feedback plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform)
    {
        $mform->addElement('header', 'smartfeedbackheader', get_string('pluginname', 'assignfeedback_smartfeedback'));

        // Instructions for AI feedback
        $mform->addElement(
            'textarea',
            'assignfeedback_smartfeedback_instructions',
            get_string('feedbackinstructions', 'assignfeedback_smartfeedback'),
            ['rows' => 6]
        );
        $mform->addHelpButton(
            'assignfeedback_smartfeedback_instructions',
            'feedbackinstructions',
            'assignfeedback_smartfeedback'
        );

        // Reference material
        $mform->addElement(
            'textarea',
            'assignfeedback_smartfeedback_reference',
            get_string('referencematerial', 'assignfeedback_smartfeedback'),
            ['rows' => 10]
        );
        $mform->addHelpButton(
            'assignfeedback_smartfeedback_reference',
            'referencematerial',
            'assignfeedback_smartfeedback'
        );

        return true;
    }

    /**
     * Save the settings for smart feedback plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data)
    {
        global $DB;

        // Save the instructions and reference material
        $config = $DB->get_record(
            'assignfeedback_smartfeedback_conf',
            ['assignment' => $this->assignment->get_instance()->id]
        );

        if ($config) {
            $config->instructions = $data->assignfeedback_smartfeedback_instructions;
            $config->referencematerial = $data->assignfeedback_smartfeedback_reference;
            return $DB->update_record('assignfeedback_smartfeedback_conf', $config);
        } else {
            $config = new stdClass();
            $config->assignment = $this->assignment->get_instance()->id;
            $config->instructions = $data->assignfeedback_smartfeedback_instructions;
            $config->referencematerial = $data->assignfeedback_smartfeedback_reference;
            return $DB->insert_record('assignfeedback_smartfeedback_conf', $config) > 0;
        }
    }

    /**
     * Get config data for this assignment - used for defaults.
     * @return stdClass The config data
     */
    public function get_config_data()
    {
        global $DB;

        $config = $DB->get_record(
            'assignfeedback_smartfeedback_conf',
            ['assignment' => $this->assignment->get_instance()->id]
        );

        if ($config) {
            return $config;
        }

        return new stdClass();
    }

    /**
     * Display the feedback in the feedback table.
     *
     * @param stdClass $grade
     * @param bool $showviewlink
     * @return string
     */
    public function view_summary(stdClass $grade, &$showviewlink)
    {
        $feedback = $this->get_feedback($grade->id);
        if ($feedback) {
            $text = format_text($feedback->feedbacktext, $feedback->feedbackformat);
            $short = shorten_text($text, 140);
            $showviewlink = $short != $text;
            return $short;
        }
        return '';
    }

    /**
     * Display the full feedback
     *
     * @param stdClass $grade
     * @return string
     */
    public function view(stdClass $grade)
    {
        $feedback = $this->get_feedback($grade->id);
        if ($feedback) {
            return format_text($feedback->feedbacktext, $feedback->feedbackformat);
        }
        return '';
    }

    /**
     * Return true if there is no feedback.
     *
     * @param stdClass $grade
     * @return bool
     */
    public function is_empty(stdClass $grade)
    {
        return $this->view($grade) == '';
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance()
    {
        global $DB;
        $DB->delete_records(
            'assignfeedback_smartfeedback',
            ['assignment' => $this->assignment->get_instance()->id]
        );
        return true;
    }
}
