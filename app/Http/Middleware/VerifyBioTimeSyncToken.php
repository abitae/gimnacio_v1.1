<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\BioTime\BioTimeSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBioTimeSyncToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = BioTimeSetting::activeSecret();
        $provided = $request->bearerToken() ?: $request->header('X-BioTime-Secret');

        if (! filled($expected) || ! hash_equals((string) $expected, (string) $provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
