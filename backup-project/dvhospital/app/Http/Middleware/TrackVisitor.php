<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $date = now()->toDateString();

        if ($ip && \Illuminate\Support\Facades\Schema::hasTable('visitor_logs')) {
            try {
                \App\Models\VisitorLog::firstOrCreate([
                    'ip_address' => $ip,
                    'visited_date' => $date
                ]);
            } catch (\Exception $e) {
                // Ignore if migration hasn't run yet
            }
        }

        return $next($request);
    }
}
