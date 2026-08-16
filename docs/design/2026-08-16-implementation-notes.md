# 2026-08-16 実装メモ

## 概要

このメモは、職務経歴書アプリの実装に直接関係する開発ノートを記録するためのものです。

## 主要コンポーネント

- フォーム: `src/resources/views/resume/create.blade.php`
- バリデーション: `src/app/Http/Requests/GenerateResumeRequest.php`
- スタイル: `src/resources/css/resume.css`
- JavaScript: `src/resources/js/resume-form.js`
- ルート: `src/routes/web.php`

## 実施した修正

### CSS / Vite の問題

- CSS の import 順序が崩れており、職務経歴書用スタイルが読み込まれていなかった。
- `src/resources/css/app.css` の `@import "./resume.css";` を先頭に移動させた。
- Vite と Laravel のホスト/ポートが混在していたため、ブラウザが古い URL を利用していた。
- `src/public/hot` を削除し、ビルド済みの `/build/` アセット参照へ戻した。

### バリデーションの強化

- 会社・プロジェクトの終了年月が開始年月より前にならないようにした。
- `is_current` が true の場合は終了年月の比較を除外した。
- 「その他」を選択した場合に詳細入力が空ではいけないようにした。
- 回帰テストを追加した。

## 開発・確認コマンド

```bash
cd /workspaces/LaravelResumeGenerationSystem/src
php artisan serve --host 0.0.0.0 --port 8000
npm run dev -- --host localhost
npm run build
php artisan test tests/Feature/ResumeValidationTest.php
```

## 運用上の注意

- ブラウザは `http://127.0.0.1:8000/` を使う。
- `localhost:5174` や `0.0.0.0` 系の URL は混乱の原因になりやすい。
- 画面確認時は `npm run dev` または `npm run build` を実行してから見る。

## 参照

- 設計メモ: `docs/design/resume-generation-system.md`
- 実装メモ: このファイル
