<?php

namespace App\Octane\NgPatterns\Pattern2;

final class ReportContext
{
    public function __construct(
        public readonly ?string $requestId
    ) {
    }
}
