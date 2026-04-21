<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();

            $table->string('resource');
            $table->string('external_id');
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('cat_name')->nullable();
            $table->float('rating')->nullable();
            $table->integer('monitoring_interval');
            $table->boolean('is_checking_active')->default(true);
            $table->timestamp('next_check_at')->nullable()->index();

            $table->timestamps();

            $table->unique(['resource', 'external_id']);
            $table->index(['is_checking_active', 'next_check_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};

