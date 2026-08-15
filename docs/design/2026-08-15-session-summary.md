# 開発検討記録 2026-08-15

## 1. 今回の目的

LaravelでITエンジニア向け職務経歴書を作成し、最終的にDOCXおよびPDFとして出力するアプリケーションを開発する。

個人情報保護とシステムの簡略化を優先し、入力された職務経歴書の内容はデータベースへ保存しない方針とする。

## 2. 決定した基本方針

- 入力内容はDBへ保存しない
- 永続セッション、キャッシュ、キューにも個人情報を保存しない
- ブラウザ上のフォーム状態を基本とし、必要な処理のときだけHTTPリクエストで送信する
- 入力、プレビュー、DOCX、PDF、AI要約では共通のデータ構造を利用する
- MVPではBlade、Alpine.js、通常のPOST、`fetch`を利用する
- Livewireは必須とせず、フォームがさらに複雑になった場合に導入を検討する
- スキル候補はDBではなく静的JSONで管理する
- 設計関連文書と画像は`docs/design/`配下に集約する
- `docs/design/`は`.gitignore`で除外されているためGitHubへ公開しない

## 3. 現在の画面実装

### 3.1 入力画面

トップページに職務経歴書の入力フォームを表示する。

実装済みの入力項目:

- 氏名
- 基準日
- 技術系アカウント・ポートフォリオURL（複数）
- 職務要約
- 得意業務
- スキル
- 所属企業
- プロジェクト履歴
- 資格
- 自己PR

スキル、所属企業、企業内プロジェクト、資格は入力欄を追加・削除できる。

### 3.2 プレビュー

- 入力中の内容を右側へライブ表示する
- フォーム送信後はLaravelのBladeによるサーバー側プレビューを表示する
- 添付された職務経歴書を参考に、職務要約、得意業務、PCスキル、職務経歴、資格、自己PRの構成で表示する
- スキルはカテゴリ、スキル名、経験年数、経験区分、備考の列で表示する
- 職務経歴は所属企業とプロジェクトの階層で表示する

## 4. 現在のデータ構造

### 4.1 ルートのデータ

```text
resume
├── full_name
├── as_of_date
├── links[]
│   ├── type
│   ├── type_custom
│   └── url
├── summary
├── specialty
├── self_pr
├── skills[]
├── companies[]
│   ├── name
│   ├── period_from
│   ├── period_to
│   ├── industry
│   ├── established
│   ├── capital
│   ├── employees
│   └── projects[]
│       ├── period_from
│       ├── period_to
│       ├── name
│       ├── description
│       ├── role
│       ├── team
│       ├── processes
│       └── technologies
└── certifications[]
```

### 4.2 スキル

```text
skills[]
├── category
├── name
├── years
├── level
└── note
```

`level`は次の選択式とする。

- 業務使用
- 個人開発
- 自己研鑽

`note`は自由記述の備考欄とする。

## 5. スキル候補の動作

スキル候補は`src/resources/data/skills.json`でカテゴリ別に管理する。

現在のカテゴリ:

- 言語
- フレームワーク
- ミドルウェア
- OS
- インフラ
- データベース
- デザインツール
- 開発ツール・その他

入力画面では、カテゴリを選択したスキル行だけに、そのカテゴリの候補を表示する。

例:

```text
言語
  PHP
  JavaScript
  TypeScript

フレームワーク
  Laravel
  React
  Vue.js
```

カテゴリを変更した場合は、以前のカテゴリで入力したスキル名をクリアする。

## 6. 職務経歴の表示順

入力順ではなく、年月を基準に降順で表示する。

- 所属企業は在籍開始年月の新しい順
- 各企業内のプロジェクトは開始年月の新しい順
- 年月は`YYYY-MM`形式で扱う
- ライブプレビューとサーバー側プレビューで同じ並び順にする

例えば、入力順が次のような場合でも、

```text
言語のプロジェクト
フレームワークのプロジェクト
言語の別プロジェクト
```

表示時には開始年月の新しい順で並び替える。

## 7. Alpine.jsの利用箇所

`src/resources/js/resume-form.js`でAlpine.jsの画面状態を定義する。

主な処理:

- `x-data`でフォーム全体の状態を作る
- `x-model`で入力値と状態を同期する
- `@click`で追加・削除メソッドを呼び出す
- `x-for`で繰り返し入力欄を生成する
- `x-show`で削除ボタンの表示を制御する
- `:name`でLaravelが受け取れる配列形式のname属性を生成する
- `x-html`でライブプレビューを表示する
- `escape()`でユーザー入力をHTMLへ埋め込む前にエスケープする

## 8. 現在の主要ファイル

