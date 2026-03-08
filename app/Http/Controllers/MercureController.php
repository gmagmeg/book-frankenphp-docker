<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MercureController extends Controller
{
    /**
     * Mercureデモ画面を表示する。
     *
     * @param Request $request
     * @return View
     */
    public function page(Request $request): View
    {
        $defaultTopic = $request->query(
            'topic',
            rtrim((string) config('app.url'), '/').'/mercure/demo/topic'
        );

        // FrankenPHP の HTTP 103 Early Hints でMercureハブへの接続を事前通知する。
        header('Link: </.well-known/mercure>; rel=preconnect');
        headers_send(103);
        
        return view('mercure.sse-demo', [
            'defaultTopic' => $defaultTopic,
        ]);
    }

    /**
     * publishされたメッセージを受信する画面を表示する。
     *
     * @param Request $request
     * @return View
     */
    public function receiver(Request $request): View
    {
        $defaultTopic = $request->query(
            'topic',
            rtrim((string) config('app.url'), '/').'/mercure/demo/topic'
        );

        return view('mercure.receiver', [
            'defaultTopic' => $defaultTopic,
        ]);
    }

    /**
     * Mercureにメッセージを公開し、送信結果をJSONで返す。
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publish(Request $request): JsonResponse
    {

        $csvFilePath = $this->generateCsv();
        // リクエストの検証と、publish実行に必要な値(topic/payload/options)を組み立てる。
        ['payload' => $payload] = $this->preparePublishRequest($csvFilePath);

        // Mercureへメッセージを送信する。
        $result = mercure_publish(
            $request['topic'] ?? 'message',
            $payload,
            (bool) ($options['private'] ?? false),
            (string) ($options['id'] ?? ''),
            (string) ($options['type'] ?? ''),
            (int) ($options['retry'] ?? -1),
        );

        // クライアント向けに、送信内容と結果をJSONで返却する。
        return response()->json([
            'ok' => true,
            'payload' => $payload,
            'result' => $result,
        ]);
    }


    private function preparePublishRequest(string $fileName): array
    {
        $payload = [
            'message' => $fileName,
            'published_at' => now()->toIso8601String(),
        ];
        $payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return [
            'payload' => $payload,
        ];
    }

    private function generateCsv(): string
    {
        sleep(1);
        $jobId = uniqid('csv_', more_entropy: true);
        $fileName = "{$jobId}.csv";
        $csvDir = storage_path('app/csv_files');
        if (! is_dir($csvDir)) {
            mkdir($csvDir, 0755, true);
        }
        $fileName = "{$csvDir}/{$fileName}";
        $fp = fopen($fileName, 'w');
        fputcsv($fp, ['id', 'name', 'email', 'created_at']);

        for ($i = 1; $i <= 100; $i++) {
            fputcsv($fp, [
                $i,
                "ユーザー {$i}",
                "user{$i}@example.com",
                now()->subDays(rand(0, 365))->toDateString(),
            ]);
        }

        fclose($fp);

        return $fileName;
    }
}
