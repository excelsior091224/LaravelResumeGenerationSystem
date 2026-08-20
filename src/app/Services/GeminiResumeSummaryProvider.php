<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiResumeSummaryProvider implements ResumeSummaryProvider
{
    private const MAX_CAREER_DATA_LENGTH = 20000;

    public function summarize(array $careerData): string
    {
        $careerJson = json_encode($careerData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (mb_strlen($careerJson) > self::MAX_CAREER_DATA_LENGTH) {
            throw new RuntimeException('AI要約に送信できる職歴情報の文字数を超えています。');
        }

        $response = Http::acceptJson()
            ->withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->timeout(20)
            ->retry(1, 500, throw: false)
            ->post(
                'https://generativelanguage.googleapis.com/v1beta/models/' . config('services.gemini.model') . ':generateContent',
                [
                    'contents' => [[
                        'parts' => [[
                            'text' => $this->prompt($careerJson),
                        ]],
                    ]],
                ],
            );

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $summary = trim((string) $response->json('candidates.0.content.parts.0.text'));

        if ($summary === '') {
            throw new RuntimeException('AIから職務要約を取得できませんでした。');
        }

        return $summary;
    }

    private function prompt(string $careerJson): string
    {
        return <<<PROMPT
以下の職歴、スキル、資格だけを根拠に、職務経歴書向けの職務要約を日本語で作成してください。

- 事実にない経験、成果、数値、役割、技術を追加しない
- 誇張や推測をしない
- 250文字以内、2から4文で簡潔にまとめる
- 氏名、連絡先、URLなどの個人情報には触れない
- 要約本文だけを返し、見出しや箇条書き、前置きは付けない

職歴情報:
{$careerJson}
PROMPT;
    }
}
