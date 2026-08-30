<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '職務経歴書の書き方ガイド') | Resume Foundry</title>
    <meta name="description" content="@yield('meta_description', '職務経歴書の書き方、自己PR例文、フォーマットの選び方を分かりやすく解説するResume Foundryのガイドページです。')">
    @include('partials.gtm-head')
    @include('partials.adsense')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="guide-body">
    @include('partials.gtm-body')
    <div class="guide-shell">
        <header class="guide-header">
            <div class="guide-header-container">
                <a href="{{ route('resume.create') }}" class="brand">
                    <span class="brand-mark">R</span>
                    <span>Resume Foundry</span>
                </a>
                <nav class="guide-nav">
                    <a href="{{ route('resume.create') }}" class="btn-create-nav">ジェネレーターに戻る</a>
                    <a href="{{ route('guides.index') }}">ガイド一覧</a>
                    <a href="{{ route('guides.how-to-write-resume') }}">書き方完全ガイド</a>
                    <a href="{{ route('guides.self-pr-examples') }}">自己PR例文集</a>
                    <a href="{{ route('guides.pdf-word-submission-rules') }}">提出マナー</a>
                </nav>
            </div>
        </header>

        <main class="guide-main">
            <div class="guide-container">
                <div class="guide-breadcrumb">
                    <a href="{{ route('resume.create') }}">ホーム</a> &gt;
                    <a href="{{ route('guides.index') }}">書き方ガイド一覧</a>
                    @hasSection('breadcrumb_current')
                        &gt; <span>@yield('breadcrumb_current')</span>
                    @endif
                </div>

                <article class="guide-content-card">
                    @yield('content')
                </article>
            </div>
        </main>

        <footer class="site-footer">
            <div class="footer-links">
                <a href="{{ route('resume.create') }}">職務経歴書を作成する</a>
                <a href="{{ route('guides.index') }}">ガイド一覧</a>
                <a href="{{ route('privacy') }}">プライバシーポリシー</a>
                <a href="{{ route('contact') }}">お問い合わせ</a>
            </div>
            <p class="copyright">&copy; {{ date('Y') }} Resume Foundry - 登録不要の即時職務経歴書作成サービス</p>
        </footer>
    </div>
</body>

</html>
