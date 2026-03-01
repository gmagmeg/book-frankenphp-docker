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
        input, textarea, button {
            font: inherit;
        }
        input, textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
        }
        button {
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
        <h1 class="title">FrankenPHP Mercure SSE Demo</h1>
        <p class="status">Hub URL: <code id="hubUrl">/.well-known/mercure</code></p>
        <div class="row">
            <label for="topic">Topic</label>
            <input id="topic" type="text" value="{{ $defaultTopic }}">
        </div>
        <div class="buttons">
            <button id="connectBtn" type="button">Connect SSE</button>
            <button id="disconnectBtn" class="secondary" type="button">Disconnect</button>
        </div>
        <p id="connectionStatus" class="status">Not connected</p>
    </section>

    <section class="card">
        <h2 class="title">Publish API</h2>
        <form id="publishForm">
            <div class="row">
                <label for="message">Message</label>
                <textarea id="message" rows="4" placeholder="Hello Mercure!"></textarea>
            </div>
            <div class="buttons">
                <button type="submit">POST /api/mercure/publish</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2 class="title">Logs</h2>
        <pre id="logs"></pre>
    </section>
</main>

<script>
    const topicInput = document.getElementById('topic');
    const messageInput = document.getElementById('message');
    const connectBtn = document.getElementById('connectBtn');
    const disconnectBtn = document.getElementById('disconnectBtn');
    const publishForm = document.getElementById('publishForm');
    const logsEl = document.getElementById('logs');
    const connectionStatus = document.getElementById('connectionStatus');

    let eventSource = null;

    function logLine(label, payload) {
        const time = new Date().toISOString();
        const line = `[${time}] ${label} ${typeof payload === 'string' ? payload : JSON.stringify(payload)}`;
        logsEl.textContent = `${line}\n${logsEl.textContent}`;
    }

    function connect() {
        const topic = topicInput.value.trim();
        if (!topic) {
            logLine('error', 'topic is required');
            return;
        }

        if (eventSource) {
            eventSource.close();
        }

        const hubUrl = `/.well-known/mercure?topic=${encodeURIComponent(topic)}`;
        eventSource = new EventSource(hubUrl);
        connectionStatus.textContent = `Connecting: ${hubUrl}`;

        eventSource.onopen = () => {
            connectionStatus.textContent = `Connected: ${hubUrl}`;
            logLine('sse-open', hubUrl);
        };

        eventSource.onmessage = (event) => {
            let parsed = event.data;
            try {
                parsed = JSON.parse(event.data);
            } catch (e) {
                // Keep raw text if data is not JSON.
            }
            logLine('sse-message', parsed);
        };

        eventSource.onerror = () => {
            connectionStatus.textContent = 'Connection error (Mercure hub may reconnect automatically)';
            logLine('sse-error', 'connection error');
        };
    }

    function disconnect() {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
            connectionStatus.textContent = 'Disconnected';
            logLine('sse-close', 'closed by client');
        }
    }

    connectBtn.addEventListener('click', connect);
    disconnectBtn.addEventListener('click', disconnect);

    publishForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const topic = topicInput.value.trim();
        const message = messageInput.value;

        const response = await fetch('/api/mercure/publish', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ topic, message }),
        });

        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            logLine('publish-error', body);
            return;
        }
        logLine('publish-ok', body);
    });
</script>
</body>
</html>
