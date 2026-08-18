<?php

namespace Tests\Feature;

use App\Support\PdfSummaryFormatter;
use Tests\TestCase;

class DocumentGenerationTest extends TestCase
{
    public function test_pdf_summary_keeps_inline_english_and_japanese_phrases_together(): void
    {
        $formatted = PdfSummaryFormatter::format("IT エンジニアへのキャリアチェンジ\nを目指しました。");

        $this->assertStringContainsString("IT\u{00A0}エンジニア", $formatted);
        $this->assertStringContainsString("約\u{00A0}3\u{00A0}年間", PdfSummaryFormatter::format('約 3 年間'));
        $this->assertStringNotContainsString("IT\nエンジニア", $formatted);
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
        $this->assertGreaterThan(10, substr_count($zip->getFromName('word/document.xml'), '<w:p>'));
        $zip->close();
        unlink($docxPath);
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
            ],
            'companies' => [
                [
                    'name' => '株式会社サンプル',
                    'employment_type' => '正社員',
                    'period_from' => '2024-04',
                    'period_to' => '2026-03',
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
        ];
    }
}
