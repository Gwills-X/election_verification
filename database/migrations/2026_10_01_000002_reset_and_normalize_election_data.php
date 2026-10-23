<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('validation_results');
        Schema::dropIfExists('election_result_candidate_votes');
        Schema::dropIfExists('election_results');
        Schema::dropIfExists('dataset_imports');
        Schema::dropIfExists('election_user');
        Schema::dropIfExists('elections');
        Schema::dropIfExists('election_types');
        Schema::dropIfExists('parties');

        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 3)->nullable()->unique();
            $table->char('country_code', 3)->default('NGA');
            $table->timestamps();
        });

        Schema::create('local_governments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
            $table->unique(['state_id', 'name']);
        });

        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_government_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
            $table->unique(['local_government_id', 'name']);
        });

        Schema::table('polling_units', function (Blueprint $table) {
            $table->foreignId('ward_id')
                ->nullable()
                ->after('id')
                ->constrained('wards')
                ->restrictOnDelete();
        });

        $removedUnscopedUnits = DB::table('polling_units')
            ->whereNull('state')
            ->orWhereNull('lga')
            ->orWhereNull('ward')
            ->orWhereNull('code')
            ->count();

        DB::table('polling_units')
            ->whereNull('state')
            ->orWhereNull('lga')
            ->orWhereNull('ward')
            ->orWhereNull('code')
            ->delete();

        DB::table('polling_units')
            ->select('id', 'state', 'lga', 'ward')
            ->orderBy('id')
            ->chunk(500, function ($units): void {
                foreach ($units as $unit) {
                    $stateId = $this->firstOrCreateNamedRow('states', [
                        'name' => trim($unit->state),
                        'country_code' => 'NGA',
                    ]);

                    $localGovernmentId = $this->firstOrCreateNamedRow(
                        'local_governments',
                        [
                            'state_id' => $stateId,
                            'name' => trim($unit->lga),
                        ]
                    );

                    $wardId = $this->firstOrCreateNamedRow('wards', [
                        'local_government_id' => $localGovernmentId,
                        'name' => trim($unit->ward),
                    ]);

                    DB::table('polling_units')
                        ->where('id', $unit->id)
                        ->update(['ward_id' => $wardId]);
                }
            });

        Schema::table('polling_units', function (Blueprint $table) {
            $table->dropIndex('polling_units_state_index');
            $table->dropIndex('polling_units_lga_index');
            $table->dropIndex('polling_units_ward_index');
            $table->dropColumn(['state', 'lga', 'ward']);
        });

        Schema::create('election_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('code', ['PRESIDENTIAL', 'GOVERNORSHIP'])->unique();
            $table->enum('scope', ['NATIONAL', 'STATE']);
            $table->foreignId('scope_state_id')
                ->nullable()
                ->constrained('states')
                ->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_type_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('name');
            $table->string('status')->default('ACTIVE')->index();
            $table->timestamps();
            $table->unique(['election_type_id', 'year']);
        });

        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('dataset_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');
            $table->string('dataset_type')->default('REAL');
            $table->unsignedInteger('records_found')->default(0);
            $table->unsignedInteger('records_submitted')->default(0);
            $table->unsignedInteger('records_imported')->default(0);
            $table->unsignedInteger('new_records')->default(0);
            $table->unsignedInteger('existing_records')->default(0);
            $table->unsignedInteger('conflicts')->default(0);
            $table->unsignedInteger('valid')->default(0);
            $table->unsignedInteger('invalid')->default(0);
            $table->unsignedInteger('flagged')->default(0);
            $table->unsignedInteger('duplicates')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->string('status')->default('COMPLETED');
            $table->timestamp('completed_at')->nullable();
            $table->json('candidate_columns')->nullable();
            $table->json('rules_skipped')->nullable();
            $table->timestamps();
        });

        Schema::create('election_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('polling_unit_id')->constrained()->cascadeOnDelete();
            $table->integer('registered_voters')->nullable();
            $table->integer('accredited_voters')->nullable();
            $table->integer('rejected_votes')->nullable();
            $table->integer('total_votes')->nullable();
            $table->string('validation_status')->default('PENDING')->index();
            $table->string('source_type')->default('REAL');
            $table->foreignId('dataset_import_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_decision')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['election_id', 'polling_unit_id']);
        });

        Schema::create('election_result_candidate_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('votes');
            $table->timestamps();
            $table->unique(
                ['election_result_id', 'party_id'],
                'result_party_unique'
            );
        });

        Schema::create('validation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_result_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('validation_rule');
            $table->text('validation_message');
            $table->timestamp('checked_at')->nullable();
        });

        Schema::create('election_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'election_id']);
        });

        if ($removedUnscopedUnits > 0) {
            echo "Removed {$removedUnscopedUnits} legacy polling units without complete state/LGA/ward/code data during election reset.\n";
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'This migration resets election data and cannot restore the removed legacy results. Restore from a database backup instead.'
        );
    }

    private function firstOrCreateNamedRow(string $table, array $values): int
    {
        $query = DB::table($table);

        foreach ($values as $column => $value) {
            if ($column === 'name') {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($value)]);
            } else {
                $query->where($column, $value);
            }
        }

        $existing = $query->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $now = now();

        return (int) DB::table($table)->insertGetId([
            ...$values,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
