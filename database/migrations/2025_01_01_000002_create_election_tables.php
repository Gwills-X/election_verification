<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $t) {
            $t->id(); $t->unsignedSmallInteger('year'); $t->string('type'); $t->string('state');
            $t->string('status')->default('active'); $t->timestamps();
            $t->unique(['year', 'type', 'state']);
        });
        Schema::create('polling_units', function (Blueprint $t) {
            $t->id(); $t->string('lga')->nullable()->index(); $t->string('ward')->nullable()->index();
            $t->string('code')->unique(); $t->string('name')->nullable(); $t->timestamps();
        });
        Schema::create('election_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('election_id')->constrained()->cascadeOnDelete();
            $t->foreignId('polling_unit_id')->constrained()->cascadeOnDelete();
            $t->integer('registered_voters')->nullable(); $t->integer('accredited_voters')->nullable();
            $t->json('candidate_results')->nullable(); // columns depend on the real dataset
            $t->integer('rejected_votes')->nullable(); $t->integer('total_votes')->nullable();
            $t->string('validation_status')->default('PENDING')->index(); // latest overall status (for fast filtering)
            $t->timestamp('reviewed_at')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('review_note')->nullable();
            $t->timestamps();
        });
        Schema::create('validation_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('election_result_id')->constrained()->cascadeOnDelete();
            $t->string('status'); $t->string('validation_rule'); $t->text('validation_message');
            $t->timestamp('checked_at')->nullable();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action'); $t->text('description'); $t->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['audit_logs', 'validation_results', 'election_results', 'polling_units', 'elections'] as $t) Schema::dropIfExists($t);
    }
};
