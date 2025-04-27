<?php

/**
 * OpenAI API client for smart feedback plugin
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignfeedback_smartfeedback\openai;

defined('MOODLE_INTERNAL') || die();

/**
 * OpenAI API client for generating feedback
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api
{
    /** @var string OpenAI API key */
    private $apikey;

    /** @var string OpenAI model to use */
    private $model;

    /** @var int Maximum tokens to generate */
    private $maxtoken;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->apikey = get_config('assignfeedback_smartfeedback', 'apikey');
        $this->model = get_config('assignfeedback_smartfeedback', 'model');
        $this->maxtoken = get_config('assignfeedback_smartfeedback', 'maxtoken');
    }

    /**
     * Check if API key is set
     *
     * @return bool True if API key is set
     */
    public function is_configured()
    {
        return !empty($this->apikey);
    }

    /**
     * Generate feedback for a student submission
     *
     * @param string $submissiontext The student's submission content
     * @param string $referencematerial Reference material or source of truth
     * @param string $instructions Instructions for feedback generation
     * @return string|bool Generated feedback text or false on failure
     */
    public function generate_feedback($submissiontext, $referencematerial, $instructions)
    {
        if (!$this->is_configured()) {
            return false;
        }

        // Prepare the system message template.
        $system_message = "You are an educational assistant helping teachers provide feedback to students. " .
            "Analyze the student submission against the reference material and provide constructive feedback. " .
            "Your feedback should include: " .
            "- Highlighted strengths and positive aspects. " .
            "- Clear, actionable suggestions for improvement. " .
            "- Feedback aligned with the learning objectives.";

        // Add teacher's instructions if provided.
        if (!empty($instructions)) {
            $system_message .= "\n\nAdditional instructions: {$instructions}";
        }

        // Prepare the user message template with submission and reference.
        $user_message = "Student submission:\n\n{$submissiontext}\n\n";

        if (!empty($referencematerial)) {
            $user_message .= "Reference material (source of truth):\n\n{$referencematerial}";
        }

        // Prepare the API call payload.
        $payload = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_message
                ],
                [
                    'role' => 'user',
                    'content' => $user_message
                ]
            ],
            'max_tokens' => intval($this->maxtoken),
            'temperature' => 0.7
        ];

        $response = $this->call_openai_api('https://api.openai.com/v1/chat/completions', $payload);

        if ($response && isset($response->choices) && isset($response->choices[0]->message->content)) {
            return $response->choices[0]->message->content;
        }

        return false;
    }

    /**
     * Call the OpenAI API
     *
     * @param string $url API endpoint URL
     * @param array $payload Request payload
     * @return object|bool Response object or false on failure
     */
    private function call_openai_api($url, $payload)
    {
        $curl = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apikey
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            debugging('OpenAI API error: ' . $err, DEBUG_DEVELOPER);
            return false;
        }

        return json_decode($response);
    }
}
