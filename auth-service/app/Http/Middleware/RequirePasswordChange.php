<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->is_password_changed === false) {
            return response()->json([
                'message' => 'Password change required.',
                'code' => 'PASSWORD_CHANGE_REQUIRED'
            ], 403);
        }

        return $next($request);
    }
}
