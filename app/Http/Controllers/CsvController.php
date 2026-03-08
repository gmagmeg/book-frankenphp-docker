<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        sleep(2);

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

    /**
     * パスを受け取り、許可ディレクトリ内であることを検証してOKを返す。
     */
    public function validateDownload(Request $request): JsonResponse
    {
        $path = (string) $request->input('path', '');
        $this->resolveAllowedPath($path);

        return response()->json(['ok' => true]);
    }

    /**
     * パスを受け取り、許可ディレクトリ内であることを検証してCSVファイルを返す。
     */
    public function downloadFile(Request $request): BinaryFileResponse
    {
        $path = (string) $request->query('path', '');
        $realPath = $this->resolveAllowedPath($path);

        return response()->download($realPath);
    }

    /**
     * パスがストレージの csv_files ディレクトリ内かを検証し、実パスを返す。
     * 不正なパスの場合は 403 / 404 を返す。
     */
    private function resolveAllowedPath(string $path): string
    {
        $allowedDir = realpath(storage_path('app/csv_files'));
        $realPath   = realpath($path);

        if (! $realPath || ! $allowedDir || ! str_starts_with($realPath, $allowedDir . DIRECTORY_SEPARATOR)) {
            abort(403, '無効なパスです');
        }

        if (! file_exists($realPath)) {
            abort(404, 'ファイルが見つかりません');
        }

        return $realPath;
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
