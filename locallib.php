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
        global $DB;

        $mform->addElement('header', 'smartfeedbackheader', get_string('pluginname', 'assignfeedback_smartfeedback'));

        // Instructions for AI feedback
        $mform->addElement(
            'textarea',
            'sf_instructions',
            get_string('feedbackinstructions', 'assignfeedback_smartfeedback'),
            ['rows' => 6]
        );
        $mform->addHelpButton(
            'sf_instructions',
            'feedbackinstructions',
            'assignfeedback_smartfeedback'
        );

        $fileoptions = $this->get_file_options();
        $draftitemid = file_get_submitted_draft_itemid("sf_referencefiles");
        // Retrieve existing configuration for the assignment.
        $record = $DB->get_record('assignfeedback_smartfeedback_configs', [
            'assignment' => $this->assignment->get_instance()->id
        ]);

        if ($record) {
            $context = $this->assignment->get_context();
            // Set the default value for the instructions field.
            $mform->setDefault("sf_instructions", $record->instructions);
            // Prepare the draft area for existing files.
            $draftitemid = file_get_submitted_draft_itemid('sf_referencefiles');
            file_prepare_draft_area(
                $draftitemid,
                $context->id,
                'assignfeedback_smartfeedback',
                'referencefiles_area',
                $record->id,
                $fileoptions
            );
        }

        $mform->addElement(
            'filemanager',
            "sf_referencefiles",
            get_string('referencematerials', 'assignfeedback_smartfeedback'),
            null,
            $fileoptions
        );
        $mform->setDefault("sf_referencefiles", $draftitemid);

        return true;
    }


    /**
     * Save the settings for smart feedback plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $formdata)
    {
        global $DB;

        $context = $this->assignment->get_context();
        $fileoptions = $this->get_file_options();

        $assignmentid = $this->assignment->get_instance()->id;

        // See if a record already exists.
        $record = $DB->get_record('assignfeedback_smartfeedback_configs', ['assignment' => $assignmentid]);

        $newrecord = new stdClass();
        $newrecord->assignment = $assignmentid;
        $newrecord->instructions = $formdata->sf_instructions;

        if ($record) {
            $newrecord->id = $record->id;
            $DB->update_record('assignfeedback_smartfeedback_configs', $newrecord);
        } else {
            $newrecord->id = $DB->insert_record('assignfeedback_smartfeedback_configs', $newrecord);
        }

        // Save uploaded files to permanent storage
        file_save_draft_area_files(
            $formdata->sf_referencefiles,
            $context->id,
            'assignfeedback_smartfeedback',
            'referencefiles_area',
            $newrecord->id, // itemid
            $fileoptions
        );

        // Get stored files
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'assignfeedback_smartfeedback',
            'referencefiles_area',
            $newrecord->id,
            'timemodified',
            false
        );

        // Send to OpenAI vector store (ignore implementation)
        $vsid = "vs_testing0"; // $this->process_files_with_openai($files); // Implement this

        // Save vectorstore ID
        $newrecord->reference_files_vs_id = $vsid;
        $DB->update_record('assignfeedback_smartfeedback_configs', $newrecord);

        return true;
    }
    /**
     * Get config data for this assignment - used for defaults.
     * @return stdClass The config data
     */
    public function get_config_data()
    {
        global $DB;

        $record = $DB->get_record(
            'assignfeedback_smartfeedback_records',
            ['assignment' => $this->assignment->get_instance()->id]
        );

        if ($record) {
            return $record;
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
