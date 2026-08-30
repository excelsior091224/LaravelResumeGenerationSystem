<?php

namespace Tests\Feature;

use Tests\TestCase;

class GuidePagesTest extends TestCase
{
    public function test_guides_index_page_returns_ok(): void
    {
        $response = $this->get('/guides');

        $response->assertOk();
        $response->assertSee('職務経歴書の書き方・例文・提出マナーガイド一覧');
        $response->assertSee('職務経歴書の書き方完全ガイド');
        $response->assertSee('職種別・自己PRと職務要約の例文集');
        $response->assertSee('職務経歴書はPDFとWordどちらが正解？');
    }

    public function test_how_to_write_resume_guide_page_returns_ok(): void
    {
        $response = $this->get('/guides/how-to-write-resume');

        $response->assertOk();
        $response->assertSee('職務経歴書の書き方完全ガイド');
        $response->assertSee('職務経歴書に必要な基本構成');
        $response->assertSee('職務要約の書き方のコツ');
    }

    public function test_self_pr_examples_guide_page_returns_ok(): void
    {
        $response = $this->get('/guides/self-pr-examples');

        $response->assertOk();
        $response->assertSee('職種別・自己PRと職務要約の例文集');
        $response->assertSee('Webエンジニア・バックエンド開発の自己PR例文');
        $response->assertSee('自己PR作成時の共通チェックリスト');
    }

    public function test_pdf_word_submission_rules_guide_page_returns_ok(): void
    {
        $response = $this->get('/guides/pdf-word-submission-rules');

        $response->assertOk();
        $response->assertSee('職務経歴書はPDFとWordどちらが正解？');
        $response->assertSee('指定がない場合は「PDF」が推奨される');
        $response->assertSee('失敗しないファイル名の付け方規則');
    }
}
