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

    public function upload_file($filename): array
    {
        $file = fopen($filename, 'r');
        $response = $this->client->files()->upload([
            'file' => $file,
            'purpose' => 'answers',
        ]);
        fclose($file);
        return $response->toArray();
    }

    public function generate_feedback(string $assignmentdescription, string $submissiontext, string $referencematerial, string $instructions)
    {
        if (!$this->is_configured()) {
            return false;
        }

        $system_message = "APENAS RESPONDA NA MESMA LÍNGUA. Você é um assistente educacional ajudando professores a fornecer feedback para os alunos. " .
            "Analise a submissão do aluno em comparação com o material de referência e forneça um feedback construtivo em português. " .
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
