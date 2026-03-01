<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MercureController extends Controller
{
    public function page(Request $request): View
    {
        $defaultTopic = $request->query('topic', rtrim((string) config('app.url'), '/').'/mercure/demo/topic');

        return view('mercure.sse-demo', [
            'defaultTopic' => $defaultTopic,
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        if (! function_exists('mercure_publish')) {
            return response()->json([
                'ok' => false,
                'message' => 'mercure_publish() is not available. Confirm FrankenPHP Mercure is enabled and OCTANE_MERCURE_* env vars are set.',
            ], 500);
        }

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

        $result = mercure_publish($validated['topic'], $payload, $options);

        return response()->json([
            'ok' => true,
            'topic' => $validated['topic'],
            'payload' => $payload,
            'options' => $options,
            'result' => $result,
        ]);
    }
}
