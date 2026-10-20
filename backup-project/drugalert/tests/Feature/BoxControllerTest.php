<?php

namespace Tests\Feature;

use App\Models\Box;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BoxControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('locations')) {
            Schema::create('locations', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->string('detail')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('boxes')) {
            Schema::create('boxes', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->string('location_id')->nullable();
                $table->string('code')->nullable();
                $table->string('status')->nullable();
                $table->decimal('value', 12, 2)->nullable();
                $table->integer('drug_count')->nullable();
                $table->string('qr_code')->nullable();
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

    public function test_box_update_accepts_camel_case_payload_and_returns_normalized_fields(): void
    {
        $user = new User();
        $user->id = 'user_test_super_admin';
        $user->name = 'Test Admin';
        $user->email = 'admin@example.com';
        $user->role = 'super_admin';

        $sourceLocation = new Location();
        $sourceLocation->id = 'loc_src_' . uniqid();
        $sourceLocation->name = 'Source Room';
        $sourceLocation->save();

        $targetLocation = new Location();
        $targetLocation->id = 'loc_dst_' . uniqid();
        $targetLocation->name = 'Target Room';
        $targetLocation->save();

        $box = new Box();
        $box->id = 'box_' . uniqid();
        $box->name = 'Emergency Box';
        $box->location_id = $sourceLocation->id;
        $box->status = 'พร้อมใช้งาน';
        $box->value = 0;
        $box->drug_count = 1;
        $box->save();

        $response = $this->actingAs($user)
            ->patchJson('/api/boxes/' . $box->id, [
                'code' => 'BOX-001',
                'name' => 'BOX-001',
                'locationId' => $targetLocation->id,
                'drugCount' => 12,
                'status' => 'รอเติมยา',
            ]);

        $response->assertOk();
        $response->assertJsonPath('id', $box->id);
        $response->assertJsonPath('locationId', $targetLocation->id);
        $response->assertJsonPath('drugCount', 12);
        $response->assertJsonPath('status', 'รอเติมยา');

        $this->assertDatabaseHas('boxes', [
            'id' => $box->id,
            'location_id' => $targetLocation->id,
            'drug_count' => 12,
            'status' => 'รอเติมยา',
        ]);
    }

    public function test_box_destroy_soft_deletes_and_hides_from_index(): void
    {
        $user = new User();
        $user->id = 'user_test_super_admin_delete';
        $user->name = 'Test Admin';
        $user->email = 'admin-delete@example.com';
        $user->role = 'super_admin';

        $location = new Location();
        $location->id = 'loc_' . uniqid();
        $location->name = 'Delete Room';
        $location->save();

        $box = new Box();
        $box->id = 'box_' . uniqid();
        $box->name = 'Delete Box';
        $box->location_id = $location->id;
        $box->status = 'เธเธฃเนเธญเธกเนเธเนเธเธฒเธ';
        $box->value = 0;
        $box->drug_count = 3;
        $box->save();

        $this->actingAs($user)
            ->deleteJson('/api/boxes/' . $box->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('boxes', [
            'id' => $box->id,
        ]);

        $this->actingAs($user)
            ->getJson('/api/boxes')
            ->assertOk()
            ->assertJsonMissing(['id' => $box->id]);
    }
}
