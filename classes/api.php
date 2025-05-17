<?php

namespace assignfeedback_smartfeedback;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/feedback/smartfeedback/vendor/autoload.php');

use OpenAI;

class api
{
    private $apikey;
    private $model;
    private $maxtoken;
    private $client;

    public function __construct()
    {
        $this->apikey = get_config('assignfeedback_smartfeedback', 'apikey');
        $this->model = get_config('assignfeedback_smartfeedback', 'model');
        $this->maxtoken = get_config('assignfeedback_smartfeedback', 'maxtoken');
        $this->client = OpenAI::client($this->apikey);
    }

    public function is_configured(): bool
    {
        return !empty($this->apikey);
    }


    public function upload_files_to_openai(array $localpaths): array
    {
        $ids = [];

        foreach ($localpaths as $path) {
            $upload = $this->client->files()->upload([
                'file' => fopen($path, 'r'),
                'purpose' => 'assistants',
            ]);
            $ids[] = $upload->id;
        }

        return $ids;
    }

    public function delete_vectorstore_and_files(string $vs_id)
    {
        $files = $this->client->vectorStores()->files()->list(
            vectorStoreId: $vs_id
        );
        foreach ($files->data as $file) {
            $this->client->files()->delete($file->id);
        }

        return $this->client->vectorStores()->delete($vs_id);
    }

    public function create_vectorstore_with_files(string $name, array $file_ids)
    {
        $vs = $this->client->vectorStores()->create(
            [
                'file_ids' => $file_ids,
                'name' => $name
            ]
        );

        return $vs->id;
    }

    public function request_feedback_from_openai($assignment, string $additional_instructions, string $submission_vs_id, string $reference_vs_id): string
    {


        $assistant = $this->client->assistants()->create([
            'model' => $this->model,
            'name' => 'SmartFeedback',
            'instructions' => get_string('assistantprompttemplate', 'assignfeedback_smartfeedback', (object)[
                'assignmentname' => $assignment->name,
                'assignmentdescription' => $assignment->intro,
                'specificinstructions' => $additional_instructions
            ]),
            'tools' => [
                ['type' => 'file_search'],
            ],
            'tool_resources' => [
                'file_search' => [
                    'vector_store_ids' => [$reference_vs_id]
                ]
            ]
        ]);

        $thread = $this->client->threads()->create(['tool_resources' => [
            'file_search' => [
                'vector_store_ids' => [$submission_vs_id]
            ]
        ]]);
        $this->client->threads()->messages()->create(
            $thread->id,
            [
                'role' => 'user',
                'content' => get_string('threadprompttemplate', 'assignfeedback_smartfeedback')
            ]
        );

        $run = $this->client->threads()->runs()->create($thread->id, [
            'assistant_id' => $assistant->id,
        ]);

        // Aguardar até a execução terminar
        do {
            sleep(1);
            $run = $this->client->threads()->runs()->retrieve($thread->id, $run->id);
        } while ($run->status !== 'completed');

        // destroy
        $this->delete_vectorstore_and_files($submission_vs_id);
        $this->client->assistants()->delete($assistant->id);

        // Pegar resposta
        $messages = $this->client->threads()->messages()->list($thread->id);
        return $messages->data[0]->content[0]->text->value ?? 'No feedback generated.';
    }
}
