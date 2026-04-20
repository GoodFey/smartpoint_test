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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('resource');
            $table->string('external_id');
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('cat_name')->nullable();
            $table->float('rating')->nullable();
            $table->integer('monitoring_interval');
            $table->boolean('is_cheking_active')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['resource', 'external_id']);
            $table->index('last_checked_at');
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

