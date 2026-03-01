# Laravel 12 + Octane + FrankenPHP (Worker) + PostgreSQL

このプロジェクトは Docker 上で Laravel Octane を FrankenPHP サーバーとして動作させる構成です。  
FrankenPHP は worker モードで起動します。

## 1. コンテナ起動方法

### 初回または再作成時
```bash
docker compose up -d --build
```

### 起動確認
```bash
docker compose ps
curl -i http://127.0.0.1:8000
```

### 初回マイグレーション
```bash
docker compose exec app php artisan migrate --force
```

### 停止
```bash
docker compose down
```

## 2. チェック用ルーティング

以下のルートで、worker 内メモリに保持した boot 情報を確認できます。

- `GET /debug/boot-check`
- 例: [http://127.0.0.1:8000/debug/boot-check](http://127.0.0.1:8000/debug/boot-check)

返却 JSON の主な項目:

- `probe.pid`: リクエストを処理した worker プロセス ID
- `probe.boot_count`: その worker で boot が何回走ったか
- `probe.request_count`: 検査ルートを処理した回数
- `probe.booted_at`: boot 実行時刻

確認ポイント:

- 同じ `pid` の間は `boot_count` が `1` のまま
- 同じ `pid` で `request_count` だけ増える

### NGパターン1: リクエスト情報を静的に保持

以下は Octane で避けるべき実装例として追加したルートです。

- `GET /debug/octane/ng-patterns/1/static-tenant`

例:

```bash
curl "http://127.0.0.1:8000/debug/octane/ng-patterns/1/static-tenant?tenant_id=101"
curl "http://127.0.0.1:8000/debug/octane/ng-patterns/1/static-tenant?tenant_id=202"
```

返却 JSON の `before` が前リクエストの `tenant_id` を引き継ぐことがあり、  
`public static` へのリクエスト情報保持が危険であることを確認できます。

### NGパターン2: Request/Form情報を singleton に閉じ込める

以下のような実装は NG です。  
リクエスト境界の値を singleton 化すると、worker 内で古い値が固定される可能性があります。

```php
$this->app->singleton(ReportContext::class, function () {
    return new ReportContext(request()->header('X-Request-Id'));
});
```

確認用ルート:

- `GET /debug/octane/ng-patterns/2/request-singleton` (説明 + 実行ページ)
- `GET /debug/octane/ng-patterns/2/request-singleton/check` (検証API)

curl 例:

```bash
curl -H "X-Request-Id: req-001" "http://127.0.0.1:8000/debug/octane/ng-patterns/2/request-singleton/check"
curl -H "X-Request-Id: req-999" "http://127.0.0.1:8000/debug/octane/ng-patterns/2/request-singleton/check"
```

返却 JSON で `header_x_request_id` と `singleton_request_id` の不一致 (`leaked=true`) が出ると、  
singleton へ request 情報を持たせる危険性を確認できます。

## 3. どのログを見れば分かるか

Laravel ログを見ます。

- ファイル: `storage/logs/laravel.log`

確認コマンド例:

```bash
tail -f storage/logs/laravel.log
```

```bash
rg "boot-probe:" storage/logs/laravel.log
```

出力されるログ種別:

- `boot-probe: app booted`  
  worker 起動時（boot 時）に出力
- `boot-probe: request handled`  
  `/debug/boot-check` 実行時に出力

## 4. コード変更時の注意（Octane）

Octane worker は常駐のため、PHPコード変更後は reload が必要です。

```bash
docker compose exec app php artisan octane:reload
```

その後、`/debug/boot-check` を再度叩いて確認してください。

## 5. Mercure SSE デモ（FrankenPHP公式Mercure連携）

`https://frankenphp.dev/docs/mercure/` のLaravel Octane向け設定に合わせて、以下を追加しています。

- `GET /mercure/sse-demo` SSE受信 + API実行ページ
- `POST /api/mercure/publish` Mercureへ配信するAPI（`mercure_publish()` 呼び出し）

### 必要な環境変数

`.env` に以下を設定してください（`.env.example` には追記済み）。

```dotenv
OCTANE_SERVER=frankenphp
MERCURE_TRANSPORT_URL=mercure://publisher:!ChangeThisMercureHubJWTSecretKey!@localhost/.well-known/mercure
OCTANE_MERCURE_PUBLISHER_JWT_KEY=!ChangeThisMercureHubJWTSecretKey!
OCTANE_MERCURE_SUBSCRIBER_JWT_KEY=!ChangeThisMercureHubJWTSecretKey!
OCTANE_MERCURE_EXTRA_DIRECTIVES=anonymous
```

### 動作確認

1. 設定反映のため再起動:

```bash
docker compose down
docker compose up -d --build
```

2. ページを開く:

- [http://127.0.0.1:8100/mercure/sse-demo](http://127.0.0.1:8100/mercure/sse-demo)

3. `Connect SSE` を押し、`POST /api/mercure/publish` を実行すると、同ページの Logs に受信イベントが表示されます。
