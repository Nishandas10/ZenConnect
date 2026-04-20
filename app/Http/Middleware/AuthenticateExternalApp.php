<?php

namespace App\Http\Middleware;

use App\Models\ExternalApp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateExternalApp
{
    /**
     * Handle an incoming request.
     *
     * External apps authenticate using X-Api-Key and X-Api-Secret headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');
        $apiSecret = $request->header('X-Api-Secret');

        if (!$apiKey || !$apiSecret) {
            return response()->json([
                'error' => 'Missing API credentials',
                'message' => 'X-Api-Key and X-Api-Secret headers are required',
            ], 401);
        }

        $app = ExternalApp::where('api_key', $apiKey)
            ->where('is_active', true)
            ->first();

        if (!$app) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or has been deactivated',
            ], 401);
        }

        if (!$app->verifySecret($apiSecret)) {
            return response()->json([
                'error' => 'Invalid API secret',
                'message' => 'The provided API secret does not match',
            ], 401);
        }

        // Check allowed origins if configured
        $origin = $request->header('Origin');
        if ($app->allowed_origins && !empty($app->allowed_origins)) {
            if ($origin && !in_array($origin, $app->allowed_origins)) {
                return response()->json([
                    'error' => 'Origin not allowed',
                    'message' => 'This origin is not authorized for this API key',
                ], 403);
            }
        }

        // Attach the external app to the request
        $request->merge(['external_app' => $app]);
        $request->setUserResolver(function () use ($app) {
            return $app;
        });

        return $next($request);
    }
}
