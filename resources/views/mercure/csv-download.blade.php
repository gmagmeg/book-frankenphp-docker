<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CSV ダウンロード (受信ページ)</title>
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
    </style>
</head>
<body>
<main class="container">
    <section class="card">
        <h2 class="title">CSV ダウンロード (受信ページ)</h2>
        <p class="description">
            このページは <a href="/mercure/sse-demo">CSV 生成ページ</a> とは別ページです。<br>
            Mercure Hub を経由して SSE でメッセージを受信し、CSV が準備できたらモーダルを表示します。
        </p>
        <p id="connectionStatus" class="status">未接続</p>
        <div class="buttons">
            <button id="connectBtn" type="button">受信開始</button>
            <button id="disconnectBtn" class="secondary" type="button">切断</button>
        </div>
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
            <button id="modalCloseBtn" type="button" class="secondary">閉じる</button>
        </div>
    </div>
</div>

<script>
    const TOPIC = '{{ $topic }}';

    const logsEl           = document.getElementById('logs');
    const connectionStatus = document.getElementById('connectionStatus');
    const connectBtn       = document.getElementById('connectBtn');
    const disconnectBtn    = document.getElementById('disconnectBtn');
    const modalBackdrop    = document.getElementById('modalBackdrop');
    const modalMessage     = document.getElementById('modalMessage');
    const csvDlBtn         = document.getElementById('csvDlBtn');
    const modalCloseBtn    = document.getElementById('modalCloseBtn');

    let eventSource  = null;
    let pendingPath  = null;

    function logLine(label, payload) {
        const time = new Date().toISOString();
        const line = `[${time}] ${label} ${typeof payload === 'string' ? payload : JSON.stringify(payload)}`;
        logsEl.textContent = `${line}\n${logsEl.textContent}`;
    }

    function openModal(message, path) {
        pendingPath = path;
        modalMessage.textContent = message;
        csvDlBtn.disabled = false;
        modalBackdrop.classList.add('open');
    }

    function closeModal() {
        modalBackdrop.classList.remove('open');
        pendingPath = null;
    }

    // ── SSE 接続 ────────────────────────────────────────────────────────────
    function connect() {
        if (eventSource) disconnect();

        const hubUrl = `/.well-known/mercure?topic=${encodeURIComponent(TOPIC)}`;
        eventSource = new EventSource(hubUrl);
        connectionStatus.textContent = `接続中: ${hubUrl}`;
        logLine('sse-connect', hubUrl);

        eventSource.onmessage = (event) => {
            logLine('sse-message', event.data);

            let data = {};
            try { data = JSON.parse(event.data); } catch {}

            if (data.path) {
                openModal(data.message ?? 'CSVの準備が整いました', data.path);
            }
        };

        eventSource.onerror = () => {
            connectionStatus.textContent = '接続エラー (Mercureが自動再接続する場合があります)';
            logLine('sse-error', 'connection error');
        };
    }

    function disconnect() {
        if (!eventSource) return;
        eventSource.close();
        eventSource = null;
        connectionStatus.textContent = '切断しました';
        logLine('sse-disconnect', 'closed');
    }

    // ── CSV ダウンロード ─────────────────────────────────────────────────────
    csvDlBtn.addEventListener('click', () => {
        if (!pendingPath) return;
        const filename = pendingPath.split('/').pop();
        logLine('download', `GET /api/csv/download/${filename}`);
        const a = document.createElement('a');
        a.href = `/api/csv/download/${encodeURIComponent(filename)}`;
        a.download = filename;
        a.click();
        closeModal();
    });

    modalCloseBtn.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', (e) => {
        if (e.target === modalBackdrop) closeModal();
    });

    connectBtn.addEventListener('click', connect);
    disconnectBtn.addEventListener('click', disconnect);

    window.addEventListener('beforeunload', disconnect);

    // ページ読み込み時に自動接続する。
    connect();
</script>
</body>
</html>
