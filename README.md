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
- [apps/resume-foundry](apps/resume-foundry) - Astro + Cloudflare Workers による保存型SaaSプロトタイプ
- [docs/design](docs/design)

## Astro + Cloudflare Workers プロトタイプ

保存型の職務経歴管理SaaSへの移行検証として、`apps/resume-foundry` に Astro + Cloudflare Workers + D1 + R2 構成の初期実装を追加している。

```bash
cd apps/resume-foundry
npm run dev
npm run build
```

既存Laravelアプリは単発の職務経歴書生成ツールとして残しつつ、保存型の職歴ログ、認証、D1保存、PDF/DOCX/AI課金の検証はAstro側で進める。

## 開発・確認コマンド

```bash
cd /workspaces/LaravelResumeGenerationSystem/src
php artisan serve --host 0.0.0.0 --port 8000
npm run dev -- --host localhost
npm run build
php artisan test tests/Feature/ResumeValidationTest.php
```

### Chromium方式のPDFテスト

開発コンテナーをリビルドすると、本番と同じChromium方式でPDFを生成できます。

```bash
docker compose build --no-cache app
docker compose up -d app
docker exec laravel-app chromium --version
docker exec laravel-app sh -lc 'cd /workspaces/LaravelResumeGenerationSystem/src && php artisan test --filter=DocumentGenerationTest --compact'
```

`chromium --version`が成功すれば、PDF生成時にChromiumが自動選択されます。Chromiumが利用できない環境では、開発用のフォールバックとしてDompdfが使われます。

## 補足

- 日々の進捗や開発メモは [docs/design](docs/design) に保存する。
- 実装の詳細や技術メモは [src/README.md](src/README.md) にまとめる。

## 本番マルチドメイン運用（Caddy）

本番で複数ドメインを同一VPSに収容する場合は、Caddyをリバースプロキシとして利用する。アプリはホストの80/443を公開せず、Caddyから内部接続する。

```bash
cd /workspaces/LaravelResumeGenerationSystem
docker compose -f docker-compose.prod.yml -f docker-compose.proxy.yml up -d --build
```

### 同じComposeプロジェクトに追加する場合

`docker-compose.prod.yml` に、ホストへポート公開しないサービスを追加する。

```yaml
	other-app:
		build: /path/to/other-app
		expose:
			- "8080"
```

`docker/caddy/sites/other.example.com.caddy` を作成する。

```caddyfile
other.example.com {
		reverse_proxy other-app:8080
}
```

VPSで反映する。

```bash
cd ~/LaravelResumeGenerationSystem
docker compose -f docker-compose.prod.yml -f docker-compose.proxy.yml up -d --build
docker compose -f docker-compose.prod.yml -f docker-compose.proxy.yml exec -w /etc/caddy caddy caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile
```

### 別リポジトリのComposeとして追加する場合

現在のCaddyネットワーク名を確認する。

```bash
docker network ls | grep laravelresumegenerationsystem
```

追加アプリ側のComposeで、表示されたネットワークを外部ネットワークとして指定する。

```yaml
services:
	other-app:
		image: example/other-app:latest
		expose:
			- "8080"
		networks:
			- edge

networks:
	edge:
		external: true
		name: laravelresumegenerationsystem_default
```

Caddy側の `docker/caddy/sites/other.example.com.caddy` に `reverse_proxy other-app:8080` を追加し、各Composeを反映する。

```bash
cd ~/other-app
docker compose up -d

cd ~/LaravelResumeGenerationSystem
docker compose -f docker-compose.prod.yml -f docker-compose.proxy.yml exec -w /etc/caddy caddy caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile
```

追加アプリにも `80` や `443` の `ports` は設定しない。公開ポートはCaddyだけが使用する。

- Caddy設定: [docker/caddy/Caddyfile](docker/caddy/Caddyfile)
- 追加ドメイン用テンプレート: [docker/caddy/sites/example-other-app.caddy.example](docker/caddy/sites/example-other-app.caddy.example)
- Compose上書き: [docker-compose.proxy.yml](docker-compose.proxy.yml)

CloudflareのFull (strict)を使う場合、証明書を `private/certs` に配置してCaddyへマウントする。
