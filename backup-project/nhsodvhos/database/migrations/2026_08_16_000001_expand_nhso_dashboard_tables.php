<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('import_logs')) {
            Schema::table('import_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('import_logs', 'imported_by')) {
                    $table->string('imported_by', 100)->default('SYSTEM')->after('file_name');
                }
                if (! Schema::hasColumn('import_logs', 'hospital_code')) {
                    $table->string('hospital_code', 10)->nullable()->after('imported_by');
                }
                if (! Schema::hasColumn('import_logs', 'hospital_name')) {
                    $table->string('hospital_name', 150)->nullable()->after('hospital_code');
                }
                if (! Schema::hasColumn('import_logs', 'fiscal_year')) {
                    $table->string('fiscal_year', 10)->nullable()->after('hospital_name');
                }
                if (! Schema::hasColumn('import_logs', 'month_num')) {
                    $table->string('month_num', 10)->nullable()->after('fiscal_year');
                }
                if (! Schema::hasColumn('import_logs', 'sub_fund_id')) {
                    $table->string('sub_fund_id', 20)->nullable()->after('month_num');
                }
                if (! Schema::hasColumn('import_logs', 'menu_key')) {
                    $table->string('menu_key', 50)->nullable()->after('sub_fund_id');
                }
                if (! Schema::hasColumn('import_logs', 'claim_amount')) {
                    $table->decimal('claim_amount', 15, 2)->default(0)->after('menu_key');
                }
                if (! Schema::hasColumn('import_logs', 'approved_amount')) {
                    $table->decimal('approved_amount', 15, 2)->default(0)->after('claim_amount');
                }
                if (! Schema::hasColumn('import_logs', 'pending_amount')) {
                    $table->decimal('pending_amount', 15, 2)->default(0)->after('approved_amount');
                }
                if (! Schema::hasColumn('import_logs', 'file_size_bytes')) {
                    $table->unsignedBigInteger('file_size_bytes')->default(0)->after('pending_amount');
                }
            });
        }

        if (Schema::hasTable('dashboard_logs')) {
            Schema::table('dashboard_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('dashboard_logs', 'log_ts')) {
                    $table->dateTime('log_ts')->nullable()->after('id');
                }
                if (! Schema::hasColumn('dashboard_logs', 'level')) {
                    $table->string('level', 20)->default('info')->after('log_ts');
                }
                if (! Schema::hasColumn('dashboard_logs', 'message')) {
                    $table->text('message')->nullable()->after('action');
                }
                if (! Schema::hasColumn('dashboard_logs', 'file_name')) {
                    $table->string('file_name', 255)->nullable()->after('message');
                }
                if (! Schema::hasColumn('dashboard_logs', 'fund')) {
                    $table->string('fund', 50)->nullable()->after('file_name');
                }
                if (! Schema::hasColumn('dashboard_logs', 'fiscal_year')) {
                    $table->string('fiscal_year', 10)->nullable()->after('fund');
                }
                if (! Schema::hasColumn('dashboard_logs', 'month_num')) {
                    $table->string('month_num', 10)->nullable()->after('fiscal_year');
                }
                if (! Schema::hasColumn('dashboard_logs', 'tab_name')) {
                    $table->string('tab_name', 50)->nullable()->after('month_num');
                }
                if (! Schema::hasColumn('dashboard_logs', 'details_json')) {
                    $table->longText('details_json')->nullable()->after('username');
                }
            });
        }

        if (! Schema::hasTable('user_preferences')) {
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
        if (Schema::hasTable('user_preferences')) {
            Schema::dropIfExists('user_preferences');
        }

        if (Schema::hasTable('dashboard_logs')) {
            Schema::table('dashboard_logs', function (Blueprint $table) {
                foreach ([
                    'log_ts', 'level', 'message', 'file_name', 'fund',
                    'fiscal_year', 'month_num', 'tab_name', 'details_json',
                ] as $column) {
                    if (Schema::hasColumn('dashboard_logs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('import_logs')) {
            Schema::table('import_logs', function (Blueprint $table) {
                foreach ([
                    'imported_by', 'hospital_code', 'hospital_name', 'fiscal_year',
                    'month_num', 'sub_fund_id', 'menu_key', 'claim_amount',
                    'approved_amount', 'pending_amount', 'file_size_bytes',
                ] as $column) {
                    if (Schema::hasColumn('import_logs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
