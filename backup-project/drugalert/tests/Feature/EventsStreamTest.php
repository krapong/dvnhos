<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class EventsStreamTest extends TestCase
{
    public function test_events_endpoint_returns_sse_content_type(): void
    {
        $user = new User();
        $user->id = 'user_test_super_admin';
        $user->name = 'Test Admin';
        $user->email = 'admin@example.com';
        $user->role = 'super_admin';

        $this->actingAs($user)
            ->get('/api/events')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
    }
}
