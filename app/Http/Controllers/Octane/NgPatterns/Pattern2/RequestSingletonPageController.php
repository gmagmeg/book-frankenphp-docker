<?php

namespace App\Http\Controllers\Octane\NgPatterns\Pattern2;

use App\Http\Controllers\Controller;

final class RequestSingletonPageController extends Controller
{
    public function __invoke()
    {
        return view('octane.ng-patterns.pattern2-request-singleton');
    }
}
