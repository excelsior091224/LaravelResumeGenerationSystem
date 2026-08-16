# Laravel Resume Generation System

職務経歴書を入力して、プレビューと出力までを一貫して扱える Laravel アプリです。

## 概要

- 職務経歴書のフォーム入力
- スキル・資格・自己PRなどの記入
- 会社ごとの職歴とプロジェクト履歴の追加
- ライブプレビュー
- バリデーションによる入力値の整合性確認
- Laravel + Blade + Alpine.js + Vite ベースの構成

## 主要ディレクトリ

- [src/app](src/app)
- [src/resources](src/resources)
- [src/routes](src/routes)
- [src/tests](src/tests)
- [docs/design](docs/design)

## 開発・確認コマンド

```bash
cd /workspaces/LaravelResumeGenerationSystem/src
php artisan serve --host 0.0.0.0 --port 8000
npm run dev -- --host localhost
npm run build
php artisan test tests/Feature/ResumeValidationTest.php
```

## 補足

- 日々の進捗や開発メモは [docs/design](docs/design) に保存する。
- 実装の詳細や技術メモは [src/README.md](src/README.md) にまとめる。
