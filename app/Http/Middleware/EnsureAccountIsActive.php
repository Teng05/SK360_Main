<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request,Closure $next): Response
    {
        if(!Auth::check()){
            return $next($request);
        }

        $user=Auth::user()->fresh();

        if(
            !$user ||
            $user->status !== 'active' ||
            (int)$user->is_verified !== 1 ||
            $user->archived_at !== null
        ){
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email'=>'Your account is no longer active. Please contact the administrator.',
                ]);
        }

        Auth::setUser($user);

        return $next($request);
    }
}