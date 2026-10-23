<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dataset_imports')) {
            return;
        }

        Schema::table('dataset_imports', function (Blueprint $table) {
            if (! Schema::hasColumn('dataset_imports', 'records_submitted')) {
                $table->unsignedInteger('records_submitted')->default(0)->after('records_found');
            }

            if (! Schema::hasColumn('dataset_imports', 'new_records')) {
                $table->unsignedInteger('new_records')->default(0)->after('records_imported');
            }

            if (! Schema::hasColumn('dataset_imports', 'existing_records')) {
                $table->unsignedInteger('existing_records')->default(0)->after('new_records');
            }

            if (! Schema::hasColumn('dataset_imports', 'conflicts')) {
                $table->unsignedInteger('conflicts')->default(0)->after('existing_records');
            }

            if (! Schema::hasColumn('dataset_imports', 'status')) {
                $table->string('status')->default('COMPLETED')->after('duplicates');
            }

            if (! Schema::hasColumn('dataset_imports', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('status');
            }
        });

        if (Schema::hasTable('election_results') && ! Schema::hasColumn('election_results', 'review_decision')) {
            Schema::table('election_results', function (Blueprint $table) {
                $table->string('review_decision')->nullable()->after('review_note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dataset_imports')) {
            Schema::table('dataset_imports', function (Blueprint $table) {
                $columns = ['records_submitted', 'new_records', 'existing_records', 'conflicts', 'status', 'completed_at'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('dataset_imports', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('election_results') && Schema::hasColumn('election_results', 'review_decision')) {
            Schema::table('election_results', function (Blueprint $table) {
                $table->dropColumn('review_decision');
            });
        }
    }
};
