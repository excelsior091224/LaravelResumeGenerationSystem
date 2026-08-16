# 2026-08-16 開発進捗メモ

## 実施内容

- Laravel の職務経歴書入力フォームのレイアウトとスタイルを整備した。
- 会社・プロジェクトの期間入力に開始年月・終了年月のラベルを追加した。
- 「現在も在籍中」「現在も継続中」のチェックボックスを追加した。
- 期間の逆転バリデーションを強化した。
- 「その他」を選択した場合に、詳細入力が必須になるバリデーションを追加した。
- CSS の import 順序と Vite/HMR のホスト混在の問題を修正した。

## 発生した問題と原因

### 1. CSS が効かない

- `resources/css/app.css` の `@import` が後ろに置かれており、CSS の読み込み順が壊れていた。
- その結果、職務経歴書用のスタイルが読み込まれず、素の HTML だけが表示された。

### 2. Vite と Laravel の URL が混在

- Vite が `0.0.0.0` ベースで動いていた一方で、Laravel は `127.0.0.1:8000` を参照していた。
- ブラウザ側が古い URL を使い続け、古いページやアセットを参照していた。
- この混在が、白画面やローディングのまま止まる現象を引き起こした。

### 3. バリデーションの不備

- 会社・プロジェクトの終了年月が開始年月より前になるケースを防ぐ必要があった。
- `is_current` が true の場合は終了年月の必須チェックを解除する必要があった。
- 「その他」選択時の追加入力が空なのを防ぐ必要があった。

## 修正方針

- `resources/css/app.css` の `@import` を先頭に整理した。
- `vite.config.js` と `package.json` で Vite の host / HMR host を `localhost` に統一した。
- `public/hot` を削除し、`/build/` アセット参照へ戻した。
- バリデーションを `GenerateResumeRequest` に追加し、回帰テストを `tests/Feature/ResumeValidationTest.php` に記載した。

## 確認結果

- `php artisan test tests/Feature/ResumeValidationTest.php` 実行済み
- 2 tests passed
- 7 assertions
- ビルド確認も `npm run build` で成功した

## 現在の運用ルール

- ブラウザは `http://127.0.0.1:8000/` を使う。
- `localhost:5174` や `0.0.0.0` 系の URL は混乱の原因になりやすい。
- UI 確認時は毎回 `npm run dev` または `npm run build` を実行する。
- 日々の進捗や開発メモは `docs/design` 配下に保存する。

## 関連ファイル

- [src/app/Http/Requests/GenerateResumeRequest.php](../../src/app/Http/Requests/GenerateResumeRequest.php)
- [src/resources/views/resume/create.blade.php](../../src/resources/views/resume/create.blade.php)
- [src/resources/css/app.css](../../src/resources/css/app.css)
- [src/resources/css/resume.css](../../src/resources/css/resume.css)
- [src/resources/js/resume-form.js](../../src/resources/js/resume-form.js)
- [src/tests/Feature/ResumeValidationTest.php](../../src/tests/Feature/ResumeValidationTest.php)
