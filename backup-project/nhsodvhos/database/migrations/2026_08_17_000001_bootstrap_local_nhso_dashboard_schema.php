<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hospitals')) {
            Schema::create('hospitals', function (Blueprint $table) {
                $table->string('hospital_code', 10)->primary();
                $table->string('hospital_name', 150);
                $table->string('province', 100)->nullable();
                $table->string('nhso_zone', 50)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('sub_funds')) {
            Schema::create('sub_funds', function (Blueprint $table) {
                $table->string('fund_id', 20)->primary();
                $table->string('fund_name', 100);
                $table->text('description')->nullable();
            });
        }

        if (! Schema::hasTable('error_codes')) {
            Schema::create('error_codes', function (Blueprint $table) {
                $table->string('error_code', 10)->primary();
                $table->string('fund_type', 20);
                $table->string('description_th', 255);
            });
        }

        if (! Schema::hasTable('import_logs')) {
            Schema::create('import_logs', function (Blueprint $table) {
                $table->id();
                $table->string('file_name', 255);
                $table->string('imported_by', 100)->default('SYSTEM');
                $table->dateTime('imported_at')->useCurrent();
                $table->integer('record_count')->default(0);
                $table->string('status', 20)->default('SUCCESS');
                $table->binary('file_data')->nullable();
                $table->string('hospital_code', 10)->nullable();
                $table->string('hospital_name', 150)->nullable();
                $table->string('fiscal_year', 10)->nullable();
                $table->string('month_num', 10)->nullable();
                $table->string('sub_fund_id', 20)->nullable();
                $table->string('menu_key', 50)->nullable();
                $table->decimal('claim_amount', 15, 2)->default(0);
                $table->decimal('approved_amount', 15, 2)->default(0);
                $table->decimal('pending_amount', 15, 2)->default(0);
                $table->unsignedBigInteger('file_size_bytes')->default(0);
            });
        }

        if (! Schema::hasTable('dashboard_logs')) {
            Schema::create('dashboard_logs', function (Blueprint $table) {
                $table->id();
                $table->dateTime('log_ts');
                $table->string('level', 20)->default('info');
                $table->string('action', 100);
                $table->text('message')->nullable();
                $table->string('file_name', 255)->nullable();
                $table->string('fund', 50)->nullable();
                $table->string('fiscal_year', 10)->nullable();
                $table->string('month_num', 10)->nullable();
                $table->string('tab_name', 50)->nullable();
                $table->string('username', 100)->nullable();
                $table->longText('details_json')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('compensation_summaries')) {
            Schema::create('compensation_summaries', function (Blueprint $table) {
                $table->id();
                $table->string('hospital_code', 10)->nullable();
                $table->string('fiscal_year', 4);
                $table->integer('month_num');
                $table->string('sub_fund_id', 20)->nullable();
                $table->integer('total_claims_count')->default(0);
                $table->decimal('claim_amount', 15, 2)->default(0);
                $table->decimal('approved_amount', 15, 2)->default(0);
                $table->decimal('pending_amount', 15, 2)->default(0);
                $table->decimal('op_amount', 15, 2)->default(0);
                $table->decimal('hc_amount', 15, 2)->default(0);
                $table->decimal('hc_drug_amount', 15, 2)->default(0);
                $table->decimal('ae_amount', 15, 2)->default(0);
                $table->decimal('ae_drug_amount', 15, 2)->default(0);
                $table->decimal('pp_amount', 15, 2)->default(0);
                $table->decimal('fs_amount', 15, 2)->default(0);
                $table->decimal('ckd_amount', 15, 2)->default(0);
                $table->decimal('ktp_amount', 15, 2)->default(0);
                $table->decimal('napp_amount', 15, 2)->default(0);
                $table->decimal('tb_amount', 15, 2)->default(0);
                $table->decimal('downs_amount', 15, 2)->default(0);
                $table->decimal('thal_screen_amount', 15, 2)->default(0);
                $table->decimal('cervical_amount', 15, 2)->default(0);
                $table->decimal('psych_amount', 15, 2)->default(0);
                $table->decimal('thal_disease_amount', 15, 2)->default(0);
                $table->decimal('rehab_amount', 15, 2)->default(0);
                $table->decimal('thai_med_amount', 15, 2)->default(0);
                $table->decimal('morprom_amount', 15, 2)->default(0);
                $table->decimal('anc_amount', 15, 2)->default(0);
                $table->decimal('ncd_amount', 15, 2)->default(0);
                $table->foreignId('import_log_id')->nullable()->constrained('import_logs')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['hospital_code', 'fiscal_year', 'month_num', 'sub_fund_id'], 'uq_hospital_period_fund');
            });
        }

        if (! Schema::hasTable('monthly_error_logs')) {
            Schema::create('monthly_error_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('summary_id')->nullable()->constrained('compensation_summaries')->cascadeOnDelete();
                $table->string('error_code', 10)->nullable();
                $table->integer('occurrence_count')->default(0);
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasTable('user_preferences')) {
            Schema::create('user_preferences', function (Blueprint $table) {
                $table->string('username', 50);
                $table->string('pref_key', 100);
                $table->longText('pref_value');
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
                $table->primary(['username', 'pref_key']);
                $table->foreign('username')->references('username')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'user_preferences',
            'monthly_error_logs',
            'compensation_summaries',
            'dashboard_logs',
            'import_logs',
            'error_codes',
            'sub_funds',
            'hospitals',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
