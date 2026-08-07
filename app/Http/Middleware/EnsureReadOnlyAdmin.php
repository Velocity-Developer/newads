<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReadOnlyAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role === UserRole::ReadOnlyAdmin && ! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(403, 'Read-only admin tidak diizinkan melakukan aksi ini.');
        }

        return $next($request);
    }
}
