<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationAndNavigationSmokeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->text('value')->nullable();
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('username')->unique();
                $table->string('name')->nullable();
                $table->string('display_name')->nullable();
                $table->string('role')->nullable();
                $table->boolean('active')->default(true);
                $table->string('password_salt')->nullable();
                $table->string('password_hash')->nullable();
                $table->string('status')->nullable();
                $table->string('location_id')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->string('remember_token')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamps();
            });
        }

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

        if (! Schema::hasTable('drugs')) {
            Schema::create('drugs', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->string('unit')->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->string('category')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('boxes')) {
            Schema::create('boxes', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->string('location_id')->nullable();
                $table->string('status')->nullable();
                $table->decimal('value', 12, 2)->nullable();
                $table->integer('drug_count')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('alerts')) {
            Schema::create('alerts', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('box_id')->nullable();
                $table->string('box_name')->nullable();
                $table->string('location_name')->nullable();
                $table->string('drug_name')->nullable();
                $table->integer('quantity')->nullable();
                $table->string('unit')->nullable();
                $table->string('severity')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->date('expiry_date')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->string('category')->nullable();
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('title');
                $table->text('body')->nullable();
                $table->string('severity')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->string('target_view')->nullable();
                $table->text('read_by')->nullable();
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

    public function test_dashboard_notifications_expose_normalized_created_at_fields(): void
    {
        $user = new User();
        $user->id = 'user_test_super_admin_notifications';
        $user->username = 'admin_notif';
        $user->name = 'Test Admin';
        $user->email = 'admin-notifications@example.com';
        $user->role = 'super_admin';
        $user->active = true;
        $user->status = 'active';
        $user->save();

        $createdAt = Carbon::parse('2026-06-14 03:00:00', 'UTC')->toISOString();

        DB::table('notifications')->insert([
            'id' => 'notif_' . uniqid(),
            'title' => 'Expiry warning',
            'body' => 'Test notification',
            'severity' => 'warning',
            'created_at' => Carbon::parse('2026-06-14 03:00:00', 'UTC'),
            'target_view' => 'dashboard',
            'read_by' => json_encode([]),
        ]);

        $this->actingAs($user)
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('notifications.0.createdAt', $createdAt)
            ->assertJsonPath('notifications.0.created_at', $createdAt);
    }

    public function test_audit_logs_expose_normalized_created_at_fields_and_navigation_after_save_still_works(): void
    {
        $user = new User();
        $user->id = 'user_test_super_admin_audit';
        $user->username = 'admin_audit';
        $user->name = 'Test Admin';
        $user->email = 'admin-audit@example.com';
        $user->role = 'super_admin';
        $user->active = true;
        $user->status = 'active';
        $user->save();

        $createdAt = Carbon::parse('2026-06-14 04:00:00', 'UTC')->toISOString();

        DB::table('audit_logs')->insert([
            'id' => 'audit_' . uniqid(),
            'actor_user_id' => $user->id,
            'action' => 'create',
            'entity_type' => 'location',
            'entity_id' => 'loc_' . uniqid(),
            'details' => json_encode(['name' => 'Smoke Room']),
            'created_at' => Carbon::parse('2026-06-14 04:00:00', 'UTC'),
        ]);

        $this->actingAs($user)
            ->postJson('/api/locations', [
                'name' => 'Smoke Room',
                'detail' => 'Smoke test',
                'status' => 'ใช้งาน',
            ])
            ->dump()
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/api/boxes')
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/api/audit-logs')
            ->assertOk()
            ->assertJsonPath('1.createdAt', $createdAt)
            ->assertJsonPath('1.created_at', $createdAt);
    }
}
