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
    private function get_file_options()
    {
        global $COURSE;

        $fileoptions = array(
            'subdirs' => 1,
            'maxbytes' => $COURSE->maxbytes,
            'accepted_types' => ['document'],
            'return_types' => FILE_INTERNAL
        );
        return $fileoptions;
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

        // --- Reference Materials filemanager ---
        // 1. Prepare draft area
        $draftitemid = file_get_submitted_draft_itemid('assignfeedback_smartfeedback_references');
        $fileoptions = $this->get_file_options();

        $data = new stdClass();
        $data = file_prepare_standard_filemanager(
            $data,
            'assignfeedback_smartfeedback_references',
            $fileoptions,
            $this->assignment->get_context(),
            'assignfeedback_smartfeedback',
            'references',          // filearea
            $draftitemid
        );

        // 2. Add filemanager element
        $mform->addElement(
            'filemanager',
            'assignfeedback_smartfeedback_references_filemanager',
            get_string('referencematerials', 'assignfeedback_smartfeedback'),
            null,
            $fileoptions
        );
        $mform->setDefault('assignfeedback_smartfeedback_references_filemanager', $draftitemid);

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

        $assignmentid = $this->assignment->get_instance()->id;
        $context      = $this->assignment->get_context();

        // 1. — Save AI instructions in your config table -----------------------

        // Try to fetch existing config row
        $config = $DB->get_record(
            'assignfeedback_smartfeedback_conf',
            ['assignment' => $assignmentid]
        );

        if ($config) {
            $config->instructions = $data->assignfeedback_smartfeedback_instructions;
            $DB->update_record('assignfeedback_smartfeedback_conf', $config);
        } else {
            $config = (object)[
                'assignment'   => $assignmentid,
                'instructions' => $data->assignfeedback_smartfeedback_instructions,
            ];
            $DB->insert_record('assignfeedback_smartfeedback_conf', $config);
        }

        // 2. — Handle Reference‐Materials Filemanager -------------------------

        $fileoptions = $this->get_file_options();

        // Move files from draft area into:
        // component = 'assignfeedback_smartfeedback'
        // filearea  = 'references'
        // itemid    = $assignmentid
        file_postupdate_standard_filemanager(
            $data,
            'assignfeedback_smartfeedback_references',
            $fileoptions,
            $context,
            'assignfeedback_smartfeedback',
            'references',
            $assignmentid
        );

        return true;
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
