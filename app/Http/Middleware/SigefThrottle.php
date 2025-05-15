<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class SigefThrottle
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('throttle.sigef.enabled', true)) {
            return $next($request);
        }

        $key = 'sigef_throttle_' . auth()->id();
        $maxAttempts = config('throttle.sigef.max_requests', 60);
        $decaySeconds = config('throttle.sigef.decay_seconds', 60);

        if (!RateLimiter::attempt($key, $maxAttempts, function() {}, $decaySeconds)) {
            Log::warning('Throttle excedido para SIGEF', [
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'path' => $request->path()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Limite de requisições excedido. Tente novamente em alguns instantes.'
            ], 429);
        }

        return $next($request);
    }
} 