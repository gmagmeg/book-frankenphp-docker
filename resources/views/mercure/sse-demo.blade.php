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

        /* ── Modal ── */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 100;
            align-items: flex-start;
            justify-content: center;
            padding-top: 48px;
        }
        .modal-backdrop.open {
            display: flex;
        }
        .modal {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            padding: 24px;
            width: min(480px, 92vw);
        }
        .modal-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .modal-icon {
            font-size: 22px;
            line-height: 1;
        }
        .modal-title {
            margin: 0;
            font-size: 17px;
            font-weight: 600;
        }
        .modal-message {
            font-size: 14px;
            color: #374151;
            margin: 0 0 20px;
            line-height: 1.6;
        }
        .modal-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .modal-actions a {
            display: inline-block;
            font: inherit;
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            color: #fff;
            background: #0f766e;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .modal-actions a:hover {
            background: #0d6461;
        }
        #dlLink {
            display: none;
        }
    </style>
</head>
<body>
<main class="container">
    <section class="card">
        <h2 class="title">CSVダウンロード</h2>
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

<!-- Modal -->
<div id="modalBackdrop" class="modal-backdrop" role="dialog" aria-modal="true">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-icon">✅</span>
            <h3 class="modal-title">CSV 準備完了</h3>
        </div>
        <p id="modalMessage" class="modal-message"></p>
        <div class="modal-actions">
            <button id="csvDlBtn" type="button">CSV をダウンロードする</button>
            <a id="dlLink" href="#">ダウンロード開始</a>
            <button id="modalCloseBtn" type="button" class="secondary">閉じる</button>
        </div>
    </div>
</div>

<script>
    const logsEl        = document.getElementById('logs');
    const generateBtn   = document.getElementById('generateBtn');
    const generateStatus = document.getElementById('generateStatus');
    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalMessage  = document.getElementById('modalMessage');
    const csvDlBtn      = document.getElementById('csvDlBtn');
    const dlLink        = document.getElementById('dlLink');
    const modalCloseBtn = document.getElementById('modalCloseBtn');

    let pendingPath = null;

    function logLine(label, payload) {
        const time = new Date().toISOString();
        const line = `[${time}] ${label} ${typeof payload === 'string' ? payload : JSON.stringify(payload)}`;
        logsEl.textContent = `${line}\n${logsEl.textContent}`;
    }

    function openModal(message) {
        modalMessage.textContent = message;
        csvDlBtn.disabled = false;
        csvDlBtn.textContent = 'CSV をダウンロードする';
        dlLink.style.display = 'none';
        modalBackdrop.classList.add('open');
    }

    function closeModal() {
        modalBackdrop.classList.remove('open');
        pendingPath = null;
    }

    // ── Step 1: CSV 生成リクエスト ──────────────────────────
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
            generateStatus.textContent = '生成完了 — モーダルを確認してください';
            logLine('publish-ok', body);
            pendingPath = body.path;
            openModal(body.message ?? 'CSVの準備が整いました');
        }

        generateBtn.disabled = false;
    });

    // ── Step 2: パスをバックエンドに送信して検証 ────────────
    csvDlBtn.addEventListener('click', async () => {
        if (!pendingPath) return;

        csvDlBtn.disabled = true;
        csvDlBtn.textContent = '確認中…';
        logLine('validate-request', 'POST /api/csv/download');

        const response = await fetch('/api/csv/download', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ path: pendingPath }),
        });

        const body = await response.json().catch(() => ({}));

        if (!response.ok) {
            csvDlBtn.disabled = false;
            csvDlBtn.textContent = 'CSV をダウンロードする';
            logLine('validate-error', body);
            modalMessage.textContent = 'パスの検証に失敗しました。';
        } else {
            logLine('validate-ok', body);
            // ── Step 3: DL ボタンを表示 ──────────────────────
            const url = `/api/csv/download?path=${encodeURIComponent(pendingPath)}`;
            dlLink.href = url;
            dlLink.download = pendingPath.split('/').pop();
            dlLink.style.display = 'inline-block';
            csvDlBtn.style.display = 'none';
        }
    });

    // モーダルを閉じる
    modalCloseBtn.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', (e) => {
        if (e.target === modalBackdrop) closeModal();
    });
</script>
</body>
</html>
