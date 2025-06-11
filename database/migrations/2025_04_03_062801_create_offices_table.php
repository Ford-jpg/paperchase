<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('acronym');
            $table->string('name');
            $table->string('head_name')->nullable();
            $table->string('designation')->nullable();
            $table->ulid('proposed_by')->required();
            $table->ulid('approved_by')->required();
            $table->timestamp('proposed_at');
            $table->timestamp('approved_at');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->ulid('office_id')->constrained('offices')->cascadeOnDelete()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
