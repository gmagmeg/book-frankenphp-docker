<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mercure Receiver</title>
    <link rel="stylesheet" href="/css/mercure-receiver.css">
</head>
<body>
<main class="container">
    <section class="card">
        <h1 class="title">Mercure 受信画面</h1>
        <p class="description">`/api/mercure/publish` で送信されたメッセージをリアルタイムで受け取ります。</p>
        <p class="description">送信テストは <a href="/mercure/sse-demo">SSE Demo 画面</a> から実行できます。</p>
        <p id="connectionStatus" class="status">未接続</p>

        <div class="row">
            <label for="topic">Topic</label>
            <input id="topic" type="text" value="{{ $defaultTopic }}">
        </div>

        <div class="buttons">
            <button id="connectBtn" type="button">受信開始</button>
            <button id="disconnectBtn" class="secondary" type="button">切断</button>
            <button id="clearBtn" class="ghost" type="button">表示クリア</button>
        </div>
    </section>

    <section class="card">
        <h2 class="title">受信メッセージ</h2>
        <p id="emptyState" class="empty">まだメッセージはありません。</p>
        <ul id="messages" class="messages"></ul>
    </section>
</main>

<script>
    const topicInput = document.getElementById('topic');
    const connectBtn = document.getElementById('connectBtn');
    const disconnectBtn = document.getElementById('disconnectBtn');
    const clearBtn = document.getElementById('clearBtn');
    const connectionStatus = document.getElementById('connectionStatus');
    const messagesEl = document.getElementById('messages');
    const emptyState = document.getElementById('emptyState');

    const maxItems = 200;
    let eventSource = null;

    /** メッセージリストが空のときに空状態テキストを表示し、要素がある場合は非表示にする */
    const updateEmptyState = () => {
        emptyState.style.display = messagesEl.children.length === 0 ? 'block' : 'none';
    };

    /**
     * 受信データを整形して返す。JSON 文字列であればインデント付きで整形し、
     * パースできない場合はそのまま返す。
     * @param {string} raw - SSE の data フィールド文字列
     * @returns {string} 整形済み文字列
     */
    const toPrettyPayload = (raw) => {
        try {
            return JSON.stringify(JSON.parse(raw), null, 2);
        } catch (e) {
            return raw;
        }
    };

    /**
     * 受信した SSE イベントをリストの先頭に追加する。
     * 最大件数（maxItems）を超えた古いメッセージは末尾から削除する。
     * @param {MessageEvent} event - EventSource から受信したイベント
     */
    const addMessage = (event) => {
        const item = document.createElement('li');
        item.className = 'message';

        const meta = document.createElement('div');
        meta.className = 'meta';
        meta.textContent = `[${new Date().toISOString()}] type=${event.type} id=${event.lastEventId || '-'}`;

        const payload = document.createElement('pre');
        payload.className = 'payload';
        payload.textContent = toPrettyPayload(event.data);

        item.appendChild(meta);
        item.appendChild(payload);
        messagesEl.prepend(item);

        while (messagesEl.children.length > maxItems) {
            messagesEl.removeChild(messagesEl.lastChild);
        }

        updateEmptyState();
    };

    /** EventSource を閉じて接続を切断し、接続状態テキストを更新する */
    const disconnect = () => {
        if (!eventSource) {
            return;
        }

        eventSource.close();
        eventSource = null;
        connectionStatus.textContent = '切断しました';
    };

    /**
     * 入力された Topic で Mercure Hub へ SSE 接続を開始する。
     * 既存の接続がある場合は先に切断してから再接続する。
     */
    const connect = () => {
        const topic = topicInput.value.trim();
        if (!topic) {
            connectionStatus.textContent = 'topic を入力してください';
            return;
        }

        disconnect();

        const hubUrl = `/.well-known/mercure?topic=${encodeURIComponent(topic)}`;
        eventSource = new EventSource(hubUrl);
        connectionStatus.textContent = `接続中: ${hubUrl}`;

        eventSource.onopen = () => {
            connectionStatus.textContent = `接続中: ${hubUrl}`;
        };

        eventSource.onmessage = (event) => {
            addMessage(event);
        };

        eventSource.onerror = () => {
            connectionStatus.textContent = '接続エラー (Mercureが自動再接続する場合があります)';
        };
    };

    connectBtn.addEventListener('click', connect);
    disconnectBtn.addEventListener('click', disconnect);
    clearBtn.addEventListener('click', () => {
        messagesEl.innerHTML = '';
        updateEmptyState();
    });

    window.addEventListener('beforeunload', disconnect);

    connect();
</script>
</body>
</html>
