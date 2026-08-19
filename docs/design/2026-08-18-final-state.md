# 2026-08-18 最終状態

## 概要

Laravel製職務経歴書生成システムの、2026-08-18時点の最終実装状態をまとめる。過去の試行錯誤ではなく、現在採用している構成を基準に記載する。

## 出力機能

- PDF: `POST /resume/download/pdf`
- DOCX: `POST /resume/download/docx`
- 入力内容はDB・永続セッション・キャッシュ・キューへ保存しない
- PDF/DOCXはリクエスト中に生成し、バイナリを直接返す

## PDFの最終構成

- ライブラリ: `dompdf/dompdf`
- 生成サービス: [src/app/Services/Document/ResumePdfGenerator.php](../../src/app/Services/Document/ResumePdfGenerator.php)
- テンプレート: [src/resources/views/resume/document.blade.php](../../src/resources/views/resume/document.blade.php)
- 共通帳票: [src/resources/views/resume/\_paper.blade.php](../../src/resources/views/resume/_paper.blade.php)
- フォント: [src/resources/fonts/IPAexGothic.ttf](../../src/resources/fonts/IPAexGothic.ttf)

### PDFの改行・空白

- ユーザー入力の改行を保持する
- `IT エンジニア`など、指定語句はHTMLの`nowrap`で保護する
- 不可視文字、ノーブレークスペースをPDFへ直接出力しない
- PDF上に四角い置換文字を出さない
- 長い文章、URL、案件詳細はCSSで折返す
- 会社・プロジェクトの`page-break-inside: avoid`は設定しない
- ページ境界では内容を連続して流す

## DOCXの最終構成

- ライブラリ: `phpoffice/phpword`
- 生成サービス: [src/app/Services/Document/ResumeDocxGenerator.php](../../src/app/Services/Document/ResumeDocxGenerator.php)
- テキスト整形: [src/app/Support/DocxTextFormatter.php](../../src/app/Support/DocxTextFormatter.php)
- フォント名: `IPAexGothic`

### DOCXの改行

- ユーザー入力の改行を保持する
- 複数行テキストを1段落内のWord改行`<w:br/>`へ変換する
- 改行ごとに別段落を生成しない
- 行ごとの`keepNext`を使用しない
- `利用者の業務`などを不可視文字で保護しない
- DOCXの改行要素をXMLテストで検証する

## 入力・バリデーション

- 初期表示の完全な空行をバリデーション前に除外
- 部分入力された行は通常どおり検証
- 期間逆転を拒否
- 現在継続中の場合は終了年月比較を除外
- 「その他」の詳細入力を必須化
- エラー時はフォーム上部にエラー概要を表示
- 「内容を確認する」ボタンは削除済み

## 大容量Fixture

テスト用に、手入力なしで以下のデータを生成するFixtureを実装済み。

- 企業: 4社
- プロジェクト: 20件
- スキル: 12件
- 資格: 10件
- 技術系リンク: 5件
- 複数行の職務要約、案件説明、担当工程、スキル備考、自己PR

確認用ファイル:

- [大容量PDF](../../src/storage/app/test-output/resume-fixture.pdf)
- [大容量DOCX](../../src/storage/app/test-output/resume-fixture.docx)

## 検証結果

```bash
cd /workspaces/LaravelResumeGenerationSystem/src
php artisan optimize:clear
php artisan test
npm run build
```

直近の結果:

- 14 tests passed
- 63 assertions
- PDF生成成功
- DOCX生成成功
- 長文Fixture生成成功
- DOCX内の`<w:br/>`確認済み
- Laravelキャッシュ全消去確認済み
- Viteビルド成功

## 残課題

- AI要約API連携（Google Gemini API、`gemini-3.5-flash-lite`を初期モデルとして採用）
- LibreOffice環境でDOCXの見た目を確認
- 正式な職務経歴書様式との細部比較

ライブプレビューとサーバー側プレビューの帳票構造共有は、`resources/js/resume-form.js` を共通帳票の項目順・表示内容に合わせることで対応済み。

## Word実環境確認

- 生成したDOCXをMicrosoft Wordで開き、WordのPDF変換機能でPDF化して確認済み
- 改ページ、日本語フォント、表、複数行テキスト、案件ブロックの表示に問題なし
- 添付確認資料: `resume-fixture.docx` をWordでPDF化した6ページの出力
