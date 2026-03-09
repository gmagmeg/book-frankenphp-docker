<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    /**
     * プライベートファイルをX-Sendfile経由で配信する。
     *
     * PHPでアクセス権を検証した後、実際のファイル送信は
     * FrankenPHP（Caddy）に委譲するため、PHPのメモリを消費しない。
     */
    public function download(Request $request, string $filename): BinaryFileResponse
    {
        // アクセス権チェック（例: 認証済みユーザーのみ許可）
        // abort_unless($request->user(), 403, 'ログインが必要です。');

        // ファイル名にパストラバーサルが含まれていないか確認する
        abort_if(str_contains($filename, '..'), 400, '不正なファイルパスです。');

        $path = base_path('private-files/' . $filename);

        abort_unless(file_exists($path), 404, 'ファイルが見つかりません。');

        // X-Sendfile-Type ヘッダーを信頼し、FrankenPHP に配信を委譲する
        BinaryFileResponse::trustXSendfileTypeHeader();

        return new BinaryFileResponse($path);
    }
}
