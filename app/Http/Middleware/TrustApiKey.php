<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiKey;
class TrustApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $api_key = $request->bearerToken();
        $check = ApiKey::where('api_key', $api_key)->first();
        if ($check) {
            return $next($request);
        }
        return response([
            'message' => 'Unauthenticated'
        ], 403);
    }
}
