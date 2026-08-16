<?php

namespace Tests\Feature;

use App\Http\Requests\GenerateResumeRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ResumeValidationTest extends TestCase
{
    public function test_it_rejects_invalid_company_and_project_date_ranges(): void
    {
        $payload = [
            'full_name' => '山田 太郎',
            'as_of_date' => '2026-08-01',
            'summary' => 'バックエンド開発を担当',
            'specialty' => 'API設計',
            'links' => [
                ['type' => 'GitHub', 'url' => 'https://github.com/example'],
            ],
            'skills' => [
                ['category' => '言語', 'name' => 'PHP', 'years' => '3年', 'level' => '業務使用'],
            ],
            'companies' => [
                [
                    'name' => '株式会社サンプル',
                    'employment_type' => '正社員',
                    'period_from' => '2024-04',
                    'period_to' => '2024-03',
                    'projects' => [
                        [
                            'name' => '社内管理ツール',
                            'period_from' => '2024-04',
                            'period_to' => '2024-03',
                        ],
                    ],
                ],
            ],
            'certifications' => [
                ['date' => '2025-01', 'name' => '基本情報技術者'],
            ],
            'self_pr' => '継続的に学習し、価値を提供します。',
        ];

        $request = new GenerateResumeRequest();
        $request->merge($payload);

        $validator = Validator::make($request->all(), $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('companies.0.period_to'));
        $this->assertTrue($validator->errors()->has('companies.0.projects.0.period_to'));
    }
}
