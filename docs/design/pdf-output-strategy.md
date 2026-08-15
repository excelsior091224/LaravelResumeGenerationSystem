# PDF出力方式の設計方針

## 1. 背景

本アプリケーションは最終的にレンタルサーバーへデプロイする予定である。一般的なレンタルサーバーでは、Chromium、LibreOffice、wkhtmltopdfなどのOSレベルの実行アプリケーションを任意にインストールまたは実行できないことが多い。

そのため、開発用Docker環境で動くことだけを基準にPDF方式を選ぶと、本番環境で出力できなくなるリスクがある。

## 2. 制約

- 本番は自由度の低いレンタルサーバーを想定する
- OSパッケージや常駐プロセスの導入を前提にしない
- 職務経歴書の内容はDBへ保存しない
- 日本語を正しく表示する
- 利用者がPDFをダウンロードできる
- PDFは採用担当者が読む帳票として十分な品質を保つ
- PDF生成時に不要な第三者へ個人情報を送信しない

## 3. 方式の比較

| 方式                          | サーバーへの追加アプリ | レンタルサーバー適性 | 見た目の再現性 | 個人情報の外部送信 | 判断                           |
| ----------------------------- | ---------------------: | -------------------: | -------------: | -----------------: | ------------------------------ |
| Chromium / Headless Chrome    |                   必要 |                 低い |           高い |               なし | 本番の第一候補にしない         |
| LibreOfficeでDOCX変換         |                   必要 |                 低い |         中〜高 |               なし | 本番の第一候補にしない         |
| wkhtmltopdf                   |                   必要 |                 低い |             中 |               なし | 本番の第一候補にしない         |
| PHPライブラリ `dompdf/dompdf` |                   不要 |                 高い |             中 |               なし | **第一候補**                   |
| PHPライブラリ `mpdf/mpdf`     |                   不要 |                 高い |             中 |               なし | 代替候補。ライセンス確認が必要 |
| ブラウザ印刷                  |                   不要 |                 高い |   ブラウザ依存 |               なし | 手動出力の代替手段             |
| 外部PDF変換API                |                   不要 |                 高い |           高い |               あり | 個人情報方針上、原則採用しない |

## 4. 採用方針

### 4.1 PDF生成はDompdfを第一候補とする

PDF生成には`dompdf/dompdf`をComposerで導入する。

```bash
composer require dompdf/dompdf
```

DompdfはPHPライブラリとして`vendor/`へ導入されるため、ChromiumやLibreOfficeのような外部実行ファイルをレンタルサーバーへ導入する必要がない。

ただし、Dompdfはブラウザの描画エンジンではない。画面用Bladeと同じHTML/CSSをそのまま渡し、完全一致を期待する設計は避ける。

### 4.2 PDF専用BladeとCSSを作る

画面プレビュー用とPDF用のレイアウトは分ける。

```text
resources/views/resume/preview.blade.php     # ブラウザ用プレビュー
resources/views/resume/document.blade.php    # PDF用帳票
resources/css/resume.css                     # 画面用CSS
resources/css/resume-pdf.css                 # PDF用CSS
```

PDF用のCSSでは、次のようなDompdfで安定しやすい表現だけを使う。

- A4ページサイズと余白を明示する
- `table`を中心とした帳票レイアウト
- 単純な罫線、背景色、余白、文字サイズ
- `page-break-inside: avoid`によるプロジェクト単位の改ページ制御
- `@page`によるページ余白

避けるもの:

- CSS GridやFlexboxへ強く依存した帳票レイアウト
- JavaScriptで描画する内容
- 外部サイトから読み込むフォント、画像、CSS
- 複雑なアニメーション、フィルター、最新CSS機能

## 5. 日本語フォント

日本語PDFでは、サーバーにインストールされているフォントへ依存しない。

ライセンス上利用可能な日本語フォントをプロジェクトへ同梱し、Dompdfへ登録する。候補はNoto Sans JPまたはNoto Serif JPとする。

```text
resources/fonts/NotoSansJP-Regular.ttf
resources/fonts/NotoSansJP-Bold.ttf
```

実装時には次を確認する。

- フォントのライセンスと配布条件
- フォントファイル容量
- 日本語、英字、記号が正しく表示されること
- 開発環境とレンタルサーバーで同じPDFになること

フォントファイルは、PDF出力に必要なアプリケーション資産であり機密情報ではない。Gitで管理するか、リリース時に確実に配置される方法を選ぶ。

## 6. 実装構成

