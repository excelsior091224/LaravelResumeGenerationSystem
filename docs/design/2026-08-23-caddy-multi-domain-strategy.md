# 2026-08-23 Caddy Multi Domain Strategy

## 目的

- 1台のVPSで複数プロジェクトをドメイン単位で配信する。
- 各アプリは内部ネットワークで待受し、外部公開はCaddyの80/443に集約する。
- 新規ドメイン追加時の変更点を最小化する。

## 構成方針

- `docker-compose.prod.yml`: アプリ本体の定義を維持。
- `docker-compose.proxy.yml`: Caddyを追加し、`app` の外部公開ポートを無効化。
- `docker/caddy/Caddyfile`: 既存ドメインのreverse proxy定義 + `sites/*.caddy` のimport。
- `docker/caddy/sites/*.caddy`: 将来アプリ用のドメイン追加ファイル。

## 現在の既存アプリ（resumefoundries）

- `resumefoundries.com` / `www.resumefoundries.com` をCaddyで終端。
- `www` は apex へ301リダイレクト。
- upstreamは `app:80`。
- Cloudflare Full (strict)前提で `private/certs/origin.crt` と `private/certs/origin.key` を利用。

## 起動手順

```bash
cd /workspaces/LaravelResumeGenerationSystem
docker compose -f docker-compose.prod.yml -f docker-compose.proxy.yml up -d --build
```

## 新規ドメイン追加手順（テンプレート）

1. 同一composeプロジェクト内に新規アプリサービスを追加（例: `other-service`）。
2. `docker/caddy/sites/<domain>.caddy` を作成して `reverse_proxy other-service:<port>` を定義。
3. 必要な証明書を `private/certs` に配置。
4. `docker compose ... up -d` で反映。
5. 公開確認（`curl -I https://<domain>`、`curl -I https://www.<domain>`）。

## 運用上の注意

- 既存のApache側443公開は停止し、TLS終端をCaddyに一本化する。
- Laravel側でproxy配下運用に必要なヘッダ信頼設定（`X-Forwarded-*`）を確認する。
- Cloudflare利用時はDNSの向き先をCaddyが待受しているVPSに統一する。
