<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use App\Models\User;

class TestMenusCommand extends Command
{
    protected $signature = 'test:menus';
    protected $description = 'Test all GET routes (menus) 2 times and report';

    public function handle()
    {
        $this->info("Starting route tests...");
        $routes = Route::getRoutes()->getRoutesByMethod()['GET'] ?? [];
        
        // Find an admin user to test with, or first user
        $user = User::whereHas('roles', function($q) { $q->where('name', 'admin'); })->first();
        if (!$user) {
            $user = User::first();
        }

        if (!$user) {
            $this->error("No user found to test with.");
            return;
        }

        $this->info("Testing with user: {$user->email}");

        $results = [];

        foreach ($routes as $route) {
            // Only test web routes, skip api and internal horizon/telescope etc
            if (!in_array('web', $route->middleware()) && !in_array('auth', $route->middleware())) {
                continue;
            }
            // skip dynamic parameter routes like {id} or {user}
            if (strpos($route->uri(), '{') !== false) {
                continue;
            }

            // Skip logout
            if (str_contains($route->uri(), 'logout')) {
                continue;
            }

            $uri = $route->uri();
            
            // Test 2 times
            for ($i = 1; $i <= 2; $i++) {
                $response = $this->actingAs($user)->get($uri);
                $status = $response->getStatusCode();
                
                $results[] = [
                    'round' => $i,
                    'uri' => $uri,
                    'status' => $status,
                    'name' => $route->getName(),
                ];
                
                $this->info("Round {$i} - GET /{$uri} - Status: {$status}");
            }
        }

        $this->info("Completed testing.");
        
        // Print summary
        $this->table(['Round', 'URI', 'Name', 'Status'], $results);
    }

    // Helper to simulate request
    protected function actingAs($user)
    {
        auth()->login($user);
        return $this;
    }

    protected function get($uri)
    {
        $app = app();
        $request = \Illuminate\Http\Request::create($uri, 'GET');
        return $app->handle($request);
    }
}
