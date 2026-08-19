# Laravel職務経歴書生成システム 設計方針

## 1. 文書の目的

ITエンジニア向けの職務経歴書を入力内容から生成するLaravelアプリケーションの初期設計をまとめる。

利用者はフォームに職務経歴、スキル、資格、自己PRなどを入力し、職務経歴書をDOCXまたはPDFとしてダウンロードする。個人情報保護とシステムの簡略化を優先し、入力内容はデータベースへ保存しない。

## 2. 対象機能

- 氏名、連絡先、職務要約、職歴、保有資格、スキル、自己PRの入力
- 職歴またはプロジェクト履歴の追加・削除
- スキル入力時の候補表示
- 職歴情報を利用したAIによる職務要約の生成
- 入力内容を使った職務経歴書のプレビュー
- DOCX出力
- PDF出力
- 発行後の一時ファイル削除
- DBに個人情報を保存しない運用

## 3. 基本方針

### 3.1 データを保存しない

職務経歴書の入力内容は、データベース、永続セッション、キャッシュ、キューへ保存しない。ブラウザのフォーム状態を基本とし、必要な処理の都度、HTTPリクエストでサーバーへ送信する。

処理の基本フローは以下とする。

```text
ブラウザ上のフォーム
    ↓
AI要約またはプレビューのHTTPリクエスト
    ↓
Laravelで一時的に処理
    ↓
画面表示またはファイル生成
    ↓
レスポンス終了後に入力内容を保持しない
```

Laravelの設定は、個人情報を扱う処理に対して次を基本値とする。

```env
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
```

`array`セッションはリクエストをまたいで保存されないため、フォーム内容を毎回ブラウザから送信する設計と組み合わせる。データベースそのものはLaravelの標準構成として残してもよいが、職務経歴書の内容を保存するモデルやマイグレーションは作成しない。

### 3.2 共通データモデルを使う

入力値をバリデーション後に`ResumeData` DTOへ変換し、プレビュー、DOCX、PDF、AI要約で共通利用する。

```text
GenerateResumeRequest
    ↓
ResumeData DTO
    ├── プレビュー用Blade
    ├── DOCX生成
    ├── PDF生成
    └── AI要約
```

これにより、プレビューとダウンロードで内容がずれることを防ぐ。

## 4. 画面構成

初期版は1画面構成とする。

### 4.1 入力項目

#### 基本情報

- 氏名
- 技術系アカウント、ポートフォリオなどのURL

#### 職務要約

- 手入力欄
- 「職歴からAI生成」ボタン
- AI生成後も編集可能なテキストエリア

#### 職歴・プロジェクト履歴

所属企業を親、プロジェクト履歴を子とする階層構造で入力する。企業ごとに複数のプロジェクトを登録できる。

企業ごとに次の項目を入力する。

- 企業名
- 在籍期間（開始年月・終了年月）
- 雇用形態・契約形態
- 事業内容
- 設立
- 資本金
- 従業員数

雇用形態・契約形態は次から選択する。

- 正社員
- 契約社員
- 派遣社員
- パート・アルバイト
- 業務委託
- フリーランス
- 役員
- その他（自由入力）

フリーランスは所属企業名を必須とせず、屋号や氏名を任意で入力できる。企業名が空欄の場合、帳票上は「フリーランス」と表示する。

プロジェクトごとに次の項目を入力する。

- プロジェクト名
- 期間
- 雇用形態または役割
- チーム規模
- 担当工程
- 使用技術
- 業務内容
- 実績

組織・役割は`resources/data/team-roles.json`のカテゴリ付き候補から選択する。候補に該当しない場合は「その他」を選択して自由入力できるようにする。

候補は次の分類とする。

