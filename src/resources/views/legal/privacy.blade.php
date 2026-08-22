<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プライバシーポリシー | 職務経歴書ジェネレーター</title>
    @vite(['resources/css/app.css'])
</head>

<body>
    <div class="info-shell">
        <header class="topbar">
            <a class="brand brand-link" href="{{ route('resume.create') }}"><span class="brand-mark">R</span><span>Resume
                    Foundry</span></a>
            <nav class="site-nav" aria-label="サイトメニュー">
                <a href="{{ route('resume.create') }}">作成画面</a>
                <a href="{{ route('contact') }}">お問い合わせ</a>
            </nav>
        </header>

        <main class="info-main">
            <div class="info-kicker">Privacy</div>
            <h1>プライバシーポリシー</h1>
            <p class="info-lead">Resume Foundry（以下「本サービス」）は、職務経歴書の作成を支援するサービスです。本ページでは、本サービスにおける情報の取り扱いを説明します。</p>

            <section class="info-section">
                <h2>1. 取得する情報</h2>
                <p>本サービスでは、職務経歴書の作成にあたり、氏名、基準日、職歴、プロジェクト、スキル、資格、自己PR、配慮事項、技術系アカウントのURLなど、利用者がフォームへ入力した情報を扱います。</p>
            </section>

            <section class="info-section">
                <h2>2. 入力内容の保存</h2>
                <p>フォーム入力内容は、本サービスのデータベース、永続セッション、キャッシュ、キューへ保存しません。入力中の下書きは、利便性のため利用者のブラウザ内のlocalStorageへ保存されます。共有端末を利用した場合は、作業終了後に画面の「下書きをクリア」を実行してください。
                </p>
                <p>PDF・DOCXの生成時は、入力内容を処理してファイルを返します。生成ファイルは利用者自身で管理してください。</p>
            </section>

            <section class="info-section">
                <h2>3. AI機能の利用</h2>
                <p>利用者が同意した場合に限り、職務要約の生成に必要な職歴、プロジェクト、スキル、資格をAIサービスへ送信します。氏名、URL、自己PR、配慮事項はAIサービスへ送信しません。</p>
                <p>AIが生成した文章には誤りが含まれる場合があります。内容を確認し、必要に応じて利用者自身で修正してください。</p>
            </section>

            <section class="info-section">
                <h2>4. アクセス解析・広告</h2>
                <p>本サービスでは、今後アクセス解析サービスやGoogle
                    AdSenseなどの広告サービスを導入する場合があります。導入時は、Cookieなどの利用目的、第三者提供、広告配信の仕組みを本ポリシーへ追記します。</p>
            </section>

            <section class="info-section">
                <h2>5. 安全管理</h2>
                <p>本サービスは、入力内容を必要以上に保持しない設計とし、不正アクセス、滅失、毀損、漏えいなどの防止に努めます。ただし、インターネット通信や利用者の端末における完全な安全性を保証するものではありません。
                </p>
            </section>

            <section class="info-section">
                <h2>6. 本ポリシーの変更</h2>
                <p>サービス内容や法令の変更に応じて、本ポリシーを改定することがあります。重要な変更がある場合は、本ページ上でお知らせします。</p>
            </section>

            <section class="info-section">
                <h2>7. お問い合わせ</h2>
                <p>本ポリシーに関するお問い合わせは、<a href="{{ route('contact') }}">お問い合わせページ</a>からお送りください。</p>
            </section>

            <p class="info-updated">制定日：2026年8月22日</p>
        </main>
    </div>
</body>

</html>
