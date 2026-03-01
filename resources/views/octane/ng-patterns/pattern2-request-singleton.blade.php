<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGパターン2: Request情報をSingletonに保持</title>
    <style>
        body {
            margin: 0;
            font-family: sans-serif;
            line-height: 1.6;
            background: #f6f8fb;
            color: #1c2333;
        }
        .container {
            max-width: 900px;
            margin: 32px auto;
            padding: 0 16px;
        }
        .card {
            background: #fff;
            border: 1px solid #d8dfeb;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 16px;
        }
        h1, h2 {
            margin-top: 0;
        }
        code {
            background: #f1f4f8;
            padding: 2px 5px;
            border-radius: 4px;
        }
        input {
            padding: 9px;
            border: 1px solid #c7d0df;
            border-radius: 6px;
            width: 280px;
            max-width: 100%;
        }
        button {
            padding: 9px 14px;
            border: 0;
            border-radius: 6px;
            background: #0b62d6;
            color: #fff;
            cursor: pointer;
            margin-left: 8px;
        }
        button:hover {
            background: #084da9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            border: 1px solid #d8dfeb;
            padding: 8px;
            text-align: left;
        }
        .bad {
            color: #b00020;
            font-weight: 700;
        }
        .ok {
            color: #0f7b3f;
            font-weight: 700;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>NGパターン2: Request情報をSingletonに保持</h1>
        <p>以下の実装は Octane では NG です。worker が生存する間、最初に解決された値を引きずる可能性があります。</p>
        <pre><code>$this->app->singleton(ReportContext::class, function () {
    return new ReportContext(request()->header('X-Request-Id'));
});</code></pre>
        <p>このページでは <code>X-Request-Id</code> を変えて連続実行し、<code>singleton_request_id</code> が更新されないケースを可視化します。</p>
    </div>

    <div class="card">
        <h2>試す</h2>
        <label for="request-id-input">X-Request-Id</label><br>
        <input id="request-id-input" type="text" value="req-001">
        <button id="send-btn">送信</button>
        <button id="random-btn">ランダムで送信</button>
        <p style="margin-top: 12px;">
            判定: <span id="status-label">未実行</span>
        </p>
    </div>

    <div class="card">
        <h2>実行結果</h2>
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>pid</th>
                <th>header_x_request_id</th>
                <th>singleton_request_id</th>
                <th>leaked</th>
            </tr>
            </thead>
            <tbody id="result-body"></tbody>
        </table>
    </div>
</div>

<script>
    const sendBtn = document.getElementById('send-btn');
    const randomBtn = document.getElementById('random-btn');
    const input = document.getElementById('request-id-input');
    const statusLabel = document.getElementById('status-label');
    const resultBody = document.getElementById('result-body');
    let counter = 0;

    async function run(requestId) {
        const res = await fetch('/debug/octane/ng-patterns/2/request-singleton/check', {
            headers: {
                'X-Request-Id': requestId
            }
        });

        const json = await res.json();
        counter += 1;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${counter}</td>
            <td>${json.pid}</td>
            <td>${json.header_x_request_id ?? ''}</td>
            <td>${json.singleton_request_id ?? ''}</td>
            <td>${json.leaked ? 'true' : 'false'}</td>
        `;
        resultBody.prepend(tr);

        if (json.leaked) {
            statusLabel.textContent = 'NG再現: リクエスト値とsingleton値が不一致';
            statusLabel.className = 'bad';
        } else {
            statusLabel.textContent = '一致: まだ漏れは見えていません';
            statusLabel.className = 'ok';
        }
    }

    sendBtn.addEventListener('click', () => run(input.value));
    randomBtn.addEventListener('click', () => {
        const value = 'req-' + Math.random().toString(36).slice(2, 8);
        input.value = value;
        run(value);
    });
</script>
</body>
</html>