- プロジェクト・プロダクト管理: PM、PL、PO、スクラムマスター、PdM
- 要件・設計: プロジェクトメンバー、要件定義担当、SE、テクニカルリード、アーキテクト、業務アナリスト
- 開発: 開発者、バックエンド、フロントエンド、フルスタック、モバイルアプリ、データエンジニア
- 品質・テスト: QA、テストエンジニア、テストリーダー、レビュアー
- インフラ・運用: インフラ、クラウド、DevOps、SRE、DB、セキュリティ
- デザイン・ユーザー体験: UI、UX、プロダクトデザイナー、UXリサーチャー

Scrumの正式なロールはProduct Owner、Scrum Master、Developersであり、会社の職位や肩書きとは必ずしも一致しない。そのため、職務経歴書では実際に担当した責任に近い候補を選択する。

プロジェクトは所属企業の中で複数追加できるようにする。プレビューでは企業を在籍開始年月の降順、企業内のプロジェクトを開始年月の降順で表示する。年月は`YYYY-MM`形式で保持し、入力順に依存しない。

#### その他

- 保有資格
- スキル
- 自己PR
- 出力形式（DOCX、PDF、両方）

技術系アカウント・ポートフォリオは複数登録できる。種別はGitHub、Qiita、Zenn、ポートフォリオ、その他から選択し、その他の場合はサイト名を自由入力する。メールアドレスと電話番号は履歴書に記載する情報とし、このアプリでは入力しない。

### 4.2 操作

- プロジェクト、資格、スキルの追加・削除
- AI要約の生成
- プレビュー表示
- DOCXまたはPDFの発行
- 発行後のフォーム内容クリア

## 5. フロントエンド方針

MVPではBlade、Alpine.js、通常のPOSTおよび`fetch`を使用する。Livewireは必須としない。

Livewireはフォーム状態管理に便利だが、入力内容を持つリクエストが増え、状態の扱いを慎重に設計する必要がある。個人情報を永続化しない要件を明確に保つため、まずはブラウザ上で状態を保持し、必要な操作だけサーバーへ送る構成とする。

Alpine.jsで次の機能を実装する。

- プロジェクト行の追加・削除
- 資格行の追加・削除
- スキルのタグ入力
- スキル候補の表示
- AI要約の非同期実行
- プレビューの表示切り替え
- 発行中のボタン無効化

## 6. ルート設計

```php
Route::get('/', [ResumeController::class, 'create'])
    ->name('resume.create');

Route::post('/resume/preview', [ResumeController::class, 'preview'])
    ->name('resume.preview');

Route::post('/resume/summary', [ResumeSummaryController::class, 'generate'])
    ->name('resume.summary');

Route::post('/resume/download', [ResumeController::class, 'download'])
    ->name('resume.download');
```

各リクエストはCSRF保護の対象とする。発行処理は、リクエスト中にデータをバリデーションし、ファイルを生成してレスポンスする。

## 7. 入力データとバリデーション

想定する入力データは次のとおり。

```text
ResumeData
├── full_name
├── email
├── phone
├── address
├── links[]
├── summary
├── companies[]
│   ├── name
│   ├── period_from
│   ├── period_to
│   ├── industry
│   ├── established
│   ├── capital
│   ├── employees
│   └── projects[]
│       ├── name
│       ├── period_from
│       ├── period_to
│       ├── role
│       ├── team
│       ├── processes
│       ├── technologies
│       └── description
├── certifications[]
├── skills[]
└── self_pr
```

バリデーション例:

```php
'full_name' => ['required', 'string', 'max:100'],
'email' => ['nullable', 'email', 'max:255'],
'summary' => ['nullable', 'string', 'max:3000'],
'companies' => ['required', 'array', 'min:1', 'max:20'],
'companies.*.name' => ['required', 'string', 'max:200'],
'companies.*.period_from' => ['nullable', 'date_format:Y-m'],
'companies.*.projects' => ['array', 'max:30'],
'companies.*.projects.*.name' => ['required', 'string', 'max:200'],
'companies.*.projects.*.period_from' => ['nullable', 'date_format:Y-m'],
'companies.*.projects.*.period_to' => ['nullable', 'date_format:Y-m'],
'companies.*.projects.*.description' => ['nullable', 'string', 'max:5000'],
'skills' => ['array', 'max:50'],
'skills.*' => ['string', 'max:100'],
```

