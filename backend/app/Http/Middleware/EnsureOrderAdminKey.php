<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrderAdminKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = trim((string) config('services.paypal.admin_key'));

        if ($configuredKey === '') {
            abort(503, 'ORDER_ADMIN_KEY is not configured.');
        }

        $providedKey = trim((string) ($request->query('key') ?: $request->header('X-Order-Admin-Key', '')));

        if ($providedKey === '' || !hash_equals($configuredKey, $providedKey)) {
            abort(403, 'Invalid order admin key.');
        }

        return $next($request);
    }
}
