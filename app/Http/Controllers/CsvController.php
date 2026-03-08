<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CsvController extends Controller
{
    public function page(): View
    {
        return view('csv.index');
    }

    /**
     * 5秒待機してからCSVを生成し、Mercure経由でSSE通知を送信する。
     */
    public function generate(Request $request): JsonResponse
    {
        sleep(5);

        $jobId = uniqid('csv_', more_entropy: true);
        $fileName = "{$jobId}.csv";

        $csvDir = storage_path('app/csv_files');
        if (! is_dir($csvDir)) {
            mkdir($csvDir, 0755, true);
        }

        $this->writeCsv("{$csvDir}/{$fileName}");

        // Mercureハブ経由でSSE通知を送信する
        $topic = rtrim((string) config('app.url'), '/') . '/csv/completed';
        $payload = json_encode([
            'file_name' => $fileName,
            'message' => 'CSVの生成が完了しました。',
            'completed_at' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE);

        mercure_publish($topic, $payload, false, '', 'csv-completed', -1);

        $result = mercure_publish(
            $topic,
            $payload,
            (bool) ($options['private'] ?? false),
            (string) ($options['id'] ?? ''),
            (string) ($options['type'] ?? ''),
            (int) ($options['retry'] ?? -1),
        );


        return response()->json([
            'file_name' => $fileName,
            'message' => 'CSVの生成が完了しました。',
        ]);
    }

    private function writeCsv(string $path): void
    {
        $fp = fopen($path, 'w');

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
    }
}
