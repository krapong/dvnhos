<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Box;
use App\Models\Drug;
use App\Models\BoxRound;
use App\Models\BoxRoundItem;
use App\Models\Alert;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BoxRepackAndAlertSyncTest extends TestCase
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
                $table->string('expiry_date')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->string('category')->nullable();
                $table->string('description')->nullable();
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

        if (! Schema::hasTable('box_rounds')) {
            Schema::create('box_rounds', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('box_id');
                $table->integer('round_number')->default(1);
                $table->string('status')->default('active');
                $table->string('created_by')->nullable();
                $table->integer('items_count')->default(0);
                $table->integer('used_count')->default(0);
                $table->decimal('used_value', 10, 2)->default(0.00);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('box_round_items')) {
            Schema::create('box_round_items', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('box_round_id');
                $table->string('drug_id');
                $table->string('drug_name');
                $table->string('lot_number');
                $table->integer('quantity');
                $table->integer('used_quantity')->default(0);
                $table->string('expiry_date');
                $table->integer('alert_days')->default(30);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('audit_logs')) {
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

    public function test_can_repack_box_and_sync_alerts_successfully(): void
    {
        $user = new User();
        $user->id = 'user_pharmacist_test';
        $user->username = 'pharmacist_test';
        $user->name = 'Pharmacist Test';
        $user->email = 'pharmacist@example.com';
        $user->role = 'pharmacist';
        $user->active = true;
        $user->status = 'active';
        $user->save();

        // 1. Create a master drug
        $drug = new Drug();
        $drug->id = 'drug_para500';
        $drug->name = 'Paracetamol 500mg';
        $drug->unit = 'เม็ด';
        $drug->price = '1.50';
        $drug->category = 'drug';
        $drug->save();

        // 2. Create a box
        $box = new Box();
        $box->id = 'box_er_test';
        $box->name = 'Emergency Box ER';
        $box->status = 'พร้อมใช้งาน';
        $box->save();

        // 3. Verify get current items is empty
        $this->actingAs($user)
            ->getJson("/api/boxes/{$box->id}/items")
            ->assertOk()
            ->assertJsonCount(0);

        // 4. Repack the box with 2 items: 1 expiring soon (warning), 1 normal
        $expiringDate = Carbon::today()->addDays(15)->format('Y-m-d');
        $normalDate = Carbon::today()->addDays(120)->format('Y-m-d');

        $payload = [
            'items' => [
                [
                    'drug_id' => $drug->id,
                    'drug_name' => $drug->name,
                    'lot_number' => 'LOT-EXP-01',
                    'quantity' => 20,
                    'expiry_date' => $expiringDate,
                    'alert_days' => 30, // expiring soon (15 days < 30 alert_days)
                ],
                [
                    'drug_id' => $drug->id,
                    'drug_name' => $drug->name,
                    'lot_number' => 'LOT-NORM-02',
                    'quantity' => 50,
                    'expiry_date' => $normalDate,
                    'alert_days' => 30, // normal (120 days > 30 alert_days)
                ]
            ]
        ];

        $this->actingAs($user)
            ->postJson("/api/boxes/{$box->id}/rounds", $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('round_number', 1);

        // 5. Verify box is updated
        $box->refresh();
        $this->assertEquals(70, $box->drug_count); // 20 + 50
        $this->assertEquals(105.00, $box->value); // 70 * 1.50

        // 6. Verify items returned from currentItems endpoint
        $this->actingAs($user)
            ->getJson("/api/boxes/{$box->id}/items")
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.lot_number', 'LOT-EXP-01')
            ->assertJsonPath('1.lot_number', 'LOT-NORM-02');

        // 7. Verify alert generated for the expiring item
        $alerts = Alert::where('box_id', $box->id)->where('status', 'active')->get();
        $this->assertCount(1, $alerts);
        $this->assertEquals('warning', $alerts[0]->severity);
        $this->assertEquals('Paracetamol 500mg', $alerts[0]->drug_name);

        // 8. Repack again: replace with normal items (resolves alert)
        $payload2 = [
            'items' => [
                [
                    'drug_id' => $drug->id,
                    'drug_name' => $drug->name,
                    'lot_number' => 'LOT-NORM-03',
                    'quantity' => 10,
                    'expiry_date' => $normalDate,
                    'alert_days' => 30,
                ]
            ]
        ];

        $this->actingAs($user)
            ->postJson("/api/boxes/{$box->id}/rounds", $payload2)
            ->assertOk();

        // 9. Verify the active alert was resolved/cleared
        $activeAlerts = Alert::where('box_id', $box->id)->where('status', 'active')->get();
        $this->assertCount(0, $activeAlerts);

        $resolvedAlerts = Alert::where('box_id', $box->id)->where('status', 'resolved')->get();
        $this->assertCount(1, $resolvedAlerts);
    }
}
