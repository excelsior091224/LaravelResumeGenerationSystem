<?php

namespace Tests\Feature;

use App\Services\ResumeSummaryProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResumeSummaryTest extends TestCase
{
    public function test_it_generates_a_summary_without_passing_personal_fields_to_the_provider(): void
    {
        $provider = new class implements ResumeSummaryProvider
        {
            public array $careerData = [];

            public function summarize(array $careerData): string
            {
                $this->careerData = $careerData;

                return 'PHPとLaravelを用いたWebアプリケーション開発を担当しました。';
            }
        };
        $this->app->instance(ResumeSummaryProvider::class, $provider);

        $response = $this->withoutMiddleware()->postJson(route('resume.summary'), [
            'ai_consent' => true,
            'full_name' => '送信してはいけない氏名',
            'links' => [['url' => 'https://example.com/private']],
            'self_pr' => '送信してはいけない自己PR',
            'companies' => [[
                'name' => '株式会社サンプル',
                'employment_type' => '正社員',
                'projects' => [[
                    'name' => '業務管理システム',
                    'description' => 'LaravelによるAPI開発を担当',
                    'technologies' => 'PHP, Laravel, MySQL',
                ]],
            ]],
            'skills' => [['category' => '言語', 'name' => 'PHP', 'years' => '5年', 'level' => '業務使用']],
        ]);

        $response->assertOk()->assertJsonPath('summary', 'PHPとLaravelを用いたWebアプリケーション開発を担当しました。');
        $this->assertSame(['companies', 'skills'], array_keys($provider->careerData));
        $this->assertStringNotContainsString('送信してはいけない氏名', json_encode($provider->careerData, JSON_UNESCAPED_UNICODE));
        $this->assertStringNotContainsString('https://example.com/private', json_encode($provider->careerData, JSON_UNESCAPED_UNICODE));
        $this->assertStringNotContainsString('送信してはいけない自己PR', json_encode($provider->careerData, JSON_UNESCAPED_UNICODE));
    }

    public function test_it_requires_consent_before_generating_a_summary(): void
    {
        $this->withoutMiddleware()->postJson(route('resume.summary'), [
            'companies' => [['name' => '株式会社サンプル']],
        ])->assertUnprocessable()->assertJsonValidationErrors('ai_consent');
    }

    public function test_it_accepts_blank_project_months_when_generating_a_summary(): void
    {
        $this->app->instance(ResumeSummaryProvider::class, new class implements ResumeSummaryProvider
        {
            public function summarize(array $careerData): string
            {
                return 'Webアプリケーション開発を担当しました。';
            }
        });

        $this->withoutMiddleware()->postJson(route('resume.summary'), [
            'ai_consent' => true,
            'companies' => [[
                'name' => '株式会社サンプル',
                'period_from' => '',
                'period_to' => '',
                'projects' => [[
                    'name' => '継続中の案件',
                    'period_from' => '2025-01',
                    'period_to' => '',
                    'description' => 'Webアプリケーション開発を担当',
                ]],
            ]],
        ])->assertOk()->assertJsonPath('summary', 'Webアプリケーション開発を担当しました。');
    }

    public function test_it_omits_completely_blank_projects_before_calling_the_provider(): void
    {
        $provider = new class implements ResumeSummaryProvider
        {
            public array $careerData = [];

            public function summarize(array $careerData): string
            {
                $this->careerData = $careerData;

                return 'Webアプリケーション開発を担当しました。';
            }
        };
        $this->app->instance(ResumeSummaryProvider::class, $provider);

        $this->withoutMiddleware()->postJson(route('resume.summary'), [
            'ai_consent' => true,
            'companies' => [[
                'name' => '株式会社サンプル',
                'projects' => [
                    ['name' => '', 'period_from' => '', 'period_to' => '', 'description' => ''],
                    ['name' => '業務管理システム', 'description' => 'Webアプリケーション開発を担当'],
                ],
            ]],
        ])->assertOk();

        $this->assertCount(1, $provider->careerData['companies'][0]['projects']);
        $this->assertSame('業務管理システム', $provider->careerData['companies'][0]['projects'][0]['name']);
    }

    public function test_it_returns_a_service_error_when_gemini_is_unavailable(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Unavailable']], 503),
        ]);

        $response = $this->withoutMiddleware()->postJson(route('resume.summary'), [
            'ai_consent' => true,
            'skills' => [['category' => '言語', 'name' => 'PHP']],
        ]);

        $response->assertStatus(503)->assertJsonPath('message', 'AI要約サービスに接続できませんでした。時間をおいて再試行してください。');
    }
}
