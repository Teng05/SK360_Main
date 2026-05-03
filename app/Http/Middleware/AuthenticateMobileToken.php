<?php

namespace App\Http\Middleware;

use App\Models\MobileApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json(['message' => 'Missing bearer token.'], 401);
        }

        $token = MobileApiToken::with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $token || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        if (! $token->user || $token->user->status !== 'active' || ! $token->user->is_verified) {
            return response()->json(['message' => 'Account is not active.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();

        Auth::setUser($token->user);
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('mobile_api_token', $token);

        return $next($request);
    }
}
