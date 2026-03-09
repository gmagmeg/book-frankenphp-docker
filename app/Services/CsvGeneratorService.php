<?php

namespace App\Services;

class CsvGeneratorService
{
    public function generate(): string
    {
        sleep(1);
        $jobId = uniqid('csv_', more_entropy: true);
        $csvDir = base_path('private-files');
        if (! is_dir($csvDir)) {
            mkdir($csvDir, 0755, true);
        }
        $fileName = "{$csvDir}/{$jobId}.csv";
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
