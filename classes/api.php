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

    public function request_feedback_from_openai(string $instructions, array $fileids): string
    {
        $vectorstore = $this->client->vectorStores()->create(
            [
                'file_ids' => $fileids,
                'name' => 'SmartFeedback Submisson store'
            ]
        );

        $assistant = $this->client->assistants()->create([
            'model' => $this->model,
            'name' => 'SmartFeedback',
            'instructions' => $instructions,
            'tools' => [['type' => 'file_search']],
            'tool_resources' => [
                'file_search' => [
                    'vector_store_ids' => [$vectorstore->id]
                ]
            ]
        ]);

        $thread = $this->client->threads()->create([]);
        $this->client->threads()->messages()->create($thread->id, [
            'role' => 'user',
            'content' => "Analise a submissão do aluno e forneça um feedback construtivo em português. " .
                "Seu feedback deve incluir: " .
                "- Pontos fortes e aspectos positivos destacados. " .
                "- Sugestões claras e acionáveis para melhoria. " .
                "- Feedback alinhado com os objetivos de aprendizagem.",
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

    public function generate_feedback(string $assignmentdescription, string $submissiontext, string $referencematerial, string $instructions)
    {
        if (!$this->is_configured()) {
            return false;
        }

        $system_message = "APENAS RESPONDA NA MESMA LÍNGUA. Você é um assistente educacional ajudando professores a fornecer feedback para os alunos. " .
            "Analise a submissão do aluno e forneça um feedback construtivo em português. " .
            "Seu feedback deve incluir: " .
            "- Pontos fortes e aspectos positivos destacados. " .
            "- Sugestões claras e acionáveis para melhoria. " .
            "- Feedback alinhado com os objetivos de aprendizagem.";

        if (!empty($assignmentdescription)) {
            $system_message .= "\n\nDescrição da tarefa: {$assignmentdescription}";
        }

        if (!empty($instructions)) {
            $system_message .= "\n\nInstruções Adicionais para o Feedback: {$instructions}";
        }

        $user_message = "Submissão do estudante:\n\n{$submissiontext}\n\n";
        if (!empty($referencematerial)) {
            $user_message .= "Material de Referência:\n\n{$referencematerial}";
        }

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $system_message],
                ['role' => 'user', 'content' => $user_message]
            ],
            'max_tokens' => intval($this->maxtoken),
            'temperature' => 0.7
        ];

        $response = $this->client->chat()->create($payload);

        return $response->choices[0]->message->content ?? false;
    }
}
