<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Box;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuditLogFlowTest extends TestCase
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

        if (! Schema::hasTable('alerts')) {
            Schema::create('alerts', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('box_id')->nullable();
                $table->string('box_name')->nullable();
                $table->string('location_name')->nullable();
                $table->string('drug_name')->nullable();
                $table->text('description')->nullable();
                $table->string('quantity')->nullable();
                $table->string('unit')->nullable();
                $table->string('severity')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->string('expiry_date')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->string('category')->nullable();
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

        if (Location::query()->count() === 0) {
            $location = new Location();
            $location->id = 'loc_test_' . uniqid();
            $location->name = 'Test Location';
            $location->save();
        }
    }

    public function test_resolving_an_alert_records_an_audit_log(): void
    {
        $user = new User();
        $user->id = 'user_test_super_admin';
        $user->name = 'Test Admin';
        $user->email = 'admin@example.com';
        $user->role = 'super_admin';
        $user->location_id = null;

        $box = Box::query()->first();

        if (! $box) {
            $location = Location::query()->firstOrFail();
            $box = new Box();
            $box->id = 'box_' . uniqid();
            $box->name = 'Audit Test Box';
            $box->location_id = $location->id;
            $box->save();
        }

        $alert = new Alert();
        $alert->id = 'alert_' . uniqid();
        $alert->box_id = $box->id;
        $alert->category = 'expiration';
        $alert->description = 'Audit test alert';
        $alert->expiry_date = '2026-06-30';
        $alert->status = 'active';
        $alert->save();

        $before = AuditLog::count();

        $this->actingAs($user)
            ->patchJson('/api/alerts/' . $alert->id, ['resolved_at' => true])
            ->assertOk();

        $this->assertSame($before + 1, AuditLog::count());

        $log = AuditLog::query()->latest('created_at')->firstOrFail();
        $this->assertSame('alert.resolve', $log->action);
        $this->assertSame('alert', $log->entity_type);
        $this->assertSame($alert->id, $log->entity_id);
    }

    public function test_telegram_command_records_success_audit_log(): void
    {
        AuditLog::query()->delete();

        $before = AuditLog::count();

        putenv('TELEGRAM_BOT_TOKEN=test-token');
        putenv('TELEGRAM_CHAT_ID=123456');
        $_ENV['TELEGRAM_BOT_TOKEN'] = 'test-token';
        $_ENV['TELEGRAM_CHAT_ID'] = '123456';

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $box = Box::query()->first();
        if (! $box) {
            $location = Location::query()->firstOrFail();
            $box = new Box();
            $box->id = 'box_' . uniqid();
            $box->name = 'Telegram Test Box';
            $box->location_id = $location->id;
            $box->save();
        }

        $alert = new Alert();
        $alert->id = 'alert_' . uniqid();
        $alert->box_id = $box->id;
        $alert->category = 'expiration';
        $alert->description = 'Telegram audit test';
        $alert->expiry_date = '2026-06-30';
        $alert->status = 'active';
        $alert->save();

        $this->artisan('alerts:telegram')->assertExitCode(0);

        $this->assertSame($before + 1, AuditLog::count());

        $log = AuditLog::query()->latest('created_at')->firstOrFail();
        $this->assertSame('alert.telegram.send', $log->action);
        $this->assertSame('telegram', $log->entity_type);
    }
}
