<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySiapWebhookToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('services.siap.token');
        $providedToken = (string) $request->bearerToken();

        if ($expectedToken === '' || $providedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'message' => 'Unauthorized webhook request.',
            ], 401);
        }

        return $next($request);
    }
}
