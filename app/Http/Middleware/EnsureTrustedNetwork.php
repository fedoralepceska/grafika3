<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrustedNetwork
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('trusted_networks.enabled')) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $allowedNetworks = $this->allowedNetworks();

        if ($clientIp !== null && $allowedNetworks !== [] && IpUtils::checkIp($clientIp, $allowedNetworks)) {
            return $next($request);
        }

        Log::warning('Blocked request from untrusted network.', [
            'ip' => $clientIp,
            'path' => $request->path(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'forwarded_for' => $request->header('X-Forwarded-For'),
        ]);

        $message = config('trusted_networks.denied_message');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
            ], 403);
        }

        return response()->view('errors.trusted-network', [
            'message' => $message,
        ], 403);
    }

    /**
     * @return array<int, string>
     */
    private function allowedNetworks(): array
    {
        $networks = config('trusted_networks.allowed', []);

        if (is_string($networks)) {
            $networks = explode(',', $networks);
        }

        return array_values(array_filter(array_map(
            static fn ($network) => trim((string) $network),
            is_array($networks) ? $networks : []
        )));
    }
}
