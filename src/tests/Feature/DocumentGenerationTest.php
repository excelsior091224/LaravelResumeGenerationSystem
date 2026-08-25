<?php

namespace Tests\Feature;

use App\Support\PdfSummaryFormatter;
use App\Support\DocxTextFormatter;
use Tests\TestCase;

class DocumentGenerationTest extends TestCase
{
    public function test_resume_form_uses_https_urls_behind_the_reverse_proxy(): void
    {
        $response = $this->withHeader('X-Forwarded-Proto', 'https')
            ->get('http://resumefoundries.com/');

        $response->assertOk();
        $response->assertSee('action="https://resumefoundries.com/resume/preview"', false);
    }

    public function test_resume_form_blocks_implicit_submission_and_uses_explicit_download_actions(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('@submit.prevent', false);
        $response->assertSee('@click="download(', false);
        $response->assertDontSee('formaction=', false);
    }

    public function test_docx_formatter_preserves_line_breaks_and_protects_target_phrase(): void
    {
        $formatted = DocxTextFormatter::format("直近ではGitHub Copilotを活用しながら、利用者の業務を改善しました。\n次の段落です。");

        $this->assertStringContainsString('利用者の業務', $formatted);
        $this->assertStringContainsString('GitHub Copilot', $formatted);
        $this->assertStringContainsString("改善しました。\n次の段落です。", $formatted);
    }
    public function test_pdf_summary_keeps_inline_english_and_japanese_phrases_together(): void
    {
        $formatted = PdfSummaryFormatter::format("IT エンジニアへのキャリアチェンジ\nを目指しました。");

        $html = PdfSummaryFormatter::toHtml('IT エンジニア、約 3 年間、GitHub Copilot');
        $this->assertStringContainsString('<span class="nowrap">IT エンジニア</span>', $html);
        $this->assertStringContainsString('<span class="nowrap">約 3 年間</span>', $html);
        $this->assertStringContainsString('<span class="nowrap">GitHub Copilot</span>', $html);
        $this->assertStringNotContainsString("\u{2060}", $html);
        $preserved = PdfSummaryFormatter::formatPreservingLineBreaks("一つ目\n二つ目");
        $this->assertSame("一つ目\n二つ目", $preserved);
        $summary = PdfSummaryFormatter::formatPreservingLineBreaks("職務要約の一行目\n職務要約の二行目");
        $this->assertSame("職務要約の一行目\n職務要約の二行目", $summary);
    }

    public function test_pdf_text_replaces_tabs_with_spaces(): void
    {
        $html = PdfSummaryFormatter::toHtml("◆\tAI エージェント\n◆\t学習姿勢");

        $this->assertStringNotContainsString("\t", $html);
        $this->assertStringContainsString('◆ AI エージェント', $html);
        $this->assertStringContainsString('◆ 学習姿勢', $html);
    }

    public function test_server_preview_uses_the_shared_paper_structure(): void
    {
        $response = $this->withoutMiddleware()->post(route('resume.preview'), $this->resumePayload());

        $response->assertOk();
        $response->assertSee('職務経歴書');
        $response->assertSee('PCスキル / テクニカルスキル');
        $response->assertSee('summary-text');
        $response->assertSee('【担当工程】');
        $response->assertSee('株式会社サンプル');
        $response->assertSee('【業務概要】');
        $response->assertSee('配慮事項');
        $response->assertSee('担当業務');
        $response->assertSee('要件定義');
        $response->assertSee('是非、面接の機会をいただければと思います。何卒よろしくお願いいたします。');
        $response->assertDontSee('内容を確認する');
        $response->assertSeeInOrder(['■ 資格', '■ 自己PR']);
    }

    public function test_it_generates_a_pdf_download_without_persisting_resume_data(): void
    {
        $this->assertFileExists(resource_path('fonts/IPAexGothic.ttf'));

        $response = $this->withoutMiddleware()->post(route('resume.download.pdf'), $this->resumePayload());

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('IPAexGothic', $response->getContent());
    }

    public function test_it_generates_a_docx_download_without_persisting_resume_data(): void
    {
        $response = $this->withoutMiddleware()->post(route('resume.download.docx'), $this->resumePayload());

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $response->headers->get('Content-Type'),
        );
        $this->assertSame('PK', substr($response->getContent(), 0, 2));