```text
app/
└── Services/
    └── Document/
        ├── ResumeDocxGenerator.php
        └── ResumePdfGenerator.php
```

`ResumePdfGenerator`の責務:

1. `ResumeData` DTOを受け取る
2. PDF用BladeをHTMLへ描画する
3. DompdfでPDFバイナリを生成する
4. `response()->streamDownload()`で直接ダウンロードさせる
5. 必要な作業ディレクトリだけを利用し、職務経歴書を永続保存しない

概念例:

```php
$html = view('resume.document', ['resume' => $resume])->render();

$pdf = new Dompdf();
$pdf->loadHtml($html);
$pdf->setPaper('A4');
$pdf->render();

return response()->streamDownload(
    static fn () => print($pdf->output()),
    '職務経歴書.pdf',
    ['Content-Type' => 'application/pdf'],
);
```

実装時は、HTMLをそのままユーザー入力から組み立てない。Bladeの通常エスケープを使い、`ResumeData`から帳票を描画する。

## 7. レンタルサーバーで事前に確認する項目

Dompdfは外部バイナリを必要としないが、レンタルサーバーのPHP設定を確認する。

- PHPのバージョンがLaravelとDompdfの要件を満たす
- `mbstring`が有効
- `dom` / `xml` が有効
- `gd`が有効。画像利用時は特に必要
- `storage/`配下など、Dompdfの一時ディレクトリに書き込み権限がある
- `memory_limit`が十分。長い職務経歴書と日本語フォントはメモリを使う
- `max_execution_time`が十分
- Composerを本番で実行できない場合、ローカルまたはCIで`vendor/`を含むデプロイ成果物を作る

Dompdfの一時ディレクトリは、専用パスを指定する。共有の`/tmp`や機密情報以外も入るディレクトリを使わない。

```text
storage/app/dompdf-temp/
```

このディレクトリはWeb公開せず、PDF生成後の不要な一時データが残らないよう運用を確認する。

## 8. mPDFを採用しない理由と代替利用

mPDFもComposerで導入でき、外部実行アプリなしでPDFを生成できる。有力な代替候補である。

ただし、公式ドキュメントでは、現代的なHTML/CSSの再現には専用テンプレートが必要であること、ブラウザに近いCSS再現を求める場合はHeadless Chromeが適することが示されている。また、mPDFのライセンスはGPL v2であるため、ライセンス上の扱いを公開形態や配布形態に応じて確認する必要がある。

Dompdfで日本語レイアウトまたは機能要件を満たせない場合に、mPDFを技術検証する。その際はライセンスを含めて改めて判断する。

## 9. ブラウザ印刷の位置付け

ブラウザの印刷機能は、追加のサーバー機能なしにPDF保存できる。

```text
ブラウザでプレビュー
    ↓
利用者が印刷を選ぶ
    ↓
「PDFに保存」を選ぶ
```

これは自動ダウンロードではないため主機能にはしないが、Dompdfの導入前、またはレンタルサーバー固有の制約でサーバー側PDFが使えない場合の代替手段として残す。

## 10. 外部PDF変換APIの扱い

外部APIは高いCSS再現性を得やすいが、職務経歴書の内容を第三者サービスへ送信することになる。個人情報保護方針と矛盾しやすいため、原則として採用しない。

採用を検討する場合は、少なくとも次を満たす必要がある。

- 利用者への明確な同意
- 送信先、保持期間、削除方針の確認
- データ処理契約やプライバシーポリシーの確認
- APIキーの安全な管理
- 障害時に入力内容をログへ残さない設計

## 11. 実装の優先順

1. `ResumeData` DTOを導入する
2. 入力値バリデーションとFeatureテストを整備する
3. PDF専用BladeとA4帳票CSSを作る
4. `dompdf/dompdf`を導入する
5. 日本語フォントを登録する
6. `ResumePdfGenerator`とPDFダウンロードルートを実装する
7. レンタルサーバー相当環境で日本語、複数ページ、長いURLをテストする
8. DOCX出力を追加する

## 12. 結論

レンタルサーバーでの稼働を優先し、PDFは**Dompdfによる純PHP方式**を第一候補とする。

```text
ResumeData
    ↓
PDF専用Blade + PDF専用CSS
    ↓
Dompdf
    ↓
streamDownload()
    ↓
PDFダウンロード
```

ChromiumとLibreOfficeは、DockerやVPSなど実行環境を完全に制御できる場合には有効である。しかし、レンタルサーバーを前提とする本番方式には採用しない。
