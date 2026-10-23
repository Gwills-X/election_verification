<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $u = $request->user();
        abort_unless($u && $u->is_active && in_array($u->role, $roles, true), 403, 'You do not have permission to access this area.');
        return $next($request);
    }
}