プロジェクト数、項目ごとの文字数、AIへ送信する総文字数に上限を設ける。HTMLやスクリプトはBladeのエスケープを通し、利用者入力をそのままHTMLとして解釈しない。

## 8. AI要約

### 8.1 抽象化

AIプロバイダーをサービスとして抽象化し、OpenAIとGeminiを切り替えられるようにする。

初期実装では複数のAI APIを契約せず、Google Gemini APIのみを採用する。中国、ロシア等の権威主義国家に拠点を置くAIサービスは採用対象外とする。モデルは、まず`gemini-2.5-flash-lite`を使用し、品質評価で不足が確認された場合に、同じGoogle API契約内で`gemini-2.5-flash`へ切り替える。これにより、API契約を増やさずに費用と品質を調整できる。

```text
ResumeSummaryProvider
    ├── OpenAIResumeSummaryProvider
    └── GeminiResumeSummaryProvider
```

```php
interface ResumeSummaryProvider
{
    public function summarize(array $careerHistory, array $skills): string;
}
```

環境変数の例:

```env
AI_PROVIDER=gemini
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash-lite
```

Laravel標準のHTTPクライアントでAPIへ接続し、最初から特定ベンダーのSDKに強く依存しない構成とする。

### 8.3 費用対効果と運用方針

職務要約は、職歴・担当工程・使用技術・実績・資格・スキルを入力し、数百文字の日本語を出力する処理である。画像、音声、検索、複雑なエージェント処理は必要ないため、軽量なテキストモデルを基本とする。

公式料金を基準にした概算では、Gemini 2.5 Flash-Liteは入力`$0.10 / 100万トークン`、出力`$0.40 / 100万トークン`、Gemini 2.5 Flashは入力`$0.30 / 100万トークン`、出力`$2.50 / 100万トークン`である。1回あたり入力4,000トークン、出力500トークンの場合、1,000回の要約生成はFlash-Liteで約`$0.60`、Flashで約`$2.45`となる。料金、為替、モデル名は変更される可能性があるため、契約時には公式料金表を再確認する。

無料枠は検証用途に限定する。無料枠では送信コンテンツがプロダクト改善に使用される条件があるため、本番の職務経歴情報には、有料プランでプロダクト改善への利用がない設定を使用する。

実装時には次の制約を必須とする。

- AIへ送信するのは職歴、担当工程、使用技術、実績、資格、スキルに限定する
- 氏名、電話番号、メールアドレス、住所、生年月日、顔写真などは送信しない
- AI送信前に利用者の同意を取得する
- 入力文字数、出力文字数、リクエスト時間、リトライ回数を制限する
- IPまたは利用者単位のレート制限を設ける
- APIキーは`.env`で管理し、ソースコードへ記載しない
- 生成結果は自動保存・自動確定せず、利用者が編集してから採用する
- API失敗時は手入力の職務要約へ戻せるようにする

### 8.2 APIへ送信する情報

送信対象は、職務要約作成に必要な職歴、担当工程、使用技術、実績、資格、スキルに限定する。

次の個人情報はAI要約へ不要なため、原則として送信しない。

- 氏名
- 電話番号
- メールアドレス
- 住所
- 生年月日
- 顔写真
- 本人確認書類に関する情報

### 8.3 プロンプトの方針

AIには、事実にない内容を追加しないこと、誇張しないこと、文字数を守ること、自然な日本語の文章にすることを指示する。AIの結果はそのまま確定せず、利用者が確認・編集できる状態で表示する。

AI処理には次の制御を入れる。

- タイムアウト
- リトライ回数制限
- レート制限
- 入力文字数制限
- 出力文字数制限
- API失敗時の手入力への復帰
- AI送信に関する同意表示

表示例:

