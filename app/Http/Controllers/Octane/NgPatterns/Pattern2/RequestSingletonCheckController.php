<?php

namespace App\Http\Controllers\Octane\NgPatterns\Pattern2;

use App\Http\Controllers\Controller;
use App\Octane\NgPatterns\Pattern2\ReportContext;
use Illuminate\Http\Request;

final class RequestSingletonCheckController extends Controller
{
    public function __invoke(Request $request, ReportContext $context)
    {
        $headerRequestId = $request->header('X-Request-Id');
        $singletonRequestId = $context->requestId;

        return response()->json([
            'pid' => getmypid(),
            'header_x_request_id' => $headerRequestId,
            'singleton_request_id' => $singletonRequestId,
            'leaked' => $headerRequestId !== $singletonRequestId,
        ]);
    }
}
