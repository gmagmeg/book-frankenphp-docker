<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSVダウンロード デモ</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 0 20px; }
        button { padding: 10px 24px; font-size: 1rem; cursor: pointer; }
        #status { margin-top: 20px; padding: 16px; background: #f4f4f4; border-radius: 6px; white-space: pre-wrap; font-family: monospace; display: none; }
        .pending    { color: #888; }
        .processing { color: #e6820e; }
        .completed  { color: #2a9d2a; }
    </style>
</head>
<body>
    <h1>CSV生成デモ</h1>
    <p>ボタンを押すと、バックグラウンドでCSV生成が開始されます（5秒後に開始）。</p>

    <button id="generateBtn" onclick="requestGenerate()">CSV生成をリクエスト</button>

    <div id="status"></div>

    <script>
        let pollTimer = null;

        async function requestGenerate() {
            document.getElementById('generateBtn').disabled = true;
            showStatus('pending', 'リクエスト送信中...');

            const res = await fetch('/api/csv/generate', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
            const data = await res.json();

            showStatus('pending', JSON.stringify(data, null, 2));
            pollStatus(data.job_id);
        }

        function pollStatus(jobId) {
            pollTimer = setInterval(async () => {
                const res = await fetch(`/api/csv/status/${jobId}`);
                const job = await res.json();

                showStatus(job.status, JSON.stringify(job, null, 2));

                if (job.status === 'completed' || job.status === 'failed') {
                    clearInterval(pollTimer);
                    document.getElementById('generateBtn').disabled = false;
                }
            }, 1000);
        }

        function showStatus(status, text) {
            const el = document.getElementById('status');
            el.style.display = 'block';
            el.className = status;
            el.textContent = text;
        }
    </script>
</body>
</html>
