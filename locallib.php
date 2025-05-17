<?php

/**
 * This file contains the definition for the library class for smart feedback plugin
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignfeedback_smartfeedback\api;

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

        $mform->addElement(
            'filemanager',
            "sf_referencefiles",
            get_string('referencematerials', 'assignfeedback_smartfeedback'),
            null,
            $fileoptions
        );

        try {
            $instance = $this->assignment->get_instance();
        } catch (\TypeError $e) {
            // No instance yet (new assignment). Nothing to load.
            return true;
        }
        if (empty($instance->id)) {
            // Still new: bail out early.
            return true;
        }

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
        global $DB, $CFG;

        // Prepare context, file options and assignment identifiers
        $context      = $this->assignment->get_context();
        $assignment   = $this->assignment->get_instance();
        $assignmentid = $assignment->id;
        $fileoptions  = $this->get_file_options();

        // Fetch existing config or prepare a new one
        $config = $DB->get_record('assignfeedback_smartfeedback_configs', ['assignment' => $assignmentid]);
        $record = (object) [
            'assignment'             => $assignmentid,
            'instructions'           => $formdata->sf_instructions,
            'reference_files_vs_id'  => $config ? $config->reference_files_vs_id : null
        ];

        // Insert or update base config (without vectorstore changes yet)
        if ($config) {
            $record->id = $config->id;
            $DB->update_record('assignfeedback_smartfeedback_configs', $record);
        } else {
            $record->id = $DB->insert_record('assignfeedback_smartfeedback_configs', $record);
        }

        // Save user-uploaded files from draft to permanent area
        file_save_draft_area_files(
            $formdata->sf_referencefiles,
            $context->id,
            'assignfeedback_smartfeedback',
            'referencefiles_area',
            $record->id,
            $fileoptions
        );

        // Gather files for AI processing
        $fs    = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'assignfeedback_smartfeedback',
            'referencefiles_area',
            $record->id,
            'timemodified',
            false
        );

        $localfiles = [];
        foreach ($files as $file) {
            if (!$file->is_directory()) {
                $tempfile = $CFG->tempdir . '/' . uniqid('REF_') . '_' . $file->get_filename();
                $file->copy_content_to($tempfile);
                $localfiles[] = $tempfile;
            }
        }

        // Interact with OpenAI vector store
        $ai = new api();

        // Remove old vectorstore if it exists
        if ($record->reference_files_vs_id) {
            $ai->delete_vectorstore_and_files($record->reference_files_vs_id);
        }

        // Create new vectorstore and capture its ID
        $storeName = "Reference files for assignment '{$assignment->name}'";
        $file_ids = $ai->upload_files_to_openai($localfiles);
        $vsid      = $ai->create_vectorstore_with_files($storeName, $file_ids);

        // Persist the new vectorstore ID
        $record->reference_files_vs_id = $vsid;
        $DB->update_record('assignfeedback_smartfeedback_configs', $record);

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
            'assignfeedback_smartfeedback_configs',
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
    public function delete_instance() // TODO: not triggered yet
    {
        global $DB;
        // Interact with OpenAI vector store
        $ai = new api();

        $record = $DB->get_record(
            'assignfeedback_smartfeedback_configs',
            ['assignment' => $this->assignment->get_instance()->id]
        );
        // Remove old vectorstore if it exists
        if ($record && $record->reference_files_vs_id) {
            $ai->delete_vectorstore_and_files($record->reference_files_vs_id);
        }

        $DB->delete_records(
            'assignfeedback_smartfeedback',
            ['assignment' => $this->assignment->get_instance()->id]
        );

        $DB->delete_records(
            'assignfeedback_smartfeedback_configs',
            ['assignment' => $this->assignment->get_instance()->id]
        );
        return true;
    }
}
