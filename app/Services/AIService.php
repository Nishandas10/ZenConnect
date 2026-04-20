<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    }

    public function summarizeTicket(string $title, string $description): ?string
    {
        $prompt = "You are a support ticket analyst. Summarize the following support ticket in 3-5 concise lines.\n\n"
            . "Title: {$title}\n"
            . "Description: {$description}\n\n"
            . "Provide a clear, professional summary highlighting the key issue, impact, and any specific details mentioned.";

        return $this->callGemini($prompt);
    }

    public function suggestReply(string $title, string $description, array $comments = []): ?string
    {
        $commentsText = '';
        foreach ($comments as $comment) {
            $commentsText .= "\n- {$comment['user']}: {$comment['body']}";
        }

        $prompt = "You are a professional customer support agent. Suggest a helpful, empathetic reply to the following support ticket.\n\n"
            . "Title: {$title}\n"
            . "Description: {$description}\n";

        if ($commentsText) {
            $prompt .= "\nPrevious comments:{$commentsText}\n";
        }

        $prompt .= "\nProvide a professional, solution-oriented reply. Be empathetic and provide actionable steps if possible.";

        return $this->callGemini($prompt);
    }

    protected function callGemini(string $prompt): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('Gemini API key not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 500,
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Gemini API exception: ' . $e->getMessage());
            return null;
        }
    }
}