        $docxPath = tempnam(sys_get_temp_dir(), 'resume-test-');
        file_put_contents($docxPath, $response->getContent());
        $zip = new \ZipArchive();
        $this->assertSame(true, $zip->open($docxPath));
        $this->assertStringContainsString('IPAexGothic', $zip->getFromName('word/styles.xml'));
        $this->assertStringContainsString('20252A', $zip->getFromName('word/styles.xml'));
        $this->assertStringContainsString('職務経歴書', $zip->getFromName('word/document.xml'));
        $this->assertStringContainsString('資格', $zip->getFromName('word/document.xml'));
        $this->assertStringContainsString('自己PR', $zip->getFromName('word/document.xml'));
        $this->assertStringContainsString('業務概要', $zip->getFromName('word/document.xml'));
        $this->assertStringContainsString('配慮事項', $zip->getFromName('word/document.xml'));
        $this->assertStringContainsString('担当業務', $zip->getFromName('word/document.xml'));
        $this->assertStringContainsString('要件定義', $zip->getFromName('word/document.xml'));
        $this->assertStringContainsString('是非、面接の機会をいただければと思います。何卒よろしくお願いいたします。', $zip->getFromName('word/document.xml'));
        $this->assertStringContainsString('<w:br', $zip->getFromName('word/document.xml'));
        $zip->close();
        unlink($docxPath);
    }

    public function test_it_generates_both_documents_from_long_form_fixture_data(): void
    {
        $payload = $this->longResumePayload();

        $this->assertCount(4, $payload['companies']);
        $this->assertSame(20, collect($payload['companies'])->sum(fn(array $company): int => count($company['projects'])));
        $this->assertCount(12, $payload['skills']);
        $this->assertCount(10, $payload['certifications']);

        $pdf = $this->withoutMiddleware()->post(route('resume.download.pdf'), $payload);
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $docx = $this->withoutMiddleware()->post(route('resume.download.docx'), $payload);
        $docx->assertOk();
        $this->assertSame('PK', substr($docx->getContent(), 0, 2));
    }

    public function test_docx_self_pr_contains_explicit_word_breaks(): void
    {
        $response = $this->withoutMiddleware()->post(route('resume.download.docx'), $this->longResumePayload());
        $docxPath = tempnam(sys_get_temp_dir(), 'resume-self-pr-');
        file_put_contents($docxPath, $response->getContent());

        $zip = new \ZipArchive();
        $this->assertSame(true, $zip->open($docxPath));
        $document = $zip->getFromName('word/document.xml');
        $selfPrPosition = strpos($document, '配慮事項');

        $this->assertNotFalse($selfPrPosition);
        $this->assertStringContainsString('<w:br', substr($document, $selfPrPosition));
        $zip->close();
        unlink($docxPath);
    }

    public function test_it_exports_long_form_fixture_artifacts_for_inspection(): void
    {
        $directory = storage_path('app/test-output');
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $payload = $this->longResumePayload();
        $pdf = $this->withoutMiddleware()->post(route('resume.download.pdf'), $payload);
        $docx = $this->withoutMiddleware()->post(route('resume.download.docx'), $payload);

        file_put_contents($directory . '/resume-fixture.pdf', $pdf->getContent());
        file_put_contents($directory . '/resume-fixture.docx', $docx->getContent());

        $this->assertFileExists($directory . '/resume-fixture.pdf');
        $this->assertFileExists($directory . '/resume-fixture.docx');
        $this->assertGreaterThan(0, filesize($directory . '/resume-fixture.pdf'));
        $this->assertGreaterThan(0, filesize($directory . '/resume-fixture.docx'));
    }

    public function test_it_ignores_initially_empty_repeatable_rows_for_docx_download(): void
    {
        $payload = $this->resumePayload();
        $payload['skills'][] = ['category' => '', 'name' => '', 'years' => '', 'level' => '', 'note' => ''];
        $payload['companies'][] = [
            'name' => '',
            'employment_type' => '',
            'projects' => [['name' => '', 'period_from' => '', 'period_to' => '']],
        ];
        $payload['certifications'][] = ['date' => '', 'name' => ''];
        $payload['links'][] = ['type' => '', 'type_custom' => '', 'url' => ''];

        $response = $this->withoutMiddleware()->post(route('resume.download.docx'), $payload);

        $response->assertOk();
        $this->assertSame('PK', substr($response->getContent(), 0, 2));
    }

    private function resumePayload(): array
    {
        return [
            'full_name' => '山田 太郎',
            'as_of_date' => '2026-08-18',
            'summary' => 'Webアプリケーション開発を担当',
            'specialty' => '業務改善システムの設計・開発',
            'links' => [
                ['type' => 'GitHub', 'url' => 'https://github.com/example'],
            ],
            'skills' => [
                ['category' => '言語', 'name' => 'PHP', 'years' => '5年', 'level' => '業務使用', 'note' => 'Laravelを含む'],
                ['category' => '担当業務', 'name' => '要件定義', 'years' => '5年', 'level' => '業務使用', 'note' => '利用部門へのヒアリングと要件整理を担当'],
            ],
            'companies' => [
                [
                    'name' => '株式会社サンプル',
                    'employment_type' => '正社員',
                    'period_from' => '2024-04',
                    'period_to' => '2026-03',
                    'business_overview' => '社内外の業務改善を目的としたWebシステムの企画、開発、運用を担当',
                    'projects' => [
                        [
                            'name' => '業務管理システム',
                            'period_from' => '2024-04',
                            'period_to' => '2026-03',
                            'description' => '要件整理から運用改善まで担当',
                            'role' => 'バックエンドエンジニア',
                            'team' => '開発4名',
                            'processes' => '設計、実装、テスト',
                            'technologies' => 'PHP / Laravel / MySQL',
                        ],
                    ],
                ],
            ],
            'certifications' => [
                ['date' => '2025-01', 'name' => '基本情報技術者'],
            ],
            'self_pr' => '利用者の課題を整理し、継続的に改善できます。',
            'considerations' => '通院などの予定は事前に相談のうえ調整します。',
        ];
    }

    private function longResumePayload(): array
    {
        $payload = $this->resumePayload();
        $payload['summary'] = "大学卒業後、2016年2月から約3年間、事務職として業務改善とデータ管理を担当しました。\n2019年10月にITエンジニアへ転職し、Python、PHP、Laravelを中心にWebアプリケーションの設計、実装、テスト、運用改善まで経験しました。直近ではGitHub Copilotを活用しながら、利用者の業務効率化と保守性の向上を意識して開発しています。";
        $payload['specialty'] = '業務整理から基本設計、実装、テスト、運用改善までを一貫して担当すること。';
        $payload['links'] = [
            ['type' => 'GitHub', 'url' => 'https://github.com/excelsior091224'],
            ['type' => 'Zenn', 'url' => 'https://zenn.dev/example'],
        ];
        $payload['skills'] = [
            ['category' => '言語', 'name' => 'PHP', 'years' => '5年', 'level' => '業務使用', 'note' => "設計から実装、テスト、運用改善まで\nLaravelを中心に使用"],
            ['category' => '言語', 'name' => 'Python', 'years' => '3年', 'level' => '業務使用', 'note' => 'データ加工と業務自動化'],
            ['category' => 'フレームワーク', 'name' => 'Laravel', 'years' => '4年', 'level' => '業務使用', 'note' => '認証、帳票、API、バッチ処理'],
        ];
        $payload['companies'][0]['industry'] = 'ITサービス・業務改善';
        $payload['companies'][0]['projects'][0]['description'] = "顧客と要件を整理し、見積業務を効率化するWebアプリケーションを開発しました。\n画面設計、API実装、帳票出力、テスト、運用改善を担当しました。";
        $payload['companies'][0]['projects'][0]['processes'] = "要件整理、基本設計、詳細設計、実装\n単体テスト、結合テスト、運用改善";
        $payload['companies'][0]['projects'][0]['technologies'] = 'PHP8.3 / Laravel / Livewire / MySQL / JavaScript / Docker';
        $payload['certifications'] = [
            ['date' => '2025-01', 'name' => '基本情報技術者試験'],
            ['date' => '2024-06', 'name' => 'AWS Certified Cloud Practitioner'],
        ];
        $payload['self_pr'] = "【配慮事項】\n曖昧な指示の場合は確認事項を整理し、認識を合わせてから作業を開始します。\n\n◆ GitHub Copilotの活用\nエラー原因の特定やコード補完に活用し、レビュー可能な形で成果物を仕上げます。\n\n◆ 学習姿勢\nLinux、Docker、クラウド技術を継続的に学習し、実務へ還元しています。";

        $payload['links'] = array_merge($payload['links'], [
            ['type' => 'ポートフォリオ', 'url' => 'https://portfolio.example.com/profile'],
            ['type' => 'その他', 'type_custom' => '個人ブログ', 'url' => 'https://blog.example.com/long-profile'],
            ['type' => 'GitHub', 'url' => 'https://github.com/example/resume-project'],
        ]);

        $payload['skills'] = array_merge($payload['skills'], [
            ['category' => 'フレームワーク', 'name' => 'Vue.js', 'years' => '2年', 'level' => '個人開発', 'note' => 'フォームと一覧画面の実装'],
            ['category' => 'ミドルウェア', 'name' => 'Docker', 'years' => '4年', 'level' => '業務使用', 'note' => '開発環境とデプロイ環境の構築'],
            ['category' => 'OS', 'name' => 'Linux', 'years' => '8年', 'level' => '業務使用', 'note' => 'ログ確認、権限設定、定期処理'],
            ['category' => 'インフラ', 'name' => 'AWS', 'years' => '2年', 'level' => '自己研鑽', 'note' => 'EC2、S3、RDSの検証'],
            ['category' => 'データベース', 'name' => 'MySQL', 'years' => '5年', 'level' => '業務使用', 'note' => 'テーブル設計、SQL、バックアップ'],
            ['category' => 'データベース', 'name' => 'PostgreSQL', 'years' => '2年', 'level' => '業務使用', 'note' => '既存システムの移行と保守'],
            ['category' => '開発ツール・その他', 'name' => 'Git', 'years' => '8年', 'level' => '業務使用', 'note' => 'ブランチ運用、レビュー、リリース管理'],
            ['category' => '開発ツール・その他', 'name' => 'GitHub Actions', 'years' => '2年', 'level' => '自己研鑽', 'note' => 'テストとビルドの自動化'],
            ['category' => '言語', 'name' => 'JavaScript', 'years' => '5年', 'level' => '業務使用', 'note' => '画面制御、API連携、入力検証'],
        ]);

        $payload['certifications'] = array_merge($payload['certifications'], [
            ['date' => '2023-10', 'name' => 'AWS Certified Solutions Architect'],
            ['date' => '2023-04', 'name' => 'LPIC-1'],
            ['date' => '2022-11', 'name' => 'PHP技術者認定試験'],
            ['date' => '2022-05', 'name' => '普通自動車第一種運転免許'],
            ['date' => '2021-09', 'name' => '情報セキュリティマネジメント試験'],
            ['date' => '2020-06', 'name' => 'MOS Excel Expert'],
            ['date' => '2019-03', 'name' => 'ITパスポート試験'],
            ['date' => '2018-08', 'name' => '日商簿記検定2級'],
        ]);

        $companies = [];
        for ($companyIndex = 0; $companyIndex < 4; $companyIndex++) {
            $projects = [];
            for ($projectIndex = 0; $projectIndex < 5; $projectIndex++) {
                $projects[] = [
                    'name' => '長文検証プロジェクト ' . ($companyIndex + 1) . '-' . ($projectIndex + 1),
                    'period_from' => sprintf('%04d-%02d', 2020 + $companyIndex, 1 + $projectIndex),
                    'period_to' => sprintf('%04d-%02d', 2020 + $companyIndex, 2 + $projectIndex),
                    'description' => "複数の業務部門から要望をヒアリングし、要件を整理してWebアプリケーションへ反映しました。\n設計、実装、テスト、リリース後の問い合わせ対応まで継続して担当しました。長い文章が複数行になった場合の帳票レイアウトを検証します。",
                    'role' => '開発者（ソフトウェアエンジニア）',
                    'team' => '開発3名、テスター2名、利用部門4名',
                    'processes' => "要件整理、基本設計、詳細設計、実装\n単体テスト、結合テスト、保守・運用",
                    'technologies' => 'PHP / Laravel / JavaScript / MySQL / Linux / Docker / GitHub Actions',
                ];
            }

            $companies[] = [
                'name' => '長文検証株式会社 ' . ($companyIndex + 1),
                'employment_type' => '正社員',
                'period_from' => sprintf('%04d-01', 2020 + $companyIndex),
                'period_to' => sprintf('%04d-12', 2020 + $companyIndex),
                'industry' => 'ITサービス・業務改善・システム開発',
                'established' => '2000年',
                'capital' => '1億円',
                'employees' => '500名',
                'projects' => $projects,
            ];
        }
        $payload['companies'] = $companies;

        return $payload;
    }
}