- 画面: `src/resources/views/resume/create.blade.php`
- サーバー側プレビュー: `src/resources/views/resume/preview.blade.php`
- 画面状態とライブプレビュー: `src/resources/js/resume-form.js`
- 画面用CSS: `src/resources/css/resume.css`
- 入力画面のController: `src/app/Http/Controllers/ResumeController.php`
- バリデーション: `src/app/Http/Requests/GenerateResumeRequest.php`
- ルート: `src/routes/web.php`
- スキル候補: `src/resources/data/skills.json`
- 設計書: `docs/design/resume-generation-system.md`

現在のルート:

```text
GET  /                  resume.create
POST /resume/preview    resume.preview
```

## 9. 現時点で未実装の機能

優先度が高い順に以下を残している。

### 9.1 入力値バリデーションの整備

現在、`GenerateResumeRequest`には基本的なルールがあるが、実運用向けには追加確認が必要。

- 初期状態の空行をどう扱うか決める
- 所属企業とプロジェクトの必須・任意条件を決める
- 開始年月と終了年月の前後関係を検証する
- 企業数、プロジェクト数、スキル数、資格数の上限を決める
- 文字数上限とエラーメッセージを整える
- URL、メールアドレス、電話番号の形式を必要に応じて検証する
- 入力エラー時に利用者が修正しやすい表示へする
- バリデーションのFeatureテストを追加する

注意点として、現在の画面は空の所属企業と空のプロジェクトを初期表示する。プロジェクト名などを必須にする場合、未入力のまま送信したときのエラー表示を設計する必要がある。

### 9.2 DTOの導入

設計上は`ResumeData` DTOを想定しているが、現在は`$request->validated()`をそのままBladeへ渡している。

次回以降、次のようなDTOを導入する。

```text
GenerateResumeRequest
    ↓
ResumeData
    ├── ResumeSkill
    ├── ResumeCompany
    ├── ResumeProject
    └── ResumeCertification
```

### 9.3 DOCX出力

`phpoffice/phpword`を導入し、共通データからDOCXを生成する。

想定クラス:

```text
app/Services/Document/ResumeDocxGenerator.php
```

一時ファイルへ出力してダウンロードレスポンスを返し、処理後に一時ファイルを削除する。

### 9.4 PDF出力

第一候補はBladeのHTMLをChromiumでPDF化する方式。

```text
Blade HTML → Chromium → PDF
```

プレビューとPDFの見た目を揃えやすい一方、DockerfileへのChromiumと日本語フォントの追加が必要。

代替案として、DOCXをLibreOfficeでPDFへ変換する方式も検討する。

### 9.5 AIによる職務要約

OpenAIまたはGeminiのAPIを利用し、職歴情報から職務要約を生成する。

次の抽象化を想定する。

```text
ResumeSummaryProvider
    ├── OpenAIResumeSummaryProvider
    └── GeminiResumeSummaryProvider
```

氏名、メールアドレス、電話番号、住所などの不要な個人情報はAI APIへ送信しない。AI生成結果は確定値として扱わず、利用者が確認・編集できるようにする。

### 9.6 ダウンロード処理

現在はプレビュー表示とブラウザ印刷のみ。将来的に以下を追加する。

- DOCXダウンロード
- PDFダウンロード
- DOCXとPDFの同時出力
- 一時ファイル削除
- 出力中の二重送信防止
- 出力時の個人情報をログへ記録しない対策

## 10. 次回の推奨着手順

1. `GenerateResumeRequest`のバリデーション仕様を確定する
2. 初期空行と入力エラーの扱いを実装する
3. `ResumeData` DTOを追加する
4. DTOを使ったサーバー側プレビューへ移行する
5. 代表的な入力パターンのFeatureテストを追加する
6. DOCX生成を実装する
7. PDF生成環境をDockerへ追加する
8. AI要約をProvider構成で実装する
9. ログ、レート制限、API送信同意などを整備する

## 11. 検証済み事項

本日の実装時点で以下を確認済み。

- `npm run build` 成功
- `php artisan view:cache` 成功
- `php artisan test` 成功
- 既存テストは2件、2アサーション通過
- 画面、JavaScript、Bladeの構文エラーなし

## 12. 次回再開時の注意

- `docs/design/`はGit管理対象外であるため、設計資料はローカル環境に保存される
- 自動整形でBladeやJavaScriptの改行・インデントが変わることがあるため、編集前に必ず現在の内容を確認する
- `public/build`はビルド生成物であり、`.gitignore`の対象である
- フォーム内容をDBへ保存する実装は追加しない
- バリデーション、DOCX、PDF、AIの各機能は一度に混ぜず、共通データ構造を使って段階的に追加する
