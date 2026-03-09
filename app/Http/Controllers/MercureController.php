<?php

namespace App\Http\Controllers;

use App\Services\CsvGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MercureController extends Controller
{
    public function __construct(private readonly CsvGeneratorService $csvGenerator) {}
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
     * CSV ダウンロード受信画面を表示する。
     *
     * @return View
     */
    public function csvDownload(): View
    {
        $topic = rtrim((string) config('app.url'), '/').'/mercure/demo/topic';

        return view('mercure.csv-download', [
            'topic' => $topic,
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
        $topic = rtrim((string) config('app.url'), '/').'/mercure/demo/topic';

        $csvFilePath = $this->csvGenerator->generate();
        $payload = json_encode([
            'path'    => $csvFilePath,
            'message' => 'CSVの準備が整いました',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        // Mercureへメッセージを送信する。
        mercure_publish($topic, $payload);

        // クライアント向けに、トピックと完了メッセージをJSONで返却する。
        return response()->json([
            'topic'   => $topic,
            'message' => '生成完了 — CSV ダウンロードページに通知が届きます',
        ]);
    }
}
