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

## 本番マルチドメイン運用（Caddy）

本番で複数ドメインを同一VPSに収容する場合は、Caddyをリバースプロキシとして利用する。

```bash
cd /workspaces/LaravelResumeGenerationSystem
docker compose -f docker-compose.prod.yml -f docker-compose.proxy.yml up -d --build
```

- Caddy設定: [docker/caddy/Caddyfile](docker/caddy/Caddyfile)
- 追加ドメイン用テンプレート: [docker/caddy/sites/example-other-app.caddy.example](docker/caddy/sites/example-other-app.caddy.example)
- Compose上書き: [docker-compose.proxy.yml](docker-compose.proxy.yml)

CloudflareのFull (strict)を使う場合、証明書を `private/certs` に配置してCaddyへマウントする。
