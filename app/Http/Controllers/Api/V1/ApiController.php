<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponder;

class ApiController extends Controller
{
    use ApiResponder;

    protected function perPage(int $default = 20): int
    {
        $requested = (int) request()->integer('per_page', $default);

        return max(1, min(100, $requested));
    }
}
