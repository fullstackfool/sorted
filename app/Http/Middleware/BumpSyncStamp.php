<?php

namespace App\Http\Middleware;

use App\Support\SyncStamp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BumpSyncStamp
{
    /**
     * After any request that can change data, tell the other open pages to reload.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodSafe()) {
            SyncStamp::bump();
        }

        return $response;
    }
}
