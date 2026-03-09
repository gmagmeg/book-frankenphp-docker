<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mercure SSE Demo</title>
    <style>
        :root {
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
        }
        body {
            margin: 0;
            padding: 24px;
            background: #f5f7fb;
            color: #111827;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            display: grid;
            gap: 16px;
        }
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
        }
        .title {
            margin: 0 0 8px;
            font-size: 20px;
        }
        .description {
            margin: 0 0 12px;
            font-size: 14px;
            color: #374151;
            line-height: 1.6;
        }
        button {
            font: inherit;
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            color: #fff;
            background: #0f766e;
            cursor: pointer;
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        pre {
            margin: 0;
            background: #0b1020;
            color: #dbeafe;
            border-radius: 8px;
            padding: 12px;
            overflow: auto;
            max-height: 360px;
        }
        .status {
            font-size: 14px;
            color: #334155;
        }
        .hint {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 14px;
            color: #166534;
            line-height: 1.6;
        }
        .hint a {
            color: #15803d;
            font-weight: 600;
        }
    </style>
</head>
<body>
<main class="container">
    <section class="card">
        <h2 class="title">CSV 生成 (発行ページ)</h2>
        <p class="description">
            ボタンを押すと CSV を生成し、Mercure Hub へ通知を送信します。<br>
            通知は <strong>このページとは別の</strong> CSV ダウンロードページで受信します。
        </p>
        <div class="hint">
            <span>💡</span>
            <span>
                先に <a href="/mercure/csv-download" target="_blank">CSV ダウンロードページ</a> を別タブで開いてから、下のボタンを押してください。<br>
                別タブにダウンロードモーダルが表示されます。
            </span>
        </div>
    </section>

    <section class="card">
        <h2 class="title">CSV 生成</h2>
        <div class="buttons">
            <button id="generateBtn" type="button">CSV 生成</button>
        </div>
        <p id="generateStatus" class="status"></p>
    </section>

    <section class="card">
        <h2 class="title">Logs</h2>
        <pre id="logs"></pre>
    </section>
</main>

<script>
    const logsEl         = document.getElementById('logs');
    const generateBtn    = document.getElementById('generateBtn');
    const generateStatus = document.getElementById('generateStatus');

    function logLine(label, payload) {
        const time = new Date().toISOString();
        const line = `[${time}] ${label} ${typeof payload === 'string' ? payload : JSON.stringify(payload)}`;
        logsEl.textContent = `${line}\n${logsEl.textContent}`;
    }

    // ── CSV 生成リクエスト ──────────────────────────────────────────────────
    generateBtn.addEventListener('click', async () => {
        generateBtn.disabled = true;
        generateStatus.textContent = 'CSV を生成中…';
        logLine('publish-request', 'POST /api/mercure/publish');

        const response = await fetch('/api/mercure/publish', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({}),
        });

        const body = await response.json().catch(() => ({}));

        if (!response.ok) {
            generateStatus.textContent = 'エラーが発生しました';
            logLine('publish-error', body);
        } else {
            generateStatus.textContent = body.message ?? '生成完了';
            logLine('publish-ok', body);
        }

        generateBtn.disabled = false;
    });
</script>
</body>
</html>
