<?php

namespace Tests\Feature;

use App\Http\Requests\GenerateResumeRequest;
use App\ResumeData;
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

    public function test_it_requires_custom_detail_when_other_is_selected(): void
    {
        $payload = [
            'full_name' => '山田 太郎',
            'as_of_date' => '2026-08-01',
            'links' => [
                ['type' => 'その他', 'url' => 'https://example.com'],
            ],
            'skills' => [
                ['category' => '言語', 'name' => 'PHP', 'years' => '2年', 'level' => '業務使用'],
            ],
            'companies' => [
                [
                    'name' => '株式会社サンプル',
                    'employment_type' => 'その他',
                    'period_from' => '2024-04',
                    'period_to' => '2025-03',
                    'projects' => [
                        [
                            'name' => '社内管理ツール',
                            'period_from' => '2024-05',
                            'period_to' => '2024-12',
                            'role' => 'その他',
                        ],
                    ],
                ],
            ],
            'certifications' => [
                ['date' => '2025-01', 'name' => '基本情報技術者'],
            ],
        ];

        $request = new GenerateResumeRequest();
        $request->merge($payload);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('links.0.type_custom'));
        $this->assertTrue($validator->errors()->has('companies.0.employment_type_custom'));
        $this->assertTrue($validator->errors()->has('companies.0.projects.0.role_custom'));
    }

    public function test_it_normalizes_resume_data_for_preview(): void
    {
        $resume = ResumeData::fromArray([
            'full_name' => '山田 太郎',
            'as_of_date' => '2026-08-01',
            'links' => [
                ['type' => 'GitHub', 'url' => 'https://github.com/example'],
                ['type' => 'その他', 'type_custom' => 'Qiita', 'url' => 'https://qiita.com/example'],
            ],
            'skills' => [
                ['category' => 'フレームワーク', 'name' => 'Laravel', 'years' => '3年', 'level' => '業務使用', 'note' => 'API開発'],
                ['category' => '言語', 'name' => 'PHP', 'years' => '5年', 'level' => '業務使用', 'note' => 'バックエンド'],
            ],
            'companies' => [
                [
                    'name' => '',
                    'employment_type' => 'フリーランス',
                    'period_from' => '2021-04',
                    'period_to' => '2024-03',
                    'projects' => [
                        ['name' => '初期案件', 'period_from' => '2023-01', 'period_to' => '2023-12', 'role' => '開発者'],
                        ['name' => '最新案件', 'period_from' => '2024-01', 'period_to' => '2024-03', 'role' => 'PL'],
                    ],
                ],
                [
                    'name' => '株式会社サンプル',
                    'employment_type' => '正社員',
                    'period_from' => '2024-04',
                    'period_to' => '2025-03',
                    'projects' => [
                        ['name' => '社内管理ツール', 'period_from' => '2024-05', 'period_to' => '2024-12', 'role' => '開発者'],
                    ],
                ],
            ],
            'certifications' => [
                ['date' => '2025-01', 'name' => '基本情報技術者'],
                ['date' => '2024-02', 'name' => 'AWS認定ソリューションアーキテクト'],
            ],
            'self_pr' => '継続的に学習し、価値を提供します。',
        ]);

        $this->assertSame('株式会社サンプル', $resume->toArray()['companies'][0]['name']);
        $this->assertSame('フリーランス', $resume->toArray()['companies'][1]['name']);
        $this->assertSame('社内管理ツール', $resume->toArray()['companies'][0]['projects'][0]['name']);
        $this->assertSame('言語', $resume->toArray()['skills'][0]['category']);
        $this->assertSame('Qiita', $resume->toArray()['links'][1]['type_custom']);
    }
}
