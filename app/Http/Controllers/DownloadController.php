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
     *
     * 仕組み:
     *  1. AppServiceProvider で BinaryFileResponse::trustXSendfileTypeHeader() を呼び出し済み
     *  2. FrankenPHP はリクエストに X-Sendfile-Type: X-Sendfile ヘッダーを付与する
     *  3. BinaryFileResponse::prepare() がそのヘッダーを検知し、
     *     レスポンスに X-Sendfile: <絶対パス> を付与してボディを空にする
     *  4. FrankenPHP が X-Sendfile ヘッダーを読み取り、ファイルを直接配信する
     */
    public function download(Request $request, string $filename): BinaryFileResponse
    {
        // アクセス権チェック（例: 認証済みユーザーのみ許可）
        // abort_unless($request->user(), 403, 'ログインが必要です。');

        // ファイル名にパストラバーサルが含まれていないか確認する
        abort_if(str_contains($filename, '..'), 400, '不正なファイルパスです。');

        $path = base_path('private-files/' . $filename);

        abort_unless(file_exists($path), 404, 'ファイルが見つかりません。');

        // Octane worker モードでは FrankenPHP がリクエストヘッダーに
        // X-Sendfile-Type を付与しない場合がある。明示的に設定することで
        // BinaryFileResponse::prepare() が確実に X-Sendfile を有効にする。
        // if (! $request->headers->has('X-Sendfile-Type')) {
        //     $request->headers->set('X-Sendfile-Type', 'X-Sendfile');
        // }

        return new BinaryFileResponse($path);
    }
}
