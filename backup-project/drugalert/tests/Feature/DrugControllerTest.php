<?php

namespace Tests\Feature;

use App\Models\Drug;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DrugControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('drugs')) {
            Schema::create('drugs', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->string('category')->nullable();
                $table->string('unit')->nullable();
                $table->string('price')->nullable();
                $table->text('details')->nullable();
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

    public function test_drug_destroy_soft_deletes_and_hides_from_index(): void
    {
        $user = new User();
        $user->id = 'user_test_super_admin';
        $user->name = 'Test Admin';
        $user->email = 'admin@example.com';
        $user->role = 'super_admin';

        $drug = new Drug();
        $drug->id = 'drug_' . uniqid();
        $drug->name = 'Soft Delete Drug';
        $drug->category = 'drug';
        $drug->unit = 'Ampule';
        $drug->price = '12.00';
        $drug->save();

        $this->actingAs($user)
            ->deleteJson('/api/drugs/' . $drug->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('drugs', [
            'id' => $drug->id,
        ]);

        $this->actingAs($user)
            ->getJson('/api/drugs')
            ->assertOk()
            ->assertJsonMissing(['id' => $drug->id]);
    }

    public function test_drug_import_preserves_deleted_at_and_keeps_soft_deleted_rows_hidden(): void
    {
        $user = new User();
        $user->id = 'user_test_super_admin_import';
        $user->name = 'Test Admin';
        $user->email = 'admin-import@example.com';
        $user->role = 'super_admin';

        $csv = implode("\n", [
            'id,name,category,unit,price,deleted_at',
            'drug_import_deleted,Imported Deleted Drug,drug,ampoule,9.99,2026-06-10 11:22:33',
        ]) . "\n";

        $file = UploadedFile::fake()->createWithContent('drugs.csv', $csv);

        $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post('/api/import/drugs', ['file' => $file])
            ->assertOk()
            ->assertJson([
                'message' => 'Import completed',
                'created' => 1,
                'updated' => 0,
            ]);

        $drug = Drug::withTrashed()->find('drug_import_deleted');

        $this->assertNotNull($drug);
        $this->assertNotNull($drug->deleted_at);
        $this->assertTrue($drug->trashed());

        $this->actingAs($user)
            ->getJson('/api/drugs')
            ->assertOk()
            ->assertJsonMissing(['id' => 'drug_import_deleted']);
    }
}
