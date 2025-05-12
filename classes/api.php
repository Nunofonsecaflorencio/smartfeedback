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

    public function request_feedback_from_openai(string $instructions, array $submission_file_ids, array $reference_file_ids): string
    {
        $submission_vectorstore = $this->client->vectorStores()->create(
            [
                'file_ids' => $submission_file_ids,
                'name' => 'SmartFeedback - Student Submission Files'
            ]
        );

        $reference_vectorstore = $this->client->vectorStores()->create(
            [
                'file_ids' => $reference_file_ids,
                'name' => 'SmartFeedback - Assignment Reference Files for Feedback'
            ]
        );

        $assistant = $this->client->assistants()->create([
            'model' => $this->model,
            'name' => 'SmartFeedback',
            'instructions' => $instructions,
            'tools' => [
                ['type' => 'file_search'],
            ],
            'tool_resources' => [
                'file_search' => [
                    'vector_store_ids' => [$reference_vectorstore->id]
                ]
            ]
        ]);

        $thread = $this->client->threads()->create(['tool_resources' => [
            'file_search' => [
                'vector_store_ids' => [$submission_vectorstore->id]
            ]
        ]]);
        $this->client->threads()->messages()->create($thread->id, [
            'role' => 'user',
            'content' => "Analise a submissão do aluno e forneça um feedback construtivo em português. " .
                "Seu feedback deve incluir: " .
                "- Pontos fortes e aspectos positivos destacados. " .
                "- Sugestões claras e acionáveis para melhoria. " .
                "- Feedback alinhado com os objetivos de aprendizagem." .
                "\nSTUDENT SUBMISSION VECTORSTORE ID:" . $submission_vectorstore->id .
                "\nASSIGMENT REFERENCE MATERIAL VECTORSTORE ID:" . $reference_vectorstore->id
        ]);

        $run = $this->client->threads()->runs()->create($thread->id, [
            'assistant_id' => $assistant->id,
        ]);

        // Aguardar até a execução terminar
        do {
            sleep(1);
            $run = $this->client->threads()->runs()->retrieve($thread->id, $run->id);
        } while ($run->status !== 'completed');

        // Pegar resposta
        $messages = $this->client->threads()->messages()->list($thread->id);
        return $messages->data[0]->content[0]->text->value ?? 'No feedback generated.';
    }
}
