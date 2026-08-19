# 2026-08-19 開発進捗

## 概要

2026-08-19時点のLaravel職務経歴書生成システムの開発状況を記録する。
本日は、AIによる職務要約機能に採用するGemini APIの利用開始確認と、モデル変更への対応を行った。

## AI APIの採用方針

- API契約は複数にせず、Google Gemini APIのみを使用する
- 中国、ロシア等の権威主義国家に拠点を置くAIサービスは採用対象外とする
- 初期モデルは`gemini-3.5-flash-lite`とする
- 品質不足時は、同じGoogle API契約内で利用可能な上位モデルへ切り替える
- 無料枠は検証用途に限定し、本番ではデータ利用条件を確認した有料プランを使用する

## Gemini APIの接続確認

### 発生した問題

当初、`gemini-2.5-flash-lite`を指定してAPIを呼び出したところ、HTTP 404が返った。
APIキー自体は読み込めており、APIレスポンスには次の理由が示された。

```text
This model models/gemini-2.5-flash-lite is no longer available to new users.
Please update your code to use models/gemini-3.5-flash-lite
```

### 対応

次の設定を`gemini-3.5-flash-lite`へ変更した。

- `src/.env`
- `src/.env.example`
- `docs/design/resume-generation-system.md`
- `docs/design/2026-08-18-final-state.md`

設定変更後に`php artisan optimize:clear`を実行し、LaravelのTinkerから再接続した。

### 検証結果

- APIキー設定: 読み込み成功
- 使用モデル: `gemini-3.5-flash-lite`
- HTTPステータス: `200`
- 日本語要約の返却: 成功

確認に使用した入力:

```text
LaravelでWebアプリケーションを開発しました。これを一文で要約してください。
```

返却例:

```text
Laravelフレームワークを活用し、効率的で保守性の高いWebアプリケーションを開発しました。
```

## curlについて

コンテナ内に`curl`コマンドは存在しなかった。Dockerfileではビルド中に`curl`を使用した後、次の処理で削除している。

```dockerfile
apt-get purge -y --auto-remove curl
```

手動で必要な場合は、次のコマンドで一時的に導入できる。

```bash
apt-get update && apt-get install -y curl
```

ただし、APIキーをコマンドへ直接書かず、LaravelのHTTPクライアントまたはPHPから`.env`の値を読み込んで接続確認する方法を推奨する。

## 実装状況

### 完了済み

- PDF生成
- DOCX生成
- Microsoft WordからPDFへの変換によるDOCX表示確認
- 大容量FixtureによるPDF/DOCX生成確認
- ライブプレビューとサーバー側帳票の構造・表示内容の統一
- Gemini APIキーの読み込み確認
- Gemini APIへの実リクエストと日本語要約の返却確認
- 採用モデル変更に伴う設計資料・環境変数サンプルの更新

### 未実装

- Gemini APIを呼び出す専用サービス
- `POST /resume/summary` エンドポイント
- フォームの「職歴からAI生成」ボタン
- Alpine.jsからの非同期要約リクエスト
- AI送信前の同意チェック
- AI入力データから氏名・連絡先等を除外する専用整形
- AI出力の文字数制限、レート制限、失敗時の手入力復帰

## 次の開発

1. `config/services.php`へGemini設定を追加
2. Gemini呼び出しを担当する`ResumeSummaryProvider`を実装
3. 職歴・スキルからAI送信用データを作成する整形処理を実装
4. `POST /resume/summary`を追加
5. 同意表示付きのAlpine.js UIを追加
6. API失敗、タイムアウト、空応答、事実の誇張を想定したテストを追加

## 残課題

- AI職務要約機能の実装
- LibreOffice環境でのDOCX表示確認
- 正式な職務経歴書様式との細部比較

## 関連資料

- [resume-generation-system.md](resume-generation-system.md)
- [2026-08-18-final-state.md](2026-08-18-final-state.md)