```text
職務経歴情報の一部をAIサービスへ送信して要約します。
個人情報を含めずに入力してください。
```

## 9. スキル候補

スキル候補はDBへ保存せず、静的なJSONまたはPHP設定ファイルでカテゴリごとに管理する。

```text
resources/data/skills.json
```

カテゴリは次を基本とする。

- 言語
- フレームワーク
- ミドルウェア
- OS
- インフラ
- データベース
- デザインツール
- 開発ツール・その他

JSONはカテゴリ識別子、表示名、候補一覧を持つオブジェクト形式とする。

```json
{
  "languages": {
    "label": "言語",
    "skills": ["PHP", "JavaScript", "TypeScript"]
  },
  "frameworks": {
    "label": "フレームワーク",
    "skills": ["Laravel", "React", "Vue.js"]
  }
}
```

画面ではカテゴリごとに候補を表示し、選択したスキルはカテゴリ情報とともに保持する。各スキルには経験年数、経験区分、備考を持たせる。経験区分は「業務使用」「個人開発」「自己研鑽」から選択し、補足説明は独立した備考欄へ入力する。カテゴリ情報を保持することで、職務経歴書の出力時に「言語」「フレームワーク」などの分類を維持できる。

MVPではカテゴリごとの`datalist`またはAlpine.jsの候補リストを利用する。複数スキルのタグ入力が必要になった段階で、カテゴリを選択してからスキルを追加するUIへ拡張する。

## 10. プレビュー

プレビューと最終出力で同じテンプレートを利用する。推奨構成は次のとおり。

```text
resources/views/resume/create.blade.php
resources/views/resume/preview.blade.php
resources/views/resume/document.blade.php
resources/css/resume.css
```

デスクトップでは入力フォームとプレビューを左右に配置し、モバイルでは入力とプレビューをタブまたは縦並びで表示する。印刷用CSSを用意し、PDF生成時にも利用する。

プレビューはフォーム内容をPOSTし、LaravelがBladeでHTMLを返す方式から始める。入力内容は保存しない。将来的にサーバーへ個人情報を送らないプレビューが必要になった場合は、ブラウザ上でテンプレートを描画する方式を検討する。

## 11. DOCX出力

DOCX生成には`phpoffice/phpword`を利用する。

```bash
composer require phpoffice/phpword
```

処理は専用サービスへ分離する。

```text
app/Services/Document/ResumeDocxGenerator.php
```

処理フロー:

```text
ResumeData
    ↓
PhpWordで文書作成
    ↓
一時ファイルへ保存
    ↓
ダウンロードレスポンス
    ↓
一時ファイル削除
```

プロジェクト数やスキル数に応じて表の行数が変わるため、初期版はPhpWordで文書を組み立てる方式を採用する。レイアウトが固まった後、必要であればDOCXテンプレートへの差し込み方式へ変更する。

## 12. PDF出力

本番環境は自由度の低いレンタルサーバーを想定するため、ChromiumやLibreOfficeなどの外部実行アプリケーションを必要とする方式は採用しない。

```text
ResumeData
    ↓
PDF専用Blade + PDF専用CSS
    ↓
Dompdf
    ↓
PDFダウンロード
```

PDF生成には純PHPライブラリである`dompdf/dompdf`を第一候補としてComposerで導入する。

```bash
composer require dompdf/dompdf
```

画面用プレビューのHTML/CSSとPDF帳票用のHTML/CSSは分ける。Dompdfはブラウザの描画エンジンではないため、PDF専用テンプレートではA4、単純な表、罫線、余白、改ページ制御を中心にする。CSS Grid、Flexbox、JavaScript描画、外部フォントなどへ強く依存しない。

日本語フォントはサーバーへインストールせず、ライセンス上利用可能なIPAex Gothicを`resources/fonts/IPAexGothic.ttf`としてプロジェクトに同梱し、PDFライブラリへ登録する。PDF用の一時ディレクトリは`storage/app/dompdf-temp/`のような専用・非公開パスにする。

