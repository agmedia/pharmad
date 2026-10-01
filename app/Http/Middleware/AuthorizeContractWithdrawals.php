<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizeContractWithdrawals
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user && ! $user->isA('customer')
            && ($user->isA('superadmin', 'admin') || $user->can('manage-contract-withdrawals')), 403);

        return $next($request);
    }
}
