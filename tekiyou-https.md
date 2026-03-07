# HTTPS 化と証明書の設定手順

FrankenPHP (Caddy) による自動 HTTPS / HTTP2 の設定手順です。

## 変更した設定

### `docker/start-container.sh`
`php artisan octane:frankenphp` に以下のフラグを追加：

- `--https` : HTTPS (HTTP/2) を有効化。Caddy がローカル CA 証明書を自動生成する
- `--watch` : PHP ファイルの変更を検知してワーカーを自動リロード

### `compose.yaml`
- `CHOKIDAR_USEPOLLING: "1"` : macOS + Docker でファイル変更を確実に検知するためのポーリング設定
- `caddy_data:/data` ボリューム: 生成された TLS 証明書を永続化し、コンテナ再起動後も再登録が不要になる

### `package.json`
- `chokidar` を devDependencies に追加（`--watch` が内部で使用）

---

## 初回セットアップ手順

### 1. イメージをリビルドして起動

```bash
docker compose build --no-cache
docker compose up -d
```

### 2. ローカル CA 証明書を macOS Keychain に登録

```bash
# CA 証明書をコンテナからホストにコピー
docker cp book-frankenphp-app:/data/caddy/pki/authorities/local/root.crt ~/caddy-local-root.crt

# macOS Keychain に信頼済みルート CA として登録
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain ~/caddy-local-root.crt
```

Chrome・Safari はこれで証明書警告が出なくなります。

### 3. アクセス

```
https://localhost:8000
```

---

## 注意事項

- `caddy_data` ボリュームが存在する限り同じ証明書が使われ続けるため、Keychain への再登録は不要
- ボリュームを削除した場合は証明書が再生成されるため、手順 2 からやり直す
- Firefox は独自の証明書ストアを使うため、別途 `about:config` で設定が必要

### Firefox での追加設定

`about:config` を開き `security.enterprise_roots.enabled` を `true` にすると macOS Keychain の証明書を参照するようになる。

---

## ボリュームを削除して証明書を再発行したい場合

```bash
docker compose down
docker volume rm book-frankenphp-caddy-data
docker compose up -d
# その後、手順 2 の証明書登録を再実行
```