初期方針:

```text
DOCX: PhpWord
PDF: Dompdf（純PHP）
プレビュー: 画面用Blade
PDF帳票: PDF専用Blade
```

`mPDF`は外部アプリ不要の代替候補だが、ライセンスと日本語帳票の検証が必要なため、Dompdfで要件を満たせない場合に評価する。ブラウザの印刷機能は、手動でPDF保存する代替手段として残す。外部PDF変換APIは職務経歴書の内容を第三者へ送信するため、原則として採用しない。

詳細は`docs/design/pdf-output-strategy.md`を参照する。

## 13. 推奨ディレクトリ構成

```text
app/
├── DTO/
│   └── ResumeData.php
├── Http/
│   ├── Controllers/
│   │   ├── ResumeController.php
│   │   └── ResumeSummaryController.php
│   └── Requests/
│       └── GenerateResumeRequest.php
├── Services/
│   ├── AI/
│   │   ├── ResumeSummaryProvider.php
│   │   ├── OpenAIResumeSummaryProvider.php
│   │   └── GeminiResumeSummaryProvider.php
│   └── Document/
│       ├── ResumeDocxGenerator.php
│       └── ResumePdfGenerator.php
└── Providers/
    └── AppServiceProvider.php

resources/
├── data/
│   └── skills.json
├── views/
│   └── resume/
│       ├── create.blade.php
│       ├── preview.blade.php
│       └── document.blade.php
├── css/
│   └── resume.css
└── js/
    └── resume-form.js
```

## 14. セキュリティと個人情報保護

- 職務経歴書の内容をDBへ保存しない
- セッション、キャッシュ、キューへ個人情報を保存しない
- リクエスト本文をアプリケーションログへ出力しない
- バリデーションエラーや例外に個人情報を含めない
- AI APIへ不要な個人情報を送信しない
- AI送信前に利用者へ説明する
- CSRF保護を有効にする
- 文字数、配列数、リクエストサイズを制限する
- 生成した一時ファイルをレスポンス後に削除する
- ストレージやアクセスログの保持期間を決める
- 発行後にブラウザ側の入力内容をクリアできるようにする
- レート制限を設け、AI APIの過剰利用を防ぐ

「DBへ保存しない」ことは「どこにも痕跡が残らない」ことと同義ではない。Webサーバーのアクセスログ、AIサービス側の取り扱い、障害時のログ、ブラウザのダウンロード履歴なども運用上の確認対象とする。

## 15. 開発フェーズ

### Phase 1: 入力とプレビュー

- 入力フォーム
- プロジェクト複数行
- スキル候補
- バリデーション
- Bladeプレビュー
- DB保存なし

### Phase 2: DOCX/PDF

- PhpWord導入
- Dompdf導入
- PDF専用BladeとA4帳票CSSの作成
- 日本語フォントの登録
- 日本語フォント確認
- 一時ファイル削除
- DOCX/PDFダウンロードテスト

### Phase 3: AI要約

- AIプロバイダー抽象化
- OpenAIまたはGeminiのどちらかを実装
- 個人情報除外
- タイムアウトとエラーハンドリング
- 生成結果の編集機能

### Phase 4: セキュリティと品質

- レート制限
- ログへの個人情報混入確認
- 入力上限の確認
- AI送信同意
- PDF/DOCXのレイアウトテスト
- 入力内容のクリア
- 運用時のログ保持期間確認

## 16. 初期採用案

```text
Laravel Blade
Alpine.js
通常のPOST + fetch
DB保存なし
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
PhpWordでDOCX
PDF専用Blade + DompdfでPDF
Laravel HTTP ClientでAI連携
OpenAI/GeminiをProviderで切り替え
```

最初に`ResumeData`の形を決め、入力、バリデーション、プレビュー、DOCX、PDF、AI要約を同じデータ構造へ接続する。Livewireの導入判断は、MVPでフォーム操作が複雑になった場合に改めて行う。
