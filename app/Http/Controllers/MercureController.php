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
        // Mercure拡張が有効かを先に確認し、未有効なら 500 を返す。
        if (! function_exists('mercure_publish')) {
            return response()->json([
                'ok' => false,
                'message' => 'mercure_publish() is not available. Confirm FrankenPHP Mercure is enabled and OCTANE_MERCURE_* env vars are set.',
            ], 500);
        }

        // リクエストの検証と、publish実行に必要な値(topic/payload/options)を組み立てる。
        ['topic' => $topic, 'payload' => $payload, 'options' => $options] = $this->preparePublishRequest($request);

        // Mercureへメッセージを送信する。
        $result = mercure_publish(
            $topic,
            $payload,
            (bool) ($options['private'] ?? false),
            (string) ($options['id'] ?? ''),
            (string) ($options['type'] ?? ''),
            (int) ($options['retry'] ?? -1),
        );

        // クライアント向けに、送信内容と結果をJSONで返却する。
        return response()->json([
            'ok' => true,
            'topic' => $topic,
            'payload' => $payload,
            'options' => $options,
            'result' => $result,
        ]);
    }

    /**
     * publish用リクエストを検証し、Mercure送信用データへ整形する。
     *
     * @param Request $request
     * @return array{topic:string,payload:string,options:array<string,mixed>}
     *
     * @throws \JsonException
     */
    private function preparePublishRequest(Request $request): array
    {
        $validated = $request->validate([
            'topic' => ['required', 'string', 'max:2048'],
            'message' => ['nullable', 'string', 'max:5000'],
            'data' => ['nullable'],
            'type' => ['nullable', 'string', 'max:255'],
            'id' => ['nullable', 'string', 'max:255'],
            'retry' => ['nullable', 'integer', 'min:0'],
            'private' => ['nullable', 'boolean'],
        ]);

        $options = array_filter([
            'type' => $validated['type'] ?? 'message',
            'id' => $validated['id'] ?? null,
            'retry' => $validated['retry'] ?? null,
            'private' => $validated['private'] ?? null,
        ], static fn ($value) => $value !== null);

        $payload = $validated['data'] ?? [
            'message' => $validated['message'] ?? 'Hello from Mercure',
            'published_at' => now()->toIso8601String(),
        ];

        if (! is_string($payload)) {
            $payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }

        return [
            'topic' => $validated['topic'],
            'payload' => $payload,
            'options' => $options,
        ];
    }
}
