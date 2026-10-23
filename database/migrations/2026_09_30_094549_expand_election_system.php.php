<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------
        // USERS
        // ---------------------------------------------------------
        if (!Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $t) {
                $t->boolean('is_active')
                    ->default(true)
                    ->after('role');
            });
        }

        // ---------------------------------------------------------
        // ELECTIONS
        // ---------------------------------------------------------
        if (!Schema::hasColumn('elections', 'name')) {
            Schema::table('elections', function (Blueprint $t) {
                $t->string('name')
                    ->nullable()
                    ->after('id');

                $t->text('description')
                    ->nullable()
                    ->after('state');

                $t->date('election_date')
                    ->nullable()
                    ->after('description');
            });
        }

        // Convert old lowercase statuses to the new uppercase values.
        foreach ([
            'active' => 'ACTIVE',
            'completed' => 'COMPLETED',
            'draft' => 'DRAFT',
            'archived' => 'ARCHIVED',
        ] as $old => $new) {
            DB::table('elections')
                ->where('status', $old)
                ->update(['status' => $new]);
        }

        // Backfill election names for existing elections.
        foreach (DB::table('elections')->whereNull('name')->get() as $e) {
            DB::table('elections')
                ->where('id', $e->id)
                ->update([
                    'name' => "{$e->year} {$e->type} Election - {$e->state}",
                ]);
        }

        // ---------------------------------------------------------
        // POLLING UNITS
        // ---------------------------------------------------------
        if (!Schema::hasColumn('polling_units', 'state')) {
            Schema::table('polling_units', function (Blueprint $t) {
                $t->string('state')
                    ->nullable()
                    ->after('id')
                    ->index();
            });
        }

        // ---------------------------------------------------------
        // DATASET IMPORTS
        //
        // IMPORTANT:
        // This table MUST be created before election_results
        // attempts to create its foreign key to this table.
        // ---------------------------------------------------------
        if (!Schema::hasTable('dataset_imports')) {
            Schema::create('dataset_imports', function (Blueprint $t) {
                $t->id();

                $t->foreignId('election_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $t->foreignId('user_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $t->string('filename');

                $t->string('dataset_type')
                    ->default('REAL');

                $t->unsignedInteger('records_found')
                    ->default(0);

                $t->unsignedInteger('records_imported')
                    ->default(0);

                $t->unsignedInteger('valid')
                    ->default(0);

                $t->unsignedInteger('invalid')
                    ->default(0);

                $t->unsignedInteger('flagged')
                    ->default(0);

                $t->unsignedInteger('duplicates')
                    ->default(0);

                $t->unsignedInteger('skipped')
                    ->default(0);

                $t->json('candidate_columns')
                    ->nullable();

                $t->json('rules_skipped')
                    ->nullable();

                $t->timestamps();
            });
        }

        // ---------------------------------------------------------
        // ELECTION RESULTS - SOURCE TYPE
        // ---------------------------------------------------------
        if (!Schema::hasColumn('election_results', 'source_type')) {
            Schema::table('election_results', function (Blueprint $t) {
                $t->string('source_type')
                    ->default('REAL')
                    ->after('validation_status');
            });
        }

        // ---------------------------------------------------------
        // ELECTION RESULTS - DATASET IMPORT ID
        // ---------------------------------------------------------
        if (!Schema::hasColumn('election_results', 'dataset_import_id')) {
            Schema::table('election_results', function (Blueprint $t) {
                $t->foreignId('dataset_import_id')
                    ->nullable()
                    ->after('source_type')
                    ->constrained('dataset_imports')
                    ->nullOnDelete();
            });
        }

        // ---------------------------------------------------------
        // ELECTION USER
        // ---------------------------------------------------------
        if (!Schema::hasTable('election_user')) {
            Schema::create('election_user', function (Blueprint $t) {
                $t->id();

                $t->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $t->foreignId('election_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $t->timestamps();

                $t->unique([
                    'user_id',
                    'election_id',
                ]);
            });
        }
    }

    public function down(): void
    {
        // Remove election_results foreign key first.
        if (Schema::hasColumn('election_results', 'dataset_import_id')) {
            Schema::table('election_results', function (Blueprint $t) {
                $t->dropForeign(['dataset_import_id']);
                $t->dropColumn('dataset_import_id');
            });
        }

        if (Schema::hasColumn('election_results', 'source_type')) {
            Schema::table('election_results', function (Blueprint $t) {
                $t->dropColumn('source_type');
            });
        }

        Schema::dropIfExists('dataset_imports');

        Schema::dropIfExists('election_user');

        if (Schema::hasColumn('polling_units', 'state')) {
            Schema::table('polling_units', function (Blueprint $t) {
                $t->dropColumn('state');
            });
        }

        if (
            Schema::hasColumn('elections', 'name') ||
            Schema::hasColumn('elections', 'description') ||
            Schema::hasColumn('elections', 'election_date')
        ) {
            Schema::table('elections', function (Blueprint $t) {
                $columns = [];

                if (Schema::hasColumn('elections', 'name')) {
                    $columns[] = 'name';
                }

                if (Schema::hasColumn('elections', 'description')) {
                    $columns[] = 'description';
                }

                if (Schema::hasColumn('elections', 'election_date')) {
                    $columns[] = 'election_date';
                }

                if (!empty($columns)) {
                    $t->dropColumn($columns);
                }
            });
        }

        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $t) {
                $t->dropColumn('is_active');
            });
        }
    }
};
