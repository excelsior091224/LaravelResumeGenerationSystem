@extends('guides.layout')

@section('title', '職務経歴書はPDFとWordどちらが正解？提出時のマナーと注意点')
@section('meta_description', '転職応募時や転職エージェント提出時における職務経歴書のファイル形式（PDF・Word/DOCX）の選び方、推奨されるファイル名の付け方、メール添付マナーを解説します。')
@section('breadcrumb_current', 'PDFとWord提出マナー')

@section('content')
    <div class="article-header">
        <span class="guide-badge">提出マナー</span>
        <h1>職務経歴書はPDFとWordどちらが正解？提出時のマナーと注意点</h1>
        <p class="article-meta">更新日: {{ date('Y年m月d日') }} | テーマ: ファイル形式・メール添付マナー</p>
    </div>

    <div class="article-body">
        <div class="intro-box">
            <p>転職応募時やエージェント経由で職務経歴書を提出する際、「PDFとWord（DOCX）のどちらで提出すべきか？」と迷う方は少なくありません。ここでは、応募形式ごとの最適なファイルフォーマットと、失敗しない提出マナーを解説します。
            </p>
        </div>

        <h2>1. 基本原則：指定がない場合は「PDF」が推奨される</h2>
        <p>企業や採用担当者からの特別な指示がない場合、一般的には<strong>PDF形式</strong>での提出が最も安全かつ推奨されます。主な理由は以下の3点です。</p>
        <ul>
            <li><strong>レイアウトが崩れない:</strong> 閲覧者のOS（Windows, Mac, iOS, Android）やOfficeソフトのバージョンに左右されず、作成者が意図した通りの見た目で表示されます。
            </li>
            <li><strong>フォントが正しく保持される:</strong> 明朝体やゴシック体、IPAexゴシックなどの埋め込みフォントが維持され、文字化けや表示崩れが防げます。</li>
            <li><strong>誤操作による編集を防げる:</strong> 送信後に誤って文章が書き換わってしまうリスクを防ぎます。</li>
        </ul>

        <h2>2. Word（DOCX）形式での提出が求められるケース</h2>
        <p>一方で、あえてWord（DOCX）形式での提出を求められるケースもあります。</p>
        <ul>
            <li><strong>転職エージェント・人材紹介会社へ提出する場合:</strong>
                エージェント担当者が推薦状の作成や、個人の連絡先を伏せるなどの編集・加工を行うため、編集可能なWord形式での提出を指定されることがあります。</li>
            <li><strong>企業側の社内フォーマットや採用管理システム（ATS）がテキスト解析を行う場合:</strong> 自動テキスト抽出を行うシステムを企業が利用している場合、Word形式が指定されるケースがあります。
            </li>
        </ul>
        <div class="point-box">
            <h4>両方の形式を用意しておくのが最もスマート</h4>
            <p>Resume
                Foundryでは、一度入力したデータから<strong>PDFダウンロード</strong>と<strong>DOCXダウンロード</strong>の両方がワンクリックで生成可能です。提出先に合わせて使い分けましょう。
            </p>
        </div>

        <h2>3. 失敗しないファイル名の付け方規則</h2>
        <p>ダウンロードしたファイルをそのまま `resume.pdf` や `無題の文書.docx` の名前で企業へ送るのはマナー違反となります。誰の何の書類か一目でわかるファイル名を付けましょう。</p>
        <div class="example-box">
            <h3>推奨されるファイル名の例</h3>
            <p><code>職務経歴書_山田太郎_20260830.pdf</code></p>
            <p><code>職務経歴書_山田太郎.docx</code></p>
        </div>
        <ul>
            <li>「書類名」「氏名」「日付」をアンダーバー（`_`）で区切る。</li>
            <li>日付を入れる場合は、作成日または送付日（例: `20260830`）とする。</li>
        </ul>

        <h2>4. メール添付・Web応募時の注意点</h2>
        <ol>
            <li><strong>パスワードロックは基本的に不要（指定時のみ）:</strong> 個人情報保護目的でパスワードを設ける指示がない限り、採用担当者の閲覧・印刷の手間が増えるため、不要なパスワード設定は避けましょう。
            </li>
            <li><strong>ファイルサイズは3MB以下に収める:</strong> 画像添付等でファイル容量が大きくなりすぎないよう注意します。Resume
                Foundryが生成するPDFは通常数十KB〜100KB程度と非常に軽量です。</li>
        </ol>

        <div class="cta-banner inner-cta">
            <h3>PDFとWord両方を一括作成できる Resume Foundry</h3>
            <p>用途に合わせてPDFとWord(DOCX)を自由に選び放題。会員登録不要・安心のセキュリティでお使いいただけます。</p>
            <a href="{{ route('resume.create') }}" class="btn btn-primary-large">無料で作ってみる</a>
        </div>
    </div>
@endsection
