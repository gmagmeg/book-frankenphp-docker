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
        .row {
            display: grid;
            gap: 8px;
            margin-bottom: 12px;
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
        button.secondary {
            background: #374151;
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
    </style>
</head>
<body>
<main class="container">
    <section class="card">
        <h2 class="title">CSVダウンロード</h2>
        <div class="buttons">
            <button id="downloadBtn" type="button">CSVダウンロード</button>
        </div>
        <p id="downloadStatus" class="status"></p>
    </section>

    <section class="card">
        <h2 class="title">Logs</h2>
        <pre id="logs"></pre>
    </section>
</main>

<script>
    const logsEl = document.getElementById('logs');
    const downloadBtn = document.getElementById('downloadBtn');
    const downloadStatus = document.getElementById('downloadStatus');

    function logLine(label, payload) {
        const time = new Date().toISOString();
        const line = `[${time}] ${label} ${typeof payload === 'string' ? payload : JSON.stringify(payload)}`;
        logsEl.textContent = `${line}\n${logsEl.textContent}`;
    }

    downloadBtn.addEventListener('click', async () => {
        downloadBtn.disabled = true;
        downloadStatus.textContent = 'リクエスト送信中…';
        logLine('download-request', 'POST /api/mercure/publish');

        const response = await fetch('/api/mercure/publish', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({}),
        });

        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            downloadStatus.textContent = 'エラー';
            logLine('download-error', body);
        } else {
            downloadStatus.textContent = '完了';
            logLine('download-ok', body);
        }
        downloadBtn.disabled = false;
    });
</script>
</body>
</html>
