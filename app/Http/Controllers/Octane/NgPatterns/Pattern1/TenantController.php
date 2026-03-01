<?php

namespace App\Http\Controllers\Octane\NgPatterns\Pattern1;

use App\Http\Controllers\Controller;
use App\Octane\NgPatterns\Pattern1\TenantContext;
use Illuminate\Http\Request;

final class TenantController extends Controller
{
    public function __invoke(Request $request)
    {
        $before = TenantContext::$tenantId;
        $updated = $request->has('tenant_id');

        if ($updated) {
            TenantContext::$tenantId = $request->integer('tenant_id');
        }

        return response()->json([
            'pid' => getmypid(),
            'initValue' => $before,
            'requestValue' => TenantContext::$tenantId,
        ]);
    }
}
