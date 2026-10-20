<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LocationControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->string('type')->nullable();
                $table->string('detail')->nullable();
                $table->string('details')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('actor_user_id')->nullable();
                $table->string('action');
                $table->string('entity_type');
                $table->string('entity_id')->nullable();
                $table->text('details')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function test_location_update_returns_normalized_payload(): void
    {
        $user = new User();
        $user->id = 'user_test_super_admin_location';
        $user->name = 'Test Admin';
        $user->email = 'admin-location@example.com';
        $user->role = 'super_admin';

        $location = new Location();
        $location->id = 'loc_' . uniqid();
        $location->name = 'Original Room';
        $location->detail = 'Old detail';
        $location->status = 'ใช้งาน';
        $location->save();

        $response = $this->actingAs($user)
            ->patchJson('/api/locations/' . $location->id, [
                'name' => 'Updated Room',
                'detail' => 'Updated detail',
                'status' => 'ยกเลิก',
            ]);

        $response->assertOk();
        $response->assertJsonPath('id', $location->id);
        $response->assertJsonPath('name', 'Updated Room');
        $response->assertJsonPath('detail', 'Updated detail');
        $response->assertJsonPath('details', 'Updated detail');

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'name' => 'Updated Room',
            'detail' => 'Updated detail',
            'status' => 'ยกเลิก',
        ]);
    }

    public function test_location_destroy_soft_deletes_and_hides_from_index(): void
    {
        $user = new User();
        $user->id = 'user_test_super_admin_location_delete';
        $user->name = 'Test Admin';
        $user->email = 'admin-location-delete@example.com';
        $user->role = 'super_admin';

        $location = new Location();
        $location->id = 'loc_' . uniqid();
        $location->name = 'Delete Location';
        $location->status = 'ใช้งาน';
        $location->save();

        $this->actingAs($user)
            ->deleteJson('/api/locations/' . $location->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('locations', [
            'id' => $location->id,
        ]);

        $this->actingAs($user)
            ->getJson('/api/locations')
            ->assertOk()
            ->assertJsonMissing(['id' => $location->id]);
    }
}
