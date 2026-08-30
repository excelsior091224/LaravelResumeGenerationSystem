@extends('guides.layout')

@section('title', '職務経歴書の書き方・例文・提出マナーガイド一覧')
@section('meta_description', '転職活動や就職活動で失敗しないための職務経歴書の書き方、職種別自己PR例文集、PDFやWordファイルの提出マナーを分かりやすく解説したガイド一覧です。')

@section('content')
    <div class="guide-header-block">
        <span class="guide-badge">Career Knowledge Base</span>
        <h1>職務経歴書の書き方・例文・提出マナーガイド</h1>
        <p>転職活動やキャリアチェンジを成功に導くためのノウハウを分かりやすくまとめました。書類作成の疑問や自己PRの表現に迷った際にご活用ください。</p>
    </div>

    <div class="guide-grid">
        <section class="guide-card">
            <div class="guide-card-tag">基礎知識</div>
            <h2><a href="{{ route('guides.how-to-write-resume') }}">職務経歴書の書き方完全ガイド</a></h2>
            <p>基本情報の書き方から、職務要約・職歴・使用技術のまとめ方まで、採用担当者の目を引く構成とポイントを徹底解説します。</p>
            <div class="guide-card-footer">
                <a href="{{ route('guides.how-to-write-resume') }}" class="read-more">記事を読む &rarr;</a>
            </div>
        </section>

        <section class="guide-card">
            <div class="guide-card-tag">例文・ノウハウ</div>
            <h2><a href="{{ route('guides.self-pr-examples') }}">職種別・自己PRと職務要約の例文集</a></h2>
            <p>ITエンジニア、Webデザイナー、営業、事務、マネジメント職など職種別の実践的な自己PR・職務要約の例文を網羅しました。</p>
            <div class="guide-card-footer">
                <a href="{{ route('guides.self-pr-examples') }}" class="read-more">記事を読む &rarr;</a>
            </div>
        </section>

        <section class="guide-card">
            <div class="guide-card-tag">提出マナー</div>
            <h2><a href="{{ route('guides.pdf-word-submission-rules') }}">職務経歴書はPDFとWordどちらが正解？提出時のマナー</a></h2>
            <p>企業応募や転職エージェント提出時におけるファイル形式の選び方、命名規則、メール添付時の注意点について解説します。</p>
            <div class="guide-card-footer">
                <a href="{{ route('guides.pdf-word-submission-rules') }}" class="read-more">記事を読む &rarr;</a>
            </div>
        </section>
    </div>

    <div class="cta-banner">
        <h2>準備ができたら、さっそく職務経歴書を作成してみましょう</h2>
        <p>会員登録不要・完全無料で、ブラウザ上からきれいなPDF / DOCXファイルを数分で生成できます。</p>
        <a href="{{ route('resume.create') }}" class="btn btn-primary-large">職務経歴書ジェネレーターを使う</a>
    </div>
@endsection
